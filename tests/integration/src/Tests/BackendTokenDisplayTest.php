<?php
/*
 * @package   panopticon
 * @copyright Copyright (c)2023-2026 Nikolaos Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

namespace Akeeba\Panopticon\Tests\Integration\Tests;

use Akeeba\Panopticon\Tests\Integration\AbstractApiTestCase;
use Akeeba\Panopticon\Tests\Integration\SiteFixtures;

/**
 * The component's backend page only shows the connection token to Super Users.
 */
class BackendTokenDisplayTest extends AbstractApiTestCase
{
	private const NOTICE = 'Only Super Users can connect this site';

	public function testNonSuperUserIsNotShownATokenOnlyAnExplanation(): void
	{
		$persona = SiteFixtures::persona(SiteFixtures::POWERFUL);
		$html    = $this->connectorPage($persona['username'], SiteFixtures::PASSWORD);

		$this->assertStringContainsString('Akeeba Panopticon Connector', $html, 'The page did not load');
		$this->assertStringNotContainsString($persona['token'], $html);
		$this->assertStringContainsString(self::NOTICE, $html);
		$this->assertStringNotContainsString('You have not created a Joomla! API token', $html);
	}

	public function testSuperUserIsShownTheToken(): void
	{
		// These are the credentials run-tests.sh installs the site with.
		$html = $this->connectorPage('admin', 'Integr8tion-Test-Passw0rd!');

		$this->assertStringContainsString(self::$token, $html);
		$this->assertStringNotContainsString(self::NOTICE, $html);
	}

	/**
	 * Log into the site's backend, and return the HTML of the connector's page.
	 */
	private function connectorPage(string $username, string $password): string
	{
		$adminUrl = preg_replace('#/api(/index\.php)?$#', '', self::$baseUrl) . '/administrator/index.php';
		$curl     = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_COOKIEFILE     => '',
			CURLOPT_TIMEOUT        => 30,
		]);

		$request = function (string $url, ?array $post = null) use ($curl): string {
			curl_setopt($curl, CURLOPT_URL, $url);

			if ($post === null)
			{
				curl_setopt($curl, CURLOPT_HTTPGET, true);
			}
			else
			{
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
			}

			$body = curl_exec($curl);

			$this->assertIsString($body, curl_error($curl));

			return $body;
		};

		$login = $request($adminUrl);

		$this->assertSame(1, preg_match('#"csrf\.token":"([0-9a-f]{32})"#', $login, $matches), 'No CSRF token');

		$request($adminUrl, [
			'username'  => $username,
			'passwd'    => $password,
			'option'    => 'com_login',
			'task'      => 'login',
			'return'    => base64_encode('index.php'),
			$matches[1] => '1',
		]);

		return $request($adminUrl . '?option=com_panopticon');
	}
}
