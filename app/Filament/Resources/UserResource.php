<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Campus;
use App\Models\User;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Access';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessUsers();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('phone')->maxLength(32),
            TextInput::make('password')->password()->required(fn (string $operation): bool => $operation === 'create')->dehydrated(fn (?string $state): bool => filled($state)),
            Select::make('role')
                ->options(fn (): array => auth()->user()?->isSuperAdmin()
                    ? [
                        UserRole::SuperAdmin->value => 'Super Admin',
                        UserRole::KoordinatorPmb->value => 'Koordinator PMB',
                        UserRole::StaffPmb->value => 'Staff PMB',
                    ]
                    : [
                        UserRole::StaffPmb->value => 'Staff PMB',
                    ])
                ->default(fn (): string => FilamentResourceScope::isCoordinator() ? UserRole::StaffPmb->value : UserRole::StaffPmb->value)
                ->disabled(fn (?User $record): bool => FilamentResourceScope::isCoordinator() && $record?->exists)
                ->dehydrated()
                ->required(),
            Select::make('status')->options([
                UserStatus::Active->value => 'Active',
                UserStatus::Inactive->value => 'Inactive',
            ])->required(),
            Select::make('campuses')
                ->relationship('campuses', 'name')
                ->options(fn (): array => auth()->user()?->isSuperAdmin()
                    ? Campus::query()->orderBy('name')->pluck('name', 'id')->all()
                    : (auth()->user()?->campuses()->orderBy('name')->pluck('campuses.name', 'campuses.id')->all() ?? []))
                ->multiple()
                ->preload(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('campuses');
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            return $query;
        }

        if ($user?->role === UserRole::KoordinatorPmb) {
            return $query
                ->where('role', UserRole::StaffPmb)
                ->whereHas('campuses', fn (Builder $campusQuery): Builder => $campusQuery->whereIn('campuses.id', $user->campuses()->select('campuses.id')));
        }

        return $query->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('role')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('campuses.name')->listWithLineBreaks()->limitList(3),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')->options([
                    UserRole::SuperAdmin->value => 'Super Admin',
                    UserRole::KoordinatorPmb->value => 'Koordinator PMB',
                    UserRole::StaffPmb->value => 'Staff PMB',
                ]),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return FilamentResourceScope::canAccessUsers();
    }

    public static function canDelete(Model $record): bool
    {
        return FilamentResourceScope::isSuperAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return FilamentResourceScope::isSuperAdmin();
    }
}
