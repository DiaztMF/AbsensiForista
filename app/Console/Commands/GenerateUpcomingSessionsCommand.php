<?php

namespace App\Console\Commands;

use App\Actions\Agenda\GenerateAgendaSessionsAction;
use App\Models\Agenda;
use Illuminate\Console\Command;

class GenerateUpcomingSessionsCommand extends Command
{
    protected $signature = 'forista:generate-sessions';

    protected $description = 'Menjamin seluruh agenda mingguan aktif memiliki jadwal sesi 4 minggu ke depan.';

    public function handle(GenerateAgendaSessionsAction $generator): int
    {
        $totalCreated = 0;

        Agenda::active()->each(function (Agenda $agenda) use ($generator, &$totalCreated) {
            $totalCreated += $generator->execute($agenda, weeksAhead: 4);
        });

        $this->info("Selesai memeriksa agenda. Sesi baru dibuat: {$totalCreated}.");

        return self::SUCCESS;
    }
}
