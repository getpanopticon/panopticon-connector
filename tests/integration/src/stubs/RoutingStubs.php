<?php
/*
 * @package   panopticon
 * @copyright Copyright (c)2023-2026 Nikolaos Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

/**
 * Just enough of Joomla to let the webservices plugin register its routes. No Joomla is loaded in the test process.
 */

namespace {
	defined('_JEXEC') || define('_JEXEC', 1);
}

namespace Joomla\CMS\Plugin {
	class CMSPlugin
	{
	}
}

namespace Joomla\CMS\Router {
	class ApiRouter
	{
		public $routes = [];

		public function addRoutes(array $routes): void
		{
			$this->routes = array_merge($this->routes, $routes);
		}
	}
}

namespace Joomla\Event {
	interface SubscriberInterface
	{
		public static function getSubscribedEvents(): array;
	}

	class Event
	{
		private $arguments;

		public function __construct(string $name, array $arguments = [])
		{
			$this->arguments = $arguments;
		}

		public function getArguments(): array
		{
			return $this->arguments;
		}
	}
}

namespace Joomla\Router {
	class Route
	{
		public $methods;

		public $pattern;

		public $controller;

		public $defaults;

		public function __construct(
			array $methods, string $pattern, $controller, array $rules = [], array $defaults = []
		)
		{
			$this->methods    = $methods;
			$this->pattern    = $pattern;
			$this->controller = $controller;
			$this->defaults   = $defaults;
		}
	}
}
