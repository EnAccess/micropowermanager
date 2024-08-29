<?php

namespace App\Services\Interfaces;

interface ISolarService
{
    public function create();

    public function list();

    public function lisByMiniGrid(int $miniGridId);

    public function showByMiniGrid(int $miniGridId);
}
