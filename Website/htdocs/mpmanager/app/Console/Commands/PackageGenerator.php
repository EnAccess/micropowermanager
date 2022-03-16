<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PackageGenerator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'micropowermanager:new-package {package-name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clones package development starter pack';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $packageNameArg = $this->argument('package-name');
        $packageName = strtolower($packageNameArg);
        $nameSpace = '';
        $strings = preg_split('/([-.*\/])/', $packageNameArg);
        $firstCapitals = array_map('ucfirst', $strings);
        foreach ($firstCapitals as $key => $item) {
            $nameSpace .= $item;
        }


        shell_exec(__DIR__ . '/../Shell/package-starter.sh' . ' ' . $packageName . ' ' . $nameSpace);
    }
}
