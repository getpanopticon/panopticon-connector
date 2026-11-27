<?php
/*
 * @package   panopticon
 * @copyright Copyright (c)2023-2026 Nikolaos Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

namespace Akeeba\Component\Panopticon\Api\Dispatcher;

defined('_JEXEC') || die;

use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Dispatcher\ComponentDispatcher;

/**
 * API application dispatcher. It is the one and only permission check of the connector: EVERY request must come from a
 * Super User.
 *
 * Panopticon manages the whole site: it installs and updates extensions and Joomla! itself, handles backups and security
 * settings, and reports the server's configuration. None of that may be available to anyone else, so there are no
 * per-route privileges. Do not add any to the controllers.
 *
 * This should extend Joomla's ApiDispatcher, but that class is final before Joomla! 5.4.11 and 6.2.1. Therefore its
 * loadLanguage() and dispatch() are reproduced here. Not running its core.api check loses nothing: a Super User passes
 * every permission check.
 */
class Dispatcher extends ComponentDispatcher
{
	/** @inheritDoc */
	public function dispatch()
	{
		$this->checkAccess();

		$task = $this->input->getCmd('task', 'display');

		// Build controller config data
		$config = ['option' => $this->option];

		// Set name of controller if it is passed in the request
		if ($this->input->exists('controller'))
		{
			$config['name'] = strtolower($this->input->get('controller'));
		}

		$controller = $this->input->get('controller');
		$controller = $this->getController($controller, ucfirst($this->app->getName()), $config);

		$controller->execute($task);
	}

	/**
	 * Only Super Users are allowed in.
	 *
	 * @return  void
	 *
	 * @throws  NotAllowed
	 */
	protected function checkAccess(): void
	{
		$user = $this->app->getIdentity();

		if (empty($user) || !$user->authorise('core.admin'))
		{
			throw new NotAllowed($this->app->getLanguage()->_('JERROR_ALERTNOAUTHOR'), 403);
		}
	}

	/** @inheritDoc */
	protected function loadLanguage()
	{
		// Load common and local language files.
		$this->app->getLanguage()->load($this->option, JPATH_BASE) ||
		$this->app->getLanguage()->load($this->option, JPATH_ADMINISTRATOR) ||
		$this->app->getLanguage()->load($this->option, JPATH_ADMINISTRATOR . '/components/' . $this->option);
	}
}
