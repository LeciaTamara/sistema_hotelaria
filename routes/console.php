<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// recuperação dos dados do XML, utilizando o CRON
// Agenda a execucao automatica do arquivo DatabaseSeeder.php que importa e sincroniza os 3 arquivos XML.
Schedule::command('db:seed')->everyMinute();