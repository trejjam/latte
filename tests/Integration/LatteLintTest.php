<?php

declare(strict_types=1);

namespace Trejjam\Latte\Tests\Integration;

use Latte\Tools\Linter;
use Tester\Assert;
use Tester\TestCase;
use Trejjam\Latte\TrejjamLatteExtension;

require __DIR__ . '/../bootstrap.php';

/**
 * Integration test for Latte linter with TrejjamLatteExtension
 *
 * @testCase
 */
final class LatteLintTest extends TestCase
{
	private Linter $linter;

	private string $tempDir;

	protected function setUp() : void
	{
		parent::setUp();

		// Create dedicated temporary directory for test files
		// Use getmypid() + uniqid() for better uniqueness
		$this->tempDir = sys_get_temp_dir() . '/latte-lint-test-' . getmypid() . '-' . uniqid();
		
		// Clean up if directory somehow already exists
		if (is_dir($this->tempDir)) {
			$this->cleanupTempDir();
		}
		
		mkdir($this->tempDir, 0755);

		// Initialize Latte linter with strict mode
		$this->linter = new Linter(
			debug: false,
			strict: true,
		);

		// Get the engine and configure it
		$latte = $this->linter->getEngine();
		$latte->setStrictParsing();
		$latte->addExtension(new TrejjamLatteExtension());
	}

	protected function tearDown() : void
	{
		$this->cleanupTempDir();
		parent::tearDown();
	}

	private function cleanupTempDir() : void
	{
		// Clean up temporary directory and all files within
		if (is_dir($this->tempDir)) {
			$files = array_diff(scandir($this->tempDir) ?: [], ['.', '..']);
			foreach ($files as $file) {
				unlink($this->tempDir . '/' . $file);
			}
			rmdir($this->tempDir);
		}
	}

	public function testLintFixturesDirectory() : void
	{
		$fixturesPath = __DIR__ . '/../fixtures';

		Assert::true(is_dir($fixturesPath), 'Fixtures directory should exist');

		$ok = $this->linter->scanDirectory($fixturesPath);

		Assert::true($ok, 'All template files should pass linting');
	}

	public function testLintFilterTestTemplate() : void
	{
		$templatePath = __DIR__ . '/../fixtures/filter-test.latte';

		Assert::true(file_exists($templatePath), 'Template file should exist');

		$ok = $this->linter->scanFiles([$templatePath]);

		Assert::true($ok, 'filter-test.latte should pass linting');
	}

	public function testLintJsonOptionsTemplate() : void
	{
		$templatePath = __DIR__ . '/../fixtures/json-options.latte';

		Assert::true(file_exists($templatePath), 'Template file should exist');

		$ok = $this->linter->scanFiles([$templatePath]);

		Assert::true($ok, 'json-options.latte should pass linting');
	}

	public function testLintHashFiltersTemplate() : void
	{
		$templatePath = __DIR__ . '/../fixtures/hash-filters.latte';

		Assert::true(file_exists($templatePath), 'Template file should exist');

		$ok = $this->linter->scanFiles([$templatePath]);

		Assert::true($ok, 'hash-filters.latte should pass linting');
	}

	public function testLintInvalidSyntax() : void
	{
		$invalidTemplate = $this->tempDir . '/invalid-syntax.latte';

		// Create a template with invalid Latte syntax
		file_put_contents($invalidTemplate, '{if $test}Unclosed if');

		$ok = $this->linter->scanFiles([$invalidTemplate]);

		// Should fail because of syntax error
		Assert::false($ok, 'Template with syntax error should fail linting');

		// No manual cleanup needed - tearDown() handles it
	}

	public function testLintValidJsonFilter() : void
	{
		$validTemplate = $this->tempDir . '/valid-json.latte';

		// Create a template with valid JSON filter usage
		file_put_contents($validTemplate, '{var $data = ["test" => "value"]}{$data|json}');

		$ok = $this->linter->scanFiles([$validTemplate]);

		Assert::true($ok, 'Template with valid JSON filter should pass linting');

		// No manual cleanup needed - tearDown() handles it
	}

	public function testLintValidHashFilters() : void
	{
		$validTemplate = $this->tempDir . '/valid-hash.latte';

		// Create a template with valid hash filters
		file_put_contents($validTemplate, '{var $text = "test"}{$text|md5} {$text|sha1}');

		$ok = $this->linter->scanFiles([$validTemplate]);

		Assert::true($ok, 'Template with valid hash filters should pass linting');

		// No manual cleanup needed - tearDown() handles it
	}

	public function testLintChainedFilters() : void
	{
		$validTemplate = $this->tempDir . '/valid-chained.latte';

		// Create a template with chained filters
		file_put_contents($validTemplate, '{var $text = "test"}{$text|md5|upper}');

		$ok = $this->linter->scanFiles([$validTemplate]);

		Assert::true($ok, 'Template with chained filters should pass linting');

		// No manual cleanup needed - tearDown() handles it
	}
}

(new LatteLintTest())->run();
