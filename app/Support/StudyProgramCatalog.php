<?php

namespace App\Support;

use Illuminate\Support\Str;

class StudyProgramCatalog
{
    public static function items(): array
    {
        return [
            ['degree_level' => 'D3', 'faculty' => 'Ekonomi & Bisnis', 'name' => 'Akuntansi'],
            ['degree_level' => 'D3', 'faculty' => 'Ekonomi & Bisnis', 'name' => 'Perbankan & Keuangan'],
            ['degree_level' => 'D3', 'faculty' => 'Teknik', 'name' => 'Teknik Informatika'],
            ['degree_level' => 'D3', 'faculty' => 'Teknik', 'name' => 'Teknik Mesin'],
            ['degree_level' => 'D3', 'faculty' => 'Teknik', 'name' => 'Teknik Sipil'],
            ['degree_level' => 'D3', 'faculty' => 'Kesehatan', 'name' => 'Keperawatan'],
            ['degree_level' => 'D3', 'faculty' => 'Kesehatan', 'name' => 'Kebidanan'],
            ['degree_level' => 'D3', 'faculty' => 'Kesehatan', 'name' => 'Farmasi'],
            ['degree_level' => 'D3', 'faculty' => 'Komunikasi', 'name' => 'Hubungan Masyarakat'],
            ['degree_level' => 'D3', 'faculty' => 'Pariwisata', 'name' => 'Perhotelan'],
            ['degree_level' => 'D4', 'faculty' => 'Teknik', 'name' => 'Teknologi Rekayasa Komputer'],
            ['degree_level' => 'D4', 'faculty' => 'Teknik', 'name' => 'Teknologi Rekayasa Otomotif'],
            ['degree_level' => 'D4', 'faculty' => 'Bisnis', 'name' => 'Bisnis Digital'],
            ['degree_level' => 'D4', 'faculty' => 'Desain', 'name' => 'Desain Grafis'],
            ['degree_level' => 'D4', 'faculty' => 'Pariwisata', 'name' => 'Manajemen Perhotelan'],
            ['degree_level' => 'S1', 'faculty' => 'Ekonomi & Bisnis', 'name' => 'Manajemen'],
            ['degree_level' => 'S1', 'faculty' => 'Ekonomi & Bisnis', 'name' => 'Akuntansi'],
            ['degree_level' => 'S1', 'faculty' => 'Ekonomi & Bisnis', 'name' => 'Ekonomi Syariah'],
            ['degree_level' => 'S1', 'faculty' => 'Ekonomi & Bisnis', 'name' => 'Bisnis Digital'],
            ['degree_level' => 'S1', 'faculty' => 'Teknik', 'name' => 'Teknik Informatika'],
            ['degree_level' => 'S1', 'faculty' => 'Teknik', 'name' => 'Sistem Informasi'],
            ['degree_level' => 'S1', 'faculty' => 'Teknik', 'name' => 'Teknologi Informasi'],
            ['degree_level' => 'S1', 'faculty' => 'Teknik', 'name' => 'Ilmu Komputer'],
            ['degree_level' => 'S1', 'faculty' => 'Teknik', 'name' => 'Teknik Industri'],
            ['degree_level' => 'S1', 'faculty' => 'Teknik', 'name' => 'Teknik Sipil'],
            ['degree_level' => 'S1', 'faculty' => 'Teknik', 'name' => 'Teknik Elektro'],
            ['degree_level' => 'S1', 'faculty' => 'Teknik', 'name' => 'Teknik Mesin'],
            ['degree_level' => 'S1', 'faculty' => 'Teknik', 'name' => 'Teknik Kimia'],
            ['degree_level' => 'S1', 'faculty' => 'Teknik', 'name' => 'Teknik Lingkungan'],
            ['degree_level' => 'S1', 'faculty' => 'Teknik', 'name' => 'Arsitektur'],
            ['degree_level' => 'S1', 'faculty' => 'Hukum', 'name' => 'Ilmu Hukum'],
            ['degree_level' => 'S1', 'faculty' => 'Ilmu Sosial & Politik', 'name' => 'Ilmu Komunikasi'],
            ['degree_level' => 'S1', 'faculty' => 'Ilmu Sosial & Politik', 'name' => 'Hubungan Internasional'],
            ['degree_level' => 'S1', 'faculty' => 'Ilmu Sosial & Politik', 'name' => 'Administrasi Publik'],
            ['degree_level' => 'S1', 'faculty' => 'Ilmu Sosial & Politik', 'name' => 'Administrasi Bisnis'],
            ['degree_level' => 'S1', 'faculty' => 'Psikologi', 'name' => 'Psikologi'],
            ['degree_level' => 'S1', 'faculty' => 'Sastra', 'name' => 'Sastra Inggris'],
            ['degree_level' => 'S1', 'faculty' => 'Sastra', 'name' => 'Sastra Jepang'],
            ['degree_level' => 'S1', 'faculty' => 'Sastra', 'name' => 'Sastra Indonesia'],
            ['degree_level' => 'S1', 'faculty' => 'Pendidikan', 'name' => 'PGSD'],
            ['degree_level' => 'S1', 'faculty' => 'Pendidikan', 'name' => 'Pendidikan Bahasa Inggris'],
            ['degree_level' => 'S1', 'faculty' => 'Pendidikan', 'name' => 'Pendidikan Matematika'],
            ['degree_level' => 'S1', 'faculty' => 'Pendidikan', 'name' => 'Pendidikan Ekonomi'],
            ['degree_level' => 'S1', 'faculty' => 'Pendidikan', 'name' => 'Pendidikan Guru PAUD'],
            ['degree_level' => 'S1', 'faculty' => 'Kedokteran', 'name' => 'Kedokteran'],
            ['degree_level' => 'S1', 'faculty' => 'Kedokteran', 'name' => 'Kedokteran Gigi'],
            ['degree_level' => 'S1', 'faculty' => 'Kesehatan', 'name' => 'Keperawatan'],
            ['degree_level' => 'S1', 'faculty' => 'Kesehatan', 'name' => 'Farmasi'],
            ['degree_level' => 'S1', 'faculty' => 'Kesehatan', 'name' => 'Kesehatan Masyarakat'],
            ['degree_level' => 'S1', 'faculty' => 'Kesehatan', 'name' => 'Gizi'],
            ['degree_level' => 'S1', 'faculty' => 'Pertanian', 'name' => 'Agroteknologi'],
            ['degree_level' => 'S1', 'faculty' => 'Pertanian', 'name' => 'Agribisnis'],
            ['degree_level' => 'S1', 'faculty' => 'Peternakan', 'name' => 'Peternakan'],
            ['degree_level' => 'S1', 'faculty' => 'Perikanan', 'name' => 'Ilmu Kelautan'],
            ['degree_level' => 'S1', 'faculty' => 'Perikanan', 'name' => 'Budidaya Perairan'],
            ['degree_level' => 'S1', 'faculty' => 'Agama Islam', 'name' => 'Pendidikan Agama Islam'],
            ['degree_level' => 'S1', 'faculty' => 'Agama Islam', 'name' => 'Hukum Keluarga Islam'],
            ['degree_level' => 'S1', 'faculty' => 'Agama Islam', 'name' => 'Manajemen Pendidikan Islam'],
            ['degree_level' => 'S1', 'faculty' => 'Seni & Desain', 'name' => 'Desain Komunikasi Visual'],
            ['degree_level' => 'S1', 'faculty' => 'Seni & Desain', 'name' => 'Desain Interior'],
            ['degree_level' => 'S1', 'faculty' => 'Seni & Desain', 'name' => 'Seni Musik'],
            ['degree_level' => 'S2', 'faculty' => 'Pascasarjana', 'name' => 'Manajemen'],
            ['degree_level' => 'S2', 'faculty' => 'Pascasarjana', 'name' => 'Akuntansi'],
            ['degree_level' => 'S2', 'faculty' => 'Pascasarjana', 'name' => 'Ilmu Hukum'],
            ['degree_level' => 'S2', 'faculty' => 'Pascasarjana', 'name' => 'Teknik Informatika'],
            ['degree_level' => 'S2', 'faculty' => 'Pascasarjana', 'name' => 'Sistem Informasi'],
            ['degree_level' => 'S2', 'faculty' => 'Pascasarjana', 'name' => 'Pendidikan Bahasa Inggris'],
            ['degree_level' => 'S2', 'faculty' => 'Pascasarjana', 'name' => 'Psikologi'],
            ['degree_level' => 'S2', 'faculty' => 'Pascasarjana', 'name' => 'Administrasi Publik'],
            ['degree_level' => 'S3', 'faculty' => 'Doktoral', 'name' => 'Ilmu Manajemen'],
            ['degree_level' => 'S3', 'faculty' => 'Doktoral', 'name' => 'Ilmu Ekonomi'],
            ['degree_level' => 'S3', 'faculty' => 'Doktoral', 'name' => 'Ilmu Hukum'],
            ['degree_level' => 'S3', 'faculty' => 'Doktoral', 'name' => 'Teknik Sipil'],
            ['degree_level' => 'S3', 'faculty' => 'Doktoral', 'name' => 'Pendidikan'],
            ['degree_level' => 'Profesi', 'faculty' => 'Kedokteran', 'name' => 'Profesi Dokter'],
            ['degree_level' => 'Profesi', 'faculty' => 'Kedokteran', 'name' => 'Profesi Dokter Gigi'],
            ['degree_level' => 'Profesi', 'faculty' => 'Kesehatan', 'name' => 'Profesi Ners'],
            ['degree_level' => 'Profesi', 'faculty' => 'Farmasi', 'name' => 'Profesi Apoteker'],
        ];
    }

    public static function options(): array
    {
        return collect(static::items())
            ->mapWithKeys(fn (array $item): array => [
                static::key($item) => $item['degree_level'].' - '.$item['name'].' ('.$item['faculty'].')',
            ])
            ->all();
    }

    public static function find(string $key): ?array
    {
        return collect(static::items())
            ->first(fn (array $item): bool => static::key($item) === $key);
    }

    public static function findByNameAndDegree(?string $name, ?string $degreeLevel): ?array
    {
        return collect(static::items())
            ->first(fn (array $item): bool => $item['name'] === $name && $item['degree_level'] === $degreeLevel);
    }

    public static function key(array $item): string
    {
        return Str::slug($item['degree_level'].' '.$item['faculty'].' '.$item['name']);
    }
}
