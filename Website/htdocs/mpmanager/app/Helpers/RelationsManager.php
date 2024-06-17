<?php

namespace App\Helpers;

use App\Relations\BelongsToMorph;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

trait RelationsManager
{
    protected static $relationsList = [];

    protected static $relationsInitialized = false;

    protected static $relationClasses = [
        'hasone' => HasOne::class,
        'hasmany' => HasMany::class,
        'belongsto' => BelongsTo::class,
        'belongstomany' => BelongsToMany::class,
        'belongstomorph' => BelongsToMorph::class,
        'morphone' => MorphOne::class,
        'morphto' => MorphTo::class,
        'morphmany' => MorphMany::class,
    ];

    public static function getAllRelations($type = null): array
    {
        if (!self::$relationsInitialized) {
            self::initAllRelations();
        }
        if ($type) {
            return self::$relationsList[strtolower($type)];
        } else {
            return self::$relationsList;
        }
    }

    protected static function initAllRelations(): void
    {
        self::$relationsInitialized = true;

        $reflect = new \ReflectionClass(static::class);

        foreach ($reflect->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->hasReturnType()) {
                foreach (self::$relationClasses as $key => $relationClass) {
                    if ((string) $method->getReturnType() === $relationClass) {
                        self::$relationsList[$key][] = $method->getName();
                    }
                }
            }
        }
    }

    public static function withAll($type = null): array
    {
        $relations = array_flatten(static::getAllRelations($type));

        return $relations;
    }
}
