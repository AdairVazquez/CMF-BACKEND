<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use Carbon\Carbon;
use App\Enums\CompanyStatus;

class AssingDisableDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assing-disable-date';

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
        $companiesDisabled = [];
        $companiesEnabled = [];
        $today = Carbon::today();
        $companies = Company::all();
        foreach ($companies as $company) {
            if (!isset($company->disabled_at)) {
                $this->info('Empresa no tiene fecha de deshabilitación: ' . $company->name);
                if ($company->subscription_ends_at && $company->subscription_ends_at->lte($today)) {
                    $company->status = CompanyStatus::INACTIVO;
                    $company->disabled_at = $today;
                    $company->save();
                    $companiesDisabled[] = $company->name;
                } else {
                    $companiesEnabled[] = $company->name;
                }
            } else {
                $this->info('Empresa ya tiene fecha de deshabilitación: ' . $company->name);
            }
        }
        $this->info('Empresas deshabilitadas: ' . implode(', ', $companiesDisabled));
        $this->info('Empresas habilitadas: ' . implode(', ', $companiesEnabled));
    }
}
