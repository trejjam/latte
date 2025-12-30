<?php

declare(strict_types=1);

namespace Trejjam\Latte\Tests\Integration;

use Latte\Engine;
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
 * Tests use fixture templates from tests/fixtures/ directory.
 *
 * @testCase
 */
final class LatteRenderingTest extends TestCase
{
	private Engine $latte;

	private string $tempDir;

	private SnapshotVerifier $snapshots;

	private string $fixturesDir;

	protected function setUp() : void
	{
		parent::setUp();

		$this->fixturesDir = __DIR__ . '/../fixtures';

		// Create temp directory for compiled templates (unique per test to avoid parallel conflicts)
		$this->tempDir = __DIR__ . '/../temp/' . getmypid() . '-' . uniqid();
		if (!is_dir($this->tempDir)) {
			mkdir($this->tempDir, 0777, true);
		}

		// Initialize Latte engine
		$this->latte = new Engine();
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

	/**
	 * Test filter-test.latte fixture
	 * Tests basic filter usage in HTML template
	 */
	public function testFilterTestFixture() : void
	{
		$templateFile = $this->fixturesDir . '/filter-test.latte';
		$params = [
			'jsonData' => ['test' => 'value', 'number' => 123],
			'textData' => 'hello world',
		];

		$result = $this->latte->renderToString($templateFile, $params);

		$this->snapshots->verifyHtml($result, 'filter-test', SnapshotVerifier::shouldUpdateSnapshots());

		// Verify expected content is present
		Assert::contains('class="json-basic"', $result);
		Assert::contains('class="json-pretty"', $result);
		Assert::contains('class="json-ascii"', $result);
		Assert::contains('class="md5"', $result);
		Assert::contains('class="sha1"', $result);
		Assert::contains(md5('hello world'), $result);
		Assert::contains(sha1('hello world'), $result);
	}

	/**
	 * Test json-options.latte fixture
	 * Tests all JSON filter options comprehensively
	 */
	public function testJsonOptionsFixture() : void
	{
		$templateFile = $this->fixturesDir . '/json-options.latte';
		$params = [];

		$result = $this->latte->renderToString($templateFile, $params);

		$this->snapshots->verify($result, 'json-options', SnapshotVerifier::shouldUpdateSnapshots());

		// Verify key JSON filter behaviors
		Assert::contains('Basic:', $result);
		Assert::contains('Pretty:', $result);
		Assert::contains('HTML-safe:', $result);
		Assert::contains('No HTML-safe:', $result);
		Assert::contains('ASCII:', $result);
		Assert::contains('Force objects:', $result);
		Assert::contains('Multiple:', $result);

		// Verify HTML-safe encoding is working
		Assert::contains('\u003C', $result); // < is escaped in HTML-safe mode
		Assert::contains('\u003E', $result); // > is escaped in HTML-safe mode
	}

	/**
	 * Test hash-filters.latte fixture
	 * Tests MD5 and SHA1 filters with chaining and loops
	 */
	public function testHashFiltersFixture() : void
	{
		$templateFile = $this->fixturesDir . '/hash-filters.latte';
		$params = [];

		$result = $this->latte->renderToString($templateFile, $params);

		$this->snapshots->verify($result, 'hash-filters', SnapshotVerifier::shouldUpdateSnapshots());

		// Verify MD5 and SHA1 output for 'test'
		Assert::contains('MD5: ' . md5('test'), $result);
		Assert::contains('SHA1: ' . sha1('test'), $result);

		// Verify chained filters (uppercase)
		Assert::contains('MD5 Upper: ' . strtoupper(md5('test')), $result);
		Assert::contains('SHA1 Upper: ' . strtoupper(sha1('test')), $result);

		// Verify loop output
		Assert::contains('MD5=' . md5('a'), $result);
		Assert::contains('MD5=' . md5('b'), $result);
		Assert::contains('MD5=' . md5('c'), $result);
		Assert::contains('SHA1=' . sha1('a'), $result);
		Assert::contains('SHA1=' . sha1('b'), $result);
		Assert::contains('SHA1=' . sha1('c'), $result);
	}

	/**
	 * Test filter-test.latte with complex JSON data
	 */
	public function testFilterTestWithComplexData() : void
	{
		$templateFile = $this->fixturesDir . '/filter-test.latte';
		$params = [
			'jsonData' => [
				'string' => 'test',
				'int' => 123,
				'float' => 45.67,
				'bool' => true,
				'null' => null,
				'array' => [1, 2, 3],
				'nested' => ['a' => 'b', 'c' => ['d' => 'e']],
				'unicode' => 'Příliš žluťoučký kůň',
				'html' => '<script>alert("XSS")</script>',
			],
			'textData' => 'test@example.com',
		];

		$result = $this->latte->renderToString($templateFile, $params);

		$this->snapshots->verifyHtml($result, 'filter-test-complex', SnapshotVerifier::shouldUpdateSnapshots());

		// Verify MD5/SHA1 of email
		Assert::contains(md5('test@example.com'), $result);
		Assert::contains(sha1('test@example.com'), $result);

		// Verify JSON contains expected data types
		Assert::contains('"string":"test"', $result);
		Assert::contains('"int":123', $result);
		Assert::contains('"bool":true', $result);
		Assert::contains('"null":null', $result);

		// Verify HTML-safe encoding in JSON
		Assert::contains('\u003Cscript\u003E', $result);
	}

	/**
	 * Test json-options.latte to verify pretty print formatting
	 */
	public function testJsonPrettyPrintInFixture() : void
	{
		$templateFile = $this->fixturesDir . '/json-options.latte';
		$params = [];

		$result = $this->latte->renderToString($templateFile, $params);

		// Verify pretty-print has newlines and indentation
		Assert::contains("\n", $result);
		Assert::contains('    ', $result); // 4-space indentation from Nette\Utils\Json
	}

	/**
	 * Test that JSON filter escapes unicode when 'ascii' option is used
	 */
	public function testJsonAsciiOptionInFixture() : void
	{
		$templateFile = $this->fixturesDir . '/json-options.latte';
		$params = [];

		$result = $this->latte->renderToString($templateFile, $params);

		// Verify unicode is escaped in ASCII mode
		Assert::contains('\u', $result); // Unicode escapes present
		// The actual Czech characters should be in the template vars
	}

	/**
	 * Test that empty arrays become objects with forceObjects option
	 */
	public function testJsonForceObjectsInFixture() : void
	{
		$templateFile = $this->fixturesDir . '/json-options.latte';
		$params = [];

		$result = $this->latte->renderToString($templateFile, $params);

		// Verify empty array is rendered as {} not [] (note: { is HTML-escaped to &#123;)
		Assert::contains('"empty":', $result);
		Assert::contains('&#123;}}', $result); // &#123; is HTML-escaped {
		Assert::notContains('"empty":[]', $result);
	}

	/**
	 * Test hash filters with different input strings
	 */
	public function testHashFiltersWithVariousInputs() : void
	{
		$templateFile = $this->fixturesDir . '/hash-filters.latte';
		$params = [];

		$result = $this->latte->renderToString($templateFile, $params);

		// Test known hash values
		Assert::same('098f6bcd4621d373cade4e832627b4f6', md5('test'));
		Assert::same('a94a8fe5ccb19ba61c4c0873d391e987982fbbd3', sha1('test'));

		// Verify these are in the output
		Assert::contains('098f6bcd4621d373cade4e832627b4f6', $result);
		Assert::contains('a94a8fe5ccb19ba61c4c0873d391e987982fbbd3', $result);
	}

	/**
	 * Test that all three fixtures render without errors
	 */
	public function testAllFixturesRenderSuccessfully() : void
	{
		$fixtures = [
			'filter-test.latte' => ['jsonData' => ['test' => 1], 'textData' => 'test'],
			'json-options.latte' => [],
			'hash-filters.latte' => [],
		];

		foreach ($fixtures as $fixture => $params) {
			$templateFile = $this->fixturesDir . '/' . $fixture;
			
			Assert::noError(function () use ($templateFile, $params) {
				$this->latte->renderToString($templateFile, $params);
			});
		}
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
