<?php

use Symfony\Config\DoctrineConfig;

return static function (DoctrineConfig $doctrine): void {
    $dbalDefault = $doctrine->dbal()
        ->connection('default');
    $dbalDefault->mappingType('enum', 'string');
};
