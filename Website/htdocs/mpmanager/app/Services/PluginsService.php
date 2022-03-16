<?php

namespace App\Services;

use App\Models\Plugins;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PluginsService
{
    /**
     * @return Builder[]|Collection
     *
     * @psalm-return \Illuminate\Database\Eloquent\Collection|array<array-key, \Illuminate\Database\Eloquent\Builder>
     */
    public function getPlugins()
    {
        return Plugins::query()->orderBy('id')->get();
    }

    public function addPlugin($name, $composer_name, $description): void
    {
        Plugins::query()->create(
            [
            'name' => $name,
            'composer_name' => $composer_name,
            'description' => $description,
            ]
        );
    }
}
