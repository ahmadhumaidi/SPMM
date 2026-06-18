<?php

namespace App\Filament\Resources;

use App\Enums\CampusStatus;
use App\Filament\Resources\StudyProgramResource\Pages;
use App\Models\StudyProgram;
use App\Support\FilamentResourceScope;
use App\Support\StudyProgramCatalog;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class StudyProgramResource extends Resource
{
    protected static ?string $model = StudyProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessMasterData();
    }

    public static function accreditationOptions(): array
    {
        return [
            'Unggul' => 'Unggul',
            'A' => 'A',
            'Baik Sekali' => 'Baik Sekali',
            'B' => 'B',
            'Baik' => 'Baik',
            'C' => 'C',
            'Terakreditasi' => 'Terakreditasi',
            'Tidak Terakreditasi' => 'Tidak Terakreditasi',
        ];
    }

    public static function degreeLevelOptions(): array
    {
        return [
            'D3' => 'D3',
            'D4' => 'D4',
            'S1' => 'S1',
            'S2' => 'S2',
            'S3' => 'S3',
            'Profesi' => 'Profesi',
        ];
    }

    public static function facultyOptions(): array
    {
        $catalogFaculties = collect(StudyProgramCatalog::items())->pluck('faculty');
        $databaseFaculties = StudyProgram::query()
            ->whereNotNull('faculty')
            ->where('faculty', '!=', '')
            ->pluck('faculty');

        return $catalogFaculties
            ->merge($databaseFaculties)
            ->filter()
            ->unique()
            ->sort()
            ->mapWithKeys(fn (string $faculty): array => [$faculty => $faculty])
            ->put('__manual', 'Tambah fakultas baru')
            ->all();
    }

    public static function programOptions(): array
    {
        $catalogOptions = collect(StudyProgramCatalog::items())
            ->mapWithKeys(fn (array $item): array => [
                StudyProgramCatalog::key($item) => $item['degree_level'].' - '.$item['name'].' ('.$item['faculty'].')',
            ]);

        $databaseOptions = StudyProgram::query()
            ->select(['id', 'name', 'degree_level', 'faculty'])
            ->orderBy('degree_level')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (StudyProgram $program): array => [
                'db:'.$program->id => trim(($program->degree_level ? $program->degree_level.' - ' : '').$program->name.' ('.($program->faculty ?: 'Tanpa fakultas').')'),
            ]);

        return $catalogOptions
            ->merge($databaseOptions)
            ->unique()
            ->all();
    }

    public static function searchProgramOptions(string $search): array
    {
        $search = trim($search);

        if (Str::length($search) < 2) {
            return [];
        }

        return collect(static::programOptions())
            ->filter(fn (string $label): bool => Str::contains(Str::lower($label), Str::lower($search)))
            ->take(30)
            ->all();
    }

    public static function programOptionLabels(array $values): array
    {
        $options = static::programOptions();

        return collect($values)
            ->mapWithKeys(fn (string $value): array => [$value => $options[$value] ?? $value])
            ->all();
    }

    public static function findProgramOption(string $key): ?array
    {
        if (Str::startsWith($key, 'db:')) {
            $program = StudyProgram::query()->find((int) Str::after($key, 'db:'));

            if (! $program) {
                return null;
            }

            return [
                'code' => $program->code ?: Str::slug(($program->degree_level ?? '').' '.($program->faculty ?? '').' '.$program->name),
                'name' => $program->name,
                'degree_level' => $program->degree_level,
                'faculty' => $program->faculty,
            ];
        }

        $item = StudyProgramCatalog::find($key);

        return $item ? array_merge(['code' => $key], $item) : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('campus_id')
                ->label('Kampus')
                ->relationship('campus', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Radio::make('input_mode')
                ->label('Cara tambah program studi')
                ->options([
                    'catalog' => 'Pilih dari database program studi',
                    'manual' => 'Tambah manual',
                ])
                ->default('catalog')
                ->live()
                ->visibleOn('create')
                ->columnSpanFull(),
            Select::make('program_catalog_keys')
                ->label('Nama Program Studi')
                ->multiple()
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => static::searchProgramOptions($search))
                ->getOptionLabelsUsing(fn (array $values): array => static::programOptionLabels($values))
                ->options([])
                ->required(fn (Get $get): bool => $get('input_mode') !== 'manual')
                ->visibleOn('create')
                ->visible(fn (Get $get): bool => $get('input_mode') !== 'manual')
                ->helperText('Ketik minimal 2 huruf, lalu pilih satu atau beberapa program studi. Data manual yang pernah ditambahkan juga akan muncul di pencarian.')
                ->columnSpanFull(),
            Select::make('program_catalog_key')
                ->label('Nama Program Studi')
                ->searchable()
                ->getSearchResultsUsing(fn (string $search): array => static::searchProgramOptions($search))
                ->getOptionLabelUsing(fn (?string $value): ?string => $value ? (static::programOptionLabels([$value])[$value] ?? $value) : null)
                ->live()
                ->visibleOn('edit')
                ->afterStateHydrated(function (Select $component, ?string $state, ?StudyProgram $record): void {
                    if (! $record) {
                        return;
                    }

                    $item = StudyProgramCatalog::findByNameAndDegree($record->name, $record->degree_level);

                    if ($item) {
                        $component->state(StudyProgramCatalog::key($item));
                    }
                })
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    $item = filled($state) ? static::findProgramOption($state) : null;

                    $set('code', $item['code'] ?? $state);
                    $set('name', $item['name'] ?? null);
                    $set('degree_level', $item['degree_level'] ?? null);
                    $set('faculty', $item['faculty'] ?? null);
                }),
            TextInput::make('code')
                ->label('Kode')
                ->maxLength(64)
                ->disabled(fn (Get $get): bool => $get('input_mode') !== 'manual')
                ->dehydrated()
                ->visible(fn (Get $get): bool => $get('input_mode') === 'manual' || request()->routeIs('filament.admin.resources.study-programs.edit')),
            TextInput::make('name')
                ->label('Program Studi')
                ->required(fn (Get $get): bool => $get('input_mode') === 'manual' || request()->routeIs('filament.admin.resources.study-programs.edit'))
                ->maxLength(255)
                ->disabled(fn (Get $get): bool => $get('input_mode') !== 'manual')
                ->dehydrated()
                ->visible(fn (Get $get): bool => $get('input_mode') === 'manual' || request()->routeIs('filament.admin.resources.study-programs.edit')),
            Select::make('degree_level')
                ->label('Jenjang')
                ->options(static::degreeLevelOptions())
                ->searchable()
                ->disabled(fn (Get $get): bool => $get('input_mode') !== 'manual')
                ->dehydrated()
                ->required(fn (Get $get): bool => $get('input_mode') === 'manual')
                ->visible(fn (Get $get): bool => $get('input_mode') === 'manual' || filled($get('degree_level'))),
            Select::make('faculty_choice')
                ->label('Fakultas')
                ->options(fn (): array => static::facultyOptions())
                ->searchable()
                ->native(false)
                ->live()
                ->required(fn (Get $get): bool => $get('input_mode') === 'manual')
                ->afterStateUpdated(function (?string $state, Set $set): void {
                    if ($state !== '__manual') {
                        $set('faculty', $state);
                    } else {
                        $set('faculty', null);
                    }
                })
                ->visible(fn (Get $get): bool => $get('input_mode') === 'manual')
                ->helperText('Pilih fakultas yang sudah ada, atau pilih "Tambah fakultas baru" untuk mengetik manual.')
                ->dehydrated(),
            TextInput::make('faculty')
                ->label(fn (Get $get): string => $get('faculty_choice') === '__manual' ? 'Nama fakultas baru' : 'Fakultas')
                ->maxLength(255)
                ->disabled(fn (Get $get): bool => $get('input_mode') !== 'manual' || ($get('faculty_choice') !== '__manual' && ! request()->routeIs('filament.admin.resources.study-programs.edit')))
                ->dehydrated()
                ->placeholder('Contoh: Ekonomi & Bisnis')
                ->required(fn (Get $get): bool => $get('input_mode') === 'manual' && $get('faculty_choice') === '__manual')
                ->visible(fn (Get $get): bool => ($get('input_mode') === 'manual' && $get('faculty_choice') === '__manual') || request()->routeIs('filament.admin.resources.study-programs.edit') || filled($get('faculty'))),
            Select::make('accreditation')
                ->label('Accreditation')
                ->options(static::accreditationOptions())
                ->searchable()
                ->native(false),
            TextInput::make('degree_title')
                ->label('Gelar')
                ->placeholder('Contoh: S.M., S.Ak., S.Kom.')
                ->maxLength(80)
                ->helperText('Gelar yang diperoleh setelah lulus dari program studi ini.'),
            Textarea::make('prospectus')
                ->label('Prospektus Program Studi')
                ->placeholder('Jelaskan prospek karier, kompetensi lulusan, bidang kerja, dan alasan memilih prodi ini.')
                ->rows(5)
                ->columnSpanFull(),
            Select::make('status')->options([
                CampusStatus::Active->value => 'Active',
                CampusStatus::Inactive->value => 'Inactive',
            ])->required(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyCampusScope(parent::getEloquentQuery());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup('degree_level')
            ->defaultSort('degree_level')
            ->groups([
                Group::make('degree_level')
                    ->label('Jenjang')
                    ->collapsible(),
            ])
            ->columns([
                TextColumn::make('campus.name')->sortable()->searchable(),
                TextColumn::make('code')->searchable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('degree_level')->label('Jenjang')->badge()->sortable(),
                TextColumn::make('degree_title')->label('Gelar')->searchable(),
                TextColumn::make('faculty')->label('Fakultas')->searchable(),
                TextColumn::make('accreditation')->label('Accreditation')->badge(),
                TextColumn::make('status')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campus_id')->relationship('campus', 'name'),
                Tables\Filters\SelectFilter::make('degree_level')
                    ->label('Jenjang')
                    ->options(static::degreeLevelOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudyPrograms::route('/'),
            'create' => Pages\CreateStudyProgram::route('/create'),
            'edit' => Pages\EditStudyProgram::route('/{record}/edit'),
        ];
    }
}
