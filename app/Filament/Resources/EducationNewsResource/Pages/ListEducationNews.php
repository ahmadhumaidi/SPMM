<?php

namespace App\Filament\Resources\EducationNewsResource\Pages;

use App\Filament\Resources\EducationNewsResource;
use App\Models\Campus;
use App\Services\AiEducationNewsDraftService;
use App\Support\FilamentResourceScope;
use Filament\Actions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Throwable;

class ListEducationNews extends ListRecords
{
    protected static string $resource = EducationNewsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateAiDraft')
                ->label('Generate Berita AI')
                ->modalHeading('Generate Berita AI')
                ->modalDescription('Sistem mengambil referensi pendidikan terbaru dari RSS, membaca konteks tren, lalu membuat draft berita. Draft belum otomatis publish.')
                ->form([
                    TextInput::make('topic')
                        ->label('Topik opsional')
                        ->placeholder('Contoh: kuliah karyawan, RPL, beasiswa, PDDIKTI')
                        ->maxLength(120),
                    CheckboxList::make('campus_ids')
                        ->label('Kampus tujuan')
                        ->columns(2)
                        ->options(fn (): array => FilamentResourceScope::applyCampusScope(Campus::query()->orderBy('name'), 'id')->pluck('name', 'id')->all())
                        ->helperText('Kosongkan jika berita umum untuk semua kampus.'),
                ])
                ->action(function (array $data): void {
                    try {
                        $news = app(AiEducationNewsDraftService::class)->createDraft(
                            campusIds: $data['campus_ids'] ?? null,
                            topic: $data['topic'] ?? null,
                        );

                        Notification::make()
                            ->title('Draft berita AI berhasil dibuat')
                            ->body($news->title)
                            ->success()
                            ->send();

                        $this->redirect(EducationNewsResource::getUrl('index'));
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Gagal membuat draft berita')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('generateSeoArticle')
                ->label('Generate Artikel SEO AI')
                ->modalHeading('Generate Artikel SEO AI')
                ->modalDescription('Isi topik dan keyword. AI akan membuat draft artikel SEO sesuai kategori, gaya bahasa, target pembaca, dan tipe artikel.')
                ->form([
                    TextInput::make('topik_artikel')
                        ->label('Topik Artikel')
                        ->placeholder('Contoh: Pentingnya kuliah di era AI')
                        ->required()
                        ->validationMessages([
                            'required' => 'Topik Artikel wajib diisi.',
                        ])
                        ->maxLength(160),
                    TextInput::make('keyword_utama')
                        ->label('Keyword Utama')
                        ->placeholder('Contoh: kuliah kelas karyawan')
                        ->required()
                        ->validationMessages([
                            'required' => 'Keyword Utama wajib diisi.',
                        ])
                        ->maxLength(160),
                    Select::make('category')
                        ->label('Kategori')
                        ->options([
                            'Jurusan' => 'Jurusan',
                            'Kampus' => 'Kampus',
                            'Pendidikan' => 'Pendidikan',
                            'Karier' => 'Karier',
                            'RPL' => 'RPL',
                            'Kelas Karyawan' => 'Kelas Karyawan',
                            'Beasiswa' => 'Beasiswa',
                            'Tips Kuliah' => 'Tips Kuliah',
                            'Umum' => 'Umum',
                        ])
                        ->required()
                        ->validationMessages([
                            'required' => 'Kategori wajib dipilih.',
                        ]),
                    Select::make('gaya_bahasa')
                        ->label('Gaya Bahasa')
                        ->options([
                            'Formal' => 'Formal',
                            'Santai' => 'Santai',
                            'Promosi Ringan' => 'Promosi Ringan',
                            'Edukatif' => 'Edukatif',
                            'Informatif' => 'Informatif',
                        ])
                        ->required()
                        ->validationMessages([
                            'required' => 'Gaya Bahasa wajib dipilih.',
                        ]),
                    TextInput::make('nama_kampus')
                        ->label('Nama Kampus')
                        ->placeholder('Opsional, contoh: Universitas Gajayana')
                        ->maxLength(160),
                    TextInput::make('target_pembaca')
                        ->label('Target Pembaca')
                        ->placeholder('Opsional, contoh: karyawan yang ingin lanjut kuliah')
                        ->maxLength(160),
                    Textarea::make('keyword_tambahan')
                        ->label('Keyword Tambahan')
                        ->placeholder('Opsional, pisahkan dengan koma')
                        ->rows(2)
                        ->maxLength(500),
                    TextInput::make('lokasi')
                        ->label('Lokasi')
                        ->placeholder('Opsional, contoh: Malang')
                        ->maxLength(160),
                    Select::make('tipe_artikel')
                        ->label('Tipe Artikel')
                        ->options([
                            'Artikel Edukasi' => 'Artikel Edukasi',
                            'Artikel SEO' => 'Artikel SEO',
                            'Artikel Soft Selling' => 'Artikel Soft Selling',
                            'Artikel Berita Ringan' => 'Artikel Berita Ringan',
                            'Artikel Panduan' => 'Artikel Panduan',
                            'Artikel Listicle' => 'Artikel Listicle',
                        ])
                        ->default('Artikel SEO'),
                    Select::make('panjang_artikel')
                        ->label('Panjang Artikel')
                        ->options([
                            'Singkat: 400-600 kata' => 'Singkat: 400-600 kata',
                            'Sedang: 700-900 kata' => 'Sedang: 700-900 kata',
                            'Panjang: 1000-1200 kata' => 'Panjang: 1000-1200 kata',
                        ])
                        ->default('Sedang: 700-900 kata'),
                    CheckboxList::make('campus_ids')
                        ->label('Kampus tujuan')
                        ->columns(2)
                        ->options(fn (): array => FilamentResourceScope::applyCampusScope(Campus::query()->orderBy('name'), 'id')->pluck('name', 'id')->all())
                        ->helperText('Kosongkan jika artikel umum untuk semua kampus.'),
                ])
                ->action(function (array $data): void {
                    try {
                        $news = app(AiEducationNewsDraftService::class)->createSeoArticleDraft(
                            payload: $data,
                            campusIds: $data['campus_ids'] ?? null,
                        );

                        Notification::make()
                            ->title('Draft artikel SEO AI berhasil dibuat')
                            ->body($news->title)
                            ->success()
                            ->send();

                        $this->redirect(EducationNewsResource::getUrl('index'));
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Generate AI gagal')
                            ->body('Generate AI gagal. Silakan coba lagi atau periksa konfigurasi API.')
                            ->danger()
                            ->send();
                    }
                }),
            Actions\CreateAction::make(),
        ];
    }
}
