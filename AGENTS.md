# Agent Guidelines for trejjam/latte

## Repository Overview

**Package**: `trejjam/latte`  
**Purpose**: Latte 3 template engine extensions and utilities  
**PHP Support**: 8.2, 8.3, 8.4, 8.5  
**License**: MIT  
**Namespace**: `Trejjam\Latte`

## Build, Lint & Test Commands

### Core Commands
```bash
make install    # Install Composer dependencies
make all        # Run all checks (ECS + PHPStan)
make cs         # Check code style with ECS
make ecsFix     # Auto-fix code style issues
make phpstan    # Run static analysis (level max)
```

### Running Individual Tools
```bash
# Code Style (Easy Coding Standard)
vendor/bin/ecs check --config=ecs.php src
vendor/bin/ecs check --config=ecs.php src --fix

# Static Analysis (PHPStan)
vendor/bin/phpstan analyse -c phpstan.neon

# Validate Composer
composer validate
```

### Container Execution
When running in the LSF container:
```bash
podman exec -it systemd-lsf-php bash -c "cd /var/www/vendor/trejjam/latte && make all"
```

**Container Path**: `/var/www/vendor/trejjam/latte`  
**Local Path**: `/opt/php/lsf/Application/vendor/trejjam/latte`

## Code Style Guidelines

### Indentation & Formatting
- **Indentation**: TABS (not spaces)
- **Line endings**: Unix (LF)
- **PSR-12 compliant** with custom modifications

### Braces & Control Structures
```php
// Opening brace on next line for functions
public function doSomething() : ReturnType
{
    // body
}

// Control structures: else/catch on next line
if ($condition) {
    // body
}
else {
    // body
}

try {
    // body
}
catch (Exception $e) {
    // body
}
```

### Return Type Declarations
- **CRITICAL**: Space BEFORE colon in return types
```php
// ✅ CORRECT
public function foo() : string

// ❌ WRONG
public function foo(): string
```

### Closures & Arrow Functions
- Space after `fn` keyword
```php
// ✅ CORRECT
fn (string $input) : string => md5($input)

// ❌ WRONG
fn(string $input): string => md5($input)
```

### PHP Features
- **Strict types**: ALWAYS use `declare(strict_types=1);`
- **Type declarations**: Use typed properties and parameters
- **Readonly**: Use `readonly` when appropriate
- **Final classes**: Prefer `final` for classes not meant to be extended
- **Union types**: Use when needed (`int|string`)
- **Mixed type**: Use `mixed` when type is truly variable

### Imports
```php
<?php

declare(strict_types=1);

namespace Trejjam\Latte;

use Latte\Extension;
use Nette\Utils\Json as NetteJson;  // Use alias for clarity

// Full namespace imports, auto-import with 'use' statements
// NO inline \Fully\Qualified\Names in code
```

### Naming Conventions
- **Classes**: PascalCase (`TrejjamLatteExtension`)
- **Methods**: camelCase (`getFilters`, `jsonFilter`)
- **Properties**: camelCase (`$rootDir`, `$options`)
- **Constants**: SCREAMING_SNAKE_CASE (if any)
- **Private methods**: camelCase with descriptive names

### PHPDoc Comments
```php
/**
 * Short description
 *
 * Longer description if needed.
 * Can span multiple lines.
 *
 * @param mixed $input Value to encode
 * @param int|string $options JSON options
 * @return string JSON encoded string
 * @throws \Nette\Utils\JsonException
 */
private function jsonFilter(mixed $input, int|string $options = 0) : string
```

### Error Handling
- **Use exceptions**: Throw exceptions for error conditions
- **Type-safe**: Let PHPStan catch type errors at max level
- **Document exceptions**: Use `@throws` in PHPDoc
- **Don't suppress errors**: No `@` operator unless absolutely necessary

### Class Structure
**Method order** (enforced by ECS):
1. Public methods
2. Protected methods
3. Private methods

Within each visibility group:
1. Constants
2. Properties
3. Constructor
4. Methods (grouped logically)

## PHPStan Configuration

**Level**: max  
**Settings**:
- `treatPhpDocTypesAsCertain: false` - Strict type checking
- Paths: `src/`

**Common Issues to Avoid**:
```php
// ❌ WRONG: PHPStan can't infer type
$options = constant($class . '::' . $name);

// ✅ CORRECT: Type-safe with fallback
$constantValue = constant($class . '::' . $name);
$options = is_int($constantValue) ? $constantValue : 0;
```

## Git Workflow

**Default Branch**: `main`  
**Protected**: Changes should go through PRs (owner can bypass)

### Commit Messages
```
Short summary (50 chars or less)

- Bullet points for details
- What changed and why
- Reference issues if applicable
```

## Testing (Future)

Currently no automated tests. When adding tests:
```bash
# Placeholder for future test commands
vendor/bin/tester tests/
vendor/bin/phpunit
```

## CI/CD

**GitHub Actions**: `.github/workflows/ci.yaml`
- Tests on PHP 8.2, 8.3, 8.4, 8.5
- Code style checks (ECS)
- Static analysis (PHPStan)

**Dependabot**: Automatic dependency updates
- Composer packages: daily
- GitHub Actions: daily
- Auto-assigns to `@trejjam` via CODEOWNERS

## Important Notes

1. **No workflow scope**: OAuth tokens can't modify `.github/workflows/*` files. Update manually or via GitHub UI.
2. **Tab indentation**: Always use tabs, not spaces (enforced by ECS).
3. **Return type spacing**: Space before colon is critical for this codebase style.
4. **PHPStan max level**: All code must pass level max with no errors.
5. **Final classes**: Classes should be `final` unless designed for extension.
6. **Latte 3 only**: This package targets Latte 3.x, not compatible with Latte 2.x.

## References

- **Repository**: https://github.com/trejjam/latte
- **Latte 3 Docs**: https://latte.nette.org/en/
- **PSR-12**: https://www.php-fig.org/psr/psr-12/
- **PHPStan**: https://phpstan.org/
