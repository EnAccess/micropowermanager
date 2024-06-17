<?php

// The main file that governs the PHP code style for this project is
// `Website/htdocs/mpmanager/.php-cs-fixer.dist.php`
// This is file is essentially a convienience copy of the same file for
// easier editor integration at the root of the repository.

// We disabled some disruptive rules for the editor integration that might
// intefer with a development flow.

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        // disabled for editor integration
        'no_unused_imports' => false,
    ])
    ->setFinder($finder)
;
