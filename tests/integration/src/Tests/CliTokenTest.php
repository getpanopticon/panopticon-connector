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
 * The panopticon:token:get command only prints the token of a Super User.
 */
class CliTokenTest extends AbstractApiTestCase
{
	public function testNoTokenIsPrintedForANonSuperUser(): void
	{
		$persona = SiteFixtures::persona(SiteFixtures::POWERFUL);

		[$exitCode, $output] = SiteFixtures::cli(['panopticon:token:get', '-u', $persona['username'], '--no-ansi']);

		$this->assertNotSame(0, $exitCode, $output);
		$this->assertStringNotContainsString($persona['token'], $output);
		$this->assertStringContainsString('not a Super User', preg_replace('#\s+#', ' ', $output));
	}

	public function testTheTokenIsPrintedForASuperUser(): void
	{
		[$exitCode, $output] = SiteFixtures::cli(['panopticon:token:get', '-u', 'admin', '--no-ansi', '-q']);

		$this->assertSame(0, $exitCode, $output);
		$this->assertStringContainsString(self::$token, $output);
	}
}
