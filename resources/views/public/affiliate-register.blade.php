@php
    $seoTitle = 'Daftar Affiliate';
    $seoDescription = 'Form pendaftaran affiliate umum Kampus Media untuk non mahasiswa.';
    $canonicalUrl = request()->getHost() === 'affiliate.kampus.media' ? url('/daftar') : url('/affiliate/daftar');
    $affiliateStoreUrl = request()->getHost() === 'affiliate.kampus.media' ? url('/daftar') : route('affiliate.store');
    $affiliateHomeUrl = request()->getHost() === 'affiliate.kampus.media' ? rtrim(url('/'), '/').'/' : route('affiliate.public');
    $termsDocumentUrl = asset('documents/sk-ketentuan-fee-dan-insentif-affiliator-mahera-media-2026.docx');
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }} | Kampus Media</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { navy: '#071a3d', gold: '#f59e0b', tealx: '#0f766e' },
                    boxShadow: { soft: '0 24px 70px rgba(15, 23, 42, .12)' },
                },
            },
        }
    </script>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white px-4 py-3 sm:px-6 lg:px-8">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4">
            <a href="{{ $affiliateHomeUrl }}" class="flex items-center gap-3">
                <img src="/images/social/logo%20kampus%20media.png" alt="Kampus Media" class="h-11 w-auto">
                <span>
                    <strong class="block text-base font-black text-navy">Kampus Media</strong>
                    <small class="block text-xs font-bold text-slate-500">Form Affiliate Umum</small>
                </span>
            </a>
            <a href="{{ $affiliateHomeUrl }}" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-black text-navy">Kembali</a>
        </div>
    </header>

    <main class="px-4 py-10 sm:px-6 lg:px-8">
        <section class="mx-auto max-w-5xl">
            <div class="mb-8 max-w-3xl">
                <p class="text-sm font-black uppercase tracking-wide text-tealx">Affiliate Umum</p>
                <h1 class="mt-3 text-3xl font-black text-navy sm:text-5xl">Daftar affiliate publik Kampus Media.</h1>
                <p class="mt-4 leading-8 text-slate-600">Isi data sesuai KTP dan rekening pribadi. Setelah submit, sistem mengirim email verifikasi untuk mengaktifkan akun affiliate.</p>
            </div>

            @if (session('status'))
                <div data-status-modal class="fixed inset-0 z-50 grid place-items-center bg-slate-950/60 px-4 py-8 backdrop-blur-sm">
                    <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl shadow-slate-950/20">
                        <span class="grid h-14 w-14 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <i data-lucide="mail-check" class="h-7 w-7"></i>
                        </span>
                        <h2 class="mt-5 text-2xl font-black text-navy">{{ session('affiliate_activated') ? 'Akun affiliate aktif.' : 'Pendaftaran berhasil dikirim.' }}</h2>
                        <p class="mt-3 font-semibold leading-7 text-slate-600">{{ session('status') }}</p>
                        <button type="button" data-status-modal-close class="mt-6 inline-flex w-full items-center justify-center rounded-full bg-tealx px-6 py-4 font-black text-white">
                            Mengerti
                        </button>
                    </div>
                </div>
            @endif

            @if (session('affiliate_activated'))
                <article class="rounded-3xl border border-emerald-200 bg-white p-6 shadow-soft sm:p-8">
                    <span class="grid h-14 w-14 place-items-center rounded-2xl bg-emerald-100 text-emerald-700">
                        <i data-lucide="badge-check" class="h-7 w-7"></i>
                    </span>
                    <h2 class="mt-5 text-2xl font-black text-navy">Akun affiliate sudah aktif.</h2>
                    <p class="mt-3 leading-8 text-slate-600">Gunakan kode referral berikut untuk membagikan form pendaftaran Kampus Media.</p>
                    <div class="mt-5 grid gap-3 rounded-3xl bg-slate-50 p-4">
                        <p class="text-sm font-black uppercase tracking-wide text-tealx">Kode referral</p>
                        <strong class="text-3xl font-black text-navy">{{ session('referral_code') }}</strong>
                        <p class="break-all text-sm font-bold text-slate-600" data-referral-link>{{ session('referral_link') }}</p>
                    </div>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ session('referral_link') }}" class="inline-flex items-center gap-2 rounded-full bg-tealx px-6 py-4 font-black text-white shadow-lg shadow-teal-700/20">
                            <i data-lucide="external-link" class="h-5 w-5"></i>
                            Buka Link Referral
                        </a>
                        @if (session('dashboard_url'))
                            <a href="{{ session('dashboard_url') }}" class="inline-flex items-center gap-2 rounded-full bg-navy px-6 py-4 font-black text-white shadow-lg shadow-slate-700/20">
                                <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
                                Buka Dashboard
                            </a>
                        @endif
                        <button type="button" data-copy-referral class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-6 py-4 font-black text-navy">
                            <i data-lucide="copy" class="h-5 w-5"></i>
                            Copy Link
                        </button>
                    </div>
                </article>
            @else

                @if ($errors->any())
                    <div class="mb-6 rounded-3xl border border-red-200 bg-red-50 p-5 text-red-800">
                        <strong class="font-black">Mohon periksa lagi data pendaftaran.</strong>
                        <ul class="mt-3 grid gap-1 text-sm font-semibold">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ $affiliateStoreUrl }}" enctype="multipart/form-data" data-affiliate-form class="rounded-3xl border border-slate-200 bg-white p-5 shadow-soft sm:p-7">
                @csrf
                <div class="grid gap-5 md:grid-cols-2">
                    <label class="grid gap-2 text-sm font-bold text-slate-700">
                        Nama sesuai KTP
                        <input name="name" value="{{ old('name') }}" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-slate-700">
                        Email
                        <input type="email" name="email" value="{{ old('email') }}" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-slate-700">
                        Password
                        <input type="password" name="password" required minlength="8" class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                        <span class="text-xs font-semibold text-slate-500">Minimal 8 karakter untuk login dashboard.</span>
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-slate-700">
                        Konfirmasi password
                        <input type="password" name="password_confirmation" required minlength="8" class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-slate-700">
                        No HP/WhatsApp
                        <input name="phone" value="{{ old('phone') }}" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-slate-700">
                        Sumber informasi
                        <select name="source_information" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                            <option value="">Pilih sumber informasi</option>
                            @foreach (['Instagram', 'Facebook', 'TikTok', 'Google', 'WhatsApp', 'Teman/Keluarga', 'Komunitas', 'Event', 'Lainnya'] as $source)
                                <option value="{{ $source }}" @selected(old('source_information') === $source)>{{ $source }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-slate-700">
                        NPWP
                        <input name="npwp" value="{{ old('npwp') }}" required inputmode="numeric" placeholder="Nomor NPWP pribadi" class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                        <span class="text-xs font-semibold text-slate-500">Digunakan untuk administrasi pajak komisi.</span>
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-slate-700 md:col-span-2">
                        Alamat lengkap sesuai KTP
                        <textarea name="address" rows="4" required class="rounded-2xl border border-slate-200 px-4 py-3 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">{{ old('address') }}</textarea>
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-slate-700">
                        Bank
                        <input name="bank_name" value="{{ old('bank_name') }}" required placeholder="Contoh: BCA, BRI, Mandiri" class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-slate-700">
                        Nomor rekening
                        <input name="bank_account_number" value="{{ old('bank_account_number') }}" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-slate-700">
                        Atas nama rekening
                        <input name="bank_account_name" value="{{ old('bank_account_name') }}" required class="h-12 rounded-2xl border border-slate-200 px-4 outline-none focus:border-tealx focus:ring-4 focus:ring-teal-100">
                        <span class="text-xs font-semibold text-slate-500">Wajib sama dengan nama sesuai KTP.</span>
                    </label>

                    <label class="grid gap-2 text-sm font-bold text-slate-700">
                        Upload KTP
                        <input type="file" name="identity_card" required accept=".jpg,.jpeg,.png,.pdf" class="h-12 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none file:mr-4 file:rounded-full file:border-0 file:bg-tealx file:px-4 file:py-1 file:font-black file:text-white">
                        <span class="text-xs font-semibold text-slate-500">Format JPG, PNG, atau PDF. Maksimal 4 MB.</span>
                    </label>
                </div>

                <section class="mt-6 grid gap-5 rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <div class="grid gap-3 sm:flex sm:items-start sm:justify-between">
                        <div>
                            <p class="text-sm font-black uppercase tracking-wide text-tealx">Syarat dan Ketentuan</p>
                            <h2 class="mt-1 text-xl font-black text-navy">Persetujuan affiliator</h2>
                            <p class="mt-2 max-w-3xl text-sm font-semibold leading-7 text-slate-600">
                                Pajak atas komisi atau fee affiliate ditanggung oleh penerima komisi sesuai ketentuan perpajakan yang berlaku.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ $termsDocumentUrl }}" target="_blank" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-black text-navy">
                                <i data-lucide="book-open" class="h-4 w-4"></i>
                                Baca Dokumen
                            </a>
                            <a href="{{ $termsDocumentUrl }}" download class="inline-flex items-center gap-2 rounded-full bg-navy px-4 py-2 text-sm font-black text-white">
                                <i data-lucide="download" class="h-4 w-4"></i>
                                Download
                            </a>
                        </div>
                    </div>

                    <label class="flex gap-3 rounded-2xl bg-white p-4 text-sm font-bold leading-6 text-slate-700">
                        <input type="checkbox" name="terms_accepted" value="1" required @checked(old('terms_accepted')) class="mt-1 h-5 w-5 rounded border-slate-300 text-tealx focus:ring-teal-100">
                        <span>Saya sudah membaca, memahami, dan menyetujui SK Ketentuan Fee dan Insentif Affiliator Mahera Media 2026.</span>
                    </label>

                    <label class="flex gap-3 rounded-2xl bg-white p-4 text-sm font-bold leading-6 text-slate-700">
                        <input type="checkbox" name="tax_notice_accepted" value="1" required @checked(old('tax_notice_accepted')) class="mt-1 h-5 w-5 rounded border-slate-300 text-tealx focus:ring-teal-100">
                        <span>Saya memahami bahwa pajak atas komisi atau fee ditanggung oleh penerima komisi.</span>
                    </label>

                    <div class="grid gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <label for="signature-pad" class="text-sm font-black text-slate-700">Tanda tangan digital</label>
                            <button type="button" data-clear-signature class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-black text-navy">Hapus</button>
                        </div>
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-3">
                            <canvas id="signature-pad" class="h-44 w-full touch-none rounded-2xl bg-white"></canvas>
                        </div>
                        <input type="hidden" name="digital_signature" data-signature-input>
                        <p data-signature-error class="hidden text-sm font-bold text-red-700">Tanda tangan digital wajib diisi sebelum daftar.</p>
                    </div>
                </section>

                <div class="mt-6 rounded-3xl bg-slate-50 p-4 text-sm font-semibold leading-7 text-slate-600">
                    Dengan mendaftar, kamu menyatakan data rekening adalah milik sendiri dan bersedia melakukan verifikasi email sebelum akun affiliate aktif.
                </div>

                <button class="mt-6 inline-flex items-center gap-2 rounded-full bg-tealx px-7 py-4 font-black text-white shadow-lg shadow-teal-700/20 transition hover:-translate-y-1">
                    <i data-lucide="mail-check" class="h-5 w-5"></i>
                    Daftar dan Kirim Verifikasi
                </button>
                </form>
            @endif
        </section>
    </main>

    <script>
        document.querySelector('[data-status-modal-close]')?.addEventListener('click', () => {
            document.querySelector('[data-status-modal]')?.remove();
        });

        document.querySelector('[data-copy-referral]')?.addEventListener('click', async () => {
            const text = document.querySelector('[data-referral-link]')?.textContent?.trim();
            if (! text) return;
            await navigator.clipboard.writeText(text);
            alert('Link referral berhasil disalin.');
        });

        const form = document.querySelector('[data-affiliate-form]');
        const canvas = document.querySelector('#signature-pad');
        const signatureInput = document.querySelector('[data-signature-input]');
        const signatureError = document.querySelector('[data-signature-error]');
        const clearSignatureButton = document.querySelector('[data-clear-signature]');
        let drawing = false;
        let hasSignature = false;

        if (canvas) {
            const context = canvas.getContext('2d');
            const resizeCanvas = () => {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                context.setTransform(ratio, 0, 0, ratio, 0, 0);
                context.lineCap = 'round';
                context.lineJoin = 'round';
                context.lineWidth = 3;
                context.strokeStyle = '#071a3d';
            };
            const point = (event) => {
                const rect = canvas.getBoundingClientRect();
                return { x: event.clientX - rect.left, y: event.clientY - rect.top };
            };
            const start = (event) => {
                drawing = true;
                hasSignature = true;
                signatureError?.classList.add('hidden');
                const current = point(event);
                context.beginPath();
                context.moveTo(current.x, current.y);
                event.preventDefault();
            };
            const move = (event) => {
                if (! drawing) return;
                const current = point(event);
                context.lineTo(current.x, current.y);
                context.stroke();
                event.preventDefault();
            };
            const stop = () => {
                drawing = false;
            };
            const clear = () => {
                context.clearRect(0, 0, canvas.width, canvas.height);
                hasSignature = false;
                if (signatureInput) signatureInput.value = '';
            };

            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);
            canvas.addEventListener('pointerdown', start);
            canvas.addEventListener('pointermove', move);
            canvas.addEventListener('pointerup', stop);
            canvas.addEventListener('pointerleave', stop);
            clearSignatureButton?.addEventListener('click', clear);

            form?.addEventListener('submit', (event) => {
                if (! hasSignature) {
                    event.preventDefault();
                    signatureError?.classList.remove('hidden');
                    canvas.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                if (signatureInput) {
                    signatureInput.value = canvas.toDataURL('image/png');
                }
            });
        }

        lucide.createIcons();
    </script>
</body>
</html>
