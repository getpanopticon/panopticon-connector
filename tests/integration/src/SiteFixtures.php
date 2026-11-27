<?php
/*
 * @package   panopticon
 * @copyright Copyright (c)2023-2026 Nikolaos Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

namespace Akeeba\Panopticon\Tests\Integration;

use mysqli;
use RuntimeException;

/**
 * Prepares data in the site under test: user accounts which are NOT Super Users, and disposable update sites.
 *
 * The integration suite runs inside the `web` container, so it can reach the database and Joomla's CLI directly.
 */
final class SiteFixtures
{
	/** An Administrator holding every privilege there is, short of being a Super User. */
	public const POWERFUL = 'powerful';

	/** A Registered user who can do nothing but log into the API application. */
	public const MINIMAL = 'minimal';

	public const PASSWORD = 'N0t-A-Super-User-Passw0rd!';

	private const SITE_ROOT = '/var/www/html';

	private const GROUP_REGISTERED = 2;

	private const GROUP_ADMINISTRATOR = 7;

	private const GROUP_SUPER_USERS = 8;

	/** @var array<string,array{id:int,username:string,token:string}> */
	private static array $personas = [];

	private static ?mysqli $db = null;

	/**
	 * A user account which can authenticate against the API application, but is not a Super User.
	 *
	 * @return array{id:int,username:string,token:string}
	 */
	public static function persona(string $type): array
	{
		return self::$personas[$type] ?? (self::$personas[$type] = self::createPersona($type));
	}

	/**
	 * Run a Joomla CLI command in the site under test.
	 *
	 * @return array{0:int,1:string} Exit code, combined output
	 */
	public static function cli(array $arguments): array
	{
		$command = 'cd ' . escapeshellarg(self::SITE_ROOT) . ' && php cli/joomla.php '
			. implode(' ', array_map('escapeshellarg', $arguments)) . ' 2>&1';

		exec($command, $output, $exitCode);

		return [$exitCode, implode("\n", $output)];
	}

	public static function createUpdateSite(): int
	{
		$extensionId = (int) self::value(
			"SELECT extension_id FROM #__extensions WHERE element = 'com_panopticon' AND type = 'component'"
		);

		self::query(
			"INSERT INTO #__update_sites (name, type, location, enabled, last_check_timestamp, extra_query) "
			. "VALUES ('Disposable test update site', 'extension', 'https://updates.example.invalid/original.xml', 1, 0, '')"
		);

		$id = (int) self::db()->insert_id;

		self::query(
			sprintf('INSERT INTO #__update_sites_extensions (update_site_id, extension_id) VALUES (%d, %d)', $id, $extensionId)
		);

		return $id;
	}

	public static function updateSite(int $id): ?array
	{
		$result = self::query(sprintf('SELECT * FROM #__update_sites WHERE update_site_id = %d', $id));

		return $result->fetch_assoc() ?: null;
	}

	private static function createPersona(string $type): array
	{
		self::allowApiTokens();

		$group    = $type === self::POWERFUL ? self::GROUP_ADMINISTRATOR : self::GROUP_REGISTERED;
		$username = 'test_' . $type;
		$userId   = (int) self::value(sprintf("SELECT id FROM #__users WHERE username = '%s'", $username));

		if ($userId === 0)
		{
			[$exitCode, $output] = self::cli([
				'user:add',
				'--username=' . $username,
				'--name=Test ' . $type,
				'--password=' . self::PASSWORD,
				'--email=' . $username . '@example.com',
				'--usergroup=' . ($type === self::POWERFUL ? 'Administrator' : 'Registered'),
				'--no-interaction',
			]);

			$userId = (int) self::value(sprintf("SELECT id FROM #__users WHERE username = '%s'", $username));

			if ($exitCode !== 0 || $userId === 0)
			{
				throw new RuntimeException('Could not create the test user: ' . $output);
			}
		}

		self::grant('root.1', $group, ['core.login.api']);
		// Lets us prove that the persona's token does authenticate, using a core API endpoint.
		self::grant('com_content', $group, ['core.api']);

		if ($type === self::POWERFUL)
		{
			$actions = ['core.api', 'core.manage', 'core.create', 'core.edit', 'core.edit.state', 'core.delete', 'core.options'];

			foreach (['root.1', 'com_panopticon', 'com_installer', 'com_joomlaupdate', 'com_plugins'] as $asset)
			{
				self::grant($asset, $group, $actions);
			}

			self::grant('root.1', $group, ['core.login.admin']);
		}
		else
		{
			self::grant('com_panopticon', $group, ['core.api']);
		}

		return ['id' => $userId, 'username' => $username, 'token' => self::createToken($userId)];
	}

