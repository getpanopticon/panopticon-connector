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
 * Only a Super User may delete an update site.
 */
class UpdatesiteDeleteTest extends AbstractApiTestCase
{
	public function testNonSuperUserCannotDeleteAnUpdateSite(): void
	{
		$id = SiteFixtures::createUpdateSite();

		$response = $this->api(
			'DELETE', 'v1/panopticon/updatesite/' . $id, null, SiteFixtures::persona(SiteFixtures::POWERFUL)['token']
		);

		$this->assertStatus(403, $response);
		$this->assertNotNull(SiteFixtures::updateSite($id), 'The update site must not have been deleted');
	}

	public function testSuperUserCanDeleteAnUpdateSite(): void
	{
		$id = SiteFixtures::createUpdateSite();

		$this->assertStatus(204, $this->api('DELETE', 'v1/panopticon/updatesite/' . $id));
		$this->assertNull(SiteFixtures::updateSite($id));
	}
}
