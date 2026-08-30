<?php

namespace App\Filament\Resources\Events\Tables;

use App\Models\Event;
use App\Support\Duration;
use Carbon\CarbonImmutable;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'eventType',
                'requestingArea',
                'externalResponsible',
                'itPeople',
            ]))
            ->columns([
                TextColumn::make('started_at')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (Event $record): string => $record->started_at->translatedFormat('l')),

                TextColumn::make('title')
                    ->label('Evento')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (Event $record): string => $record->eventType->name),

                TextColumn::make('requestingArea.name')
                    ->label('Área')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('externalResponsible.name')
                    ->label('Responsable')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('itPeople.name')
                    ->label('Personas de TI')
                    ->badge()
                    ->color('gray')
                    ->listWithLineBreaks()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('event_window')
                    ->label('Horario')
                    ->state(fn (Event $record): string => sprintf(
                        '%s – %s',
                        $record->started_at->format('H:i'),
                        $record->ended_at->format('H:i'),
                    ))
                    ->description(fn (Event $record): string => Duration::humanize($record->it_duration_minutes)),

                TextColumn::make('it_outside_hours_minutes')
                    ->label('Fuera de horario')
                    ->badge()
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => $state === 0 ? '—' : Duration::humanize($state))
                    ->color(fn (int $state): string => $state === 0 ? 'gray' : 'warning'),

                TextColumn::make('on_non_working_day')
                    ->label('Día')
                    ->badge()
                    ->state(fn (Event $record): string => $record->on_non_working_day ? 'No laboral' : 'Laboral')
                    ->color(fn (Event $record): string => $record->on_non_working_day ? 'danger' : 'gray')
                    ->toggleable(),

                TextColumn::make('incidents')
                    ->label('Incidencias')
                    ->limit(40)
                    ->placeholder('Sin incidencias')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                Filter::make('rango')
                    ->schema([
                        DatePicker::make('desde')->label('Desde')->native(false)->displayFormat('d/m/Y'),
                        DatePicker::make('hasta')->label('Hasta')->native(false)->displayFormat('d/m/Y'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, string $desde): Builder => $q
                            ->where('started_at', '>=', CarbonImmutable::parse($desde)->startOfDay()))
                        ->when($data['hasta'] ?? null, fn (Builder $q, string $hasta): Builder => $q
                            ->where('started_at', '<=', CarbonImmutable::parse($hasta)->endOfDay())))
                    ->indicateUsing(function (array $data): ?string {
                        $desde = $data['desde'] ?? null;
                        $hasta = $data['hasta'] ?? null;

                        return match (true) {
                            $desde && $hasta => sprintf(
                                'Del %s al %s',
                                CarbonImmutable::parse($desde)->format('d/m/Y'),
                                CarbonImmutable::parse($hasta)->format('d/m/Y'),
                            ),
                            (bool) $desde => 'Desde el '.CarbonImmutable::parse($desde)->format('d/m/Y'),
                            (bool) $hasta => 'Hasta el '.CarbonImmutable::parse($hasta)->format('d/m/Y'),
                            default => null,
                        };
                    }),

                SelectFilter::make('event_type_id')
                    ->label('Tipo de evento')
                    ->relationship('eventType', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('requesting_area_id')
                    ->label('Área solicitante')
                    ->relationship('requestingArea', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('external_responsible_id')
                    ->label('Responsable del evento')
                    ->relationship('externalResponsible', 'name')
                    ->searchable(),

                SelectFilter::make('itPeople')
                    ->label('Persona de TI')
                    ->relationship('itPeople', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('exceeds_working_hours')
                    ->label('Sobrepasa el horario')
                    ->placeholder('Todos')
                    ->trueLabel('Solo los que sobrepasan')
                    ->falseLabel('Solo los que no'),

                TernaryFilter::make('on_non_working_day')
                    ->label('Día no laboral')
                    ->placeholder('Todos')
                    ->trueLabel('Solo días no laborales')
                    ->falseLabel('Solo días laborales'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Todavía no hay eventos registrados')
            ->emptyStateDescription('Registra el primero para empezar a acumular datos del auditorio.');
    }
}
