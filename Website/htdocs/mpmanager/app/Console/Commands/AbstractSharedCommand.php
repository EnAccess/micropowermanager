<?php

namespace App\Console\Commands;

use App\Models\DatabaseProxy;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

abstract class AbstractSharedCommand extends Command
{

    public function handle()
    {
        $databaseProxyManagerService = app()->make(DatabaseProxyManagerService::class);

        $databaseProxyManagerService->queryAllConnections()->chunkById(50, function(Collection $modelCollection) use($databaseProxyManagerService){
            $modelCollection->each(function (DatabaseProxy $databaseProxy)use($databaseProxyManagerService) {
                $this->info("Running ". $this->name. " for company ID : ". $databaseProxy->getCompanyId());
                $databaseProxyManagerService->runForCompany($databaseProxy->getCompanyId(), function(){

                    $this->runInCompanyScope();
                });
            });
        });
    }


    abstract function runInCompanyScope():void;

}
