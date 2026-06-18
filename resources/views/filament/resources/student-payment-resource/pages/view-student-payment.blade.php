<x-filament-panels::page>
    @php
        $record->loadMissing(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'latestInvoice', 'studentPayments' => fn ($query) => $query->orderBy('month')]);
        $selectionNumber = $record->studentBiodata?->selection_number ?? \App\Filament\Resources\StudentPaymentResource::selectionNumberFor($record);
        $virtualAccount = $record->latestInvoice?->va_number ?: ($record->latestInvoice?->gateway_reference ?: '-');
        $registrationInvoice = $record->latestInvoice;
        $formPayment = $record->studentPayments->firstWhere('month', 0);
        $semesterPayments = $record->studentPayments->where('month', '>', 0)->values();
        $semesterGroups = $semesterPayments->groupBy(fn ($payment) => (int) ceil($payment->month / 6));
        $paidTotal = $record->studentPayments->where('status', 'paid')->sum('amount');
        $plannedTotal = $record->studentPayments->sum('amount');
        $herregistrationTotal = $semesterPayments->sum('amount');
    @endphp

    <div class="spmm-payment-detail">
        <section class="spmm-payment-hero">
            <div>
                <span class="spmm-report-kicker">Pembayaran Mahasiswa</span>
                <h2>{{ $record->full_name }}</h2>
                <p>{{ $selectionNumber }} - {{ $record->studyProgram?->name ?? 'Jurusan belum diisi' }} - {{ $record->campus?->name ?? 'Kampus belum diisi' }}</p>
            </div>
            <div class="spmm-payment-summary">
                <span>Virtual Account</span>
                <strong>{{ $virtualAccount }}</strong>
                <small>Status: {{ str($record->payment_status->value ?? $record->payment_status)->replace('_', ' ')->title() }}</small>
            </div>
        </section>

        <section class="spmm-payment-stats spmm-payment-registration">
            <div>
                <span>Formulir Pendaftaran</span>
                <strong>Rp {{ number_format($formPayment?->amount ?? $registrationInvoice?->amount ?? 0, 0, ',', '.') }}</strong>
            </div>
            <div>
                <span>Tgl Rencana Formulir</span>
                <strong>{{ ($formPayment?->due_date ?? $record->created_at)?->format('d M Y') ?? '-' }}</strong>
            </div>
            <div>
                <span>Status Pendaftaran</span>
                <strong>{{ str($formPayment?->status ?? $registrationInvoice?->status?->value ?? $registrationInvoice?->status ?? '-')->replace('_', ' ')->title() }}</strong>
            </div>
        </section>

        <section class="spmm-payment-stats">
            <div>
                <span>Total Rencana</span>
                <strong>Rp {{ number_format($plannedTotal, 0, ',', '.') }}</strong>
            </div>
            <div>
                <span>Rencana Semester 1 dst.</span>
                <strong>Rp {{ number_format($herregistrationTotal, 0, ',', '.') }}</strong>
            </div>
            <div>
                <span>Total Realita Lunas</span>
                <strong>Rp {{ number_format($paidTotal, 0, ',', '.') }}</strong>
            </div>
        </section>

        <section class="spmm-payment-table-card">
            <header>
                <h3>Tabel Rencana dan Realita Pembayaran</h3>
                <p>Formulir pendaftaran berada paling atas dan tidak dihitung sebagai bulan 1. Bulan 1-48 dipisahkan per semester.</p>
            </header>

            <div class="overflow-x-auto">
                <table class="spmm-payment-table">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Komponen Rencana</th>
                            <th>Total Rencana</th>
                            <th>Tgl Rencana Pembayaran</th>
                            <th>Realita</th>
                            <th>Tanggal Realita</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($formPayment)
                            <tr class="spmm-payment-registration-row">
                                <td class="font-bold">{{ $formPayment->payment_label }}</td>
                                <td>Formulir Rp {{ number_format($formPayment->registration_fee, 0, ',', '.') }}</td>
                                <td class="font-bold">Rp {{ number_format($formPayment->amount, 0, ',', '.') }}</td>
                                <td>{{ $formPayment->due_date?->format('d M Y') ?? '-' }}</td>
                                <td>
                                    <span class="spmm-payment-badge spmm-payment-badge-{{ $formPayment->status }}">
                                        {{ match ($formPayment->status) {
                                            'paid' => 'Lunas',
                                            'pending' => 'Menunggu',
                                            'waived' => 'Dibebaskan',
                                            default => 'Belum dibayar',
                                        } }}
                                    </span>
                                </td>
                                <td>{{ $formPayment->paid_at?->format('d M Y H:i') ?? '-' }}</td>
                                <td>{{ $formPayment->notes ?: '-' }}</td>
                            </tr>
                        @endif

                        @forelse ($semesterGroups as $semester => $payments)
                            <tr class="spmm-payment-semester-row">
                                <td colspan="7">Semester {{ $semester }} - Bulan {{ (($semester - 1) * 6) + 1 }} sampai {{ $semester * 6 }}</td>
                            </tr>

                            @foreach ($payments as $payment)
                                @php
                                    $components = collect([
                                        ['label' => 'Development', 'amount' => $payment->development_fee],
                                        ['label' => 'Tuition', 'amount' => $payment->tuition_fee],
                                        ['label' => 'UKT', 'amount' => $payment->ukt],
                                    ])->filter(fn ($component) => (int) $component['amount'] > 0);
                                @endphp
                                <tr>
                                    <td class="font-bold">Bulan {{ $payment->month }}</td>
                                    <td>
                                        @forelse ($components as $component)
                                            <span>{{ $component['label'] }} Rp {{ number_format($component['amount'], 0, ',', '.') }}</span>{{ ! $loop->last ? ' | ' : '' }}
                                        @empty
                                            -
                                        @endforelse
                                    </td>
                                    <td class="font-bold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                    <td>{{ $payment->due_date?->format('d M Y') ?? '-' }}</td>
                                    <td>
                                        <span class="spmm-payment-badge spmm-payment-badge-{{ $payment->status }}">
                                            {{ match ($payment->status) {
                                                'paid' => 'Lunas',
                                                'pending' => 'Menunggu',
                                                'waived' => 'Dibebaskan',
                                                default => 'Belum dibayar',
                                            } }}
                                        </span>
                                    </td>
                                    <td>{{ $payment->paid_at?->format('d M Y H:i') ?? '-' }}</td>
                                    <td>{{ $payment->notes ?: '-' }}</td>
                                </tr>
                            @endforeach
                        @empty
                            @unless ($formPayment)
                                <tr>
                                    <td colspan="7">Belum ada jadwal pembayaran. Klik tombol Generate semua jadwal di halaman daftar.</td>
                                </tr>
                            @endunless
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
