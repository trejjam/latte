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
	 * JSON encoding filter with granular control options
	 *
	 * Usage:
	 *   {$data|json}                           - basic encoding (HTML-safe by default)
	 *   {$data|json:'pretty'}                  - pretty-printed output
	 *   {$data|json:'pretty':'ascii'}          - pretty + ASCII-safe
	 *   {$data|json:'pretty':'!html'}          - pretty + disable HTML-safe
	 *   {$data|json:'forceObjects'}            - force objects (empty arrays as {})
	 *
	 * Options (multiple string parameters):
	 *   - pretty         : Pretty-print with indentation
	 *   - ascii          : Escape unicode as \uXXXX
	 *   - html           : HTML-safe encoding - escapes <, >, &, ', " (enabled by default)
	 *   - !html          : Disable HTML-safe encoding
	 *   - forceObjects   : Force arrays to objects
	 *
	 * @param mixed $input Value to encode
	 * @param string ...$options Variable number of option strings
	 * @return string JSON encoded string
	 * @throws \Nette\Utils\JsonException
	 */
	private function jsonFilter(mixed $input, string ...$options) : string
	{
		$pretty = false;
		$asciiSafe = false;
		$htmlSafe = true; // Default: HTML-safe encoding
		$forceObjects = false;

		foreach ($options as $option) {
			switch (strtolower(trim($option))) {
				case 'pretty':
					$pretty = true;
					break;

				case 'ascii':
					$asciiSafe = true;
					break;

				case 'html':
					$htmlSafe = true;
					break;

				case '!html':
					$htmlSafe = false;
					break;

				case 'forceobjects':
					$forceObjects = true;
					break;

				default:
					// Ignore unknown options for forward compatibility
					break;
			}
		}

		return NetteJson::encode(
			value: $input,
			pretty: $pretty,
			asciiSafe: $asciiSafe,
			htmlSafe: $htmlSafe,
			forceObjects: $forceObjects,
		);
	}
}
