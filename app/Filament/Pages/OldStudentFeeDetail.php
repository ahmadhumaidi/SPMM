<?php

namespace App\Filament\Pages;

use App\Models\Campus;
use App\Models\Lead;
use App\Services\StudentBiodataProvisioner;
use App\Support\FilamentResourceScope;
use App\Support\FilamentTable;
use App\Support\FinancialStatusLabels;
use App\Support\VirtualAccountNumber;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OldStudentFeeDetail extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Mahasiswa Lama';

    protected static ?string $navigationLabel = 'Rincian Biaya (RR)';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.student-table-page';

    public string $moduleTitle = 'Rincian Biaya (RR) Mahasiswa Lama';

    public string $moduleDescription = 'Pembayaran mahasiswa lama. Data ini mengambil mahasiswa yang biodatanya ditandai sebagai mahasiswa lama.';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessStudentRecords();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FilamentResourceScope::applyManagedLeadCampusScope(Lead::query())
                    ->with(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'latestInvoice', 'studentPayments'])
                    ->whereHas('studentPayments')
                    ->whereHas('studentBiodata', fn (Builder $biodataQuery): Builder => $biodataQuery->where('student_type', 'old'))
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                FilamentTable::rowNumberColumn(),
                TextColumn::make('studentBiodata.selection_number')
                    ->label('NO. SELEKSI')
                    ->state(fn (Lead $record): string => $record->studentBiodata?->selection_number ?? app(StudentBiodataProvisioner::class)->selectionNumberFor($record))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('studentBiodata', fn (Builder $biodataQuery) => $biodataQuery->where('selection_number', 'like', "%{$search}%"))),
                TextColumn::make('latestInvoice.va_number')
                    ->label('VIRTUAL ACCOUNT')
                    ->state(fn (Lead $record): string => VirtualAccountNumber::forLead($record)),
                TextColumn::make('full_name')->label('N A M A')->searchable()->sortable(),
                TextColumn::make('payment_status')
                ->label('STATUS KEUANGAN')
                ->state(fn (Lead $record): string => FinancialStatusLabels::leadStatus($record))
                ->formatStateUsing(fn (string $state) => FinancialStatusLabels::statusDotHtml($state))
                ->html(),
                TextColumn::make('studentBiodata.group')
                    ->label('KLP')
                    ->state(fn (Lead $record): string => $record->studentBiodata?->group ?: ($record->classTrack?->name ?: '-')),
                TextColumn::make('studyProgram.name')->label('JRS')->searchable(),
                TextColumn::make('campus.name')->label('Kampus')->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campus_id')
                    ->label('Kampus')
                    ->options(fn (): array => FilamentResourceScope::applyCampusScope(Campus::query()->orderBy('name'), 'id')->pluck('name', 'id')->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Lead $record): string => EditStudentFeePlan::getUrl(['lead' => $record->id])),
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (Lead $record): string => EditStudentFeePlan::getUrl(['lead' => $record->id])),
            ])
            ->bulkActions([]);
    }
}


