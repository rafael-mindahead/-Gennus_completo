<?php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (){
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Artisan::command('gennus:limpar-backups-produtos', function (){
    $apagados = DB::table('produto_backups_temporarios')->where('expira_em', '<=', now())->delete();
    $this->info("Snapshots expirados removidos: {$apagados}");
})->purpose('Remove snapshots temporarios expirados');
Schedule::command('gennus:limpar-backups-produtos')->everyMinute()->withoutOverLapping();
