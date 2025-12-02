<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait to sort models by a given field.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
trait SortModelQuery {
    /**
     * Sort the model by a given field.
     *
     * @param Builder<TModel> $query
     *
     * @return Builder<TModel>
     */
    protected function scopeSort(Builder $query, ?string $sortBy = null, string $sortDirection = 'asc'): Builder {
        $sortBy ??= $this->defaultSortField ?? 'created_at';
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc'])
            ? $sortDirection
            : 'asc';

        $sortableFields = $this->sortable ?? ['id', 'created_at'];

        if (in_array($sortBy, $sortableFields)) {
            return $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    /**
     * Sort the model by a given field from the request.
     *
     * @param Builder<TModel>                                 $query
     * @param callable(Builder<TModel>, string, string): bool $customSorter
     *
     * @return Builder<TModel>
     */
    protected function scopeSortFromRequest(Builder $query, ?callable $customSorter = null): Builder {
        $request = request();

        $sortBy = $request->input('sort_by') ?? $request->input('sort');
        $sortDirection = $request->input('sort_direction') ?? $request->input('direction', 'asc');

        if ($sortBy && str_starts_with($sortBy, '-')) {
            $sortBy = substr($sortBy, 1);
            $sortDirection = 'desc';
        }

        if ($customSorter && $customSorter($query, $sortBy, $sortDirection)) {
            return $query;
        }

        return $this->scopeSort($query, $sortBy, $sortDirection);
    }
}
