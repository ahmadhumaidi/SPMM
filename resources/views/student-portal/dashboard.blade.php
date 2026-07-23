@php
    $lead = $account->lead;
    $invoice = $lead->latestInvoice;
    $studentName = $lead->full_name;
    $nim = $lead->studentNumber?->nim ?? 'NIM sementara';
    $paymentStatus = str($lead->payment_status->value)->replace('_', ' ')->title();
    $activeBillAmount = $activeBillTotal ?? 0;
    $activeBillStatus = ($currentMonthPayments ?? collect())->isNotEmpty() ? 'Belum Lunas' : 'Tidak Ada Tagihan';
    $invoiceNumber = $invoice?->invoice_number ?? 'INV-PMB-DEMO';
    $virtualAccount = $invoice?->va_number ?: ($invoice?->gateway_reference ?: '-');
    $avatarInitial = mb_substr($studentName, 0, 1);
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
    <title>Dashboard Mahasiswa | Kampus Media</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        navy: '#071a3d',
                        ink: '#102033',
                        cyanx: '#06b6d4',
                    },
                    boxShadow: {
                        soft: '0 24px 80px rgba(15, 23, 42, .10)',
                        glow: '0 20px 70px rgba(6, 182, 212, .18)',
                    },
                },
            },
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .glass { background: rgba(255, 255, 255, .78); backdrop-filter: blur(18px); }
        .dark .glass { background: rgba(15, 23, 42, .72); }
        .reveal { opacity: 0; transform: translateY(16px); transition: opacity .55s ease, transform .55s ease; }
        .reveal.is-visible { opacity: 1; transform: translateY(0); }
        .shimmer { position: relative; overflow: hidden; }
        .shimmer::after { content: ""; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent); animation: shimmer 1.8s infinite; }
        @keyframes shimmer { 100% { transform: translateX(100%); } }
        .skeleton-loader.is-hidden { display: none; }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased transition dark:bg-[#050b18] dark:text-slate-100">
    <div class="skeleton-loader fixed inset-0 z-[80] bg-slate-100 p-5 dark:bg-[#050b18]">
        <div class="flex gap-5">
            <div class="hidden h-[calc(100vh-40px)] w-72 animate-pulse rounded-[2rem] bg-white/80 dark:bg-white/10 lg:block"></div>
            <div class="flex-1 space-y-5">
                <div class="h-20 animate-pulse rounded-[2rem] bg-white/80 dark:bg-white/10"></div>
                <div class="grid gap-5 lg:grid-cols-4">
                    <div class="h-36 animate-pulse rounded-[2rem] bg-white/80 dark:bg-white/10"></div>
                    <div class="h-36 animate-pulse rounded-[2rem] bg-white/80 dark:bg-white/10"></div>
                    <div class="h-36 animate-pulse rounded-[2rem] bg-white/80 dark:bg-white/10"></div>
                    <div class="h-36 animate-pulse rounded-[2rem] bg-white/80 dark:bg-white/10"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-24 top-0 h-96 w-96 rounded-full bg-cyan-300/30 blur-3xl dark:bg-cyan-500/10"></div>
        <div class="absolute right-0 top-36 h-[32rem] w-[32rem] rounded-full bg-indigo-400/20 blur-3xl dark:bg-indigo-600/10"></div>
    </div>

    <div class="min-h-screen lg:grid lg:grid-cols-[18rem_1fr]">
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full border-r border-white/70 glass p-4 shadow-2xl shadow-slate-950/10 transition duration-300 dark:border-white/10 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
            <div class="flex h-full flex-col">
                <div class="flex items-center justify-between">
                    <a href="{{ route('student-portal.dashboard') }}" class="flex items-center gap-3">
                        @include('student-portal.partials.campus-logo', ['campus' => $lead->campus, 'class' => 'h-12 w-12'])
                        <span>
                            <span class="block font-black text-navy dark:text-white">Kampus Media</span>
                            <span class="block text-xs font-bold text-slate-500 dark:text-slate-400">Student OS</span>
                        </span>
                    </a>
                    <button type="button" data-close-sidebar class="grid h-10 w-10 place-items-center rounded-xl bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-200 lg:hidden">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <nav class="mt-8 flex-1 space-y-1 overflow-y-auto pr-1">
                    @foreach ([
                        ['Dashboard', 'layout-dashboard', true],
                        ['Profil Mahasiswa', 'user-round', false, route('student-portal.profile')],
                        ['Pemberkasan', 'folder-up', false, route('student-portal.documents')],
                        ['Jadwal Kuliah', 'calendar-days', false],
                        ['KRS', 'list-checks', false],
                        ['KHS', 'file-bar-chart', false],
                        ['Transkrip Nilai', 'scroll-text', false],
                        ['Tagihan Kuliah', 'receipt', false],
                        ['Pembayaran', 'credit-card', false, route('student-portal.payments')],
                        ['Dashboard Affiliator', 'hand-coins', false, route('student-portal.affiliate')],
                        ['Materi Kuliah', 'book-open', false, route('student-portal.materials')],
                        ['Tugas', 'clipboard-check', false, route('student-portal.assignments')],
                        ['Presensi', 'scan-face', false],
                        ['Pengumuman', 'megaphone', false],
                        ['Konsultasi Akademik', 'messages-square', false],
                        ['Sertifikat', 'badge-check', false],
                        ['Pengaturan Akun', 'settings', false, route('student-portal.account-settings')],
                    ] as $menu)
                        <a href="{{ $menu[3] ?? '#'.str($menu[0])->slug() }}" class="group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-black transition hover:bg-white hover:shadow-sm dark:hover:bg-white/10 {{ $menu[2] ? 'bg-navy text-white shadow-glow' : 'text-slate-600 dark:text-slate-300' }}">
                            <i data-lucide="{{ $menu[1] }}" class="h-5 w-5 {{ $menu[2] ? 'text-cyan-200' : 'text-slate-400 group-hover:text-cyanx' }}"></i>
                            <span>{{ $menu[0] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="mt-5 rounded-3xl border border-cyan-200/70 bg-gradient-to-br from-cyan-50 to-indigo-50 p-4 dark:border-white/10 dark:from-cyan-500/10 dark:to-indigo-500/10">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-white text-cyan-700 shadow-sm dark:bg-white/10 dark:text-cyan-200">
                            <i data-lucide="bot" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <p class="font-black text-navy dark:text-white">AI Assistant</p>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Tanya jadwal, tugas, atau pembayaran.</p>
                        </div>
                    </div>
                    <button class="mt-4 w-full rounded-2xl bg-navy px-4 py-3 text-sm font-black text-white transition hover:-translate-y-0.5">Mulai Chat</button>
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="sticky top-0 z-40 border-b border-white/70 glass px-4 py-3 dark:border-white/10 sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button type="button" data-open-sidebar class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-slate-700 shadow-sm dark:bg-white/10 dark:text-white lg:hidden">
                        <i data-lucide="menu" class="h-5 w-5"></i>
                    </button>
                    <div class="relative hidden flex-1 md:block">
                        <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"></i>
                        <input class="h-12 w-full rounded-2xl border border-white/70 bg-white/80 pl-12 pr-4 text-sm font-semibold outline-none ring-cyan-400/20 transition focus:ring-4 dark:border-white/10 dark:bg-white/10" placeholder="Cari jadwal, tagihan, tugas, materi kuliah...">
                    </div>
                    <button class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-slate-700 shadow-sm transition hover:-translate-y-0.5 dark:bg-white/10 dark:text-white">
                        <i data-lucide="bell" class="h-5 w-5"></i>
                    </button>
                    <button type="button" data-theme-toggle class="grid h-11 w-11 place-items-center rounded-2xl bg-white text-slate-700 shadow-sm transition hover:-translate-y-0.5 dark:bg-white/10 dark:text-white">
                        <i data-lucide="moon" class="h-5 w-5 dark:hidden"></i>
                        <i data-lucide="sun" class="hidden h-5 w-5 dark:block"></i>
                    </button>
                    <div class="hidden items-center gap-3 rounded-2xl bg-white px-3 py-2 shadow-sm dark:bg-white/10 sm:flex">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-navy to-cyanx font-black text-white">{{ $avatarInitial }}</span>
                        <span>
                            <span class="block text-sm font-black">{{ $studentName }}</span>
                            <span class="block text-xs font-bold text-slate-500 dark:text-slate-400">{{ $nim }}</span>
                        </span>
                    </div>
                    <form method="POST" action="{{ route('student-portal.logout') }}">
                        @csrf
                        <button class="hidden rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 shadow-sm dark:border-white/10 dark:bg-white/10 dark:text-white md:block">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="mx-auto max-w-[100rem] px-4 py-6 pb-28 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="reveal mb-5 rounded-3xl border border-green-200 bg-green-50 p-4 font-bold text-green-700 dark:border-green-500/20 dark:bg-green-500/10 dark:text-green-200">{{ session('status') }}</div>
                @endif

                <section id="dashboard" class="reveal overflow-hidden rounded-[2rem] bg-gradient-to-br from-navy via-[#0b2d63] to-cyan-700 p-6 text-white shadow-glow lg:p-8">
                    <div class="grid gap-8 lg:grid-cols-[1fr_22rem] lg:items-center">
                        <div>
                            <p class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-black text-cyan-100">Semester aktif 2026/2027 Genap</p>
                            <h1 class="mt-5 max-w-4xl text-3xl font-black tracking-normal sm:text-5xl">Halo, {{ $studentName }}. Siap naik level akademik minggu ini?</h1>
                            <p class="mt-4 max-w-2xl leading-8 text-sky-50">Pantau jadwal, pembayaran, KRS, nilai, dan progres kelulusan dalam satu dashboard modern.</p>
                            <div class="mt-6 flex flex-wrap gap-3">
                                @foreach (['Isi KRS', 'Bayar Kuliah', 'Download KHS', 'Lihat Jadwal', 'Hubungi Dosen'] as $action)
                                    <button class="rounded-full bg-white/12 px-5 py-3 text-sm font-black backdrop-blur transition hover:-translate-y-1 hover:bg-white/20">{{ $action }}</button>
                                @endforeach
                            </div>
                        </div>
                        <div class="rounded-[1.75rem] border border-white/15 bg-white/10 p-5 backdrop-blur">
                            <div class="flex items-center justify-between">
                                <span>
                                    <span class="block text-sm font-bold text-cyan-100">Progress Kelulusan</span>
                                    <strong class="mt-1 block text-4xl font-black">64%</strong>
                                </span>
                                <span class="grid h-16 w-16 place-items-center rounded-3xl bg-gold text-navy">
                                    <i data-lucide="rocket" class="h-8 w-8"></i>
                                </span>
                            </div>
                            <div class="mt-6 h-3 rounded-full bg-white/15">
                                <div class="h-3 rounded-full bg-gradient-to-r from-gold to-cyan-300" style="width: 64%"></div>
                            </div>
                            <p class="mt-4 text-sm font-semibold leading-6 text-sky-50">Tetap konsisten. Kamu berada di jalur yang bagus untuk menyelesaikan target SKS.</p>
                        </div>
                    </div>
                </section>

                <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    @foreach ([['IPK', '3.72', 'Naik 0.08', 'trending-up'], ['Total SKS', '86', '58 SKS tersisa', 'layers-3'], ['Semester Aktif', '4', 'Reguler Malam', 'calendar-check'], ['Tagihan Aktif', 'Rp '.number_format($activeBillAmount, 0, ',', '.'), $activeBillStatus.' - '.$billingMonthLabel, 'wallet-cards'], ['Progress', '64%', 'Kelulusan', 'activity']] as $stat)
                        <article class="reveal rounded-[1.75rem] border border-white/70 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-soft dark:border-white/10 dark:bg-white/10">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-black text-slate-500 dark:text-slate-400">{{ $stat[0] }}</p>
                                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-200"><i data-lucide="{{ $stat[3] }}" class="h-5 w-5"></i></span>
                            </div>
                            <strong class="mt-4 block text-2xl font-black text-navy dark:text-white">{{ $stat[1] }}</strong>
                            <p class="mt-2 text-xs font-bold text-slate-500 dark:text-slate-400">{{ $stat[2] }}</p>
                            <div class="mt-4 flex h-9 items-end gap-1">
                                @foreach ([30, 48, 42, 70, 58, 86, 76] as $bar)
                                    <span class="flex-1 rounded-t bg-gradient-to-t from-cyanx to-indigo-400" style="height: {{ $bar }}%"></span>
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </section>

                <section class="mt-6 grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
                    <article id="jadwal-kuliah" class="reveal rounded-[2rem] border border-white/70 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/10">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Jadwal Kuliah</p>
                                <h2 class="mt-1 text-2xl font-black text-navy dark:text-white">Timeline hari ini</h2>
                            </div>
                            <button class="rounded-full bg-slate-100 px-4 py-2 text-sm font-black text-slate-600 dark:bg-white/10 dark:text-slate-200">Lihat kalender</button>
                        </div>
                        <div class="mt-6 grid gap-4">
                            @foreach ([['08:00 - 09:40', 'Sistem Basis Data', 'Dr. Hanif Pratama', 'Online'], ['10:00 - 11:40', 'Manajemen Proyek TI', 'Nadia Kurnia, M.Kom', 'Offline'], ['19:00 - 20:30', 'Bahasa Inggris Bisnis', 'Ratri Dewi, M.Pd', 'Hybrid']] as $class)
                                <div class="group flex gap-4 rounded-3xl border border-slate-100 bg-slate-50 p-4 transition hover:-translate-y-1 hover:bg-white hover:shadow-soft dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
                                    <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-white text-cyan-700 shadow-sm dark:bg-white/10 dark:text-cyan-200">
                                        <i data-lucide="video" class="h-6 w-6"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="font-black text-navy dark:text-white">{{ $class[1] }}</h3>
                                            <span class="rounded-full {{ $class[3] === 'Online' ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-200' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200' }} px-3 py-1 text-xs font-black">{{ $class[3] }}</span>
                                        </div>
                                        <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $class[2] }}</p>
                                        <p class="mt-2 text-sm font-black text-slate-700 dark:text-slate-200">{{ $class[0] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <article id="pembayaran" class="reveal rounded-[2rem] bg-gradient-to-br from-white to-cyan-50 p-6 shadow-soft dark:from-white/10 dark:to-cyan-500/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Pembayaran</p>
                                <h2 class="mt-1 text-2xl font-black text-navy dark:text-white">Tagihan Kuliah</h2>
                            </div>
                            <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-black text-orange-700 dark:bg-orange-500/10 dark:text-orange-200">{{ $activeBillStatus }}</span>
                        </div>
                        <div class="mt-6 rounded-[1.5rem] bg-navy p-5 text-white shadow-glow">
                            <p class="text-sm font-bold text-cyan-100">Total tagihan aktif bulan {{ $billingMonthLabel }}</p>
                            <strong class="mt-2 block text-3xl font-black">Rp {{ number_format($activeBillAmount, 0, ',', '.') }}</strong>
                            <p class="mt-4 text-xs font-semibold text-sky-100">Invoice terakhir: {{ $invoiceNumber }}</p>
                            <p class="mt-1 text-xs font-semibold text-sky-100">VA: {{ $virtualAccount }}</p>
                        </div>
                        <div class="mt-5 grid gap-3">
                            @forelse ($currentMonthPayments as $bill)
                                <div class="flex items-center justify-between rounded-2xl bg-white p-4 dark:bg-white/10">
                                    <span>
                                        <strong class="block text-sm font-black">{{ $bill->payment_label ?: 'Bulan '.$bill->month }}</strong>
                                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $componentLabel($bill) }} - Jatuh tempo {{ $bill->due_date?->translatedFormat('d M Y') }}</span>
                                    </span>
                                    <span class="text-right">
                                        <strong class="block text-sm font-black text-navy dark:text-white">Rp {{ number_format($bill->amount, 0, ',', '.') }}</strong>
                                        <span class="mt-1 inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-white/10 dark:text-slate-200">{{ str($bill->status)->replace('_', ' ')->title() }}</span>
                                    </span>
                                </div>
                            @empty
                                <div class="rounded-2xl bg-white p-5 text-center dark:bg-white/10">
                                    <i data-lucide="badge-check" class="mx-auto h-8 w-8 text-cyan-600 dark:text-cyan-200"></i>
                                    <strong class="mt-3 block text-sm font-black text-navy dark:text-white">Tidak ada tagihan aktif bulan ini</strong>
                                    <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Tagihan akan muncul otomatis jika tanggal rencana pembayaran masuk bulan berjalan.</p>
                                </div>
                            @endforelse
                        </div>
                        <a href="{{ route('student-portal.payments') }}#bayar-sekarang" class="mt-5 block w-full rounded-2xl bg-cyanx px-5 py-4 text-center font-black text-white shadow-glow transition hover:-translate-y-1">Bayar Sekarang</a>
                    </article>
                </section>

                <section class="mt-6 grid gap-6 xl:grid-cols-[.9fr_1.1fr]">
                    <article id="akademik" class="reveal rounded-[2rem] border border-white/70 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/10">
                        <p class="text-sm font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Akademik</p>
                        <h2 class="mt-1 text-2xl font-black text-navy dark:text-white">Analytics semester</h2>
                        <div class="mt-6 h-56 rounded-3xl bg-slate-50 p-5 dark:bg-white/5">
                            <div class="flex h-full items-end gap-3">
                                @foreach ([2.9, 3.2, 3.4, 3.72, 3.61, 3.8] as $ip)
                                    <div class="flex flex-1 flex-col items-center gap-2">
                                        <span class="w-full rounded-t-2xl bg-gradient-to-t from-indigo-500 to-cyan-300 shadow-glow" style="height: {{ $ip / 4 * 100 }}%"></span>
                                        <span class="text-xs font-black text-slate-500 dark:text-slate-400">{{ $ip }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5"><p class="text-xs font-bold text-slate-500">Status</p><strong class="mt-1 block">Aktif</strong></div>
                            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5"><p class="text-xs font-bold text-slate-500">SKS Lulus</p><strong class="mt-1 block">86/144</strong></div>
                            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5"><p class="text-xs font-bold text-slate-500">KHS</p><strong class="mt-1 block">Ready</strong></div>
                        </div>
                    </article>

                    <article id="pengumuman" class="reveal rounded-[2rem] border border-white/70 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/10">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Notifikasi</p>
                                <h2 class="mt-1 text-2xl font-black text-navy dark:text-white">Pengumuman terbaru</h2>
                            </div>
                            <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-black text-red-700 dark:bg-red-500/10 dark:text-red-200">4 baru</span>
                        </div>
                        <div class="mt-6 space-y-3">
                            @foreach ([['Jadwal UTS sudah tersedia', 'Cek kalender akademik dan siapkan kartu ujian.', 'calendar-clock'], ['Deadline tugas Basis Data', 'Kumpulkan ERD final sebelum Jumat 23:59.', 'clipboard-list'], ['Info pembayaran semester', 'Tagihan aktif bisa dibayar melalui VA.', 'wallet'], ['Kampus update', 'Konsultasi akademik dibuka setiap Rabu.', 'megaphone']] as $notice)
                                <div class="flex gap-4 rounded-3xl bg-slate-50 p-4 dark:bg-white/5">
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-white text-cyan-700 dark:bg-white/10 dark:text-cyan-200"><i data-lucide="{{ $notice[2] }}" class="h-5 w-5"></i></span>
                                    <span>
                                        <strong class="block font-black text-navy dark:text-white">{{ $notice[0] }}</strong>
                                        <span class="mt-1 block text-sm leading-6 text-slate-600 dark:text-slate-400">{{ $notice[1] }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </article>
                </section>

                <section id="profil-mahasiswa" class="mt-6 grid gap-6 xl:grid-cols-[1fr_.8fr]">
                    <article class="reveal rounded-[2rem] border border-white/70 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/10">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <div class="grid h-24 w-24 place-items-center rounded-[2rem] bg-gradient-to-br from-navy to-cyanx text-4xl font-black text-white shadow-glow">{{ $avatarInitial }}</div>
                            <div class="flex-1">
                                <p class="text-sm font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Profile Mahasiswa</p>
                                <h2 class="mt-1 text-3xl font-black text-navy dark:text-white">{{ $studentName }}</h2>
                                <p class="mt-2 font-semibold text-slate-500 dark:text-slate-400">{{ $lead->campus->name }} - {{ $lead->studyProgram->name }}</p>
                            </div>
                            <a href="{{ route('student-portal.profile') }}" class="rounded-full bg-navy px-5 py-3 text-sm font-black text-white dark:bg-white dark:text-navy">Edit Profile</a>
                        </div>
                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            @foreach ([['Email', $account->email], ['No WhatsApp', $lead->whatsapp_number], ['NIM', $nim], ['Verifikasi', $account->email_verified_at ? 'Terverifikasi' : 'Belum verifikasi'], ['Orang Tua', 'Belum dilengkapi'], ['Alamat', 'Belum dilengkapi']] as $profile)
                                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-white/5">
                                    <p class="text-xs font-bold text-slate-500">{{ $profile[0] }}</p>
                                    <p class="mt-1 font-black">{{ $profile[1] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </article>

                    <article class="reveal rounded-[2rem] border border-cyan-200 bg-gradient-to-br from-cyan-50 to-indigo-50 p-6 shadow-sm dark:border-white/10 dark:from-cyan-500/10 dark:to-indigo-500/10">
                        <div class="flex items-center gap-3">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-cyan-700 shadow-sm dark:bg-white/10 dark:text-cyan-200"><i data-lucide="sparkles" class="h-6 w-6"></i></span>
                            <div>
                                <h2 class="text-xl font-black text-navy dark:text-white">Motivasi hari ini</h2>
                                <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Satu langkah kecil tetap membawa kamu lebih dekat ke gelar.</p>
                            </div>
                        </div>
                        <div class="mt-6 rounded-3xl bg-white p-5 dark:bg-white/10">
                            <p class="leading-7 text-slate-700 dark:text-slate-200">Target minggu ini: selesaikan 2 tugas, cek jadwal UTS, dan pastikan tagihan aktif sudah diproses.</p>
                        </div>
                    </article>
                </section>

            </main>
        </div>
    </div>

    <nav class="fixed bottom-0 left-0 right-0 z-40 border-t border-white/70 glass px-3 py-2 dark:border-white/10 lg:hidden">
        <div class="grid grid-cols-5 gap-1">
            @foreach ([['Dashboard', 'layout-dashboard', '#dashboard'], ['Berkas', 'folder-up', route('student-portal.documents')], ['Bayar', 'credit-card', route('student-portal.payments')], ['Affiliator', 'hand-coins', route('student-portal.affiliate')], ['Profil', 'user-round', route('student-portal.profile')]] as $item)
                <a href="{{ $item[2] }}" class="grid justify-items-center gap-1 rounded-2xl px-2 py-2 text-xs font-black text-slate-600 dark:text-slate-300">
                    <i data-lucide="{{ $item[1] }}" class="h-5 w-5"></i>
                    <span>{{ $item[0] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    <button class="fixed bottom-24 right-5 z-40 grid h-14 w-14 place-items-center rounded-full bg-cyanx text-white shadow-glow transition hover:-translate-y-1 lg:bottom-6" aria-label="Quick action">
        <i data-lucide="plus" class="h-7 w-7"></i>
    </button>

    <script>
        window.addEventListener('load', () => {
            document.querySelector('.skeleton-loader')?.classList.add('is-hidden');
        });

        const root = document.documentElement;
        const savedTheme = localStorage.getItem('student-theme');
        if (savedTheme === 'dark') root.classList.add('dark');

        document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
            root.classList.toggle('dark');
            localStorage.setItem('student-theme', root.classList.contains('dark') ? 'dark' : 'light');
        });

        const sidebar = document.getElementById('sidebar');
        document.querySelector('[data-open-sidebar]')?.addEventListener('click', () => sidebar.classList.remove('-translate-x-full'));
        document.querySelector('[data-close-sidebar]')?.addEventListener('click', () => sidebar.classList.add('-translate-x-full'));

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) entry.target.classList.add('is-visible');
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));

        lucide.createIcons();
    </script>
</body>
</html>
