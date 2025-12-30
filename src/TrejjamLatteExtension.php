<?php

declare(strict_types=1);

namespace Trejjam\Latte;

use Latte\ContentType;
use Latte\Extension;
use Latte\Runtime\FilterInfo;
use Latte\Runtime\Html;
use Nette\Utils\Json as NetteJson;
use RuntimeException;

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
	 * Returns Html when HTML-safe mode is enabled (default) to prevent double-escaping.
	 * Returns plain string when !html option is used.
	 *
	 * Usage:
	 *   {$data|json}                           - basic encoding (HTML-safe by default, returns Html)
	 *   {$data|json:'pretty'}                  - pretty-printed output (returns Html)
	 *   {$data|json:'pretty':'ascii'}          - pretty + ASCII-safe (returns Html)
	 *   {$data|json:'pretty':'!html'}          - pretty + disable HTML-safe (returns string)
	 *   {$data|json:'forceObjects'}            - force objects (empty arrays as {})
	 *
	 * Options (multiple string parameters):
	 *   - pretty         : Pretty-print with indentation
	 *   - ascii          : Escape unicode as \uXXXX
	 *   - html           : HTML-safe encoding - escapes <, >, &, ', " (enabled by default)
	 *   - !html          : Disable HTML-safe encoding (returns plain string instead of Html)
	 *   - forceObjects   : Force arrays to objects
	 *
	 * @param FilterInfo $info Latte filter context (validates contentType)
	 * @param mixed $input Value to encode
	 * @param string ...$options Variable number of option strings
	 * @return Html|string Returns Html when htmlSafe=true (default), string when htmlSafe=false
	 * @throws RuntimeException If used in incompatible content type
	 * @throws \Nette\Utils\JsonException If JSON encoding fails
	 */
	private function jsonFilter(FilterInfo $info, mixed $input, string ...$options) : Html|string
	{
		if (!in_array($info->contentType, [null, ContentType::JavaScript], true)) {
			$actualType = $info->contentType ?? 'mixed';
			throw new RuntimeException(
				"Filter |json used in incompatible content type {$actualType}. Expected text or null."
			);
		}

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

		$json = NetteJson::encode(
			value: $input,
			pretty: $pretty,
			asciiSafe: $asciiSafe,
			htmlSafe: $htmlSafe,
			forceObjects: $forceObjects,
		);

		if (!$htmlSafe) {
			$info->contentType = ContentType::JavaScript;
		}

		// Return Html when HTML-safe to prevent Latte from double-escaping
		// Return plain string when !html to allow raw JSON output
		return $htmlSafe ? new Html($json) : $json;
	}
}
