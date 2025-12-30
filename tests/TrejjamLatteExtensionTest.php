<?php

declare(strict_types=1);

namespace Trejjam\Latte\Tests;

use Latte\Runtime\Html;
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

		// Basic encoding - returns Html\Node by default (HTML-safe mode)
		$result = $jsonFilter(['foo' => 'bar']);
		Assert::type(Html::class, $result);
		Assert::same('{"foo":"bar"}', (string) $result);

		// HTML-safe by default (escapes <, >, &, ', ")
		$result = $jsonFilter(['html' => '<script>alert("XSS")</script>']);
		Assert::type(Html::class, $result);
		$resultStr = (string) $result;
		Assert::contains('\u003C', $resultStr); // < is escaped
		Assert::contains('\u003E', $resultStr); // > is escaped
	}

	public function testJsonFilterPretty() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['foo' => 'bar', 'nested' => ['key' => 'value']];
		$result = $jsonFilter($data, 'pretty');

		Assert::type(Html::class, $result);
		$resultStr = (string) $result;
		Assert::contains("\n", $resultStr);
		Assert::contains('    ', $resultStr); // Nette\Utils\Json uses spaces for indentation
		Assert::contains('"foo": "bar"', $resultStr);
	}

	public function testJsonFilterAscii() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['unicode' => 'Příliš žluťoučký kůň'];
		$result = $jsonFilter($data, 'ascii');

		Assert::type(Html::class, $result);
		$resultStr = (string) $result;
		// Unicode characters should be escaped as \uXXXX
		Assert::notContains('ř', $resultStr);
		Assert::notContains('ž', $resultStr);
		Assert::contains('\u', $resultStr);
	}

	public function testJsonFilterHtmlSafe() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['html' => '<script>alert("test")</script>'];

		// Default: HTML-safe enabled - returns Html\Node
		$resultDefault = $jsonFilter($data);
		Assert::type(Html::class, $resultDefault);
		$resultStr = (string) $resultDefault;
		Assert::contains('\u003C', $resultStr);
		Assert::contains('\u003E', $resultStr);

		// Explicit HTML-safe - returns Html\Node
		$resultHtml = $jsonFilter($data, 'html');
		Assert::type(Html::class, $resultHtml);
		$resultStr = (string) $resultHtml;
		Assert::contains('\u003C', $resultStr);
		Assert::contains('\u003E', $resultStr);

		// Disable HTML-safe - returns plain string
		$resultNoHtml = $jsonFilter($data, '!html');
		Assert::type('string', $resultNoHtml);
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

		Assert::type(Html::class, $result);
		Assert::contains('"empty":{}', (string) $result);
	}

	public function testJsonFilterMultipleOptions() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['unicode' => 'Příliš', 'html' => '<tag>'];
		$result = $jsonFilter($data, 'pretty', 'ascii', 'html');

		Assert::type(Html::class, $result);
		$resultStr = (string) $result;
		// Should be pretty-printed
		Assert::contains("\n", $resultStr);
		// Should escape unicode
		Assert::contains('\u', $resultStr);
		Assert::notContains('ř', $resultStr);
		// Should be HTML-safe
		Assert::contains('\u003C', $resultStr);
	}

	public function testJsonFilterCaseInsensitive() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['test' => 'value'];

		$resultLower = $jsonFilter($data, 'pretty');
		$resultUpper = $jsonFilter($data, 'PRETTY');
		$resultMixed = $jsonFilter($data, 'PrEtTy');

		// All return Html\Node, compare string representations
		Assert::same((string) $resultLower, (string) $resultUpper);
		Assert::same((string) $resultLower, (string) $resultMixed);
	}

	public function testJsonFilterWhitespaceHandling() : void
	{
		$filters = $this->extension->getFilters();
		$jsonFilter = $filters['json'];

		$data = ['test' => 'value'];

		$resultNoSpace = $jsonFilter($data, 'pretty');
		$resultWithSpace = $jsonFilter($data, '  pretty  ');

		// Both return Html\Node, compare string representations
		Assert::same((string) $resultNoSpace, (string) $resultWithSpace);
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
