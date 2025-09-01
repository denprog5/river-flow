<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->exclude(['vendor', 'build', 'coverage']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],
        'phpdoc_order' => true,
        'phpdoc_align' => ['align' => 'left'],
        'blank_line_before_statement' => ['statements' => ['return']],
        'no_trailing_whitespace' => true,
        'single_quote' => true,
        'binary_operator_spaces' => ['default' => 'align_single_space_minimal'],
        'native_function_invocation' => ['include' => ['@compiler_optimized']]
    ])
    ->setFinder($finder);
