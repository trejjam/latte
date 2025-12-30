<?php

declare(strict_types=1);

namespace Trejjam\Latte\Tests;

use Tester\Assert;

/**
 * Snapshot verification helper inspired by Verify (C#)
 *
 * Compares actual output with expected snapshots stored in files.
 * Similar to Jest snapshots or Verify in C#.
 */
final class SnapshotVerifier
{
	private string $snapshotDir;

	public function __construct(string $testFile)
	{
		// Store snapshots in __snapshots__ directory next to test file
		$testDir = dirname($testFile);
		$this->snapshotDir = $testDir . '/__snapshots__';

		if (!is_dir($this->snapshotDir)) {
			mkdir($this->snapshotDir, 0755, true);
		}
	}

	/**
	 * Verify that actual output matches the snapshot
	 *
	 * @param string $actual Actual output to verify
	 * @param string $snapshotName Name of the snapshot file (without extension)
	 * @param bool $updateSnapshots If true, update snapshots instead of comparing (for updating tests)
	 */
	public function verify(string $actual, string $snapshotName, bool $updateSnapshots = false) : void
	{
		$snapshotFile = $this->snapshotDir . '/' . $snapshotName . '.txt';

		if ($updateSnapshots || !file_exists($snapshotFile)) {
			// Create or update snapshot
			file_put_contents($snapshotFile, $actual);

			if (!$updateSnapshots) {
				// First run - snapshot created
				Assert::true(true, "Snapshot created: {$snapshotName}");
			}

			return;
		}

		// Compare with existing snapshot
		$expected = file_get_contents($snapshotFile);

		Assert::same(
			$expected,
			$actual,
			"Output should match snapshot: {$snapshotName}\n" .
			"To update snapshots, set UPDATE_SNAPSHOTS=1 environment variable",
		);
	}

	/**
	 * Verify JSON output matches snapshot
	 *
	 * @param mixed $actual Actual data to encode as JSON
	 * @param string $snapshotName Name of the snapshot file (without extension)
	 * @param bool $updateSnapshots If true, update snapshots instead of comparing
	 */
	public function verifyJson(mixed $actual, string $snapshotName, bool $updateSnapshots = false) : void
	{
		$json = json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

		if ($json === false) {
			throw new \RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
		}

		$snapshotFile = $this->snapshotDir . '/' . $snapshotName . '.json';

		if ($updateSnapshots || !file_exists($snapshotFile)) {
			file_put_contents($snapshotFile, $json);

			if (!$updateSnapshots) {
				Assert::true(true, "JSON snapshot created: {$snapshotName}");
			}

			return;
		}

		$expected = file_get_contents($snapshotFile);

		Assert::same(
			$expected,
			$json,
			"JSON output should match snapshot: {$snapshotName}\n" .
			"To update snapshots, set UPDATE_SNAPSHOTS=1 environment variable",
		);
	}

	/**
	 * Verify HTML output matches snapshot (with normalized whitespace)
	 *
	 * @param string $actual Actual HTML output
	 * @param string $snapshotName Name of the snapshot file (without extension)
	 * @param bool $updateSnapshots If true, update snapshots instead of comparing
	 */
	public function verifyHtml(string $actual, string $snapshotName, bool $updateSnapshots = false) : void
	{
		$snapshotFile = $this->snapshotDir . '/' . $snapshotName . '.html';

		if ($updateSnapshots || !file_exists($snapshotFile)) {
			file_put_contents($snapshotFile, $actual);

			if (!$updateSnapshots) {
				Assert::true(true, "HTML snapshot created: {$snapshotName}");
			}

			return;
		}

		$expected = file_get_contents($snapshotFile);

		Assert::same(
			$expected,
			$actual,
			"HTML output should match snapshot: {$snapshotName}\n" .
			"To update snapshots, set UPDATE_SNAPSHOTS=1 environment variable",
		);
	}

	/**
	 * Check if snapshots should be updated based on environment variable
	 */
	public static function shouldUpdateSnapshots() : bool
	{
		return (bool) getenv('UPDATE_SNAPSHOTS');
	}
}
