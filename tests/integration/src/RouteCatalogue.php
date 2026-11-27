<?php
/*
 * @package   panopticon
 * @copyright Copyright (c)2023-2026 Nikolaos Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

namespace Akeeba\Panopticon\Tests\Integration;

/**
 * Lists every route the "Web Services - Panopticon" plugin registers.
 *
 * It runs the plugin's own registration code against minimal stand-ins for the Joomla classes it uses, so a newly added
 * route is covered by the tests without anyone having to remember to list it here.
 */
final class RouteCatalogue
{
	/**
	 * @return \Joomla\Router\Route[]
	 */
	public static function routes(): array
	{
		require_once __DIR__ . '/stubs/RoutingStubs.php';
		require_once __DIR__ . '/../../../plugins/webservices/panopticon/src/Extension/Panopticon.php';

		$router = new \Joomla\CMS\Router\ApiRouter();

		(new \Akeeba\Plugin\WebServices\Panopticon\Extension\Panopticon())
			->registerRoutes(new \Joomla\Event\Event('onBeforeApiRoute', [$router]));

		return $router->routes;
	}
}
