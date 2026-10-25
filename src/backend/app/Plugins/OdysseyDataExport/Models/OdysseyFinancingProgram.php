<?php

namespace App\Plugins\OdysseyDataExport\Models;

enum OdysseyFinancingProgram: string {
    case ReaNepPbg = 'REA_NEP_PBG';
    case ReaNepObf = 'REA_NEP_OBF';
    case BrilhoShs = 'BRILHO_SHS';
    case UefSspu = 'UEF_SSPU';
}
