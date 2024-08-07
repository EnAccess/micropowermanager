<?php

namespace App\Services\Interfaces;

interface IAssociative
{
    public function make($data);

    public function save($model);
}
