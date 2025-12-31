<?php

declare(strict_types=1);

namespace Trejjam\Latte\DI;

use Latte\Engine;
use Nette\DI\CompilerExtension;
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
	 * Register Latte extension before container compilation
	 *
	 * Hooks into the Latte Engine service and adds TrejjamLatteExtension
	 * to provide json, md5, and sha1 filters.
	 */
	public function beforeCompile() : void
	{
		$builder = $this->getContainerBuilder();

		// Find the Latte Engine service by type
		$latteDefinition = $builder->getDefinitionByType(Engine::class);

		// Ensure we have a ServiceDefinition (not just Definition)
		if (!$latteDefinition instanceof ServiceDefinition) {
			return;
		}

		// Register TrejjamLatteExtension with the Latte engine
		$latteDefinition->addSetup(
			'addExtension',
			[new TrejjamLatteExtension()]
		);
	}
}
