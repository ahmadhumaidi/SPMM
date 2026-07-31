<x-layouts.public title="Pendaftaran Diterima" :campus="$lead->campus" :noindex="true">
    <section class="status-page">
        <div class="status-card">
            <p class="eyebrow">Pendaftaran diterima</p>
            <h1>Terima kasih, {{ $lead->full_name }}.</h1>
            <p>Invoice pendaftaran sudah dibuat. Simpan nomor invoice ini untuk konfirmasi ke tim PMB.</p>

            @if (session('status'))
                <div class="status-banner success">{{ session('status') }}</div>
            @endif

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
                <button type="button" class="button primary" onclick="openPaymentModal()">Bayar sekarang</button>
                @if ($invoice->payment_url)
                    <a class="button primary" href="{{ $invoice->payment_url }}" target="_blank" rel="noopener">Bayar QRIS</a>
                @endif
                @if (app()->environment('local'))
                    <a class="button primary" href="{{ route('registration.local-email', $lead) }}" target="_blank">Lihat email verifikasi lokal</a>
                @endif
                <a class="button secondary" href="{{ route('student-portal.login') }}">Login akun mahasiswa</a>
                <a class="button secondary" href="{{ route('home') }}">Kembali ke portal</a>
            </div>
        </div>
    </section>

    <div id="payment-modal-overlay" class="modal-overlay">
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="payment-modal-heading">
            <button type="button" class="modal-close" aria-label="Tutup" onclick="closePaymentModal()">&times;</button>

            <p class="payment-dialog__eyebrow">Bayar Sekarang</p>
            <h2 id="payment-modal-heading" class="payment-dialog__heading">Transfer ke Rekening Mahera Media</h2>

            <div class="payment-box">
                <p class="payment-box__amount-label">Total yang harus dibayar</p>
                <p class="payment-box__amount">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</p>

                <div class="payment-meta">
                    <div>
                        <dt>Bank</dt>
                        <dd>Mandiri</dd>
                    </div>
                    <div>
                        <dt>Atas nama</dt>
                        <dd>Mahera Media</dd>
                    </div>
                </div>
                <dl class="account-number">
                    <dt>Nomor rekening</dt>
                    <dd>1440029473219</dd>
                </dl>
            </div>

            <p class="payment-dialog__note">Setelah transfer, upload bukti transfer di bawah ini. Tidak perlu login dulu &mdash; tim keuangan akan memverifikasi secara manual.</p>

            <form method="POST" action="{{ route('registration.payment-proof.upload', $lead) }}" enctype="multipart/form-data" class="payment-proof-form">
                @csrf
                <input type="hidden" name="token" value="{{ $lead->payment_proof_token }}">
                <label>
                    <span>Upload bukti transfer</span>
                    <input type="file" name="proof_path" accept=".jpg,.jpeg,.png,.pdf" required>
                    <small>Format JPG, PNG, atau PDF. Maksimal 5 MB.</small>
                </label>
                @error('proof_path')
                    <p class="field-error">{{ $message }}</p>
                @enderror
                <div class="dialog-actions">
                    <button type="button" class="button secondary" onclick="closePaymentModal()">Tutup</button>
                    <button type="submit" class="button primary">Kirim Bukti Transfer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPaymentModal() {
            document.getElementById('payment-modal-overlay').classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }

        function closePaymentModal() {
            document.getElementById('payment-modal-overlay').classList.remove('is-open');
            document.body.style.overflow = '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            var overlay = document.getElementById('payment-modal-overlay');

            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) {
                    closePaymentModal();
                }
            });

            var shouldOpen = @json($errors->has('proof_path'));
            if (shouldOpen) {
                openPaymentModal();
            }
        });
    </script>
</x-layouts.public>
