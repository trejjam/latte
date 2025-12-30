<?php

declare(strict_types=1);

namespace Trejjam\Latte\Tests;

use Tester\Assert;
use Tester\TestCase;
use Trejjam\Latte\TrejjamLatteExtension;

require __DIR__ . '/bootstrap.php';

/**
 * @testCase
 */
final class TrejjamLatteExtensionTest extends TestCase
{
	private TrejjamLatteExtension $extension;

	protected function setUp() : void
	{
		parent::setUp();
		$this->extension = new TrejjamLatteExtension();
	}

	public function testGetFilters() : void
	{
		$filters = $this->extension->getFilters();

		Assert::type('array', $filters);
		Assert::count(3, $filters);
		Assert::true(isset($filters['json']));
		Assert::true(isset($filters['md5']));
		Assert::true(isset($filters['sha1']));
		Assert::type('callable', $filters['json']);
		Assert::type('callable', $filters['md5']);
		Assert::type('callable', $filters['sha1']);
	}

	public function testJsonFilterBasic() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		// Basic encoding
		$result = $jsonFilter(['foo' => 'bar']);
		Assert::same('{"foo":"bar"}', $result);

		// HTML-safe by default (escapes <, >, &, ', ")
		$result = $jsonFilter(['html' => '<script>alert("XSS")</script>']);
		Assert::contains('\u003C', $result); // < is escaped
		Assert::contains('\u003E', $result); // > is escaped
	}

	public function testJsonFilterPretty() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['foo' => 'bar', 'nested' => ['key' => 'value']];
		$result = $jsonFilter($data, 'pretty');

		Assert::contains("\n", $result);
		Assert::contains('    ', $result); // Nette\Utils\Json uses spaces for indentation
		Assert::contains('"foo": "bar"', $result);
	}

	public function testJsonFilterAscii() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['unicode' => 'Příliš žluťoučký kůň'];
		$result = $jsonFilter($data, 'ascii');

		// Unicode characters should be escaped as \uXXXX
		Assert::notContains('ř', $result);
		Assert::notContains('ž', $result);
		Assert::contains('\u', $result);
	}

	public function testJsonFilterHtmlSafe() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['html' => '<script>alert("test")</script>'];

		// Default: HTML-safe enabled
		$resultDefault = $jsonFilter($data);
		Assert::contains('\u003C', $resultDefault);
		Assert::contains('\u003E', $resultDefault);

		// Explicit HTML-safe
		$resultHtml = $jsonFilter($data, 'html');
		Assert::contains('\u003C', $resultHtml);
		Assert::contains('\u003E', $resultHtml);

		// Disable HTML-safe
		$resultNoHtml = $jsonFilter($data, '!html');
		Assert::notContains('\u003C', $resultNoHtml);
		Assert::notContains('\u003E', $resultNoHtml);
		Assert::contains('<', $resultNoHtml);
		Assert::contains('>', $resultNoHtml);
	}

	public function testJsonFilterForceObjects() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		// Empty array should become {} instead of []
		$data = ['empty' => []];
		$result = $jsonFilter($data, 'forceObjects');

		Assert::contains('"empty":{}', $result);
	}

	public function testJsonFilterMultipleOptions() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['unicode' => 'Příliš', 'html' => '<tag>'];
		$result = $jsonFilter($data, 'pretty', 'ascii', 'html');

		// Should be pretty-printed
		Assert::contains("\n", $result);
		// Should escape unicode
		Assert::contains('\u', $result);
		Assert::notContains('ř', $result);
		// Should be HTML-safe
		Assert::contains('\u003C', $result);
	}

	public function testJsonFilterCaseInsensitive() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['test' => 'value'];

		$resultLower = $jsonFilter($data, 'pretty');
		$resultUpper = $jsonFilter($data, 'PRETTY');
		$resultMixed = $jsonFilter($data, 'PrEtTy');

		Assert::same($resultLower, $resultUpper);
		Assert::same($resultLower, $resultMixed);
	}

	public function testJsonFilterWhitespaceHandling() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['test' => 'value'];

		$resultNoSpace = $jsonFilter($data, 'pretty');
		$resultWithSpace = $jsonFilter($data, '  pretty  ');

		Assert::same($resultNoSpace, $resultWithSpace);
	}

	public function testJsonFilterUnknownOption() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['test' => 'value'];

		// Unknown options should be ignored (forward compatibility)
		Assert::noError(function () use ($jsonFilter, $data) {
			$jsonFilter($data, 'unknownOption', 'anotherUnknown');
		});
	}

	public function testMd5Filter() : void
	{
		$filters = $this->extension->getFilters();
		$md5Filter = $filters['md5'];

		$input = 'test string';
		$expected = md5($input);

		Assert::same($expected, $md5Filter($input));
		Assert::same('098f6bcd4621d373cade4e832627b4f6', $md5Filter('test'));
	}

	public function testSha1Filter() : void
	{
		$filters = $this->extension->getFilters();
		$sha1Filter = $filters['sha1'];

		$input = 'test string';
		$expected = sha1($input);

		Assert::same($expected, $sha1Filter($input));
		Assert::same('a94a8fe5ccb19ba61c4c0873d391e987982fbbd3', $sha1Filter('test'));
	}

	public function testGetFunctions() : void
	{
		$functions = $this->extension->getFunctions();

		Assert::type('array', $functions);
		Assert::count(0, $functions);
	}

	public function testGetTags() : void
	{
		$tags = $this->extension->getTags();

		Assert::type('array', $tags);
		Assert::count(0, $tags);
	}

	public function testGetPasses() : void
	{
		$passes = $this->extension->getPasses();

		Assert::type('array', $passes);
		Assert::count(0, $passes);
	}
}

(new TrejjamLatteExtensionTest())->run();
