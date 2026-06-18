# Arsitektur Sistem Terpisah: SPMM, SIAKAD, dan LMS

Tanggal: 2026-06-02
Status: Keputusan arsitektur

## Prinsip Utama

SPMM, SIAKAD, dan LMS adalah tiga sistem berbeda yang saling terintegrasi.

SPMM bukan induk aplikasi akademik. SPMM berperan sebagai pusat PMB/CRM sampai calon mahasiswa siap menjadi mahasiswa aktif.

## Pembagian Sistem

### 1. SPMM

Domain contoh:

- `spmm.maheramedia.com`
- `kampusmedia.cloud`
- `*.kampusmedia.cloud`

Tanggung jawab:

- Portal Kampus Media.
- Web kampus mitra.
- Pendaftaran calon mahasiswa.
- CRM dan follow-up PMB.
- Invoice pendaftaran dan payment gateway.
- Pemberkasan awal.
- Verifikasi data calon mahasiswa.
- Penerbitan NIM oleh admin pusat.
- Export/sinkron data mahasiswa aktif ke sistem akademik.

SPMM mengirim data ke SIAKAD setelah mahasiswa valid dan aktif.

### 2. SIAKAD Kampus

Domain contoh:

- `siakad.maheramedia.com`
- `akademik.kampus-a.ac.id`

Tanggung jawab:

- Biodata akademik mahasiswa aktif.
- Tahun akademik.
- Mata kuliah.
- Kelas dan jadwal.
- KRS.
- KHS.
- Nilai.
- Transkrip.
- Status akademik mahasiswa.
- Kalender akademik.
- Dosen dan pengampu mata kuliah.

SIAKAD menerima data mahasiswa aktif dari SPMM.

### 3. LMS Kampus

Domain contoh:

- `lms.maheramedia.com`
- `elearning.kampus-a.ac.id`

Tanggung jawab:

- Materi kuliah.
- Modul pembelajaran.
- Tugas kuliah.
- Pengumpulan tugas.
- Feedback/nilai tugas.
- Presensi pembelajaran online.
- Aktivitas belajar mahasiswa.
- Konten video/link/file pembelajaran.

LMS mengambil data kelas dan peserta dari SIAKAD.

## Alur Integrasi Data

1. Calon mahasiswa daftar di SPMM.
2. SPMM membuat invoice dan memproses pembayaran pendaftaran.
3. Mahasiswa melengkapi biodata dan berkas.
4. Admin SPMM memverifikasi dan mengisi NIM.
5. SPMM mengirim mahasiswa aktif ke SIAKAD.
6. Admin akademik membuat tahun akademik, mata kuliah, kelas, dan KRS di SIAKAD.
7. LMS mengambil data kelas dan peserta dari SIAKAD.
8. Dosen/admin LMS membuat materi dan tugas berdasarkan kelas.
9. Mahasiswa login ke portal akademik/LMS untuk kuliah, tugas, dan materi.

## Integrasi Minimal MVP

### SPMM ke SIAKAD

Data yang dikirim:

- `student_uuid`
- `lead_id`
- `campus_id`
- `nim`
- `name`
- `email`
- `whatsapp_number`
- `study_program_id`
- `class_track_id`
- `student_status`
- `verified_at`

Trigger:

- Saat status mahasiswa berubah menjadi `mahasiswa_aktif`.

### SIAKAD ke LMS

Data yang dikirim/diambil:

- `academic_term`
- `course`
- `course_class`
- `lecturer`
- `student_enrollment`
- `schedule`

Trigger:

- Saat KRS disetujui.
- Saat kelas/jadwal berubah.

### LMS ke SIAKAD

Data balik:

- Rekap kehadiran pembelajaran.
- Nilai tugas.
- Aktivitas belajar.

Catatan:

- Nilai final tetap diputuskan di SIAKAD.
- LMS hanya menyumbang komponen nilai/aktivitas.

## Mekanisme Teknis

Rekomendasi produksi:

- Masing-masing sistem punya database sendiri.
- Integrasi memakai API token/server-to-server token.
- Gunakan webhook/event untuk perubahan penting.
- Gunakan `external_id` atau `uuid` agar data bisa disinkronkan lintas sistem.
- Jangan mengandalkan `id` auto-increment antar database.

Endpoint awal yang disarankan:

- `POST /api/integrations/students` di SIAKAD, dipanggil oleh SPMM.
- `POST /api/integrations/classes` di LMS, dipanggil oleh SIAKAD.
- `POST /api/integrations/enrollments` di LMS, dipanggil oleh SIAKAD.
- `POST /api/integrations/learning-scores` di SIAKAD, dipanggil oleh LMS.

## Catatan Implementasi Lokal

Prototype SIAKAD dan LMS yang sudah dibuat di workspace SPMM saat ini diperlakukan sebagai rancangan awal model data dan UI.

Tahap berikutnya adalah memecahnya menjadi aplikasi mandiri:

1. `SPMM` tetap menjadi aplikasi PMB/CRM.
2. `SIAKAD` dibuat sebagai project Laravel terpisah.
3. `LMS` dibuat sebagai project Laravel terpisah.
4. Data yang sekarang berada di prototype dipindahkan ke migration/project masing-masing.
5. SPMM hanya menyimpan status integrasi dan referensi eksternal, bukan seluruh operasional akademik/LMS.
