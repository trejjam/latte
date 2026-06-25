<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\Basic\BracesPositionFixer;
use PhpCsFixer\Fixer\ControlStructure\ControlStructureContinuationPositionFixer;
use PhpCsFixer\Fixer\FunctionNotation\ReturnTypeDeclarationFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

$rootDir = __DIR__ . '/';

return ECSConfig::configure()
	->withSets([
		$rootDir . 'vendor/symplify/easy-coding-standard/config/set/common/array.php',
		$rootDir . 'vendor/symplify/easy-coding-standard/config/set/common/control-structures.php',
		$rootDir . 'vendor/symplify/easy-coding-standard/config/set/common/docblock.php',
		$rootDir . 'vendor/symplify/easy-coding-standard/config/set/common/namespaces.php',
		$rootDir . 'vendor/symplify/easy-coding-standard/config/set/clean-code.php',
		$rootDir . 'vendor/symplify/easy-coding-standard/config/set/psr12.php',
	])
	// Equivalent of the removed "common/strict" set, which now throws a
	// DeprecatedException when imported (easy-coding-standard >= 13).
	->withRules([
		DeclareStrictTypesFixer::class,
		StrictComparisonFixer::class,
		StrictParamFixer::class,
	])
	->withSpacing(indentation: Option::INDENTATION_TAB)
	->withSkip([
		BlankLineAfterOpeningTagFixer::class => null,
		BracesFixer::class => null,
		BinaryOperatorSpacesFixer::class => null,
	])
	->withConfiguredRule(ReturnTypeDeclarationFixer::class, [
		'space_before' => 'one',
	])
	->withConfiguredRule(ControlStructureContinuationPositionFixer::class, [
		'position' => ControlStructureContinuationPositionFixer::NEXT_LINE,
	])
	->withConfiguredRule(BracesPositionFixer::class, [
		'functions_opening_brace' => BracesPositionFixer::NEXT_LINE_UNLESS_NEWLINE_AT_SIGNATURE_END,
	]);
