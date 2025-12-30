<?php

declare(strict_types=1);

namespace Trejjam\Latte\Tests\Integration;

use Latte\Engine;
use Latte\Loaders\StringLoader;
use Tester\Assert;
use Tester\TestCase;
use Trejjam\Latte\Tests\SnapshotVerifier;
use Trejjam\Latte\TrejjamLatteExtension;

require __DIR__ . '/../bootstrap.php';

/**
 * Integration test for Latte rendering with TrejjamLatteExtension
 *
 * Uses snapshot verification (similar to Verify in C# or Jest snapshots)
 * to ensure rendered output matches expected results.
 *
 * @testCase
 */
final class LatteRenderingTest extends TestCase
{
	private Engine $latte;

	private string $tempDir;

	private SnapshotVerifier $snapshots;

	protected function setUp() : void
	{
		parent::setUp();

		// Create temp directory for compiled templates
		$this->tempDir = __DIR__ . '/../temp';
		if (!is_dir($this->tempDir)) {
			mkdir($this->tempDir, 0777, true);
		}

		// Initialize Latte engine
		$this->latte = new Engine();
		$this->latte->setLoader(new StringLoader()); // Use string loader for inline templates
		$this->latte->setTempDirectory($this->tempDir);
		$this->latte->setStrictParsing();
		$this->latte->addExtension(new TrejjamLatteExtension());

		// Initialize snapshot verifier
		$this->snapshots = new SnapshotVerifier(__FILE__);
	}

	protected function tearDown() : void
	{
		parent::tearDown();

		// Clean up temp directory
		if (is_dir($this->tempDir)) {
			$this->removeDirectory($this->tempDir);
		}
	}

