<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Enums\CompanyStatus;
use Carbon\Carbon;

class DeleteDisabledCompany extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-disabled-company';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companies = Company::all();
        $today = Carbon::now();
        foreach ($companies as $company) {
            if (isset($company->disabled_at)) {
                if ($company->status === CompanyStatus::INACTIVO && $company->disabled_at->copy()->addYear() <= $today) {
                    $company->delete();
                    $this->info('Empresa eliminada: ' . $company->name);
                } else {
                    $this->info('Empresa no eliminada: ' . $company->name);
                }
            } else {
                $this->info('Empresa sin fecha de deshabilitación: ' . $company->name);
            }
        }
    }
}
