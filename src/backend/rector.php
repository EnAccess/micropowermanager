<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php85\Rector\Property\AddOverrideAttributeToOverriddenPropertiesRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use RectorLaravel\Rector\StaticCall\CarbonToDateFacadeRector;
use RectorLaravel\Set\LaravelSetList;
use RectorLaravel\Set\LaravelSetProvider;

return RectorConfig::configure()
    ->withImportNames(importShortClasses: false)
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkipPath(
        __DIR__.'/bootstrap/cache',
    )
    ->withSkip([
        __DIR__.'/app/Console/Commands/MpmModelsCommand.php',
    ])
    ->withPhpSets(
        php85: true
    )
    ->withPreparedSets(
        codeQuality: true,
        deadCode: true,
        typeDeclarations: true
    )
    ->withSetProviders(LaravelSetProvider::class)
    ->withComposerBased(laravel: true)
    ->withSets([
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        // Next rector-laravel release add
        // LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
    ])
    ->withSkip([
        AddArrowFunctionReturnTypeRector::class,
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        AddOverrideAttributeToOverriddenMethodsRector::class,
        AddOverrideAttributeToOverriddenPropertiesRector::class,
        CarbonToDateFacadeRector::class,
        NullToStrictStringFuncCallArgRector::class,
        ReadOnlyPropertyRector::class,
        // exclude temporarily as creating too much noise.
        SafeDeclareStrictTypesRector::class,
    ])
;
