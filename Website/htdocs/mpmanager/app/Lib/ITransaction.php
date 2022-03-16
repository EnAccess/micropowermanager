<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 24.05.18
 * Time: 20:53
 */

namespace App\Lib;

interface ITransaction
{
    public function getAmount();
    public function getSender();
    public function getProvider();
}
