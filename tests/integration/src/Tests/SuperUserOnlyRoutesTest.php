<?php
/*
 * @package   panopticon
 * @copyright Copyright (c)2023-2026 Nikolaos Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

namespace Akeeba\Panopticon\Tests\Integration\Tests;

use Akeeba\Panopticon\Tests\Integration\AbstractApiTestCase;
use Akeeba\Panopticon\Tests\Integration\RouteCatalogue;
use Akeeba\Panopticon\Tests\Integration\SiteFixtures;

/**
 * EVERY route of the connector requires a Super User.
 */
class SuperUserOnlyRoutesTest extends AbstractApiTestCase
{
	/** Values for route parameters. The IDs deliberately match no record. */
	private const PARAMETERS = [':id' => '999999', ':element' => 'pkg_panopticon', ':step' => '0'];

	public static function routeProvider(): array
	{
		$cases = [];

		foreach (RouteCatalogue::routes() as $route)
		{
			foreach ($route->methods as $method)
			{
				$path = strtr($route->pattern, self::PARAMETERS);

				$cases[$method . ' ' . $route->pattern] = [$method, $path];
			}
		}

		return $cases;
	}

	public function testTheCatalogueSeesTheRoutes(): void
	{
		$this->assertGreaterThanOrEqual(35, count(self::routeProvider()));

		foreach (RouteCatalogue::routes() as $route)
		{
			$this->assertStringStartsWith('v1/panopticon/', $route->pattern);
			$this->assertSame(
				'com_panopticon', $route->defaults['component'] ?? null,
				$route->pattern . ' must go through the connector\'s dispatcher, which checks for a Super User'
			);
			$this->assertArrayNotHasKey('public', $route->defaults, $route->pattern . ' must not be public');
		}
	}

	public static function personaProvider(): array
	{
		return [
			SiteFixtures::POWERFUL => [SiteFixtures::POWERFUL],
			SiteFixtures::MINIMAL  => [SiteFixtures::MINIMAL],
		];
	}

	/**
	 * Without this, the 403 responses below could be authentication failures in disguise.
	 *
	 * @dataProvider personaProvider
	 */
	public function testTheNonSuperUsersCanUseTheApiApplication(string $persona): void
	{
		$this->assertStatus(
			200, $this->api('GET', 'v1/content/articles', null, SiteFixtures::persona($persona)['token'])
		);
	}

	/** @dataProvider routeProvider */
	public function testRouteRefusesAnAdministratorWhoIsNotASuperUser(string $method, string $path): void
	{
		$this->assertStatus(
			403, $this->api($method, $path, null, SiteFixtures::persona(SiteFixtures::POWERFUL)['token'])
		);
	}

	/** @dataProvider routeProvider */
	public function testRouteRefusesAUserWithOnlyApiAccess(string $method, string $path): void
	{
		$this->assertStatus(
			403, $this->api($method, $path, null, SiteFixtures::persona(SiteFixtures::MINIMAL)['token'])
		);
	}

	/** @dataProvider routeProvider */
	public function testRouteRefusesAnonymousRequests(string $method, string $path): void
	{
		$this->assertStatus(401, $this->api($method, $path, null, null));
	}

	public static function readOnlyRouteProvider(): array
	{
		return [
			'extensions'               => ['v1/panopticon/extensions'],
			'updatesites'              => ['v1/panopticon/updatesites'],
			'core/update'              => ['v1/panopticon/core/update'],
			'template/overrides'       => ['v1/panopticon/template/overrides/changed'],
			'akeebabackup/info'        => ['v1/panopticon/akeebabackup/info'],
		];
	}

	/** @dataProvider readOnlyRouteProvider */
	public function testSuperUserIsServed(string $path): void
	{
		$this->assertJsonApiSuccess($this->api('GET', $path));
	}
}
