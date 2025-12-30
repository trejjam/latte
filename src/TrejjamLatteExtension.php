<?php

declare(strict_types=1);

namespace Trejjam\Latte;

use Latte\Extension;
use Nette\Utils\Json as NetteJson;

/**
 * Trejjam Latte 3 Extension
 *
 * Provides utility filters for Latte templates:
 * - json: JSON encoding with options support
 * - md5: MD5 hash generation
 * - sha1: SHA1 hash generation
 *
 * Migrated from trejjam/utils package for Latte 3 compatibility.
 *
 * @author Jan Trejbal
 */
final class TrejjamLatteExtension extends Extension
{
	/**
	 * Returns list of Latte filters
	 *
	 * @return array<string, callable>
	 */
	public function getFilters() : array
	{
		return [
			'json' => $this->jsonFilter(...),
			'md5' => fn (string $input) : string => md5($input),
			'sha1' => fn (string $input) : string => sha1($input),
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

	/**
	 * JSON encoding filter with options support
	 *
	 * Usage:
	 *   {$data|json}                  - basic encoding
	 *   {$data|json:'PRETTY'}         - with constant name
	 *   {$data|json:256}              - with numeric flag
	 *
	 * @param mixed $input Value to encode
	 * @param int|string $options JSON options (numeric or constant name like 'PRETTY')
	 * @return string JSON encoded string
	 * @throws \Nette\Utils\JsonException
	 */
	private function jsonFilter(mixed $input, int|string $options = 0) : string
	{
		if (is_string($options)) {
			// Convert string constant to numeric value
			// Example: 'PRETTY' → Nette\Utils\Json::PRETTY
			$constantValue = constant(NetteJson::class . '::' . strtoupper($options));
			$options = is_int($constantValue) ? $constantValue : 0;
		}

		return NetteJson::encode($input, $options);
	}
}
