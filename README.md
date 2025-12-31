# Trejjam Latte Extensions

[![Latest stable](https://img.shields.io/packagist/v/trejjam/latte.svg)](https://packagist.org/packages/trejjam/latte)

Latte 3 template engine extensions and utilities, providing commonly used filters for Nette applications.

## Features

- **JSON Filter**: Encode values to JSON with formatting options
- **Hash Filters**: MD5 and SHA1 hash generation
- **Latte 3 Compatible**: Built for Latte 3.x using the Extension API
- **Auto-Configuration**: Automatic registration with Nette DI via Composer
- **Linting Support**: Works with `latte-lint` without requiring DI container

## Requirements

- PHP 8.2 or higher
- Latte 3.0.20 or higher
- Nette DI 3.2 or higher
- Nette Utils 4.0 or higher

## Installation

```bash
composer require trejjam/latte
```

**That's it!** If you're using Nette DI, the extension is automatically registered via Composer auto-discovery. You can start using the filters immediately.

## Configuration

### Nette DI (Recommended)

When using Nette DI container, the extension is **automatically registered** via Composer auto-discovery. No manual configuration needed!

If auto-discovery is disabled, manually register the DI extension in `config.neon`:

```neon
extensions:
	trejjam.latte: Trejjam\Latte\DI\LatteExtension
```

This automatically adds all filters to your Latte engine.

### Manual Latte Extension Registration

If you prefer to register the Latte extension directly (without the DI extension):

```neon
latte:
	extensions:
		- Trejjam\Latte\TrejjamLatteExtension
```

### Standalone (Without Nette DI)

For **latte-lint** or other tools without Nette DI:

```php
$latte = new Latte\Engine();
$latte->addExtension(new Trejjam\Latte\TrejjamLatteExtension());
```

## Available Filters

### `|json`

Encodes value to JSON with granular control options. **HTML-safe by default** (escapes `<`, `>`, `&`, `'`, `"` to prevent XSS).

**Basic usage** (HTML-safe, unicode-friendly):
```latte
{$data|json}
```

**Options** (multiple string parameters):
- `pretty` - Pretty print with indentation
- `ascii` - Escape unicode as `\uXXXX` (for ASCII-only output)
- `html` - Enable HTML-safe encoding (default, explicit)
- `!html` - Disable HTML-safe encoding
- `forceObjects` - Force arrays to objects (empty arrays become `{}`)

**Examples**:
```latte
{* Basic JSON encoding (HTML-safe, unicode) *}
<script type="application/json">{$config|json|noescape}</script>

{* Pretty printed JSON *}
<pre>{$data|json:'pretty'}</pre>

{* Pretty + ASCII-safe (for old browsers) *}
{$data|json:'pretty','ascii'}

{* Disable HTML-safety for API responses *}
{$data|json:'!html'}

{* Force objects + pretty print *}
{$emptyArray|json:'forceObjects','pretty'}
{* Output: {} instead of [] *}

{* Multiple options combined *}
{$data|json:'pretty','ascii','forceObjects'}
```

**Default behavior**:
- ✅ HTML-safe (escapes `<>&'"` as `\u003C`, `\u003E`, `\u0026`, `\u0027`, `\u0022`)
- ✅ Unicode-friendly (preserves UTF-8 characters like `€`, `中`)
- ✅ Compact output (no indentation)

**Security note**: The default HTML-safe encoding prevents XSS attacks when embedding JSON in HTML `<script>` tags.

### `|md5`

Generates MD5 hash of string.

**Usage**:
```latte
{$email|md5}
{* Output: 5d41402abc4b2a76b9719d911017c592 *}

{* Example: Gravatar URL *}
<img src="https://www.gravatar.com/avatar/{$email|md5}">

{* Example: Cache busting *}
<script id="cache-code">{$timestamp|md5}</script>
```

### `|sha1`

Generates SHA1 hash of string.

**Usage**:
```latte
{$password|sha1}
{* Output: 5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8 *}

{* Example: Simple checksum *}
<meta name="content-hash" content="{$content|sha1}">
```

## Migration from trejjam/utils

This package extracts the Latte filters from `trejjam/utils` and provides them as a standalone Latte 3 extension.

### If you're using trejjam/utils

You have two options:

**Option A**: Keep using `trejjam/utils` (once it's updated to v4.0)
- The filters will continue to work via the DI extension

**Option B**: Migrate to `trejjam/latte` (this package)
1. Install: `composer require trejjam/latte`
2. Remove `trejjam.utils.latte` from extensions in `config.neon`
3. Add `Trejjam\Latte\TrejjamLatteExtension` to `latte.extensions` in `config.neon`

### For latte-lint

Update your `bin/latte-lint` script:

```php
#!/usr/bin/env php
<?php
declare(strict_types=1);

$rootDir = __DIR__ . '/../';
require $rootDir . 'vendor/autoload.php';

$linter = new Latte\Tools\Linter(debug: false, strict: true);
$latte = $linter->getEngine();

$latte->setStrictParsing();
$latte->addExtension(new Trejjam\Latte\TrejjamLatteExtension());  // ← Add this

$ok = $linter->scanDirectory($argv[1] ?? '.');
exit($ok ? 0 : 1);
```

## Testing

This package includes comprehensive tests using **nette/tester** with snapshot verification (similar to Verify in C# or Jest snapshots).

### Running Tests

```bash
make test        # Run all tests (37 test cases)
make latte-lint  # Lint template fixtures
make all         # Run all checks (ECS + PHPStan + latte-lint + tests)
```

### Test Coverage

- **Unit tests** (15 test cases): Direct filter testing
- **Integration tests** (22 test cases):
  - Real Latte Engine rendering tests
  - Latte linter validation tests
  - Snapshot verification for rendered output

### Snapshot Testing

The integration tests use snapshot verification to ensure rendered output matches expectations. Snapshots are stored in `tests/Integration/__snapshots__/`.

**Updating snapshots** (when filter behavior changes intentionally):
```bash
UPDATE_SNAPSHOTS=1 make test
```

**SnapshotVerifier API** (similar to C# Verify):
```php
$snapshots = new SnapshotVerifier(__FILE__);

// Verify text output
$snapshots->verify($actualOutput, 'test-name');

// Verify JSON output  
$snapshots->verifyJson($actualData, 'test-name');

// Verify HTML output
$snapshots->verifyHtml($actualHtml, 'test-name');
```

## Development

This package is extracted from `trejjam/utils` to provide standalone Latte 3 filter support.

### Original Implementation

The filters were originally implemented in `trejjam/utils` as:
- `Trejjam\Utils\Latte\Filter\Json`
- `Trejjam\Utils\Latte\Filter\Md5`
- `Trejjam\Utils\Latte\Filter\Sha1`

And registered via a Nette DI extension using the deprecated Latte 2.x `addFilter()` method.

### Latte 3 Migration

This package implements the same filters using the Latte 3 Extension API (`getFilters()`), making them:
- Compatible with latte-lint
- Independent of Nette DI
- Following Latte 3 best practices

## License

MIT License. See [LICENSE](LICENSE) for details.

## Author

**Jan Trejbal**

## Links

- [Latte Documentation](https://latte.nette.org/)
- [Latte Extensions Guide](https://latte.nette.org/en/creating-extension)
- [Original trejjam/utils](https://github.com/trejjam/Utils)
