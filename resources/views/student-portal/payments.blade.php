@php
    $lead = $account->lead;
    $studentName = $lead->full_name;
    $nim = $lead->studentNumber?->nim ?? 'NIM sementara';
    $avatarInitial = mb_substr($studentName, 0, 1);
    $invoice = $lead->latestInvoice;
    $virtualAccount = $invoice?->va_number ?: ($invoice?->gateway_reference ?: '-');
    $billablePayments = $billablePayments ?? $payments;
    $totalPlan = $billablePayments->sum('amount');
    $totalPaid = $billablePayments->whereIn('status', ['paid', 'waived'])->sum('amount');
    $totalActive = $billablePayments->whereNotIn('status', ['paid', 'waived'])->sum('amount');
    $activePayments = $activePayments ?? collect();
    $activePaymentTotal = $activePaymentTotal ?? 0;
    $billingMonthLabel = $billingMonthLabel ?? now()->translatedFormat('F Y');
    $progress = (int) round(($totalPaid / max($totalPlan, 1)) * 100);
    $statusLabel = [
        'unpaid' => 'Belum dibayar',
        'pending' => 'Menunggu verifikasi',
        'paid' => 'Lunas',
        'waived' => 'Dibebaskan',
    ];
    $componentLabel = function ($payment): string {
        return collect([
            'Formulir' => $payment->registration_fee,
            'Development' => $payment->development_fee,
            'Tuition' => $payment->tuition_fee,
            'UKT' => $payment->ukt,
        ])->filter(fn ($amount) => (int) $amount > 0)->keys()->join(', ') ?: 'Tagihan';
    };
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran | Kampus Media</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { navy: '#071a3d', cyanx: '#06b6d4' },
                    boxShadow: { soft: '0 24px 80px rgba(15, 23, 42, .10)', glow: '0 20px 70px rgba(6, 182, 212, .18)' },
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
                    <span class="block font-black text-navy dark:text-white">Pembayaran</span>
                    <span class="block text-xs font-bold text-slate-500 dark:text-slate-400">Terkoneksi realita sistem pusat</span>
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
        @if (session('status'))
            <div class="mb-6 rounded-[1.5rem] border border-cyan-200 bg-cyan-50 p-5 font-bold text-cyan-900 dark:border-cyan-500/20 dark:bg-cyan-500/10 dark:text-cyan-100">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-[1.5rem] border border-red-200 bg-red-50 p-5 text-red-900 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-100">
                <strong class="block font-black">Upload bukti pembayaran belum berhasil.</strong>
                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm font-semibold">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-navy via-[#0b2d63] to-cyan-700 p-6 text-white shadow-glow lg:p-8">
            <div class="grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-center">
                <div>
                    <p class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-black text-cyan-100">Rencana dan Realita</p>
                    <h1 class="mt-5 text-3xl font-black sm:text-5xl">Pantau pembayaran kuliahmu.</h1>
                    <p class="mt-4 max-w-2xl leading-8 text-sky-50">{{ $studentName }} - {{ $nim }} - {{ $lead->studyProgram?->name ?? 'Program studi belum diisi' }}</p>
                </div>
                <div class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur">
                    <p class="text-sm font-bold text-cyan-100">Progress pembayaran</p>
                    <strong class="mt-2 block text-4xl font-black">{{ $progress }}%</strong>
                    <div class="mt-5 h-3 rounded-full bg-white/15">
                        <div class="h-3 rounded-full bg-gradient-to-r from-yellow-300 to-cyan-300" style="width: {{ $progress }}%"></div>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-sky-100">VA: {{ $virtualAccount }}</p>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach ([['Total Rencana', $totalPlan, 'receipt'], ['Sudah Lunas', $totalPaid, 'badge-check'], ['Sisa Tagihan', $totalActive, 'wallet-cards']] as $card)
                <article class="rounded-[1.75rem] border border-white/70 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/10">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-black text-slate-500 dark:text-slate-400">{{ $card[0] }}</p>
                        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-200">
                            <i data-lucide="{{ $card[2] }}" class="h-5 w-5"></i>
                        </span>
                    </div>
                    <strong class="mt-4 block text-2xl font-black text-navy dark:text-white">Rp {{ number_format($card[1], 0, ',', '.') }}</strong>
                    @if ($card[0] === 'Sudah Lunas' && $totalPaid > 0)
                        <a href="{{ route('student-portal.payments.receipt') }}" target="_blank" class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-navy px-4 py-3 text-sm font-black text-white shadow-sm transition hover:-translate-y-1 dark:bg-white dark:text-navy">
                            <i data-lucide="printer" class="h-4 w-4"></i>
                            Cetak Kwitansi
                        </a>
                    @endif
                </article>
            @endforeach
        </section>

        <section class="mt-6 overflow-hidden rounded-[2rem] border border-cyan-200/70 bg-gradient-to-br from-white to-cyan-50 p-5 shadow-soft dark:border-white/10 dark:from-white/10 dark:to-cyan-500/10">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Tagihan Aktif</p>
                    <h2 class="mt-1 text-2xl font-black text-navy dark:text-white">Bulan {{ $billingMonthLabel }}</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Tagihan yang masuk bulan berjalan dan belum lunas.</p>
                </div>
                <div class="rounded-3xl bg-navy px-6 py-4 text-white shadow-glow">
                    <p class="text-xs font-bold text-cyan-100">Total aktif</p>
                    <strong class="mt-1 block text-2xl font-black">Rp {{ number_format($activePaymentTotal, 0, ',', '.') }}</strong>
                </div>
            </div>

            <div class="mt-5 grid gap-3 lg:grid-cols-2">
                @forelse ($activePayments as $payment)
                    <article class="rounded-3xl border border-white/70 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/10">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <strong class="text-lg font-black text-navy dark:text-white">{{ $payment->payment_label ?: 'Bulan '.$payment->month }}</strong>
                                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-black text-red-700 dark:bg-red-500/10 dark:text-red-200">{{ $statusLabel[$payment->status] ?? str($payment->status)->replace('_', ' ')->title() }}</span>
                                </div>
                                <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $componentLabel($payment) }} - Tgl rencana {{ $payment->due_date?->translatedFormat('d M Y') ?? '-' }}</p>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-xs font-bold text-slate-500 dark:text-slate-400">Nominal</p>
                                <strong class="mt-1 block text-xl font-black text-navy dark:text-white">Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                    </article>
                @empty
                    <article class="rounded-3xl border border-white/70 bg-white p-6 text-center dark:border-white/10 dark:bg-white/10 lg:col-span-2">
                        <i data-lucide="badge-check" class="mx-auto h-10 w-10 text-cyan-600 dark:text-cyan-200"></i>
                        <strong class="mt-3 block text-navy dark:text-white">Tidak ada tagihan aktif bulan ini</strong>
                        <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Semua tagihan bulan berjalan sudah lunas atau belum ada jadwal pembayaran pada bulan ini.</p>
                    </article>
                @endforelse
            </div>
        </section>

        <section id="bayar-sekarang" class="mt-6 overflow-hidden rounded-[2rem] border border-white/70 bg-white shadow-soft dark:border-white/10 dark:bg-white/10">
            <div class="grid gap-0 lg:grid-cols-[.95fr_1.05fr]">
                <div class="bg-gradient-to-br from-navy via-[#0b2d63] to-cyan-700 p-6 text-white lg:p-8">
                    <p class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-black uppercase tracking-wide text-cyan-100">Bayar Sekarang</p>
                    <h2 class="mt-5 text-3xl font-black">Transfer ke rekening resmi Mahera Media</h2>
                    <p class="mt-3 text-sm font-semibold leading-7 text-sky-100">Setelah transfer sesuai nominal tagihan aktif, upload struk pembayaran agar tim keuangan bisa melakukan verifikasi.</p>

                    <div class="mt-6 rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur">
                        <p class="text-xs font-black uppercase tracking-wide text-cyan-100">Bank tujuan</p>
                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold text-sky-100">Bank</p>
                                <strong class="mt-1 block text-2xl font-black">Mandiri</strong>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-sky-100">Atas nama</p>
                                <strong class="mt-1 block text-2xl font-black">Mahera Media</strong>
                            </div>
                        </div>
                        <div class="mt-5 rounded-2xl bg-white p-4 text-navy">
                            <p class="text-xs font-black uppercase tracking-wide text-slate-500">Nomor rekening</p>
                            <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <strong class="text-3xl font-black tracking-wide">1440029473219</strong>
                                <button type="button" data-copy-account class="inline-flex items-center justify-center gap-2 rounded-2xl bg-cyanx px-4 py-3 text-sm font-black text-white shadow-glow">
                                    <i data-lucide="copy" class="h-4 w-4"></i>
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 lg:p-8">
                    <p class="text-sm font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Upload Struk</p>
                    <h3 class="mt-1 text-2xl font-black text-navy dark:text-white">Kirim bukti transfer</h3>
                    <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Status pembayaran menjadi menunggu verifikasi sampai disetujui tim keuangan.</p>

                    @if ($activePayments->isNotEmpty())
                        <div class="mt-5 rounded-3xl bg-cyan-50 p-5 dark:bg-cyan-500/10">
                            <p class="text-xs font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-200">Total tagihan aktif</p>
                            <strong class="mt-1 block text-3xl font-black text-navy dark:text-white">Rp {{ number_format($activePaymentTotal, 0, ',', '.') }}</strong>
                        </div>

                        <form method="POST" action="{{ route('student-portal.payments.proof') }}" enctype="multipart/form-data" class="mt-5 grid gap-4">
                            @csrf
                            <label class="grid gap-2">
                                <span class="text-sm font-black text-slate-700 dark:text-slate-200">Pilih tagihan yang dibayar</span>
                                <select name="student_payment_id" required class="h-12 rounded-2xl border border-slate-200 bg-white px-4 font-bold outline-none focus:border-cyanx focus:ring-4 focus:ring-cyan-100 dark:border-white/10 dark:bg-white/10">
                                    @foreach ($activePayments as $payment)
                                        <option value="{{ $payment->id }}">
                                            {{ $payment->payment_label ?: ($payment->month === 0 ? 'Formulir Pendaftaran' : 'Bulan '.$payment->month) }} - Rp {{ number_format($payment->amount, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-2">
                                <span class="text-sm font-black text-slate-700 dark:text-slate-200">Upload struk transfer</span>
                                <input type="file" name="proof_path" accept=".jpg,.jpeg,.png,.pdf" required class="rounded-2xl border border-slate-200 bg-white p-3 text-sm font-bold file:mr-4 file:rounded-xl file:border-0 file:bg-navy file:px-4 file:py-2 file:font-black file:text-white dark:border-white/10 dark:bg-white/10">
                                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Format JPG, PNG, atau PDF. Maksimal 8 MB.</span>
                            </label>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-cyanx px-6 py-4 font-black text-white shadow-glow transition hover:-translate-y-1">
                                <i data-lucide="upload-cloud" class="h-5 w-5"></i>
                                Kirim Struk untuk Verifikasi
                            </button>
                        </form>
                    @else
                        <div class="mt-5 rounded-3xl bg-slate-50 p-6 text-center dark:bg-white/5">
                            <i data-lucide="badge-check" class="mx-auto h-10 w-10 text-cyan-600 dark:text-cyan-200"></i>
                            <strong class="mt-3 block text-navy dark:text-white">Tidak ada tagihan aktif untuk dibayar.</strong>
                            <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Form upload struk akan muncul saat ada tagihan berjalan yang belum lunas.</p>
                        </div>
                    @endif
                </div>
            </div>

            @if ($activePayments->isNotEmpty())
                <div class="border-t border-slate-100 bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 lg:px-8">
                    Pastikan nominal transfer sesuai tagihan yang dipilih. Pembayaran baru dianggap lunas setelah struk diverifikasi oleh tim keuangan.
                </div>
            @else
                <div class="border-t border-slate-100 bg-slate-50 px-6 py-4 text-sm font-semibold text-slate-600 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 lg:px-8">
                    Rekening ini hanya digunakan saat ada tagihan aktif yang tampil di akun mahasiswa.
                </div>
            @endif
        </section>

        <section class="mt-6 overflow-hidden rounded-[2rem] border border-white/70 bg-white shadow-soft dark:border-white/10 dark:bg-white/10">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 dark:border-white/10 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Sistem Pusat</p>
                    <h2 class="mt-1 text-2xl font-black text-navy dark:text-white">Tabel Rencana dan Realita Pembayaran</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Status, tanggal realita, dan catatan mengikuti data yang diubah admin di sistem pusat.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[980px] w-full border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-white/5 dark:text-slate-300">
                            <th class="px-5 py-4">Periode</th>
                            <th class="px-5 py-4">Komponen</th>
                            <th class="px-5 py-4 text-right">Rencana</th>
                            <th class="px-5 py-4">Tgl Rencana</th>
                            <th class="px-5 py-4">Realita</th>
                            <th class="px-5 py-4">Tgl Realita</th>
                            <th class="px-5 py-4">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                        @forelse ($billablePayments as $payment)
                            @php
                                $isPaid = in_array($payment->status, ['paid', 'waived'], true);
                                $badgeClass = $isPaid
                                    ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-200'
                                    : ($payment->status === 'pending'
                                        ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-200'
                                        : 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-200');
                            @endphp
                            <tr class="transition hover:bg-cyan-50/60 dark:hover:bg-white/5">
                                <td class="px-5 py-4 font-black text-navy dark:text-white">{{ $payment->payment_label ?: ($payment->month === 0 ? 'Formulir Pendaftaran' : 'Bulan '.$payment->month) }}</td>
                                <td class="px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">{{ $componentLabel($payment) }}</td>
                                <td class="px-5 py-4 text-right font-black text-navy dark:text-white">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">{{ $payment->due_date?->translatedFormat('d M Y') ?? '-' }}</td>
                                <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-black {{ $badgeClass }}">{{ $statusLabel[$payment->status] ?? str($payment->status)->replace('_', ' ')->title() }}</span></td>
                                <td class="px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">{{ $payment->paid_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                                <td class="px-5 py-4 font-semibold text-slate-500 dark:text-slate-400">{{ $payment->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center">
                                    <i data-lucide="receipt-text" class="mx-auto h-10 w-10 text-cyan-600 dark:text-cyan-200"></i>
                                    <strong class="mt-3 block text-navy dark:text-white">Belum ada jadwal pembayaran</strong>
                                    <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Jadwal akan muncul setelah admin membuat rencana pembayaran di sistem pusat.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        const root = document.documentElement;
        if (localStorage.getItem('student-theme') === 'dark') root.classList.add('dark');
        document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
            root.classList.toggle('dark');
            localStorage.setItem('student-theme', root.classList.contains('dark') ? 'dark' : 'light');
        });
        document.querySelector('[data-copy-account]')?.addEventListener('click', async (event) => {
            await navigator.clipboard?.writeText('1440029473219');
            event.currentTarget.innerHTML = '<i data-lucide="check" class="h-4 w-4"></i> Tersalin';
            lucide.createIcons();
        });
        lucide.createIcons();
    </script>
</body>
</html>
