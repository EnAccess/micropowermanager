<?php

namespace App\Lib;

interface ITransaction {
    public function getAmount(): int;

    public function getSender(): string;

    public function getProvider(): string;
}
