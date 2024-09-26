<?php

// The is the main file that governs the PHP code style for this project.

// When updating this file, please make sure to also update the
// convienience copy for easier editor integration at the root of the repository.

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
    ])
    ->setFinder($finder)
;
