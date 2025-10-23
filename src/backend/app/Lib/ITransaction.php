<?php

namespace App\Lib;

interface ITransaction {
    public function getAmount(): float;

    public function getSender(): string;

    public function getProvider(): string;
}
