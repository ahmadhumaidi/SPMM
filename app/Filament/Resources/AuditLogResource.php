<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Reports';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Ringkasan aktivitas')
                ->columns(3)
                ->schema([
                    Placeholder::make('activity_label')
                        ->label('Aktivitas')
                        ->content(fn (AuditLog $record): string => self::eventLabel($record->event)),
                    Placeholder::make('data_label')
                        ->label('Data')
                        ->content(fn (AuditLog $record): string => self::auditableLabel($record)),
                    Placeholder::make('actor_label')
                        ->label('Dilakukan oleh')
                        ->content(fn (AuditLog $record): string => $record->user?->name ?: 'Sistem'),
                    Placeholder::make('record_label')
                        ->label('Nama/identitas data')
                        ->content(fn (AuditLog $record): string => (string) data_get($record->metadata, 'label', '-')),
                    Placeholder::make('time_label')
                        ->label('Waktu')
                        ->content(fn (AuditLog $record): string => $record->created_at?->format('d M Y H:i') ?: '-'),
                    Placeholder::make('summary_label')
                        ->label('Kesimpulan')
                        ->content(fn (AuditLog $record): string => self::activitySummary($record)),
                ]),
            Section::make('Perubahan yang terjadi')
                ->visible(fn (AuditLog $record): bool => self::hasReadableChanges($record))
                ->schema([
                    Placeholder::make('changes_readable')
                        ->hiddenLabel()
                        ->content(fn (AuditLog $record): HtmlString => self::changesHtml($record))
                        ->columnSpanFull(),
                ]),
            Section::make('Detail tambahan')
                ->collapsed(fn (AuditLog $record): bool => self::hasReadableChanges($record))
                ->schema([
                    Placeholder::make('metadata_readable')
                        ->hiddenLabel()
                        ->content(fn (AuditLog $record): HtmlString => self::metadataHtml($record))
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
                TextColumn::make('event')
                    ->label('Aktivitas')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::eventLabel($state))
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('Sistem')
                    ->searchable(),
                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (?string $state): string => class_basename($state ?? '-')),
                TextColumn::make('metadata.label')
                    ->label('Data')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('auditable_id')->label('ID'),
                TextColumn::make('restore_status')
                    ->label('Status restore')
                    ->badge()
                    ->state(fn (AuditLog $record): string => $record->event === 'model_deleted' ? self::restoreStatus($record) : '-')
                    ->color(fn (string $state): string => match ($state) {
                        'Bisa direstore' => 'success',
                        'Sudah aktif' => 'info',
                        '-' => 'gray',
                        default => 'warning',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->label('Aktivitas')
                    ->options(fn (): array => AuditLog::query()
                        ->distinct()
                        ->pluck('event', 'event')
                        ->map(fn (string $event): string => self::eventLabel($event))
                        ->all()),
                Tables\Filters\SelectFilter::make('auditable_type')
                    ->label('Tipe data')
                    ->options(fn (): array => AuditLog::query()
                        ->whereNotNull('auditable_type')
                        ->distinct()
                        ->pluck('auditable_type', 'auditable_type')
                        ->mapWithKeys(fn (string $type, string $key): array => [$key => class_basename($type)])
                        ->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Restore data terhapus?')
                    ->modalDescription('Data akan dikembalikan berdasarkan record soft delete atau snapshot audit saat data dihapus.')
                    ->visible(fn (AuditLog $record): bool => $record->event === 'model_deleted')
                    ->action(fn (AuditLog $record): mixed => self::restoreDeletedRecord($record)),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    private static function eventLabel(string $event): string
    {
        return match ($event) {
            'model_created' => 'Data dibuat',
            'model_updated' => 'Data diubah',
            'model_deleted' => 'Data dihapus',
            'admin_login' => 'Login admin/staff',
            'lead_created' => 'Lead dibuat',
            'lead_assigned' => 'Lead dibagikan',
            'invoice_created' => 'Invoice dibuat',
            'invoice_paid' => 'Invoice lunas',
            'invoice_expired' => 'Invoice expired',
            'invoice_regenerated' => 'Invoice dibuat ulang',
            'student_profile_completed' => 'Biodata selesai',
            'enrollment_status_changed' => 'Status enrollment berubah',
            'nim_issued' => 'NIM diterbitkan',
            default => Str::headline(str_replace('_', ' ', $event)),
        };
    }

    private static function auditableLabel(AuditLog $record): string
    {
        $model = self::modelDisplayName($record->auditable_type ?: data_get($record->metadata, 'model'));
        $id = $record->auditable_id ? ' #'.$record->auditable_id : '';

        return trim($model.$id) ?: '-';
    }

    private static function activitySummary(AuditLog $record): string
    {
        $actor = $record->user?->name ?: 'Sistem';
        $data = self::auditableLabel($record);
        $label = (string) data_get($record->metadata, 'label', '');
        $target = filled($label) ? $label.' ('.$data.')' : $data;

        return match ($record->event) {
            'model_created', 'lead_created' => $actor.' membuat data '.$target.'.',
            'model_updated' => $actor.' mengubah data '.$target.'.',
            'model_deleted' => $actor.' menghapus data '.$target.'.',
            'admin_login' => $actor.' login ke sistem admin.',
            'lead_assigned' => $actor.' membagikan lead '.$target.'.',
            'invoice_created' => $actor.' membuat invoice untuk '.$target.'.',
            'invoice_paid' => 'Invoice '.$target.' tercatat lunas.',
            'invoice_expired' => 'Invoice '.$target.' melewati batas waktu pembayaran.',
            'invoice_regenerated' => $actor.' membuat ulang invoice '.$target.'.',
            default => $actor.' melakukan aktivitas '.self::eventLabel($record->event).' pada '.$target.'.',
        };
    }

    private static function hasReadableChanges(AuditLog $record): bool
    {
        return is_array(data_get($record->metadata, 'changes')) && data_get($record->metadata, 'changes') !== [];
    }

    private static function changesHtml(AuditLog $record): HtmlString
    {
        $changes = data_get($record->metadata, 'changes', []);
        $original = data_get($record->metadata, 'original', []);

        if (! is_array($changes) || $changes === []) {
            return new HtmlString('<p style="color:#64748b;margin:0;">Tidak ada perubahan field yang tercatat.</p>');
        }

        $rows = collect($changes)
            ->map(function (mixed $after, string $field) use ($original): string {
                $before = is_array($original) ? ($original[$field] ?? null) : null;

                return '<tr>'
                    .'<td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;font-weight:600;color:#0f172a;">'.e(self::fieldLabel($field)).'</td>'
                    .'<td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#64748b;">'.self::valueHtml($before).'</td>'
                    .'<td style="padding:10px 12px;border-bottom:1px solid #e5e7eb;color:#0f172a;">'.self::valueHtml($after).'</td>'
                    .'</tr>';
            })
            ->implode('');

        return new HtmlString('<div style="overflow:auto;border:1px solid #e5e7eb;border-radius:14px;background:#fff;"><table style="width:100%;border-collapse:collapse;font-size:14px;"><thead><tr style="background:#f8fafc;color:#475569;text-align:left;"><th style="padding:10px 12px;">Field</th><th style="padding:10px 12px;">Sebelumnya</th><th style="padding:10px 12px;">Menjadi</th></tr></thead><tbody>'.$rows.'</tbody></table></div>');
    }

    private static function metadataHtml(AuditLog $record): HtmlString
    {
        $metadata = collect($record->metadata ?? [])
            ->reject(fn (mixed $value, string $key): bool => in_array($key, ['changes', 'original', 'restore_payload', 'snapshot'], true))
            ->map(function (mixed $value, string $key): string {
                return '<tr>'
                    .'<td style="padding:9px 12px;border-bottom:1px solid #e5e7eb;font-weight:600;color:#0f172a;">'.e(self::fieldLabel($key)).'</td>'
                    .'<td style="padding:9px 12px;border-bottom:1px solid #e5e7eb;color:#475569;">'.self::valueHtml($value).'</td>'
                    .'</tr>';
            })
            ->implode('');

        if ($metadata === '') {
            return new HtmlString('<p style="color:#64748b;margin:0;">Tidak ada detail tambahan.</p>');
        }

        return new HtmlString('<div style="overflow:auto;border:1px solid #e5e7eb;border-radius:14px;background:#fff;"><table style="width:100%;border-collapse:collapse;font-size:14px;"><tbody>'.$metadata.'</tbody></table></div>');
    }

    private static function modelDisplayName(?string $class): string
    {
        $name = class_basename((string) $class);

        return match ($name) {
            'Lead' => 'Lead / Calon Mahasiswa',
            'StudentBiodata' => 'Biodata Mahasiswa',
            'StudentPayment' => 'Pembayaran Mahasiswa',
            'Invoice' => 'Invoice',
            'User' => 'User / Staff',
            'ReferralPartner' => 'Affiliator',
            'ReferralConversion' => 'Konversi Referral',
            'Campus' => 'Kampus',
            'PartnerCampusSite' => 'Website Kampus Mitra',
            default => Str::headline($name ?: 'Data'),
        };
    }

    private static function fieldLabel(string $field): string
    {
        return match ($field) {
            'label' => 'Nama data',
            'model' => 'Jenis data',
            'full_name', 'name' => 'Nama',
            'email' => 'Email',
            'whatsapp_number', 'phone', 'mobile_phone' => 'No. HP/WA',
            'lead_status' => 'Status lead',
            'prospect_status' => 'Status prospek',
            'payment_status' => 'Status pembayaran',
            'enrollment_status' => 'Status enrollment',
            'campus_id' => 'Kampus',
            'study_program_id' => 'Program studi',
            'class_track_id' => 'Program perkuliahan',
            'assigned_to_user_id' => 'Staff PMB',
            'source_channel' => 'Sumber lead',
            'source_detail' => 'Detail sumber lead',
            'invoice_number' => 'Nomor invoice',
            'amount' => 'Nominal',
            'status' => 'Status',
            'paid_at' => 'Tanggal lunas',
            'created_at' => 'Dibuat pada',
            'updated_at' => 'Diubah pada',
            'deleted_at' => 'Dihapus pada',
            'ip' => 'Alamat IP',
            'user_agent' => 'Perangkat/browser',
            'role' => 'Role',
            default => Str::headline(str_replace('_', ' ', $field)),
        };
    }

    private static function valueHtml(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '<span style="color:#94a3b8;">Kosong</span>';
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (is_array($value)) {
            if (isset($value['summary'])) {
                return e((string) $value['summary']);
            }

            if (isset($value['path'])) {
                return e((string) $value['path']);
            }

            return '<pre style="margin:0;white-space:pre-wrap;font-size:12px;color:#475569;">'.e(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)).'</pre>';
        }

        if (is_string($value) && str_starts_with($value, 'App\\Models\\')) {
            return e(self::modelDisplayName($value));
        }

        return e((string) $value);
    }
    private static function restoreStatus(AuditLog $record): string
    {
        $class = self::modelClass($record);

        if ($class === null) {
            return 'Model tidak tersedia';
        }

        if (self::usesSoftDeletes($class)) {
            $model = $class::withTrashed()->whereKey($record->auditable_id)->first();

            if ($model?->trashed()) {
                return 'Bisa direstore';
            }

            if ($model !== null) {
                return 'Sudah aktif';
            }
        }

        return filled(data_get($record->metadata, 'restore_payload')) ? 'Bisa direstore' : 'Snapshot tidak ada';
    }

    private static function restoreDeletedRecord(AuditLog $record): void
    {
        $class = self::modelClass($record);

        if ($class === null) {
            self::notifyError('Model data ini sudah tidak tersedia di sistem.');

            return;
        }

        try {
            if (self::usesSoftDeletes($class)) {
                $model = $class::withTrashed()->whereKey($record->auditable_id)->first();

                if ($model?->trashed()) {
                    $model->restore();
                    self::notifySuccess('Data berhasil direstore.');

                    return;
                }

                if ($model !== null) {
                    self::notifySuccess('Data sudah dalam kondisi aktif.');

                    return;
                }
            }

            $payload = data_get($record->metadata, 'restore_payload');

            if (! is_array($payload) || $payload === []) {
                self::notifyError('Snapshot data tidak tersedia, jadi data lama ini belum bisa direstore.');

                return;
            }

            /** @var Model $model */
            $model = new $class;
            $table = $model->getTable();
            $keyName = $model->getKeyName();

            $attributes = collect($payload)
                ->filter(fn (mixed $value, string $field): bool => Schema::hasColumn($table, $field))
                ->all();

            $attributes[$keyName] = $record->auditable_id;
            unset($attributes['deleted_at']);

            $existing = $class::query()->whereKey($record->auditable_id)->exists();

            if ($existing) {
                self::notifySuccess('Data sudah dalam kondisi aktif.');

                return;
            }

            Model::unguarded(function () use ($class, $attributes): void {
                $class::query()->create($attributes);
            });

            self::notifySuccess('Data berhasil direstore dari snapshot audit.');
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Restore gagal')
                ->body('Data tidak bisa direstore otomatis. Periksa relasi data atau file terkait terlebih dahulu.')
                ->danger()
                ->send();
        }
    }

    private static function modelClass(AuditLog $record): ?string
    {
        $class = data_get($record->metadata, 'model', $record->auditable_type);

        if (! is_string($class) || ! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        return $class;
    }

    private static function usesSoftDeletes(string $class): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($class), true);
    }

    private static function notifySuccess(string $message): void
    {
        Notification::make()->title($message)->success()->send();
    }

    private static function notifyError(string $message): void
    {
        Notification::make()->title($message)->danger()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}

