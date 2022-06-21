<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

/**
 * The goal is to have the database connection on each incomming http request.
 * This will save querying in each model the correct database connection string
 */
class UserDefaultDatabaseConnectionMiddleware{


    public function __construct(private DatabaseProxyManagerService $databaseProxyManager)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        //adding new company should not be proxied. It should use the base database to create the record
        if($request->path() === 'api/company' && $request->isMethod('post')) {
            return $next($request);
        }

        //webclient login
        if($request->path() === 'api/auth/login') {
          $databaseName = $this->databaseProxyManager->findByEmail($request->input('email'));
        }
        //agent app login
        elseif($this->isAgentApp($request->path()) &&  Str::contains($request->path(),'login') ){
            $databaseName = $this->databaseProxyManager->findByEmail($request->input('email'));
        }
        //agent app authenticated user requests
        elseif($this->isAgentApp($request->path())){
            $companyId = auth('agent_api')->payload()->get('companyId');
            if(!is_numeric($companyId)) {
                throw new \Exception("JWT is not provided");
            }
            $databaseName = $this->databaseProxyManager->findCompanyId($companyId);
        }
        //web client authenticated user requests
        else {

            $companyId = auth('api')->payload()->get('companyId');
            if(!is_numeric($companyId)) {
                throw new \Exception("JWT is not provided");
            }
            $databaseName = $this->databaseProxyManager->findCompanyId($companyId);
        }

        $this->buildDatabaseConnection($databaseName);

        return $next($request);
    }

    private function buildDatabaseConnection(string $databaseName): void
    {
        $databaseConnections = config()->get('database.connections');
        $databaseConnections['shard'] = [
            'driver' => 'mysql',
            'host' => 'db',
            'port' => '3306',
            'database' => $databaseName,
            'username' => 'root',
            'password' => 'inensus2022.',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ];
        config()->set('database.connections', $databaseConnections);

    }

    private function isAgentApp(string $path){
        return Str::startsWith($path,'api/app/');
    }
}
