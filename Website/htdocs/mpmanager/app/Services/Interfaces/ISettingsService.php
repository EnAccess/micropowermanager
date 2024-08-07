<?php

namespace App\Services\Interfaces;

interface ISettingsService
{
    public function get();

    public function update($model, $data);
}
