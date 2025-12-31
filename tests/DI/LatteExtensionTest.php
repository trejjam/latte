<?php

declare(strict_types=1);

namespace Trejjam\Latte\Tests\DI;

use Latte\Engine;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\ContainerLoader;
use Tester\Assert;
use Tester\TestCase;
use Trejjam\Latte\DI\LatteExtension;
use Trejjam\Latte\TrejjamLatteExtension;

require __DIR__ . '/../bootstrap.php';

/**
 * Tests for Nette DI LatteExtension
 *
 * @testCase
 */
final class LatteExtensionTest extends TestCase
{
	private string $tempDir;

	protected function setUp() : void
	{
		parent::setUp();
		$this->tempDir = __DIR__ . '/../temp/di-extension-' . getmypid();

		if (!is_dir($this->tempDir)) {
			mkdir($this->tempDir, 0777, true);
		}
	}

	protected function tearDown() : void
	{
		parent::tearDown();

		// Clean up temp files
		if (is_dir($this->tempDir)) {
			$files = glob($this->tempDir . '/*');
			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
			rmdir($this->tempDir);
		}
	}

	/**
	 * Creates a DI container with the LatteExtension registered
	 *
	 * @param array<string, mixed> $config Extension configuration
	 * @return Container
	 */
	private function createContainer(array $config = []) : Container
	{
		$loader = new ContainerLoader($this->tempDir, true);

		$containerClass = $loader->load(function (Compiler $compiler) use ($config) : void {
			// Manually register Latte Engine service
			$compiler->addConfig([
				'services' => [
					'latte.latteFactory' => Engine::class,
				],
			]);

			// Register our DI extension
			$compiler->addExtension('trejjam.latte', new LatteExtension());

			// Set config if provided
			if ($config !== []) {
				$compiler->addConfig(['trejjam.latte' => $config]);
			}
		}, [__FILE__, $config]);

		return new $containerClass();
	}

	public function testExtensionCanBeRegistered() : void
	{
		Assert::noError(function () : void {
			$this->createContainer();
		});
	}

	public function testLatteEngineServiceExists() : void
	{
		$container = $this->createContainer();

		Assert::true($container->hasService('latte.latteFactory'));
		Assert::type(Engine::class, $container->getService('latte.latteFactory'));
	}

	public function testTrejjamLatteExtensionIsRegistered() : void
	{
		$container = $this->createContainer();

		/** @var Engine $latte */
		$latte = $container->getService('latte.latteFactory');

		// Render a simple template using the json filter to verify extension is loaded
		$result = $latte->renderToString('{$data|json}', ['data' => ['test' => 'value']]);

		Assert::contains('{"test":"value"}', $result);
	}

	public function testJsonFilterIsAvailable() : void
	{
		$container = $this->createContainer();

		/** @var Engine $latte */
		$latte = $container->getService('latte.latteFactory');

		// Test basic json filter
		$result = $latte->renderToString('{$data|json}', ['data' => ['foo' => 'bar']]);
		Assert::contains('"foo"', $result);
		Assert::contains('"bar"', $result);
	}

	public function testMd5FilterIsAvailable() : void
	{
		$container = $this->createContainer();

		/** @var Engine $latte */
		$latte = $container->getService('latte.latteFactory');

		// Test md5 filter
		$result = $latte->renderToString('{$value|md5}', ['value' => 'test']);
		Assert::same('098f6bcd4621d373cade4e832627b4f6', trim($result));
	}

	public function testSha1FilterIsAvailable() : void
	{
		$container = $this->createContainer();

		/** @var Engine $latte */
		$latte = $container->getService('latte.latteFactory');

		// Test sha1 filter
		$result = $latte->renderToString('{$value|sha1}', ['value' => 'test']);
		Assert::same('a94a8fe5ccb19ba61c4c0873d391e987982fbbd3', trim($result));
	}

	public function testConfigSchemaIsValid() : void
	{
		// Test that extension can be created with empty config
		Assert::noError(function () : void {
			$this->createContainer([]);
		});

		// Future: test config options when they are added
		// Assert::noError(function () : void {
		// 	$this->createContainer(['enabled' => true]);
		// });
	}

	public function testMultipleOptionsInJsonFilter() : void
	{
		$container = $this->createContainer();

		/** @var Engine $latte */
		$latte = $container->getService('latte.latteFactory');

		// Test json filter with multiple options
		$data = ['unicode' => 'Příliš'];
		$result = $latte->renderToString('{$data|json:"pretty","ascii"}', ['data' => $data]);

		// Should be pretty-printed
		Assert::contains("\n", $result);

		// Should escape unicode
		Assert::contains('\u', $result);
		Assert::notContains('ř', $result);
	}
}

(new LatteExtensionTest())->run();
