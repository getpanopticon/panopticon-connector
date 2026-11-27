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
 * The Akeeba Backup information includes its Secret Word; only a Super User may have it.
 */
class BackupInfoAccessTest extends AbstractApiTestCase
{
	public function testNonSuperUserCannotReadTheAkeebaBackupInformation(): void
	{
		$response = $this->api(
			'GET', 'v1/panopticon/akeebabackup/info', null, SiteFixtures::persona(SiteFixtures::POWERFUL)['token']
		);

		$this->assertStatus(403, $response);
		$this->assertStringNotContainsString('"secret"', $response['raw']);
	}
}
