<section class="simple-campus-hero">
    <p class="eyebrow">Detail Kampus</p>
    <div class="simple-campus-title">
        <div>
            <h1>{{ $campus->name }}</h1>
            <p>{{ $campus->address ?: 'Alamat kampus belum diisi.' }}</p>
        </div>
        @if ($campus->logo_path)
            <img src="{{ asset('storage/'.$campus->logo_path) }}" alt="Logo {{ $campus->name }}" width="110" height="110" decoding="async">
        @endif
    </div>
    <a class="button primary" href="{{ route('registration.create', ['kampus' => $campus->slug]) }}">Daftar ke kampus ini</a>
</section>

<section class="simple-campus-content">
    <div>
        <h2>Program studi</h2>
        <div class="simple-list">
            @forelse ($campus->studyPrograms as $program)
                <div class="simple-row">
                    <strong>{{ $program->name }}</strong>
                    <span>{{ $program->degree_level ?: 'Jenjang belum diisi' }}{{ $program->accreditation ? ' - Akreditasi '.$program->accreditation : '' }}</span>
                </div>
            @empty
                <div class="simple-row">
                    <strong>Belum ada program studi</strong>
                    <span>Silakan lengkapi data dari dashboard admin.</span>
                </div>
            @endforelse
        </div>
    </div>

    <div>
        <h2>Biaya awal</h2>
        <div class="simple-list">
            @forelse ($campus->feeSchemes as $fee)
                <div class="simple-row">
                    <strong>Rp {{ number_format($fee->total_initial_payment, 0, ',', '.') }}</strong>
                    <span>Pendaftaran Rp {{ number_format($fee->registration_fee, 0, ',', '.') }} - SPP Rp {{ number_format($fee->monthly_tuition_fee, 0, ',', '.') }}/bulan</span>
                </div>
            @empty
                <div class="simple-row">
                    <strong>Biaya belum tersedia</strong>
                    <span>Silakan lengkapi Fee Scheme dari dashboard admin.</span>
                </div>
            @endforelse
        </div>
    </div>
</section>
