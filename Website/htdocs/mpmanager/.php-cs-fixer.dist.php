<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        // Keeping consistency fow now.
        // Later change to:
        // '@PER-CS' => true,
        // '@PHP82Migration' => true,
    ])
    ->setFinder($finder)
;
