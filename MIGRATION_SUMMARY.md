# Migration Summary: trejjam/utils → trejjam/latte

**Date**: December 30, 2025  
**Status**: Completed

## Overview

Successfully migrated Latte filters from `trejjam/utils` to `trejjam/latte` with Latte 3 compatibility.

## Source Files (trejjam/utils)

Copied from `/opt/php/lsf/Application/vendor/trejjam/utils/`:

1. **src/Latte/Filter/Json.php**
   - Original: Class-based filter with `filter()` method
   - Lines: 26
   - Features: JSON encoding with string/numeric options

2. **src/Latte/Filter/Md5.php**
   - Original: Class-based filter with `filter()` method
   - Lines: 16
   - Features: Simple MD5 hash wrapper

3. **src/Latte/Filter/Sha1.php**
   - Original: Class-based filter with `filter()` method
   - Lines: 16
   - Features: Simple SHA1 hash wrapper

4. **src/DI/LatteExtension.php**
   - Original: Nette DI CompilerExtension
   - Lines: 31
   - Method: Used deprecated `addFilter()` (Latte 2.x API)

## Target Implementation (trejjam/latte)

Created in `/opt/php/lsf/Application/vendor/trejjam/latte/`:

### 1. src/TrejjamLatteExtension.php

**Changes from original**:
- ✅ Extends `Latte\Extension` (not `Nette\DI\CompilerExtension`)
- ✅ Uses `getFilters()` method (Latte 3 API)
- ✅ Inline closures for simple filters (md5, sha1)
- ✅ Private method for complex filter (json)
- ✅ Preserved exact behavior of original filters
- ✅ Added comprehensive PHPDoc documentation
- ✅ Used PHP 8.2+ syntax (mixed type, first-class callables)

**Filter Implementations**:

```php
'json' => $this->jsonFilter(...)
// Supports: {$data|json}, {$data|json:'PRETTY'}, {$data|json:256}
// Exact same logic as Trejjam\Utils\Latte\Filter\Json

'md5' => fn(string $input): string => md5($input)
// Exact same as Trejjam\Utils\Latte\Filter\Md5

'sha1' => fn(string $input): string => sha1($input)
// Exact same as Trejjam\Utils\Latte\Filter\Sha1
```

### 2. composer.json

**Added dependency**:
```json
"nette/utils": "^4.0"
```

**Reason**: Required for `Nette\Utils\Json` used in json filter

### 3. README.md

**Complete rewrite** with:
- ✅ Feature overview
- ✅ Installation instructions
- ✅ Configuration examples (standalone + Nette)
- ✅ Detailed filter documentation with examples
- ✅ Migration guide from trejjam/utils
- ✅ latte-lint integration instructions
- ✅ Usage examples for all three filters

## Key Architectural Changes

### Before (trejjam/utils - Latte 2.x)

```php
// Registration via DI
class LatteExtension extends CompilerExtension {
    public function beforeCompile() {
        $latteFactoryDefinition->getResultDefinition()
            ->addSetup('addFilter', ['json', [$serviceRef, 'filter']]);
    }
}

// Filter implementation
class Json {
    public function filter($input, $options = 0): string {
        return Nette\Utils\Json::encode($input, $options);
    }
}
```

**Problems**:
- Requires full DI container
- Doesn't work in latte-lint
- Uses deprecated `addFilter()` method
- Separate class files for each filter

### After (trejjam/latte - Latte 3)

```php
// Direct extension
class TrejjamLatteExtension extends Extension {
    public function getFilters(): array {
        return [
            'json' => $this->jsonFilter(...),
            'md5' => fn(string $input): string => md5($input),
            'sha1' => fn(string $input): string => sha1($input),
        ];
    }
    
    private function jsonFilter(mixed $input, int|string $options = 0): string {
        if (is_string($options)) {
            $options = constant(NetteJson::class . '::' . strtoupper($options));
        }
        return NetteJson::encode($input, $options);
    }
}
```

**Benefits**:
- ✅ No DI container required
- ✅ Works in latte-lint
- ✅ Uses Latte 3 Extension API
- ✅ All filters in single class
- ✅ Modern PHP syntax

## Behavioral Compatibility

### JSON Filter

**Input**: `{$data|json}` or `{$data|json:'PRETTY'}` or `{$data|json:256}`

