<?php

namespace App\Filament\Resources;

use App\Enums\CampusStatus;
use App\Filament\Resources\PartnerCampusSiteResource\Pages;
use App\Models\Campus;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class PartnerCampusSiteResource extends Resource
{
    protected static ?string $model = Campus::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Portal Publik';

    protected static ?string $navigationLabel = 'Web Kampus Mitra';

    protected static ?string $modelLabel = 'Web Kampus Mitra';

    protected static ?string $pluralModelLabel = 'Web Kampus Mitra';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::isSuperAdmin();
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyCampusScope(parent::getEloquentQuery(), 'id');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identitas Web')
                ->description('Atur alamat publik untuk web kampus mitra.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama kampus')
                        ->disabled(),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->disabled(),
                    TextInput::make('subdomain')
                        ->label('Subdomain')
                        ->helperText('Contoh: mahera-jakarta. Nanti menjadi mahera-jakarta.kampusnusantara.com')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('custom_domain')
                        ->label('Custom domain')
                        ->helperText('Opsional. Contoh: pmb.kampus-a.ac.id')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('status')
                        ->label('Status web')
                        ->options([
                            CampusStatus::Active->value => 'Aktif',
                            CampusStatus::Inactive->value => 'Nonaktif',
                        ])
                        ->required(),
                ]),
            Section::make('Preview URL')
                ->columns(1)
                ->schema([
                    Placeholder::make('local_preview')
                        ->label('Preview lokal')
                        ->content(fn (Campus $record): HtmlString => new HtmlString(
                            '<a href="'.route('campuses.show', ['campus' => $record->name]).'" target="_blank">'.route('campuses.show', ['campus' => $record->name]).'</a>'
                        )),
                    Placeholder::make('subdomain_preview')
                        ->label('URL subdomain produksi')
                        ->content(fn (Campus $record): string => filled($record->subdomain)
                            ? $record->subdomain.'.kampusnusantara.com'
                            : 'Isi subdomain terlebih dahulu.'),
                    Placeholder::make('custom_domain_preview')
                        ->label('URL custom domain')
                        ->content(fn (Campus $record): string => $record->custom_domain ?: 'Belum diatur.'),
                ]),
            Section::make('Hero Website')
                ->description('Konten utama yang tampil paling atas di halaman kampus.')
                ->columns(2)
                ->schema([
                    TextInput::make('website_settings.hero_badge')
                        ->label('Badge promo')
                        ->placeholder('Promo beasiswa PMB terbatas tahun ini')
                        ->maxLength(120),
                    TextInput::make('website_settings.hero_headline')
                        ->label('Headline')
                        ->placeholder('Kuliah Karyawan & Reguler Lebih Fleksibel')
                        ->maxLength(160)
                        ->columnSpanFull(),
                    Textarea::make('website_settings.hero_subheadline')
                        ->label('Subheadline')
                        ->placeholder('Kuliah bisa sambil kerja, biaya terjangkau, dan bisa dicicil bulanan.')
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('website_settings.primary_cta_label')
                        ->label('Teks tombol utama')
                        ->placeholder('Daftar Sekarang')
                        ->maxLength(80),
                    TextInput::make('website_settings.secondary_cta_label')
                        ->label('Teks tombol kedua')
                        ->placeholder('Konsultasi Gratis')
                        ->maxLength(80),
                    FileUpload::make('website_settings.hero_image_path')
                        ->label('Gambar mahasiswa / kampus')
                        ->image()
                        ->directory('campus-website')
                        ->imageEditor()
                        ->columnSpanFull(),
                ]),
            Section::make('Kontak & Promo')
                ->columns(2)
                ->schema([
                    TextInput::make('website_settings.whatsapp_number')
                        ->label('Nomor WhatsApp PMB')
                        ->helperText('Gunakan format internasional, contoh: 6281234567890')
                        ->maxLength(32),
                    TextInput::make('website_settings.promo_title')
                        ->label('Judul promo')
                        ->placeholder('Daftar lebih awal, peluang beasiswa lebih besar.')
                        ->maxLength(160),
                    Textarea::make('website_settings.promo_description')
                        ->label('Deskripsi promo')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
            Section::make('Statistik Kepercayaan')
                ->schema([
                    Repeater::make('website_settings.trust_stats')
                        ->label('Statistik')
                        ->schema([
                            TextInput::make('value')->label('Angka')->required()->maxLength(32),
                            TextInput::make('label')->label('Keterangan')->required()->maxLength(80),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah statistik')
                        ->columnSpanFull(),
                ]),
            Section::make('Keunggulan Kampus')
                ->schema([
                    Repeater::make('website_settings.features')
                        ->label('Keunggulan')
                        ->schema([
                            TextInput::make('icon')->label('Icon Lucide')->placeholder('clock-3')->maxLength(80),
                            TextInput::make('title')->label('Judul')->required()->maxLength(120),
                            Textarea::make('description')->label('Deskripsi')->rows(2)->required(),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah keunggulan')
                        ->columnSpanFull(),
                ]),
            Section::make('Testimoni')
                ->schema([
                    Repeater::make('website_settings.testimonials')
                        ->label('Testimoni mahasiswa')
                        ->schema([
                            FileUpload::make('photo_path')
                                ->label('Foto')
                                ->image()
                                ->imageEditor()
                                ->disk('public')
                                ->directory('campus-testimonials')
                                ->visibility('public'),
                            TextInput::make('name')->label('Nama')->required()->maxLength(120),
                            TextInput::make('role')->label('Keterangan')->placeholder('Karyawan Swasta')->maxLength(120),
                            Textarea::make('quote')->label('Testimoni')->rows(3)->required(),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah testimoni')
                        ->columnSpanFull(),
                ]),
            Section::make('Galeri Kampus')
                ->description('Foto fasilitas, aktivitas mahasiswa, ruang kelas, gedung, atau suasana kampus.')
                ->schema([
                    Repeater::make('website_settings.gallery')
                        ->label('Foto galeri')
                        ->schema([
                            FileUpload::make('image_path')
                                ->label('Foto')
                                ->image()
                                ->imageEditor()
                                ->disk('public')
                                ->directory('campus-gallery')
                                ->visibility('public')
                                ->required(),
                            TextInput::make('title')
                                ->label('Judul')
                                ->placeholder('Ruang kelas modern')
                                ->maxLength(120),
                            Textarea::make('description')
                                ->label('Keterangan')
                                ->rows(2)
                                ->maxLength(220),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah foto galeri')
                        ->columnSpanFull(),
                ]),
            Section::make('FAQ')
                ->schema([
                    Repeater::make('website_settings.faqs')
                        ->label('Pertanyaan & jawaban')
                        ->schema([
                            TextInput::make('question')->label('Pertanyaan')->required()->maxLength(160),
                            Textarea::make('answer')->label('Jawaban')->rows(3)->required(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah FAQ')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Kampus')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subdomain')
                    ->label('Subdomain')
                    ->placeholder('Belum diatur')
                    ->formatStateUsing(fn (?string $state): string => $state ? $state.'.kampusnusantara.com' : 'Belum diatur')
                    ->copyable(),
                TextColumn::make('custom_domain')
                    ->label('Custom domain')
                    ->placeholder('Belum diatur')
                    ->copyable(),
                TextColumn::make('slug')
                    ->label('Preview lokal')
                    ->formatStateUsing(fn (string $state, Campus $record): string => route('campuses.show', ['campus' => $record->name]))
                    ->copyable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        CampusStatus::Active->value => 'Aktif',
                        CampusStatus::Inactive->value => 'Nonaktif',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('open_public')
                    ->label('Buka')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Campus $record): string => route('campuses.show', ['campus' => $record->name]))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartnerCampusSites::route('/'),
            'edit' => Pages\EditPartnerCampusSite::route('/{record}/edit'),
        ];
    }
}
