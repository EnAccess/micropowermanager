<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PackageGenerator extends Command {
    protected $signature = 'micropowermanager:new-package {package-name}';
    protected $description = 'Clones package development starter pack';

    public function handle(): void {
        $packageNameArg = $this->argument('package-name');
        $packageName = strtolower($packageNameArg);
        $nameSpace = '';
        $strings = preg_split('/([-.*\/])/', $packageNameArg);
        $firstCapitals = array_map('ucfirst', $strings);
        foreach ($firstCapitals as $key => $item) {
            $nameSpace .= $item;
        }

        shell_exec(__DIR__.'/../Shell/package-starter.sh '.$packageName.' '.$nameSpace);
    }
}
