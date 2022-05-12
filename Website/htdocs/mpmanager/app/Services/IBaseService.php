<?php

namespace App\Services;

interface IBaseService
{
     public function getById($id);

     public function create($data);

     public function update($model, $data);

     public function delete($model);

     public function getAll($limit = null);
}