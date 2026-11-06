<?php

namespace App\Plugins\StsMeterVend\Enums;

enum StsEncryptionAlgorithm: string {
    case Sta = 'STA';
    case Dea = 'DEA';
    case Misty1 = 'MISTY1';
}
