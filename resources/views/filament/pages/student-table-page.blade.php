<x-filament-panels::page>
    <div class="spmm-student-page">
        <section class="spmm-student-page-hero">
            <div>
                <span class="spmm-report-kicker">Menu Mahasiswa</span>
                <h2>{{ $this->moduleTitle }}</h2>
                <p>{{ $this->moduleDescription }}</p>
            </div>
        </section>

        <section class="spmm-student-page-table">
            {{ $this->table }}
        </section>
    </div>
</x-filament-panels::page>
