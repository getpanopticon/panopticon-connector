<?php
/*
 * @package   panopticon
 * @copyright Copyright (c)2023-2026 Nikolaos Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;

?>
<div class="alert alert-info">
	<h3 class="alert-heading">
		<?= Text::_('COM_PANOPTICON_WELCOME_ERR_NOT_SUPERUSER_TITLE') ?>
	</h3>
	<p>
		<?= Text::_('COM_PANOPTICON_WELCOME_ERR_NOT_SUPERUSER_DETAILS') ?>
	</p>
</div>
