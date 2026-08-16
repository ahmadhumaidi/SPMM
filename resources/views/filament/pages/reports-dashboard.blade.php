<x-filament-panels::page>
    @php
        $payment = $this->getPaymentSummary();
        $sections = [
            ['title' => 'Lead per kampus', 'rows' => $this->getLeadByCampus()],
            ['title' => 'Lead per status', 'rows' => $this->getLeadByStatus()],
            ['title' => 'Lead per program studi', 'rows' => $this->getLeadByStudyProgram()],
        ];
        $totalLeads = $sections[0]['rows']->sum('total');
        $stats = [
            ['label' => 'Transaction', 'value' => number_format($payment['transaction']), 'tone' => 'from-cyan-500 to-indigo-500'],
            ['label' => 'Income', 'value' => 'Rp '.number_format($payment['income'], 0, ',', '.'), 'tone' => 'from-emerald-500 to-teal-500'],
            ['label' => 'Expense', 'value' => 'Rp '.number_format($payment['expense'], 0, ',', '.'), 'tone' => 'from-amber-500 to-orange-500'],
            ['label' => 'Total Funding', 'value' => 'Rp '.number_format($payment['total_funding'], 0, ',', '.'), 'tone' => 'from-sky-600 to-cyan-500'],
        ];
    @endphp

    <div class="spmm-report-shell">
        <section class="spmm-report-hero">
            <div>
                <span class="spmm-report-kicker">MVP Command Center</span>
                <h2>Ringkasan performa PMB dan pembayaran.</h2>
                <p>Pantau lead, transaksi pembayaran realtime, komisi referral, dan total funding dalam satu dashboard ringkas.</p>
            </div>
            <div class="spmm-report-hero-card">
                <span>Total Lead</span>
                <strong>{{ number_format($totalLeads) }}</strong>
                <small>Data terkumpul dari portal publik dan web kampus mitra.</small>
            </div>
        </section>

        <section class="spmm-stat-grid">
            @foreach ($stats as $stat)
                <article class="spmm-stat-card">
                    <span class="spmm-stat-icon {{ $stat['tone'] }}">
                        <x-heroicon-o-chart-bar-square />
                    </span>
                    <div>
                        <p>{{ $stat['label'] }}</p>
                        <strong>{{ $stat['value'] }}</strong>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="spmm-report-grid">
            @foreach ($sections as $section)
                @php($max = max((int) $section['rows']->max('total'), 1))
                <article class="spmm-panel">
                    <header>
                        <span class="spmm-panel-icon">
                            <x-heroicon-o-chart-pie />
                        </span>
                        <div>
                            <h3>{{ $section['title'] }}</h3>
                            <p>{{ $section['rows']->count() }} kategori terpantau</p>
                        </div>
                    </header>

                    <div class="spmm-list">
                        @forelse ($section['rows'] as $row)
                            @php($percent = max(6, ((int) $row->total / $max) * 100))
                            <div class="spmm-list-row">
                                <div class="spmm-list-top">
                                    <span>{{ str($row->label)->replace('_', ' ')->title() }}</span>
                                    <strong>{{ number_format($row->total) }}</strong>
                                </div>
                                <div class="spmm-progress">
                                    <span style="width: {{ $percent }}%"></span>
                                </div>
                            </div>
                        @empty
                            <div class="spmm-empty">
                                <x-heroicon-o-inbox />
                                <span>Belum ada data.</span>
                            </div>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </section>

        <section class="spmm-export-card">
            <div>
                <span class="spmm-report-kicker">Export Data</span>
                <h3>Mahasiswa aktif</h3>
                <p>Unduh CSV mahasiswa aktif dengan field utama PDDIKTI untuk kebutuhan pelaporan.</p>
            </div>
            <a href="{{ route('exports.active-students.csv') }}">
                <x-heroicon-o-arrow-down-tray />
                Download CSV
            </a>
        </section>
    </div>
</x-filament-panels::page>
