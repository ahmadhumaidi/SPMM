@php
    use Illuminate\Support\Facades\Storage;

    $lead = $account->lead;
    $data = $biodata;
    $avatarInitial = mb_substr($lead->full_name, 0, 1);
    $photoUrl = $data?->photo_path ? Storage::url($data->photo_path) : null;
    $selectionNumber = $data?->selection_number ?? 'Otomatis setelah disimpan';
    $nim = $lead->studentNumber?->nim ?? 'Belum diterbitkan admin';
    $financialStatus = \App\Support\FinancialStatusLabels::leadStatus($lead->loadMissing('studentPayments'));
    $developmentFee = $feeScheme?->building_fee ?? 0;
    $semesterFee = $feeScheme?->monthly_tuition_fee ?: ($feeScheme?->ukt_total ?? 0);
    $formFee = $feeScheme?->registration_fee ?? 0;

    $field = fn (string $name, $fallback = null) => old($name, $data?->{$name} ?? $fallback);
    $selectOptions = [
        'group' => ['D3 ke S1', 'SMU/Sederajat ke S1'],
        'religion' => ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'],
        'gender' => ['Laki-laki', 'Perempuan'],
        'information_source' => ['Tiktok', 'Instagram', 'Facebook', 'Youtube', 'Iklan', 'Teman', 'Saudara', 'Rekan Kerja', 'Brosur', 'Spanduk', 'Affiliator', 'Lainnya'],
    ];
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil Mahasiswa | Kampus Media</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { navy: '#071a3d', cyanx: '#06b6d4' },
                    boxShadow: { soft: '0 24px 80px rgba(15, 23, 42, .10)' },
                },
            },
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 text-slate-900 antialiased transition dark:bg-[#050b18] dark:text-slate-100">
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-24 top-0 h-96 w-96 rounded-full bg-cyan-300/30 blur-3xl dark:bg-cyan-500/10"></div>
        <div class="absolute right-0 top-36 h-[32rem] w-[32rem] rounded-full bg-indigo-400/20 blur-3xl dark:bg-indigo-600/10"></div>
    </div>

    <header class="sticky top-0 z-40 border-b border-white/70 bg-white/80 px-4 py-3 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-slate-950/70 sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <a href="{{ route('student-portal.dashboard') }}" class="flex items-center gap-3">
                @include('student-portal.partials.campus-logo', ['campus' => $lead->campus])
                <span>
                    <span class="block font-black text-navy dark:text-white">Profil Mahasiswa</span>
                    <span class="block text-xs font-bold text-slate-500 dark:text-slate-400">Terkoneksi sistem pusat</span>
                </span>
            </a>
            <div class="flex items-center gap-2">
                <button type="button" data-theme-toggle class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-slate-700 shadow-sm dark:bg-white/10 dark:text-white">
                    <i data-lucide="moon" class="h-5 w-5 dark:hidden"></i>
                    <i data-lucide="sun" class="hidden h-5 w-5 dark:block"></i>
                </button>
                <a href="{{ route('student-portal.dashboard') }}" class="rounded-2xl bg-navy px-4 py-3 text-sm font-black text-white dark:bg-white dark:text-navy">Dashboard</a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-navy via-[#0b2d63] to-cyan-700 p-6 text-white shadow-2xl shadow-cyan-900/15 lg:p-8">
            <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <p class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-black text-cyan-100">Biodata Pusat</p>
                    <h1 class="mt-5 text-3xl font-black sm:text-5xl">Lengkapi profil akademikmu.</h1>
                    <p class="mt-4 max-w-2xl leading-8 text-sky-50">Data ini tersimpan di sistem pusat dan dapat digunakan untuk pemberkasan, pembayaran, serta kebutuhan akademik.</p>
                </div>
                <div class="flex items-center gap-4 rounded-3xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt="Foto {{ $lead->full_name }}" class="h-20 w-20 rounded-3xl object-cover">
                    @else
                        <span class="grid h-20 w-20 place-items-center rounded-3xl bg-white text-3xl font-black text-navy">{{ $avatarInitial }}</span>
                    @endif
                    <div>
                        <strong class="block text-xl font-black">{{ $lead->full_name }}</strong>
                        <span class="mt-1 block text-sm font-semibold text-sky-100">{{ $lead->studyProgram->name }}</span>
                    </div>
                </div>
            </div>
        </section>

        @if (session('status'))
            <div class="mt-6 rounded-3xl border border-green-200 bg-green-50 p-4 font-bold text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-200">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mt-6 rounded-3xl border border-red-200 bg-red-50 p-4 font-bold text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-200">
                <p>Mohon periksa kembali isian berikut:</p>
                <ul class="mt-2 list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('student-portal.profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf

            <x-student-profile-section title="1. Data Pendaftaran" icon="file-user">
                <x-student-input name="name" label="Nama" :value="$field('name', $lead->full_name)" required />
                <x-student-input name="selection_number_display" label="No. Seleksi" :value="$selectionNumber" readonly />
                <x-student-input name="student_number_display" label="NIM" :value="$nim" readonly />
                <label class="grid gap-2 text-sm font-bold">
                    Program Studi
                    <select name="study_program_id" class="h-12 rounded-2xl border border-slate-200 bg-white px-4 outline-none focus:border-cyanx focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/10">
                        @foreach ($studyPrograms as $program)
                            <option value="{{ $program->id }}" @selected((string) $field('study_program_id', $lead->study_program_id) === (string) $program->id)>
                                {{ $program->degree_level ? $program->degree_level.' - ' : '' }}{{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <x-student-select name="group" label="Kelompok" :options="$selectOptions['group']" :value="$field('group')" />
                <label class="grid gap-2 text-sm font-bold">
                    Program Perkuliahan
                    <select name="class_track_id" class="h-12 rounded-2xl border border-slate-200 bg-white px-4 outline-none focus:border-cyanx focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/10">
                        @foreach ($classTracks as $track)
                            <option value="{{ $track->id }}" @selected((string) $field('class_track_id', $lead->class_track_id) === (string) $track->id)>{{ $track->name }}</option>
                        @endforeach
                    </select>
                </label>
                <x-student-input name="financial_status_display" label="Status Keuangan" :value="$financialStatus" readonly />
                <label class="grid gap-2 text-sm font-bold">
                    Sumber Informasi
                    <select name="information_source" data-information-source class="h-12 rounded-2xl border border-slate-200 bg-white px-4 outline-none focus:border-cyanx focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/10">
                        <option value="">Pilih sumber informasi</option>
                        @foreach ($selectOptions['information_source'] as $option)
                            <option value="{{ $option }}" @selected($field('information_source') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
                <div data-affiliator-code class="{{ $field('information_source') === 'Affiliator' ? '' : 'hidden' }}">
                    <x-student-input name="affiliator_code" label="Kode Affiliator" :value="$field('affiliator_code')" />
                </div>
                <div data-information-other class="{{ $field('information_source') === 'Lainnya' ? '' : 'hidden' }}">
                    <x-student-input name="information_source_other" label="Sumber Informasi Lainnya" :value="$field('information_source_other')" />
                </div>
                <label class="grid gap-2 text-sm font-bold md:col-span-2">
                    Foto
                    <input type="file" name="photo" accept="image/*" class="rounded-2xl border border-slate-200 bg-white p-3 file:mr-4 file:rounded-xl file:border-0 file:bg-navy file:px-4 file:py-2 file:font-black file:text-white dark:border-white/10 dark:bg-white/10">
                </label>
            </x-student-profile-section>

            <x-student-profile-section title="2. Biodata Mahasiswa" icon="id-card">
                <x-student-input name="birth_place" label="Tempat Lahir" :value="$field('birth_place')" />
                <x-student-input type="date" name="birth_date" label="Tanggal Lahir" :value="old('birth_date', $data?->birth_date?->format('Y-m-d'))" />
                <x-student-select name="religion" label="Agama" :options="$selectOptions['religion']" :value="$field('religion')" />
                <x-student-select name="gender" label="Gender" :options="$selectOptions['gender']" :value="$field('gender')" />
                <x-student-textarea name="address" label="Alamat KTP" :value="$field('address')" />
                <x-student-textarea name="mailing_address" label="Alamat Domisili saat ini" :value="$field('mailing_address')" />
                <x-student-input name="city" label="Kota/Kabupaten" :value="$field('city')" />
                <x-student-input name="district" label="Kecamatan" :value="$field('district')" />
                <x-student-input name="village" label="Kelurahan" :value="$field('village')" />
                <x-student-input name="postal_code" label="Kode Pos" :value="$field('postal_code')" />
                <x-student-input name="rt_rw" label="RT/RW" :value="$field('rt_rw')" />
                <x-student-input name="phone" label="No. Telepon" :value="$field('phone')" />
                <x-student-input name="mobile_phone" label="No. Handphone" :value="$field('mobile_phone')" />
                <x-student-input type="email" name="email" label="Email" :value="$field('email', $account->email)" />
                <x-student-input name="whatsapp_number" label="No. WA" :value="$field('whatsapp_number', $lead->whatsapp_number)" />
                <x-student-input name="identity_number" label="NIK KTP Mahasiswa" :value="$field('identity_number')" />
            </x-student-profile-section>

            <x-student-profile-section title="3. Data Keluarga" icon="users">
                <x-student-input name="mother_name" label="Nama Ibu Kandung" :value="$field('mother_name')" />
                <x-student-input name="family_name" label="Nama kontak keluarga" :value="$field('family_name')" />
                <x-student-input name="family_number" label="No HP/WA kontak keluarga" :value="$field('family_number')" />
                <x-student-input name="family_relationship" label="Hubungan" :value="$field('family_relationship')" />
            </x-student-profile-section>

            <x-student-profile-section title="4. Data Pekerjaan" icon="briefcase-business">
                <x-student-input name="company_name" label="Perusahaan/Instansi" :value="$field('company_name')" />
                <x-student-input name="job_title" label="Jabatan" :value="$field('job_title')" />
                <x-student-textarea name="company_address" label="Alamat Perusahaan" :value="$field('company_address')" />
                <x-student-input name="company_city" label="Kota/Kabupaten" :value="$field('company_city')" />
                <x-student-input name="company_postal_code" label="Kode Pos" :value="$field('company_postal_code')" />
                <x-student-input name="company_phone" label="No. Telepon" :value="$field('company_phone')" />
            </x-student-profile-section>

            <x-student-profile-section title="6. Data Biaya" icon="wallet-cards">
                <x-student-input type="number" name="development_contribution_display" label="Sumbangan Pengembangan" :value="$developmentFee" prefix="Rp" readonly />
                <x-student-input type="number" name="semester_education_contribution_display" label="Sumbangan Pendidikan Per Semester" :value="$semesterFee" prefix="Rp" readonly />
                <x-student-input type="number" name="form_fee_display" label="Formulir" :value="$formFee" prefix="Rp" readonly />
            </x-student-profile-section>

            <div class="sticky bottom-4 z-20 rounded-[2rem] border border-white/70 bg-white/90 p-4 shadow-2xl shadow-slate-300/40 backdrop-blur dark:border-white/10 dark:bg-slate-950/80">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400">Pastikan data sudah benar sebelum disimpan ke sistem pusat.</p>
                    <button class="rounded-2xl bg-gradient-to-r from-navy to-cyanx px-6 py-4 font-black text-white shadow-lg shadow-cyan-500/20 transition hover:-translate-y-1">
                        Simpan Biodata
                    </button>
                </div>
            </div>
        </form>
    </main>

    <script>
        const root = document.documentElement;
        if (localStorage.getItem('student-theme') === 'dark') root.classList.add('dark');
        document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
            root.classList.toggle('dark');
            localStorage.setItem('student-theme', root.classList.contains('dark') ? 'dark' : 'light');
        });

        const informationSource = document.querySelector('[data-information-source]');
        const affiliatorCode = document.querySelector('[data-affiliator-code]');
        const informationOther = document.querySelector('[data-information-other]');

        function syncInformationSourceFields() {
            if (! informationSource) return;

            affiliatorCode?.classList.toggle('hidden', informationSource.value !== 'Affiliator');
            informationOther?.classList.toggle('hidden', informationSource.value !== 'Lainnya');
        }

        informationSource?.addEventListener('change', syncInformationSourceFields);
        syncInformationSourceFields();
        lucide.createIcons();
    </script>
</body>
</html>


