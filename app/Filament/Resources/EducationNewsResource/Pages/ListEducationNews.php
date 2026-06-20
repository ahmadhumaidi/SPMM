<?php

namespace App\Filament\Resources\EducationNewsResource\Pages;

use App\Filament\Resources\EducationNewsResource;
use App\Models\Campus;
use App\Services\AiEducationNewsDraftService;
use App\Support\FilamentResourceScope;
use Filament\Actions;
use Filament\Forms\Components\CheckboxList;
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
                ->modalDescription('Tulis kata kunci, lalu AI akan membuat artikel evergreen SEO penuh berdasarkan knowledge Kampus Media. Draft belum otomatis publish.')
                ->form([
                    TextInput::make('keyword')
                        ->label('Kata kunci artikel')
                        ->placeholder('Contoh: jurusan favorit di indonesia')
                        ->required()
                        ->maxLength(160),
                    CheckboxList::make('campus_ids')
                        ->label('Kampus tujuan')
                        ->columns(2)
                        ->options(fn (): array => FilamentResourceScope::applyCampusScope(Campus::query()->orderBy('name'), 'id')->pluck('name', 'id')->all())
                        ->helperText('Kosongkan jika artikel umum untuk semua kampus.'),
                ])
                ->action(function (array $data): void {
                    try {
                        $news = app(AiEducationNewsDraftService::class)->createSeoArticleDraft(
                            keyword: $data['keyword'],
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
                            ->title('Gagal membuat artikel SEO')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\CreateAction::make(),
        ];
    }
}
