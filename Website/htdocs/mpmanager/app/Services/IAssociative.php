<?php

namespace App\Services;

interface IAssociative
{
    public function make($data);

    public function save($model);
}
