<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewStudentDocumentResource\Pages;
use App\Models\StudentDocument;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NewStudentDocumentResource extends Resource
{
    protected static ?string $model = StudentDocument::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder-open';

    protected static ?string $navigationGroup = 'Mahasiswa Baru';

    protected static ?string $navigationLabel = 'Pemberkasan';

    protected static ?string $modelLabel = 'Pemberkasan';

    protected static ?string $pluralModelLabel = 'Pemberkasan';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessStudentRecords();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Mahasiswa')
                ->columns(2)
                ->schema([
                    TextInput::make('lead.full_name')->label('Nama mahasiswa')->disabled()->dehydrated(false),
                    TextInput::make('lead.selection_number')->label('No. seleksi')->disabled()->dehydrated(false),
                    TextInput::make('lead.campus.name')->label('Kampus')->disabled()->dehydrated(false),
                    TextInput::make('lead.studyProgram.name')->label('Program studi')->disabled()->dehydrated(false),
                ]),
            Section::make('Dokumen')
                ->columns(2)
                ->schema([
                    Select::make('document_type')
                        ->label('Jenis dokumen')
                        ->options(static::documentTypeOptions())
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('status')
                        ->label('Status pemberkasan')
                        ->options(static::statusOptions())
                        ->default('uploaded')
                        ->required(),
                    TextInput::make('original_name')->label('Nama file')->disabled()->dehydrated(false),
                    TextInput::make('file_size')
                        ->label('Ukuran file')
                        ->formatStateUsing(fn (?int $state): string => $state ? number_format($state / 1024, 1, ',', '.').' KB' : '-')
                        ->disabled()
                        ->dehydrated(false),
                    FileUpload::make('file_path')
                        ->label('File pemberkasan')
                        ->disk('public')
                        ->directory(fn (?StudentDocument $record): string => 'student-documents/'.($record?->lead_id ?: 'admin'))
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                        ->maxSize(4096)
                        ->fetchFileInformation(false)
                        ->disabled()
                        ->dehydrated(false)
                        ->openable()
                        ->downloadable()
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('Catatan verifikasi')
                        ->placeholder('Contoh: KTP kurang jelas, mohon upload ulang.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->groups([
                Group::make('lead.full_name')->label('Mahasiswa')->collapsible(),
                Group::make('lead.campus.name')->label('Kampus')->collapsible(),
            ])
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('lead.full_name')->label('Mahasiswa')->searchable()->sortable()->wrap(),
                TextColumn::make('lead.selection_number')->label('No. Seleksi')->searchable()->toggleable(),
                TextColumn::make('lead.campus.name')->label('Kampus')->searchable()->sortable(),
                TextColumn::make('lead.studyProgram.name')->label('Program Studi')->searchable()->toggleable(),
                TextColumn::make('document_type')
                    ->label('Dokumen')
                    ->formatStateUsing(fn (?string $state): string => static::documentTypeLabel($state))
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (?string $state): string => static::statusLabel($state))
                    ->badge()
                    ->color(fn (?string $state): string => static::statusColor($state)),
                TextColumn::make('original_name')->label('File')->limit(28)->searchable()->toggleable(),
                TextColumn::make('updated_at')->label('Update')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campus')
                    ->label('Kampus')
                    ->relationship('lead.campus', 'name', fn (Builder $query): Builder => FilamentResourceScope::applyCampusScope($query->orderBy('name'), 'campuses.id')),
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Jenis dokumen')
                    ->options(static::documentTypeOptions()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(static::statusOptions()),
            ])
            ->actions([
                Action::make('open_file')
                    ->label('Lihat File')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn (StudentDocument $record): ?string => $record->file_path ? Storage::disk('public')->url($record->file_path) : null)
                    ->openUrlInNewTab()
                    ->visible(fn (StudentDocument $record): bool => filled($record->file_path)),
                Tables\Actions\EditAction::make()->label('Verifikasi'),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyRelatedCampusScope(
            parent::getEloquentQuery()
                ->with(['lead.campus', 'lead.studyProgram', 'lead.studentBiodata'])
                ->whereHas('lead.studentBiodata', fn (Builder $query): Builder => $query->where('student_type', 'new')),
            'lead'
        );
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'uploaded')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return 'warning';
    }

    public static function documentTypeOptions(): array
    {
        return [
            'ktp' => 'KTP',
            'kk' => 'KK',
            'ijazah' => 'Ijazah',
            'transkrip_skhu' => 'Transkrip/SKHU',
            'pass_foto_formal' => 'Pass Foto Formal',
            'dokumen_pendukung' => 'Dokumen Pendukung Lainnya',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'uploaded' => 'Menunggu verifikasi',
            'verified' => 'Terverifikasi',
            'revision' => 'Perlu revisi',
            'rejected' => 'Ditolak',
        ];
    }

    public static function documentTypeLabel(?string $state): string
    {
        return static::documentTypeOptions()[$state] ?? str((string) $state)->replace('_', ' ')->title()->toString();
    }

    public static function statusLabel(?string $state): string
    {
        return static::statusOptions()[$state] ?? str((string) $state)->replace('_', ' ')->title()->toString();
    }

    public static function statusColor(?string $state): string
    {
        return match ($state) {
            'verified' => 'success',
            'revision' => 'warning',
            'rejected' => 'danger',
            default => 'info',
        };
    }

    public static function canDelete(Model $record): bool
    {
        return FilamentResourceScope::isSuperAdmin();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewStudentDocuments::route('/'),
            'edit' => Pages\EditNewStudentDocument::route('/{record}/edit'),
        ];
    }
}