	public function testJsonFilterBasicRendering() : void
	{
		$template = '{contentType text}{$data|json}';
		$params = ['data' => ['foo' => 'bar']];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'json-basic', SnapshotVerifier::shouldUpdateSnapshots());
		Assert::same('{"foo":"bar"}', $result);
	}

	public function testJsonFilterHtmlSafeByDefault() : void
	{
		$template = '{$data|json}';
		$params = ['data' => ['html' => '<script>alert("XSS")</script>']];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'json-html-safe', SnapshotVerifier::shouldUpdateSnapshots());

		// HTML-safe by default (< and > are escaped)
		Assert::contains('\u003C', $result);
		Assert::contains('\u003E', $result);
		Assert::notContains('<script>', $result);
	}

	public function testJsonFilterPrettyRendering() : void
	{
		$template = "{\$data|json:'pretty'}";
		$params = ['data' => ['foo' => 'bar', 'nested' => ['key' => 'value']]];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'json-pretty', SnapshotVerifier::shouldUpdateSnapshots());

		Assert::contains("\n", $result);
		Assert::contains('    ', $result); // Indentation with spaces
		Assert::contains('"foo": "bar"', $result);
		Assert::contains('"nested": {', $result);
	}

	public function testJsonFilterAsciiRendering() : void
	{
		$template = "{\$data|json:'ascii'}";
		$params = ['data' => ['text' => 'Příliš žluťoučký kůň']];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'json-ascii', SnapshotVerifier::shouldUpdateSnapshots());

		// Unicode should be escaped
		Assert::notContains('ř', $result);
		Assert::notContains('ž', $result);
		Assert::contains('\u', $result);
	}

	public function testJsonFilterDisableHtmlSafe() : void
	{
		$template = "{\$data|json:'!html'}";
		$params = ['data' => ['html' => '<div>test</div>']];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'json-no-html-safe', SnapshotVerifier::shouldUpdateSnapshots());

		// HTML-safe disabled
		Assert::contains('<div>', $result);
		Assert::contains('</div>', $result);
		Assert::notContains('\u003C', $result);
	}

	public function testJsonFilterForceObjects() : void
	{
		$template = "{\$data|json:'forceObjects'}";
		$params = ['data' => ['empty' => []]];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'json-force-objects', SnapshotVerifier::shouldUpdateSnapshots());

		Assert::contains('"empty":{}', $result);
		Assert::notContains('"empty":[]', $result);
	}

	public function testJsonFilterMultipleOptions() : void
	{
		$template = "{\$data|json:'pretty', 'ascii', 'html'}";
		$params = ['data' => ['unicode' => 'Příliš', 'html' => '<tag>']];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'json-multiple-options', SnapshotVerifier::shouldUpdateSnapshots());

		// Should be pretty-printed
		Assert::contains("\n", $result);
		// Should escape unicode
		Assert::contains('\u', $result);
		Assert::notContains('ř', $result);
		// Should be HTML-safe
		Assert::contains('\u003C', $result);
		Assert::contains('\u003E', $result);
	}

	public function testMd5FilterRendering() : void
	{
		$template = '{$text|md5}';
		$params = ['text' => 'test'];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'md5-test', SnapshotVerifier::shouldUpdateSnapshots());

		Assert::same('098f6bcd4621d373cade4e832627b4f6', $result);
	}

	public function testSha1FilterRendering() : void
	{
		$template = '{$text|sha1}';
		$params = ['text' => 'test'];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'sha1-test', SnapshotVerifier::shouldUpdateSnapshots());

		Assert::same('a94a8fe5ccb19ba61c4c0873d391e987982fbbd3', $result);
	}

	public function testChainedFilters() : void
	{
		$template = '{$text|md5|upper}';
		$params = ['text' => 'test'];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'chained-filters', SnapshotVerifier::shouldUpdateSnapshots());

		Assert::same('098F6BCD4621D373CADE4E832627B4F6', $result);
	}

	public function testFiltersInConditionals() : void
	{
		$template = '{if ($data|json) === \'{"foo":"bar"}\'}Match{else}No match{/if}';
		$params = ['data' => ['foo' => 'bar']];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'filters-in-conditionals', SnapshotVerifier::shouldUpdateSnapshots());

		Assert::same('Match', $result);
	}

	public function testFiltersInLoops() : void
	{
		$template = '{foreach $items as $item}{$item|md5}{if !$iterator->last},{/if}{/foreach}';
		$params = ['items' => ['test', 'foo', 'bar']];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'filters-in-loops', SnapshotVerifier::shouldUpdateSnapshots());

		$expected = md5('test') . ',' . md5('foo') . ',' . md5('bar');
		Assert::same($expected, $result);
	}

	public function testJsonFilterWithComplexData() : void
	{
		$template = '{$data|json}';
		$params = [
			'data' => [
				'string' => 'test',
				'int' => 123,
				'float' => 45.67,
				'bool' => true,
				'null' => null,
				'array' => [1, 2, 3],
				'nested' => ['a' => 'b'],
			],
		];

		$result = $this->latte->renderToString($template, $params);

		$this->snapshots->verify($result, 'json-complex-data', SnapshotVerifier::shouldUpdateSnapshots());

		$decoded = json_decode($result, true);
		Assert::same($params['data'], $decoded);
	}

	public function testFiltersFromTemplateFile() : void
	{
		$templateFile = __DIR__ . '/../fixtures/filter-test.latte';
		$params = [
			'jsonData' => ['test' => 'value'],
			'textData' => 'hello',
		];

		$result = $this->latte->renderToString(file_get_contents($templateFile), $params);

		$this->snapshots->verifyHtml($result, 'template-file-rendering', SnapshotVerifier::shouldUpdateSnapshots());

		// Check JSON filter works
		Assert::contains('{"test":"value"}', $result);
		// Check MD5 filter works
		Assert::contains(md5('hello'), $result);
		// Check SHA1 filter works
		Assert::contains(sha1('hello'), $result);
	}

	private function removeDirectory(string $dir) : void
	{
		if (!is_dir($dir)) {
			return;
		}

		$scan = scandir($dir);
		if ($scan === false) {
			return;
		}

		$files = array_diff($scan, ['.', '..']);
		foreach ($files as $file) {
			$path = $dir . '/' . $file;
			is_dir($path) ? $this->removeDirectory($path) : unlink($path);
		}

		rmdir($dir);
	}
}

(new LatteRenderingTest())->run();
