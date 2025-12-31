<?php

declare(strict_types=1);

namespace Trejjam\Latte\DI;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Trejjam\Latte\TrejjamLatteExtension;

/**
 * Nette DI extension for automatic Latte extension registration
 *
 * Automatically registers TrejjamLatteExtension with the Latte engine
 * when using Nette DI container.
 *
 * Usage in config.neon:
 *   extensions:
 *     trejjam.latte: Trejjam\Latte\DI\LatteExtension
 *
 * Or use Composer auto-discovery (no manual registration needed).
 *
 * @author Jan Trejbal
 */
final class LatteExtension extends CompilerExtension
{
	/**
	 * Returns configuration schema
	 *
	 * Currently minimal - placeholder for future configuration options.
	 */
	public function getConfigSchema() : Schema
	{
		return Expect::structure([
			// Future configuration options can be added here
			// Example:
			// 'enabled' => Expect::bool(true),
			// 'filters' => Expect::arrayOf('bool')->default(['json' => true, 'md5' => true, 'sha1' => true]),
		]);
	}

	/**
	 * Load configuration and register services
	 */
	public function loadConfiguration() : void
	{
		$this->buildLatteExtension();
	}

	/**
	 * Register Latte extension before container compilation
	 *
	 * Hooks into the Latte Engine factory service and adds TrejjamLatteExtension
	 * to provide json, md5, and sha1 filters.
	 *
	 * Note: This extension requires 'latte.latteFactory' service to be registered.
	 * If using nette/application, ensure the 'latte' extension is registered first.
	 * Alternatively, register TrejjamLatteExtension directly in latte.extensions config.
	 */
	public function beforeCompile() : void
	{
		$builder = $this->getContainerBuilder();

		// Check if Latte factory service exists
		if (!$builder->hasDefinition('latte.latteFactory')) {
			// Latte factory not registered - skip auto-registration
			// User should either:
			// 1. Register nette/application's latte extension first, or
			// 2. Register TrejjamLatteExtension directly in latte.extensions config
			return;
		}

		// Get the factory definition
		$latteFactoryDefinition = $builder->getDefinition('latte.latteFactory');

		// Handle FactoryDefinition (nette/application 3.2+)
		if ($latteFactoryDefinition instanceof FactoryDefinition) {
			$latteFactoryDefinition->getResultDefinition()
				->addSetup('addExtension', [$builder->getDefinition($this->prefix('extension'))]);
			return;
		}

		// Handle ServiceDefinition (older versions or direct Engine registration)
		if ($latteFactoryDefinition instanceof ServiceDefinition) {
			$latteFactoryDefinition->addSetup(
				'addExtension',
				[$builder->getDefinition($this->prefix('extension'))]
			);
			return;
		}
	}

	/**
	 * Build and register TrejjamLatteExtension service
	 */
	private function buildLatteExtension() : void
	{
		$this->getContainerBuilder()->addDefinition($this->prefix('extension'))
			->setFactory(TrejjamLatteExtension::class)
			->setAutowired(false);
	}
}
