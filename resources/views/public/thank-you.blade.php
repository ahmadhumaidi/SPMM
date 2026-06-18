<x-layouts.public title="Pendaftaran Diterima">
    <section class="status-page">
        <div class="status-card">
            <p class="eyebrow">Pendaftaran diterima</p>
            <h1>Terima kasih, {{ $lead->full_name }}.</h1>
            <p>Invoice pendaftaran sudah dibuat. Simpan nomor invoice ini untuk konfirmasi ke tim PMB.</p>

            <div class="email-verification-box">
                <strong>Cek email verifikasi akun mahasiswa</strong>
                <p>
                    Kami sudah mengirim email ke <b>{{ $lead->email }}</b>. Buka email tersebut untuk verifikasi, lalu gunakan email dan password sementara yang ada di dalamnya untuk login ke akun mahasiswa.
                </p>
                <ol>
                    <li>Buka inbox email atau folder spam/promosi.</li>
                    <li>Klik link verifikasi email.</li>
                    <li>Login ke akun mahasiswa menggunakan email dan password sementara.</li>
                    <li>Setelah login, kamu bisa upload berkas, lengkapi biodata, cek pembayaran, dan cetak kwitansi.</li>
                </ol>
            </div>

            <dl class="invoice-box">
                <div>
                    <dt>Nomor invoice</dt>
                    <dd>{{ $invoice->invoice_number }}</dd>
                </div>
                <div>
                    <dt>Total pembayaran</dt>
                    <dd>Rp {{ number_format($invoice->amount, 0, ',', '.') }}</dd>
                </div>
                <div>
                    <dt>Kedaluwarsa</dt>
                    <dd>{{ $invoice->expires_at->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</dd>
                </div>
            </dl>

            <div class="hero-actions">
                @if ($invoice->payment_url)
                    <a class="button primary" href="{{ $invoice->payment_url }}">Bayar sekarang</a>
                @endif
                @if (app()->environment('local'))
                    <a class="button primary" href="{{ route('registration.local-email', $lead) }}" target="_blank">Lihat email verifikasi lokal</a>
                @endif
                <a class="button secondary" href="{{ route('student-portal.login') }}">Login akun mahasiswa</a>
                <a class="button secondary" href="{{ route('home') }}">Kembali ke portal</a>
            </div>
        </div>
    </section>
</x-layouts.public>
