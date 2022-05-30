<?php

namespace App\Services;

interface ISettingsService
{
    public function get();

    public function update($model, $data);

}