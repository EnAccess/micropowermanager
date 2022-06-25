<?php

namespace App\Console\Commands;


class RoutesGenerator extends AbstractSharedCommand
{
    protected $signature = 'routes:generate';
    protected $description = 'Generates new routes from packages';

    public function runInCompanyScope(): void
    {
        $routeTmp = 'storage/skeletons/routes.tmp';
        fopen($routeTmp, 'w');

        $exportedRoutes = 'resources/assets/js/ExportedRoutes.js';
        file_put_contents($routeTmp, file($exportedRoutes));
        $this->removeLine($routeTmp, 'last');

        $directories = glob('resources/assets/js/plugins' . '/*', GLOB_ONLYDIR);
        if (count($directories) > 0) {
            foreach ($directories as $key => $value) {
                $this->createPluginRoutesTmp($value, $routeTmp);
            }
            $tmpFile = fopen($routeTmp, 'a+');
            fwrite($tmpFile, ']');
            fclose($tmpFile);
            file_put_contents($exportedRoutes, file($routeTmp));
        }
    }

    private function createPluginRoutesTmp(string $src, string $coreRoutesTmp): void
    {
        $packageRoutes = $src . "/js/routes.js";
        $packageRoutesTmp = $src . "/js/routes.tmp";
        fopen($packageRoutesTmp, 'w');
        file_put_contents($packageRoutesTmp, file($packageRoutes));
        $this->removeLine($packageRoutesTmp, 'first');
        $this->removeLine($packageRoutesTmp, 'last');
        $this->appendLines($packageRoutesTmp, $coreRoutesTmp);
    }

    /**
     * @return void
     */
    private function removeLine(string $packageRoutesTmp, string $type)
    {
        $lines = file($packageRoutesTmp);
        if ($type === 'first') {
            array_shift($lines);
        } elseif ($type === 'last') {
            array_pop($lines);
        } else {
            return;
        }
        $file = join('', $lines);
        file_put_contents($packageRoutesTmp, $file);
    }

    private function appendLines(string $packageRoutesTmp, $coreRoutesTmp): void
    {

        $lines = file($packageRoutesTmp);
        $tmp = fopen($coreRoutesTmp, 'a+');
        $counter = 1;
        foreach ($lines as $key => $value) {
            if ($counter % 5 === 0 || $counter % 5 == 1) {
                $newLine = str_pad($value, strlen($value) + 2, ' ', STR_PAD_LEFT);
            } else {
                $newLine = str_pad($value, strlen($value) + 4, ' ', STR_PAD_LEFT);
            }
            fwrite($tmp, $newLine);
            $counter++;
        }
        fclose($tmp);
    }
}