	private static function allowApiTokens(): void
	{
		$params = json_encode(
			['allowedUserGroups' => [self::GROUP_REGISTERED, self::GROUP_ADMINISTRATOR, self::GROUP_SUPER_USERS]]
		);

		self::query(
			sprintf(
				"UPDATE #__extensions SET params = '%s' WHERE type = 'plugin' AND folder = 'user' AND element = 'token'",
				self::db()->real_escape_string($params)
			)
		);
	}

	private static function createToken(int $userId): string
	{
		$configuration = (string) file_get_contents(self::SITE_ROOT . '/configuration.php');

		if (!preg_match('#\$secret\s*=\s*\'([^\']+)\'#', $configuration, $matches))
		{
			throw new RuntimeException('Cannot read the site secret.');
		}

		$seed = base64_encode(random_bytes(32));

		self::query(
			sprintf("DELETE FROM #__user_profiles WHERE user_id = %d AND profile_key LIKE 'joomlatoken.%%'", $userId)
		);
		self::query(
			sprintf(
				"INSERT INTO #__user_profiles (user_id, profile_key, profile_value, ordering) "
				. "VALUES (%1\$d, 'joomlatoken.token', '%2\$s', 1), (%1\$d, 'joomlatoken.enabled', '1', 2)",
				$userId, $seed
			)
		);

		return base64_encode(
			'sha256:' . $userId . ':' . hash_hmac('sha256', base64_decode($seed), $matches[1])
		);
	}

	private static function grant(string $assetName, int $group, array $actions): void
	{
		$escapedName = self::db()->real_escape_string($assetName);
		$rules       = self::value(sprintf("SELECT rules FROM #__assets WHERE name = '%s'", $escapedName));

		if ($rules === null)
		{
			throw new RuntimeException('No such asset: ' . $assetName);
		}

		$rules = json_decode($rules, true) ?: [];

		foreach ($actions as $action)
		{
			$rules[$action]         = $rules[$action] ?? [];
			$rules[$action][$group] = 1;
		}

		self::query(
			sprintf(
				"UPDATE #__assets SET rules = '%s' WHERE name = '%s'",
				self::db()->real_escape_string(json_encode($rules)),
				$escapedName
			)
		);
	}

	private static function value(string $sql): ?string
	{
		$row = self::query($sql)->fetch_row();

		return $row === null || $row === false ? null : $row[0];
	}

	/** @return \mysqli_result|bool */
	private static function query(string $sql)
	{
		$result = self::db()->query(str_replace('#__', getenv('JTEST_DB_PREFIX') ?: 'jtest_', $sql));

		if ($result === false)
		{
			throw new RuntimeException(self::db()->error);
		}

		return $result;
	}

	private static function db(): mysqli
	{
		return self::$db ?? (self::$db = new mysqli(
			getenv('JTEST_DB_HOST') ?: 'db',
			getenv('JTEST_DB_USER') ?: 'root',
			getenv('JTEST_DB_PASSWORD') ?: 'rootpw',
			getenv('JTEST_DB_NAME') ?: 'joomla_test',
			(int) (getenv('JTEST_DB_PORT') ?: 3306)
		));
	}
}
