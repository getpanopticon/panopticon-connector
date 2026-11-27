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
 * A refused request must be reported as 403 Forbidden, not as a server error.
 */
class ForbiddenStatusTest extends AbstractApiTestCase
{
	public static function adminToolsProvider(): array
	{
		return [
			'plugin/disable'   => ['admintools/plugin/disable'],
			'plugin/enable'    => ['admintools/plugin/enable'],
			'htaccess/disable' => ['admintools/htaccess/disable'],
			'htaccess/enable'  => ['admintools/htaccess/enable'],
			'tempsuperuser'    => ['admintools/tempsuperuser'],
			'scanner/start'    => ['admintools/scanner/start'],
			'scanner/step'     => ['admintools/scanner/step'],
		];
	}

	/** @dataProvider adminToolsProvider */
	public function testRefusedAdminToolsRequestIsForbidden(string $path): void
	{
		$response = $this->api(
			'POST', 'v1/panopticon/' . $path, null, SiteFixtures::persona(SiteFixtures::MINIMAL)['token']
		);

		$this->assertStatus(403, $response);
		$this->assertStringNotContainsString('JERROR_ALERTNOAUTHOR', $response['raw']);
	}
}
