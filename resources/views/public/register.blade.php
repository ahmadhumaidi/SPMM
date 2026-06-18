@php
    $campusCount = $campuses->count();
    $programCount = $campuses->sum(fn ($campus) => $campus->studyPrograms->count());
    $trackCount = $campuses->sum(fn ($campus) => $campus->classTracks->count());
@endphp

<x-layouts.public title="Daftar Mahasiswa Baru">
    <section class="relative overflow-hidden bg-slate-950 px-5 py-12 text-white sm:px-8 lg:px-16 lg:py-16">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_15%_10%,rgba(6,182,212,.32),transparent_34%),radial-gradient(circle_at_90%_0%,rgba(245,158,11,.24),transparent_28%)]"></div>
        <div class="relative mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1fr_29rem] lg:items-center">
            <div>
                <p class="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-black text-cyan-100">Pendaftaran Mahasiswa Baru</p>
                <h1 class="mt-5 max-w-4xl text-4xl font-black leading-tight tracking-normal sm:text-6xl">Mulai pendaftaran kuliah dengan alur yang lebih mudah.</h1>
                <p class="mt-5 max-w-2xl text-lg font-semibold leading-8 text-sky-100">Pilih kampus, program studi, dan program perkuliahan. Setelah submit, sistem otomatis membuat invoice dan mengirim akun mahasiswa ke email kamu.</p>

                <div class="mt-7 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-3xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <strong class="block text-3xl font-black text-white">{{ $campusCount }}</strong>
                        <span class="mt-1 block text-sm font-bold text-sky-100">Kampus aktif</span>
                    </div>
                    <div class="rounded-3xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <strong class="block text-3xl font-black text-white">{{ $programCount }}</strong>
                        <span class="mt-1 block text-sm font-bold text-sky-100">Program studi</span>
                    </div>
                    <div class="rounded-3xl border border-white/15 bg-white/10 p-4 backdrop-blur">
                        <strong class="block text-3xl font-black text-white">{{ $trackCount }}</strong>
                        <span class="mt-1 block text-sm font-bold text-sky-100">Program kuliah</span>
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/15 bg-white/10 p-5 shadow-2xl shadow-cyan-950/30 backdrop-blur">
                <div class="rounded-[1.5rem] bg-white p-5 text-slate-900">
                    <p class="text-sm font-black uppercase tracking-wide text-cyan-700">Setelah submit</p>
                    <div class="mt-4 grid gap-3">
                        @foreach ([['1', 'Invoice otomatis dibuat'], ['2', 'Email verifikasi dan password dikirim'], ['3', 'Login akun mahasiswa'], ['4', 'Lengkapi biodata dan pemberkasan']] as $step)
                            <div class="flex gap-3 rounded-2xl bg-slate-50 p-4">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-slate-950 to-cyan-700 text-sm font-black text-white">{{ $step[0] }}</span>
                                <strong class="self-center text-sm font-black">{{ $step[1] }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-slate-100 px-5 py-8 sm:px-8 lg:px-16 lg:py-12">
        <div class="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[.72fr_1.28fr]">
            <aside class="space-y-5">
                <div class="rounded-[2rem] border border-white/80 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,.08)]">
                    <p class="text-sm font-black uppercase tracking-wide text-cyan-700">Tahap 1</p>
                    <h2 class="mt-2 text-3xl font-black leading-tight text-slate-950">Isi data awal untuk membuat akun dan invoice PMB.</h2>
                    <p class="mt-4 font-semibold leading-7 text-slate-600">Pastikan email aktif karena password sementara dan link verifikasi akun mahasiswa dikirim ke email tersebut.</p>
                </div>

                <div class="grid gap-4">
                    @foreach ([['Kampus dan prodi terhubung', 'Pilihan program studi otomatis mengikuti kampus yang kamu pilih.'], ['Program perkuliahan fleksibel', 'Pilih kelas karyawan, full online, hybrid, atau RPL sesuai data kampus.'], ['Pembayaran lebih jelas', 'Invoice dan tagihan akan masuk ke sistem pusat dan akun mahasiswa.']] as $info)
                        <article class="rounded-3xl border border-white/80 bg-white p-5 shadow-sm">
                            <strong class="block text-slate-950">{{ $info[0] }}</strong>
                            <p class="mt-2 text-sm font-semibold leading-6 text-slate-600">{{ $info[1] }}</p>
                        </article>
                    @endforeach
                </div>
            </aside>

            <form class="rounded-[2rem] border border-white/80 bg-white p-5 shadow-[0_24px_80px_rgba(15,23,42,.10)] sm:p-7" method="post" action="{{ route('registration.store') }}">
                @csrf
                <input type="hidden" name="referral_code" value="{{ old('referral_code', $referralCode ?? request('ref')) }}">

                <div class="mb-6 flex flex-col gap-3 border-b border-slate-100 pb-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-black uppercase tracking-wide text-cyan-700">Form Pendaftaran</p>
                        <h2 class="mt-1 text-2xl font-black text-slate-950">Data calon mahasiswa</h2>
                    </div>
                    <span class="rounded-full bg-cyan-50 px-4 py-2 text-sm font-black text-cyan-700">Online 24 jam</span>
                </div>

                @if ($errors->any())
                    <div class="mb-5 rounded-3xl border border-red-200 bg-red-50 p-4 font-bold text-red-700">
                        <strong>Periksa lagi data pendaftaran.</strong>
                        <ul class="mt-2 list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (filled(old('referral_code', $referralCode ?? request('ref'))))
                    <div class="mb-5 rounded-3xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-black text-amber-800">Kode referral aktif</p>
                        <p class="mt-1 text-sm font-semibold text-amber-700">{{ old('referral_code', $referralCode ?? request('ref')) }}</p>
                    </div>
                @endif

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="grid gap-2 text-sm font-black text-slate-800">
                        Kampus tujuan
                        <select name="campus_id" id="campus_id" required class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100">
                            <option value="">Pilih kampus</option>
                            @foreach ($campuses as $campus)
                                <option value="{{ $campus->id }}" @selected(old('campus_id', $selectedCampus?->id) == $campus->id)>{{ $campus->name }}</option>
                            @endforeach
                        </select>
                        @error('campus_id') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="grid gap-2 text-sm font-black text-slate-800">
                        Program studi
                        <select name="study_program_id" id="study_program_id" required class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100 disabled:cursor-not-allowed disabled:opacity-60">
                            <option value="">Pilih program studi</option>
                            @foreach ($campuses as $campus)
                                @foreach ($campus->studyPrograms as $program)
                                    <option value="{{ $program->id }}" data-campus="{{ $campus->id }}" @selected(old('study_program_id') == $program->id)>{{ $program->degree_level ? $program->degree_level.' - ' : '' }}{{ $program->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('study_program_id') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="grid gap-2 text-sm font-black text-slate-800">
                        Program perkuliahan
                        <select name="class_track_id" id="class_track_id" required class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100 disabled:cursor-not-allowed disabled:opacity-60">
                            <option value="">Pilih program perkuliahan</option>
                            @foreach ($campuses as $campus)
                                @foreach ($campus->classTracks as $track)
                                    <option value="{{ $track->id }}" data-campus="{{ $campus->id }}" @selected(old('class_track_id') == $track->id)>{{ $track->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('class_track_id') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="grid gap-2 text-sm font-black text-slate-800">
                        Nama lengkap
                        <input name="full_name" value="{{ old('full_name') }}" required class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100" placeholder="Nama sesuai identitas">
                        @error('full_name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="grid gap-2 text-sm font-black text-slate-800">
                        Nomor WhatsApp
                        <input name="whatsapp_number" value="{{ old('whatsapp_number') }}" placeholder="08xxxxxxxxxx" required class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100">
                        @error('whatsapp_number') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="grid gap-2 text-sm font-black text-slate-800">
                        Email mahasiswa
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100">
                        @error('email') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="grid gap-2 text-sm font-black text-slate-800">
                        Asal sekolah
                        <input name="origin_school" value="{{ old('origin_school') }}" class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100" placeholder="Nama sekolah terakhir">
                    </label>

                    <label class="grid gap-2 text-sm font-black text-slate-800">
                        Tahun lulus
                        <input name="graduation_year" type="number" value="{{ old('graduation_year') }}" min="1970" max="{{ now()->year + 1 }}" class="h-13 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 font-bold outline-none transition focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100" placeholder="{{ now()->year }}">
                    </label>
                </div>

                <div class="mt-6 rounded-3xl border border-cyan-100 bg-cyan-50 p-4">
                    <strong class="block text-sm font-black text-slate-950">Penting</strong>
                    <p class="mt-1 text-sm font-semibold leading-6 text-slate-600">Setelah klik kirim, cek email untuk verifikasi akun dan password login mahasiswa. Jika tidak ada di inbox, cek folder spam atau promosi.</p>
                </div>

                <button class="mt-6 flex min-h-14 w-full items-center justify-center rounded-2xl bg-gradient-to-r from-slate-950 to-cyan-700 px-6 py-4 text-base font-black text-white shadow-[0_20px_60px_rgba(6,182,212,.22)] transition hover:-translate-y-1" type="submit">
                    Kirim pendaftaran
                </button>
            </form>
        </div>
    </section>

    <script>
        const campusSelect = document.getElementById('campus_id');
        const dependentSelects = [
            document.getElementById('study_program_id'),
            document.getElementById('class_track_id'),
        ];

        function syncDependentOptions() {
            const campusId = campusSelect.value;

            dependentSelects.forEach((select) => {
                const selectedOption = select.selectedOptions[0];

                Array.from(select.options).forEach((option) => {
                    if (! option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = option.dataset.campus !== campusId;
                });

                if (selectedOption && selectedOption.value && selectedOption.dataset.campus !== campusId) {
                    select.value = '';
                }

                select.disabled = ! campusId;
            });
        }

        campusSelect.addEventListener('change', syncDependentOptions);
        syncDependentOptions();
    </script>
</x-layouts.public>
