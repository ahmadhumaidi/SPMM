@php
    $lead = $account->lead;
    $studentName = $lead->full_name;
    $nim = $lead->studentNumber?->nim ?? 'NIM sementara';
    $avatarInitial = mb_substr($studentName, 0, 1);
    $statusLabel = [
        'pending' => 'Menunggu pembayaran mahasiswa',
        'approved' => 'Komisi disetujui',
        'paid' => 'Komisi dibayar',
        'cancelled' => 'Dibatalkan',
    ];
@endphp

<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Affiliator | Kampus Media</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: { navy: '#071a3d', cyanx: '#06b6d4', gold: '#f59e0b' },
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
                <span class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-navy to-cyanx font-black text-white">KN</span>
                <span>
                    <span class="block font-black text-navy dark:text-white">Dashboard Affiliator</span>
                    <span class="block text-xs font-bold text-slate-500 dark:text-slate-400">Sebarkan informasi, dapatkan komisi</span>
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
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-navy via-[#0b2d63] to-cyan-700 p-6 text-white shadow-glow lg:p-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_25rem] lg:items-center">
                <div>
                    <p class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-black text-cyan-100">Program Afiliasi Mahasiswa</p>
                    <h1 class="mt-5 text-3xl font-black leading-tight sm:text-5xl">Bantu kampus menyebarkan informasi PMB dan raih komisi hingga jutaan rupiah.</h1>
                    <p class="mt-4 max-w-3xl leading-8 text-sky-50">Bagikan link referral pribadi kamu ke teman, saudara, rekan kerja, atau komunitas. Setiap pendaftar yang masuk lewat link kamu akan tercatat otomatis di sistem pusat.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ $referralLink }}" target="_blank" class="rounded-full bg-white px-5 py-3 text-sm font-black text-navy transition hover:-translate-y-1">Buka Link</a>
                        <button type="button" data-copy-link class="rounded-full bg-white/12 px-5 py-3 text-sm font-black backdrop-blur transition hover:-translate-y-1 hover:bg-white/20">Copy Link Referral</button>
                    </div>
                </div>

                <div class="rounded-[1.75rem] border border-white/15 bg-white/10 p-5 backdrop-blur">
                    <div class="flex items-center gap-4">
                        <span class="grid h-16 w-16 place-items-center rounded-3xl bg-white text-2xl font-black text-navy">{{ $avatarInitial }}</span>
                        <div>
                            <strong class="block text-xl font-black">{{ $studentName }}</strong>
                            <span class="mt-1 block text-sm font-semibold text-sky-100">{{ $nim }}</span>
                        </div>
                    </div>
                    <div class="mt-5 rounded-3xl bg-white/10 p-4">
                        <p class="text-xs font-bold text-cyan-100">Kode referral</p>
                        <strong class="mt-1 block text-2xl font-black tracking-wide">{{ $partner->referral_code }}</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-4 md:grid-cols-4">
            @foreach ([['Pendaftar', $registeredCount, 'user-plus'], ['Sudah Bayar', $paidCount, 'badge-check'], ['Komisi Disetujui', 'Rp '.number_format($approvedCommission, 0, ',', '.'), 'hand-coins'], ['Komisi Dibayar', 'Rp '.number_format($paidCommission, 0, ',', '.'), 'wallet']] as $card)
                <article class="rounded-[1.75rem] border border-white/70 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/10">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-black text-slate-500 dark:text-slate-400">{{ $card[0] }}</p>
                        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-cyan-50 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-200">
                            <i data-lucide="{{ $card[2] }}" class="h-5 w-5"></i>
                        </span>
                    </div>
                    <strong class="mt-4 block text-2xl font-black text-navy dark:text-white">{{ $card[1] }}</strong>
                </article>
            @endforeach
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-[.85fr_1.15fr]">
            <article class="rounded-[2rem] border border-white/70 bg-white p-6 shadow-soft dark:border-white/10 dark:bg-white/10">
                <p class="text-sm font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Link Referral Pribadi</p>
                <h2 class="mt-1 text-2xl font-black text-navy dark:text-white">Bagikan link ini</h2>
                <div class="mt-5 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-white/10 dark:bg-white/5">
                    <p class="break-all text-sm font-black text-slate-700 dark:text-slate-200" data-referral-link>{{ $referralLink }}</p>
                </div>
                <div class="mt-5 grid gap-3">
                    @foreach ([['Share ke WhatsApp', 'message-circle', 'https://wa.me/?text='.urlencode('Yuk daftar kuliah lewat link saya: '.$referralLink)], ['Buka Form Daftar', 'external-link', $referralLink]] as $action)
                        <a href="{{ $action[2] }}" target="_blank" class="flex items-center justify-between rounded-2xl bg-slate-50 p-4 font-black text-navy transition hover:-translate-y-1 hover:bg-cyan-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                            <span>{{ $action[0] }}</span>
                            <i data-lucide="{{ $action[1] }}" class="h-5 w-5 text-cyan-600"></i>
                        </a>
                    @endforeach
                </div>
                <p class="mt-5 text-sm font-semibold leading-6 text-slate-500 dark:text-slate-400">Komisi akan dihitung setelah calon mahasiswa melakukan pembayaran sesuai aturan kampus dan diverifikasi oleh sistem pusat.</p>
            </article>

            <article class="overflow-hidden rounded-[2rem] border border-white/70 bg-white shadow-soft dark:border-white/10 dark:bg-white/10">
                <div class="border-b border-slate-100 p-5 dark:border-white/10">
                    <p class="text-sm font-black uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Perolehan Komisi</p>
                    <h2 class="mt-1 text-2xl font-black text-navy dark:text-white">Status pendaftar referral</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-[760px] w-full border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-white/5 dark:text-slate-300">
                                <th class="px-5 py-4">Nama</th>
                                <th class="px-5 py-4">Kampus</th>
                                <th class="px-5 py-4">Prodi</th>
                                <th class="px-5 py-4">Komisi</th>
                                <th class="px-5 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                            @forelse ($conversions as $conversion)
                                <tr class="transition hover:bg-cyan-50/60 dark:hover:bg-white/5">
                                    <td class="px-5 py-4 font-black text-navy dark:text-white">{{ $conversion->lead?->full_name ?? '-' }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">{{ $conversion->lead?->campus?->name ?? '-' }}</td>
                                    <td class="px-5 py-4 font-semibold text-slate-600 dark:text-slate-300">{{ $conversion->lead?->studyProgram?->name ?? '-' }}</td>
                                    <td class="px-5 py-4 font-black text-navy dark:text-white">Rp {{ number_format($conversion->commission_amount, 0, ',', '.') }}</td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $conversion->commission_status === 'paid' ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-200' : ($conversion->commission_status === 'approved' ? 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-200' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-200') }}">
                                            {{ $statusLabel[$conversion->commission_status] ?? str($conversion->commission_status)->title() }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-10 text-center">
                                        <i data-lucide="users-round" class="mx-auto h-10 w-10 text-cyan-600 dark:text-cyan-200"></i>
                                        <strong class="mt-3 block text-navy dark:text-white">Belum ada pendaftar dari link referral kamu</strong>
                                        <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-slate-400">Mulai bagikan link ke teman, saudara, rekan kerja, dan komunitasmu.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </main>

    <script>
        const root = document.documentElement;
        if (localStorage.getItem('student-theme') === 'dark') root.classList.add('dark');
        document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
            root.classList.toggle('dark');
            localStorage.setItem('student-theme', root.classList.contains('dark') ? 'dark' : 'light');
        });

        document.querySelector('[data-copy-link]')?.addEventListener('click', async () => {
            const text = document.querySelector('[data-referral-link]')?.textContent?.trim();
            if (! text) return;
            await navigator.clipboard.writeText(text);
            alert('Link referral berhasil disalin.');
        });

        lucide.createIcons();
    </script>
</body>
</html>
