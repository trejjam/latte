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

	protected function setUp() : void
	{
		parent::setUp();

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
		$invalidTemplate = __DIR__ . '/../fixtures/invalid-syntax.latte';

		// Create a template with invalid Latte syntax
		file_put_contents($invalidTemplate, '{if $test}Unclosed if');

		$ok = $this->linter->scanFiles([$invalidTemplate]);

		// Should fail because of syntax error
		Assert::false($ok, 'Template with syntax error should fail linting');

		// Clean up
		@unlink($invalidTemplate);
	}

	public function testLintValidJsonFilter() : void
	{
		$validTemplate = __DIR__ . '/../fixtures/valid-json.latte';

		// Create a template with valid JSON filter usage
		file_put_contents($validTemplate, '{var $data = ["test" => "value"]}{$data|json}');

		$ok = $this->linter->scanFiles([$validTemplate]);

		Assert::true($ok, 'Template with valid JSON filter should pass linting');

		// Clean up
		@unlink($validTemplate);
	}

	public function testLintValidHashFilters() : void
	{
		$validTemplate = __DIR__ . '/../fixtures/valid-hash.latte';

		// Create a template with valid hash filters
		file_put_contents($validTemplate, '{var $text = "test"}{$text|md5} {$text|sha1}');

		$ok = $this->linter->scanFiles([$validTemplate]);

		Assert::true($ok, 'Template with valid hash filters should pass linting');

		// Clean up
		@unlink($validTemplate);
	}

	public function testLintChainedFilters() : void
	{
		$validTemplate = __DIR__ . '/../fixtures/valid-chained.latte';

		// Create a template with chained filters
		file_put_contents($validTemplate, '{var $text = "test"}{$text|md5|upper}');

		$ok = $this->linter->scanFiles([$validTemplate]);

		Assert::true($ok, 'Template with chained filters should pass linting');

		// Clean up
		@unlink($validTemplate);
	}
}

(new LatteLintTest())->run();
