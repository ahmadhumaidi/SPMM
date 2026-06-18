<x-layouts.public title="Pemberkasan Mahasiswa">
    <section class="form-shell">
        <div class="form-copy">
            <p class="eyebrow">Pemberkasan tahap 2</p>
            <h1>Lengkapi data PDDIKTI.</h1>
            <p>Data ini dipakai untuk pemutakhiran calon mahasiswa dan penerbitan NIM. Pastikan sesuai dokumen resmi.</p>

            @if (session('status'))
                <div class="alert success">{{ session('status') }}</div>
            @endif
        </div>

        <form class="form-card" method="post" action="{{ route('student-profile.store', $lead->pemberkasan_token) }}">
            @csrf

            <label>Nama calon mahasiswa
                <input value="{{ $lead->full_name }}" disabled>
            </label>

            <label>NIK
                <input name="nik" value="{{ old('nik', $lead->studentProfile?->nik) }}" required>
                @error('nik') <small>{{ $message }}</small> @enderror
            </label>

            <label>NISN
                <input name="nisn" value="{{ old('nisn', $lead->studentProfile?->nisn) }}">
            </label>

            <label>Tempat lahir
                <input name="birth_place" value="{{ old('birth_place', $lead->studentProfile?->birth_place) }}" required>
            </label>

            <label>Tanggal lahir
                <input name="birth_date" type="date" value="{{ old('birth_date', optional($lead->studentProfile?->birth_date)->format('Y-m-d')) }}" required>
            </label>

            <label>Jenis kelamin
                <select name="gender" required>
                    <option value="">Pilih</option>
                    <option value="L" @selected(old('gender', $lead->studentProfile?->gender) === 'L')>Laki-laki</option>
                    <option value="P" @selected(old('gender', $lead->studentProfile?->gender) === 'P')>Perempuan</option>
                </select>
            </label>

            <label>Agama
                <input name="religion" value="{{ old('religion', $lead->studentProfile?->religion) }}">
            </label>

            <label>Nama ibu
                <input name="mother_name" value="{{ old('mother_name', $lead->studentProfile?->mother_name) }}" required>
            </label>

            <label>Nama ayah
                <input name="father_name" value="{{ old('father_name', $lead->studentProfile?->father_name) }}">
            </label>

            <label class="wide">Alamat
                <textarea name="address" required>{{ old('address', $lead->studentProfile?->address) }}</textarea>
            </label>

            <label>Provinsi
                <input name="province" value="{{ old('province', $lead->studentProfile?->province) }}" required>
            </label>

            <label>Kota/Kabupaten
                <input name="city" value="{{ old('city', $lead->studentProfile?->city) }}" required>
            </label>

            <label>Kecamatan
                <input name="district" value="{{ old('district', $lead->studentProfile?->district) }}" required>
            </label>

            <label>Desa/Kelurahan
                <input name="village" value="{{ old('village', $lead->studentProfile?->village) }}" required>
            </label>

            <label>Kode pos
                <input name="postal_code" value="{{ old('postal_code', $lead->studentProfile?->postal_code) }}">
            </label>

            <label>NPSN sekolah
                <input name="school_npsn" value="{{ old('school_npsn', $lead->studentProfile?->school_npsn) }}">
            </label>

            <label>Kewarganegaraan
                <input name="citizenship" value="{{ old('citizenship', $lead->studentProfile?->citizenship ?? 'Indonesia') }}" required>
            </label>

            <label>Nomor telepon
                <input name="phone" value="{{ old('phone', $lead->studentProfile?->phone ?? $lead->whatsapp_number) }}" required>
            </label>

            <label>Email
                <input name="email" type="email" value="{{ old('email', $lead->studentProfile?->email) }}">
            </label>

            <button class="button primary full" type="submit">Simpan pemberkasan</button>
        </form>
    </section>
</x-layouts.public>
