<?php

namespace App\Utils\ScrambleExtensions;

use App\Http\Resources\ApiResource;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Types\Type as TypesType;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\Union;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Enumerable;

/**
 * In the MPM legacy codebase there are a lot of `ApiResource::make(...)` calls which
 * shortcut the use of a proper Resource definition. While this is generally
 * discouraged for newly added code, we need to document existing functionality.
 *
 * This extension aims to document those `ApiResource::make(...)` calls that Scramble
 * otherwise collapses into a single empty `ApiResource` component. `new ApiResource(...)`
 * is covered too — Scramble resolves `make()` to the constructor, so both are identical.
 *
 * The wrapped value is read from the resource's first template type (`TResource`) and
 * documented to match the *runtime* output of `ApiResource::make($value)`:
 *
 *   - literal keyed array — `make(['token' => $tx->token])` → `{data: {...}}`;
 *   - single model        — `new ApiResource($model)`       → `{data: Model}`;
 *   - model `Collection`   — `make($query->get())`         → `{data: [Model]}`;
 *   - `LengthAwarePaginator` — `make($query->paginate())`  → the bare paginator
 *     `{current_page, data: [Model], links, total, ...}`.
 *
 * The paginator case is why we hand the paginator type to Scramble's built-in schema
 * rather than flattening to an array: at runtime the paginator is serialized
 * *unwrapped* (it already exposes its own `data` key), and ResourceResponse's wrapper
 * skips re-wrapping when a `data` key is already present — so the documented shape
 * matches exactly, pagination metadata included.
 *
 * When the wrapped type is a union, the most descriptive branch wins: a literal keyed
 * array first (e.g. a `['x' => ...]` literal unioned with an untyped helper return),
 * then a paginator (e.g. `Collection|LengthAwarePaginator`, a common "paginate when
 * ?per_page is set" return) because it is the richer, metadata-carrying shape.
 * Anything else (single models, untyped/non-model collections) falls through to
 * Scramble's defaults.
 */
class ApiResourceTypeToSchema extends TypeToSchemaExtension {
    public function shouldHandle(Type $type): bool {
        return $type instanceof Generic
            && $type->name === ApiResource::class
            && $this->wrappedSchemaType($type->templateTypes[0] ?? null) instanceof Type;
    }

    /**
     * @param Generic $type
     */
    public function toSchema(Type $type): TypesType {
        return $this->openApiTransformer->transform(
            $this->wrappedSchemaType($type->templateTypes[0] ?? null),
        );
    }

    private function wrappedSchemaType(?Type $wrapped): ?Type {
        $branches = $wrapped instanceof Union ? $wrapped->types : [$wrapped];

        foreach ($branches as $branch) {
            if ($branch instanceof KeyedArrayType) {
                return $branch;
            }
        }

        foreach ($branches as $branch) {
            if ($branch instanceof Generic
                && $branch->isInstanceOf(LengthAwarePaginator::class)
                && $this->itemModel($branch) instanceof ObjectType) {
                return $branch;
            }
        }

        foreach ($branches as $branch) {
            if ($branch instanceof Generic
                && $this->isCollectionLike($branch)
                && ($itemModel = $this->itemModel($branch)) instanceof ObjectType) {
                return new ArrayType(value: $itemModel);
            }
        }

        foreach ($branches as $branch) {
            if ($branch instanceof ObjectType && $branch->isInstanceOf(Model::class)) {
                return $branch;
            }
        }

        return null;
    }

    private function itemModel(Generic $type): ?ObjectType {
        // The collected item is the second template argument for inferred collections
        // (`Collection<int, Model>`) and the first for manually constructed ones —
        // mirrors Scramble's own paginator handling.
        $itemType = $type->templateTypes[1] ?? $type->templateTypes[0] ?? null;

        return $itemType instanceof ObjectType ? $itemType : null;
    }

    private function isCollectionLike(Generic $type): bool {
        return $type->isInstanceOf(Enumerable::class)
            || $type->isInstanceOf(PaginatorContract::class);
    }
}