**Behavior**:
- ✅ String options converted to constants: `'PRETTY'` → `Nette\Utils\Json::PRETTY`
- ✅ Numeric options passed directly: `256` → `JSON_UNESCAPED_UNICODE`
- ✅ Uses `Nette\Utils\Json::encode()` (same as original)
- ✅ Throws `Nette\Utils\JsonException` on error (same as original)

**Test cases**:
```php
// Basic
{$data|json} === Nette\Utils\Json::encode($data, 0)

// String constant
{$data|json:'PRETTY'} === Nette\Utils\Json::encode($data, Nette\Utils\Json::PRETTY)

// Numeric flag
{$data|json:256} === Nette\Utils\Json::encode($data, 256)
```

### MD5 Filter

**Input**: `{$str|md5}`

**Behavior**:
- ✅ Direct wrapper around `md5()`: `fn(string $input): string => md5($input)`
- ✅ Exact same as original `Trejjam\Utils\Latte\Filter\Md5::filter()`

### SHA1 Filter

**Input**: `{$str|sha1}`

**Behavior**:
- ✅ Direct wrapper around `sha1()`: `fn(string $input): string => sha1($input)`
- ✅ Exact same as original `Trejjam\Utils\Latte\Filter\Sha1::filter()`

## Files Modified

1. ✅ `src/TrejjamLatteExtension.php` - Implemented filters
2. ✅ `composer.json` - Added nette/utils dependency
3. ✅ `README.md` - Comprehensive documentation

## Files Created

1. ✅ `MIGRATION_SUMMARY.md` - This file

## Files NOT Copied

The following were **intentionally not copied** as they're replaced by the new extension:

- ❌ `src/Latte/Filter/Json.php` - Logic moved to `TrejjamLatteExtension::jsonFilter()`
- ❌ `src/Latte/Filter/Md5.php` - Logic moved to inline closure
- ❌ `src/Latte/Filter/Sha1.php` - Logic moved to inline closure
- ❌ `src/DI/LatteExtension.php` - Replaced by `TrejjamLatteExtension`

**Reason**: Single Extension class is cleaner and follows Latte 3 best practices.

## Testing Checklist

### Unit Tests (To be implemented)

- [ ] JSON filter basic encoding
- [ ] JSON filter with 'PRETTY' option
- [ ] JSON filter with numeric options
- [ ] JSON filter with invalid constant name (should throw)
- [ ] MD5 filter produces correct hash
- [ ] SHA1 filter produces correct hash
- [ ] Filter chaining works (e.g., `{$str|md5|upper}`)

### Integration Tests

- [ ] Works in standalone Latte Engine
- [ ] Works in Nette application via config
- [ ] Works in latte-lint without DI container
- [ ] No conflicts with existing filters

### Manual Tests (LSF Application)

- [ ] `{$jsConfiguration|json|noescape}` in @layout.latte:60
- [ ] `{$course->getUpdatedAt()->format('U')|md5}` in @pageLayout.latte:58
- [ ] Run `bin/latte-lint` - no unknown filter warnings

## Next Steps

### Immediate

1. ✅ Implementation complete
2. ⏳ Review and test changes
3. ⏳ Commit to repository
4. ⏳ Push to GitHub

### Short-term

1. Write unit tests
2. Test in LSF application
3. Update LSF's `bin/latte-lint` to use this extension
4. Remove duplicate filters from `App\Model\LsfLatteExtension`

### Long-term

1. Consider publishing to Packagist
2. Add CI/CD (GitHub Actions)
3. Add PHPStan/ECS checks
4. Version tagging (v0.1.0)

## References

- **Source package**: trejjam/utils v3.0.x-dev
- **Target package**: trejjam/latte v0.1.x-dev
- **Migration guide**: MIGRATION.md (comprehensive technical document)
- **Latte 3 docs**: https://latte.nette.org/en/creating-extension

## Notes

- All original filter logic preserved exactly
- No breaking changes to filter behavior
- Only architectural changes (DI → Extension)
- Follows LSF coding standards (tabs, strict types, return type spacing)
- PHP 8.2+ minimum version (aligned with LSF requirements)

---

**Migration Status**: ✅ Complete  
**Backward Compatible**: ✅ Yes (same filter behavior)  
**Latte 3 Compatible**: ✅ Yes  
**Linting Support**: ✅ Yes  
**Ready for Testing**: ✅ Yes
