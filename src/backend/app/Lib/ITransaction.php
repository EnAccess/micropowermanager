<?php

namespace App\Lib;

interface ITransaction {
    public function getAmount();

    public function getSender();

    public function getProvider();
}
