<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\ExternalResponsible;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class ClearDemoData extends Command
{
    protected $signature = 'auditorio:limpiar-demo';

    protected $description = 'Borra los eventos de demostración sembrados con DemoSeeder';

    /**
     * Solo toca lo que lleva la marca del seeder de demostración: los eventos
     * capturados a mano se quedan.
     */
    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Este comando no corre en producción.');

            return self::FAILURE;
        }

        $responsibles = ExternalResponsible::query()
            ->where('name', 'like', '%'.DemoSeeder::RESPONSIBLE_SUFFIX)
            ->get();

        if ($responsibles->isEmpty()) {
            $this->comment('No hay datos de demostración que borrar.');

            return self::SUCCESS;
        }

        $events = Event::query()
            ->whereIn('external_responsible_id', $responsibles->pluck('id'))
            ->get();

        if (! $this->confirm(sprintf(
            '¿Borrar %d evento%s de demostración y %d responsable%s?',
            $events->count(),
            $events->count() === 1 ? '' : 's',
            $responsibles->count(),
            $responsibles->count() === 1 ? '' : 's',
        ), default: true)) {
            $this->comment('Cancelado.');

            return self::SUCCESS;
        }

        // Uno por uno para que las cascadas de los pivotes se apliquen.
        $events->each(fn (Event $event) => $event->delete());
        $responsibles->each(fn (ExternalResponsible $responsible) => $responsible->delete());

        $this->info(sprintf('%d eventos borrados. Quedan %d.', $events->count(), Event::count()));
        $this->comment('Las personas de TI y los catálogos no se tocaron; revísalos si quieres dejarlos limpios.');

        return self::SUCCESS;
    }
}
