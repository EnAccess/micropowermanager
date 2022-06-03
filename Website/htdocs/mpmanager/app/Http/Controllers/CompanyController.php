<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyDatabase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CompanyController extends Controller
{

    public function __construct(private Company $company, private CompanyDatabase $companyDatabase)
    {
    }


    public function store(Request $request)
    {

        $company = $this->company->newQuery()->create($request->only(['name','address', 'phone', 'country_id']));


        $name = str_replace(" ", "", $company->name);
        $companyDatabase = $this->companyDatabase->newQuery()->create([
            'company_id' => $company->id,
            'database_name' => $name . '_' . Carbon::now()->timestamp,
        ]);
        $sourcePath = __DIR__ . '/../../../';
        shell_exec(__DIR__ . '/../../../database_creator.sh --database='
            . $companyDatabase->database_name . ' --user=root' . ' --path='
            . $sourcePath);
        return response()->json([
            'message' => 'Company created successfully',
            'company' => $company,
            'db_name' => $companyDatabase->database_name
        ], 201);
    }

}
