<?php
/*
 * @package   panopticon
 * @copyright Copyright (c)2023-2026 Nikolaos Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

namespace Akeeba\Panopticon\Tests\Integration\Tests;

use Akeeba\Panopticon\Tests\Integration\AbstractApiTestCase;

/**
 * Chunked core update download, resumed over several requests (issue #32).
 *
 * The "update server" is a static fixture file served by the test stack's own Apache, which supports HEAD and Range
 * requests like a real download server. No Joomla update is involved: the endpoint is told the URL to download.
 *
 * The file is deliberately larger than the first chunk, so the download needs more than one request, and its size is
 * odd and its content non-repetitive: a dropped or duplicated byte at a chunk boundary changes the hash.
 */
class ChunkedDownloadTest extends AbstractApiTestCase
{
	private const FIXTURE_SIZE = 400001;

	private string $fixturePath = '';

	private string $downloadedPath = '';

	protected function tearDown(): void
	{
		foreach ([$this->fixturePath, $this->downloadedPath] as $file)
		{
			if ($file !== '' && is_file($file))
			{
				@unlink($file);
			}
		}

		parent::tearDown();
	}

	public function testChunkedDownloadResumedOverSeveralRequestsIsByteIdentical(): void
	{
		// Sending the size skips the HEAD size probe: this test is about resuming the download.
		$this->assertChunkedDownloadIsByteIdentical(['size' => self::FIXTURE_SIZE]);
	}

	/**
	 * Without a size the connector finds it with a HEAD request. The test stack's Apache speaks HTTP/1.1 and sends
	 * capitalised header names, which must be understood just like HTTP/2's lowercase ones.
	 */
	public function testChunkedDownloadWithoutSizeProbesItFromTheServer(): void
	{
		$this->assertChunkedDownloadIsByteIdentical([]);
	}

	/**
	 * Downloads a fixture file through the API, one request at a time, and checks the result.
	 *
	 * @param   array  $extraPost  POST parameters to send with the first request, besides the URL.
	 */
	private function assertChunkedDownloadIsByteIdentical(array $extraPost): void
	{
		$webRoot = rtrim(getenv('PANOPTICON_WEB_ROOT') ?: '/var/www/html', '/');

		if (!is_dir($webRoot) || !is_writable($webRoot))
		{
			$this->markTestSkipped("The web root $webRoot is not writable from the test process.");
		}

		$basename = 'panopticon-itest-' . bin2hex(random_bytes(4)) . '.zip';
		$content  = $this->makeFixtureContent(self::FIXTURE_SIZE);

		$this->fixturePath    = $webRoot . '/' . $basename;
		$this->downloadedPath = $webRoot . '/tmp/' . $basename;

		file_put_contents($this->fixturePath, $content);

		$parts = parse_url(self::$baseUrl);
		$url   = sprintf('%s://%s%s/%s', $parts['scheme'], $parts['host'], isset($parts['port']) ? ':' . $parts['port'] : '', $basename);

		$post     = ['url' => $url] + $extraPost;
		$requests = 0;
		$done     = false;

		while (!$done)
		{
			$this->assertLessThan(20, ++$requests, 'The chunked download does not finish.');

			$response = $this->api('POST', 'v1/panopticon/core/update/download/chunked', $post, self::SELF_TOKEN, true);

			$this->assertJsonApiSuccess($response);

			$attributes = $response['json']['data']['attributes'] ?? [];

			$this->assertNull($attributes['error'] ?? null, 'The download reported an error: ' . $response['raw']);
			$this->assertSame(self::FIXTURE_SIZE, (int) $attributes['size']);

			$done = (bool) $attributes['done'];

			// The offset is the index of the last byte written, so the file holds offset + 1 bytes.
			clearstatcache(true, $this->downloadedPath);
			$this->assertFileExists($this->downloadedPath);
			$this->assertSame(
				(int) $attributes['offset'] + 1, filesize($this->downloadedPath),
				sprintf('Request %d: the file size does not match the reported offset (%d).', $requests, $attributes['offset'])
			);

			$post = [
				'url'         => $url,
				'size'        => (int) $attributes['size'],
				'offset'      => (int) $attributes['offset'],
				'chunk_index' => (int) $attributes['chunk_index'],
			];
		}

		$this->assertGreaterThan(1, $requests, 'The test must exercise a download resumed over several requests.');
		$this->assertSame(self::FIXTURE_SIZE, filesize($this->downloadedPath), 'The package has the wrong size.');
		$this->assertSame(
			hash('sha256', $content), hash_file('sha256', $this->downloadedPath), 'The package content differs.'
		);
	}

	/**
	 * Deterministic, non-repetitive content of the exact requested size.
	 */
	private function makeFixtureContent(int $size): string
	{
		$content = '';

		for ($i = 0; strlen($content) < $size; $i++)
		{
			$content .= hash('sha256', 'panopticon-' . $i, true);
		}

		return substr($content, 0, $size);
	}
}
