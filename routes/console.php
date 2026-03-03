<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command('fiscal:processar-recorrencias')
    ->dailyAt('08:00')
    ->timezone('America/Manaus')
    ->appendOutputTo(storage_path('logs/recorrencias.log'));
Schedule::command('fiscal:processar-recorrencias')
    ->dailyAt('12:00')
    ->timezone('America/Manaus')
    ->appendOutputTo(storage_path('logs/recorrencias.log'));
Schedule::command('fiscal:processar-recorrencias')
    ->dailyAt('16:00')
    ->timezone('America/Manaus')
    ->appendOutputTo(storage_path('logs/recorrencias.log'));
Schedule::command('fiscal:processar-recorrencias')
    ->dailyAt('23:20')
    ->timezone('America/Manaus')
    ->appendOutputTo(storage_path('logs/recorrencias.log'));
Schedule::command('fiscal:verificar-cobrancas-vencidas')
    ->dailyAt('00:01')
    ->timezone('America/Manaus')
    ->appendOutputTo(storage_path('logs/financeiro_vencimentos.log'));
