<?php

declare(strict_types=1);

namespace Trejjam\Latte;

use Latte\Extension;

final class TrejjamLatteExtension extends Extension
{
	public function getFilters() : array
	{
		return [
			// Add custom filters here
		];
	}

	public function getFunctions() : array
	{
		return [
			// Add custom functions here
		];
	}

	public function getTags() : array
	{
		return [
			// Add custom tags here
		];
	}

	public function getPasses() : array
	{
		return [
			// Add custom passes here
		];
	}
}
