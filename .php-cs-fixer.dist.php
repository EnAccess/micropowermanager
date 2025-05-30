<?php

// The main file that defines the PHP code style for this project is located at
// `src/backend/.php-cs-fixer.dist.php`

// This file is essentially a convienience copy of the same file for
// easier editor integration at the root of the repository.

// We disabled some disruptive rules for the editor integration that might
// intefer with a development flow.

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'bootstrap/cache',
    ])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        // customisation
        'no_superfluous_phpdoc_tags' => false,
        'yoda_style' => false,
        'method_chaining_indentation' => true,
        'braces_position' => [
            'control_structures_opening_brace' => 'same_line',
            'functions_opening_brace' => 'same_line',
            'anonymous_functions_opening_brace' => 'same_line',
            'classes_opening_brace' => 'same_line',
            'anonymous_classes_opening_brace' => 'same_line',
            'allow_single_line_empty_anonymous_classes' => true,
            'allow_single_line_anonymous_functions' => true,
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'single_line_empty_body' => true,
        // disabled for editor integration
        'no_unused_imports' => false,
    ])
    ->setFinder($finder)
;
