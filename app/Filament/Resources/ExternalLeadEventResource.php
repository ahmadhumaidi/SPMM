<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExternalLeadEventResource\Pages;
use App\Models\ExternalLeadEvent;
use App\Services\MetaLeadImportService;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ExternalLeadEventResource extends Resource
{
    protected static ?string $model = ExternalLeadEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?string $navigationLabel = 'Meta Lead Event';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessReports();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Status Event')
                ->columns(3)
                ->schema([
                    TextInput::make('provider')->disabled(),
                    TextInput::make('external_id')->disabled(),
                    TextInput::make('status')->disabled(),
                    TextInput::make('campus.name')->label('Kampus')->disabled(),
                    TextInput::make('studyProgram.name')->label('Program Studi')->disabled(),
                    TextInput::make('classTrack.name')->label('Program Perkuliahan')->disabled(),
                    TextInput::make('lead.full_name')->label('Lead')->disabled(),
                    DateTimePicker::make('received_at')->disabled(),
                    DateTimePicker::make('processed_at')->disabled(),
                    Textarea::make('error_message')->disabled()->columnSpanFull(),
                ]),
            Section::make('Payload')
                ->schema([
                    Textarea::make('normalized_payload_text')
                        ->label('Payload Ternormalisasi')
                        ->formatStateUsing(fn (?ExternalLeadEvent $record): string => json_encode($record?->normalized_payload_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(12)
                        ->columnSpanFull(),
                    Textarea::make('payload_text')
                        ->label('Payload Mentah')
                        ->formatStateUsing(fn (?ExternalLeadEvent $record): string => json_encode($record?->payload_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                        ->disabled()
                        ->dehydrated(false)
                        ->rows(12)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('provider')->badge()->sortable(),
                TextColumn::make('external_id')->label('Leadgen ID')->searchable()->copyable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('lead.full_name')->label('Lead')->searchable()->placeholder('-'),
                TextColumn::make('campus.name')->label('Kampus')->searchable()->placeholder('-'),
                TextColumn::make('error_message')->limit(45)->placeholder('-'),
                TextColumn::make('processed_at')->dateTime()->sortable()->placeholder('-'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processed' => 'Processed',
                        'duplicate' => 'Duplicate',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('provider')
                    ->options(['meta' => 'Meta']),
            ])
            ->actions([
                Tables\Actions\Action::make('reprocess')
                    ->label('Proses Ulang')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('Proses ulang lead Meta?')
                    ->modalDescription('Sistem akan mengambil ulang detail Meta dan memperbaiki mapping kampus, prodi, serta program perkuliahan pada lead yang sudah terkait.')
                    ->action(function (ExternalLeadEvent $record, MetaLeadImportService $importer): void {
                        $importer->importLead($record->external_id, true);
                    }),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['lead', 'campus', 'studyProgram', 'classTrack']);

        if (FilamentResourceScope::isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas('lead', fn (Builder $leadQuery): Builder => FilamentResourceScope::applyManagedLeadCampusScope($leadQuery));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExternalLeadEvents::route('/'),
            'view' => Pages\ViewExternalLeadEvent::route('/{record}'),
        ];
    }
}
