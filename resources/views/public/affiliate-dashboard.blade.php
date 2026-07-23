@php
    $seoTitle = 'Dashboard Affiliator';
    $seoDescription = 'Dashboard Affiliator Kampus Media untuk memantau referral, herregistrasi, kelayakan pencairan fee, dan status pembayaran komisi.';
    $canonicalUrl = request()->url();
    $affiliateHomeUrl = request()->getHost() === 'affiliate.kampus.media' ? rtrim(url('/'), '/').'/' : route('affiliate.public');

    $paymentLabels = [
        'unpaid' => 'Belum bayar',
        'pending' => 'Menunggu pembayaran',
        'paid' => 'Sudah herregistrasi',
        'expired' => 'Kadaluarsa',
        'cancelled' => 'Dibatalkan',
    ];

    $commissionLabels = [
        'pending' => 'Menunggu validasi',
        'approved' => 'Layak dicairkan',
        'paid' => 'Sudah terbayar',
        'cancelled' => 'Dibatalkan',
    ];

    $paymentClass = [
        'paid' => 'bg-emerald-100 text-emerald-700',
        'pending' => 'bg-amber-100 text-amber-700',
        'unpaid' => 'bg-slate-100 text-slate-700',
        'expired' => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];

    $commissionClass = [
        'paid' => 'bg-emerald-100 text-emerald-700',
        'approved' => 'bg-cyan-100 text-cyan-700',
        'pending' => 'bg-amber-100 text-amber-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $seoTitle }} | Kampus Media</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="noindex, nofollow">
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
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <a href="{{ $affiliateHomeUrl }}" class="flex items-center gap-3">
                <img src="/images/social/logo%20kampus%20media.png" alt="Kampus Media" class="h-11 w-auto">
                <span>
                    <strong class="block text-base font-black text-navy">Kampus Media</strong>
                    <small class="block text-xs font-bold text-slate-500">Dashboard Affiliator</small>
                </span>
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ $referralLink }}" class="inline-flex items-center gap-2 rounded-full bg-tealx px-4 py-3 text-sm font-black text-white">
                    <i data-lucide="external-link" class="h-4 w-4"></i>
                    Form PMB
                </a>
                <form method="POST" action="{{ url('/logout') }}">
                    @csrf
                    <button class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-3 text-sm font-black text-navy">
                        <i data-lucide="log-out" class="h-4 w-4"></i>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="px-4 py-8 sm:px-6 lg:px-8">
        <section class="mx-auto max-w-7xl">
            <div class="rounded-[2rem] bg-navy p-6 text-white shadow-soft sm:p-8">
                <div class="grid gap-6 lg:grid-cols-[1.1fr_.9fr] lg:items-end">
                    <div>
                        <p class="text-sm font-black uppercase tracking-wide text-cyan-100">Affiliator aktif</p>
                        <h1 class="mt-3 text-3xl font-black sm:text-5xl">{{ $partner->name }}</h1>
                        <p class="mt-4 max-w-2xl leading-8 text-sky-50">Pantau mahasiswa yang mendaftar melalui link referral kamu, status herregistrasi, kelayakan pencairan fee, dan status pembayaran komisi.</p>
                    </div>
                    <div class="rounded-3xl bg-white/10 p-4">
                        <p class="text-sm font-black uppercase tracking-wide text-cyan-100">Link referral</p>
                        <p class="mt-2 break-all text-sm font-bold text-white" data-referral-link>{{ $referralLink }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white px-4 py-2 text-sm font-black text-navy">{{ $partner->referral_code }}</span>
                            <button type="button" data-copy-referral class="inline-flex items-center gap-2 rounded-full border border-white/20 px-4 py-2 text-sm font-black text-white">
                                <i data-lucide="copy" class="h-4 w-4"></i>
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            @if ($partner->status !== 'active' || blank($partner->email_verified_at))
                <div class="mt-6 rounded-3xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
                    <strong class="font-black">Akun belum aktif.</strong>
                    <p class="mt-2 font-semibold leading-7">Silakan verifikasi email terlebih dahulu agar referral dan dashboard dapat digunakan secara penuh.</p>
                </div>
            @endif

            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([['Pendaftar', number_format($registeredCount), 'user-plus'], ['Sudah herregistrasi', number_format($paidCount), 'badge-check'], ['Fee layak cair', 'Rp '.number_format($eligibleCommission, 0, ',', '.'), 'hand-coins'], ['Fee terbayar', 'Rp '.number_format($paidCommission, 0, ',', '.'), 'wallet']] as $card)
                    <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-slate-100 text-tealx">
                            <i data-lucide="{{ $card[2] }}" class="h-5 w-5"></i>
                        </span>
                        <p class="mt-4 text-sm font-black uppercase tracking-wide text-slate-500">{{ $card[0] }}</p>
                        <strong class="mt-2 block text-2xl font-black text-navy">{{ $card[1] }}</strong>
                    </article>
                @endforeach
            </div>

            <section class="mt-6 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-5 sm:p-6">
                    <h2 class="text-2xl font-black text-navy">Perolehan Mahasiswa</h2>
                    <p class="mt-2 leading-7 text-slate-600">Data berikut terhubung dengan pembayaran mahasiswa dan status komisi di sistem SPMM.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-4">Mahasiswa</th>
                                <th class="px-5 py-4">Kampus/Prodi</th>
                                <th class="px-5 py-4">Tanggal Daftar</th>
                                <th class="px-5 py-4">Herregistrasi</th>
                                <th class="px-5 py-4">Komisi Herregistrasi</th>
                                <th class="px-5 py-4">Komisi Semester 1</th>
                                <th class="px-5 py-4 text-right">Total Fee Layak</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($conversions as $conversion)
                                @php
                                    $lead = $conversion->lead;
                                    $paymentStatus = $conversion->herregistration_paid_at ? 'paid' : 'unpaid';
                                    $herStatus = $conversion->herregistration_commission_status ?? 'pending';
                                    $semesterStatus = $conversion->semester1_commission_status ?? 'pending';
                                    $latestPaidAt = $conversion->herregistration_paid_at;
                                    $eligibleTotal = (in_array($herStatus, ['approved', 'paid'], true) ? (int) $conversion->herregistration_commission_amount : 0)
                                        + (in_array($semesterStatus, ['approved', 'paid'], true) ? (int) $conversion->semester1_commission_amount : 0);
                                @endphp
                                <tr class="align-top">
                                    <td class="px-5 py-4">
                                        <strong class="block font-black text-navy">{{ $lead?->full_name ?? 'Data lead tidak ditemukan' }}</strong>
                                        <span class="mt-1 block text-xs font-semibold text-slate-500">{{ $lead?->whatsapp_number ?? '-' }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="block font-bold text-slate-700">{{ $lead?->campus?->name ?? '-' }}</span>
                                        <span class="mt-1 block text-xs font-semibold text-slate-500">{{ $lead?->studyProgram?->name ?? '-' }}</span>
                                    </td>
                                    <td class="px-5 py-4 font-semibold text-slate-600">{{ $conversion->registered_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $paymentClass[$paymentStatus] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ $paymentLabels[$paymentStatus] ?? str($paymentStatus)->replace('_', ' ')->title() }}
                                        </span>
                                        <span class="mt-2 block text-xs font-semibold text-slate-500">{{ $latestPaidAt ? 'Terakhir bayar: '.\Illuminate\Support\Carbon::parse($latestPaidAt)->translatedFormat('d M Y H:i') : 'Belum ada pembayaran lunas' }}</span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $commissionClass[$herStatus] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ $commissionLabels[$herStatus] ?? str($herStatus)->replace('_', ' ')->title() }}
                                        </span>
                                        <span class="mt-2 block text-xs font-black text-navy">Rp {{ number_format((int) $conversion->herregistration_commission_amount, 0, ',', '.') }}</span>
                                        <span class="mt-1 block text-xs font-semibold text-slate-500">Lunas herregistrasi</span>
                                        @if ($conversion->herregistration_payout_proof_path)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($conversion->herregistration_payout_proof_path) }}" target="_blank" class="mt-2 inline-flex text-xs font-black text-tealx">
                                                Lihat bukti transfer
                                            </a>
                                        @endif
                                        @if ($conversion->herregistration_payout_notes)
                                            <span class="mt-1 block text-xs font-semibold text-slate-500">{{ $conversion->herregistration_payout_notes }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-black {{ $commissionClass[$semesterStatus] ?? 'bg-slate-100 text-slate-700' }}">
                                            {{ $commissionLabels[$semesterStatus] ?? str($semesterStatus)->replace('_', ' ')->title() }}
                                        </span>
                                        <span class="mt-2 block text-xs font-black text-navy">Rp {{ number_format((int) $conversion->semester1_commission_amount, 0, ',', '.') }}</span>
                                        <span class="mt-1 block text-xs font-semibold text-slate-500">Lunas SPP+SPB/UKT semester 1</span>
                                        @if ($conversion->semester1_payout_proof_path)
                                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($conversion->semester1_payout_proof_path) }}" target="_blank" class="mt-2 inline-flex text-xs font-black text-tealx">
                                                Lihat bukti transfer
                                            </a>
                                        @endif
                                        @if ($conversion->semester1_payout_notes)
                                            <span class="mt-1 block text-xs font-semibold text-slate-500">{{ $conversion->semester1_payout_notes }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right font-black text-navy">Rp {{ number_format($eligibleTotal, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-12 text-center">
                                        <i data-lucide="users-round" class="mx-auto h-10 w-10 text-slate-300"></i>
                                        <strong class="mt-4 block text-lg font-black text-navy">Belum ada mahasiswa dari referral ini.</strong>
                                        <p class="mt-2 font-semibold text-slate-500">Bagikan link referral untuk mulai mencatat pendaftaran.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </main>

    <script>
        document.querySelector('[data-copy-referral]')?.addEventListener('click', async () => {
            const text = document.querySelector('[data-referral-link]')?.textContent?.trim();
            if (! text) return;
            await navigator.clipboard.writeText(text);
            alert('Link referral berhasil disalin.');
        });

        lucide.createIcons();
    </script>
</body>
</html>
