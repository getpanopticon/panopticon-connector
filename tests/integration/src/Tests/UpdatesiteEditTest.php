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
 * Editing an update site through the API may only change whether it is enabled, and its Download Key.
 */
class UpdatesiteEditTest extends AbstractApiTestCase
{
	public function testEditingAnUpdateSiteIgnoresAllOtherFields(): void
	{
		$id = SiteFixtures::createUpdateSite();

		$response = $this->api(
			'PATCH', 'v1/panopticon/updatesite/' . $id, [
				'enabled'  => 0,
				'location' => 'https://attacker.example.invalid/evil.xml',
				'name'     => 'Renamed',
				'type'     => 'collection',
			]
		);

		$this->assertStatus(200, $response);

		$row = SiteFixtures::updateSite($id);

		$this->assertSame('0', (string) $row['enabled'], 'The allowed field must be saved');
		$this->assertSame('https://updates.example.invalid/original.xml', $row['location']);
		$this->assertSame('Disposable test update site', $row['name']);
		$this->assertSame('extension', $row['type']);
	}

	public function testEditingTheDownloadKeyStillWorks(): void
	{
		$id = SiteFixtures::createUpdateSite();

		$response = $this->api('PATCH', 'v1/panopticon/updatesite/' . $id, ['extra_query' => 'dlid=abc123']);

		$this->assertStatus(200, $response);

		$row = SiteFixtures::updateSite($id);

		$this->assertStringContainsString('abc123', (string) $row['extra_query']);
		$this->assertSame('https://updates.example.invalid/original.xml', $row['location']);
	}
}
