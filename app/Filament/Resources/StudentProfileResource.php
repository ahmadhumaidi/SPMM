<?php

namespace App\Filament\Resources;

use App\Enums\EnrollmentStatus;
use App\Filament\Resources\StudentProfileResource\Pages;
use App\Models\StudentProfile;
use App\Services\AuditLogger;
use App\Support\FilamentResourceScope;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentProfileResource extends Resource
{
    protected static ?string $model = StudentProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'PDDIKTI';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessPddikti();
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereHas('lead', fn ($query) => $query->where('enrollment_status', EnrollmentStatus::MenungguPemutakhiran))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('lead.full_name')->label('Nama')->disabled(),
            TextInput::make('nik')->required()->maxLength(32),
            TextInput::make('nisn')->maxLength(32),
            TextInput::make('birth_place')->required()->maxLength(255),
            DatePicker::make('birth_date')->required(),
            TextInput::make('gender')->required()->maxLength(16),
            TextInput::make('religion')->maxLength(64),
            TextInput::make('mother_name')->required()->maxLength(255),
            TextInput::make('father_name')->maxLength(255),
            TextInput::make('province')->required()->maxLength(255),
            TextInput::make('city')->required()->maxLength(255),
            TextInput::make('district')->required()->maxLength(255),
            TextInput::make('village')->required()->maxLength(255),
            TextInput::make('postal_code')->maxLength(16),
            TextInput::make('school_npsn')->maxLength(32),
            TextInput::make('citizenship')->required()->maxLength(64),
            TextInput::make('phone')->required()->maxLength(32),
            TextInput::make('email')->email()->maxLength(255),
            DateTimePicker::make('completed_at')->disabled(),
            DateTimePicker::make('verified_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('lead.full_name')->searchable()->sortable(),
                TextColumn::make('nik')->searchable(),
                TextColumn::make('nisn')->searchable(),
                TextColumn::make('lead.campus.name')->sortable(),
                TextColumn::make('lead.enrollment_status')->badge(),
                TextColumn::make('completed_at')->dateTime()->sortable(),
                TextColumn::make('verified_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('mark_processing')
                    ->label('Proses Pemutakhiran')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (StudentProfile $record): bool => $record->lead->enrollment_status === EnrollmentStatus::MenungguPemutakhiran)
                    ->action(function (StudentProfile $record, AuditLogger $audit): void {
                        $record->lead->update(['enrollment_status' => EnrollmentStatus::ProsesPemutakhiran]);
                        $record->update(['verified_at' => now()]);
                        $audit->record('enrollment_status_changed', $record->lead, ['to' => EnrollmentStatus::ProsesPemutakhiran->value]);

                        Notification::make()->title('Status masuk proses pemutakhiran')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentProfiles::route('/'),
            'edit' => Pages\EditStudentProfile::route('/{record}/edit'),
        ];
    }
}
