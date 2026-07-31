<x-filament-panels::page>
    @php
        $lead = $this->lead;
        $lead->loadMissing(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'latestInvoice', 'studentPayments' => fn ($query) => $query->orderBy('month')]);
        $schedulePayments = $lead->studentPayments
            ->filter(fn ($payment) => $payment->payment_type !== 'manual')
            ->values();
        $selectionNumber = $lead->studentBiodata?->selection_number ?? app(\App\Services\StudentBiodataProvisioner::class)->selectionNumberFor($lead);
        $virtualAccount = $lead->latestInvoice?->va_number ?: ($lead->latestInvoice?->gateway_reference ?: '-');
        $formPayment = $schedulePayments->firstWhere('month', 0);
        $semesterPayments = $schedulePayments->where('month', '>', 0)->values();
        $semesterGroups = $semesterPayments->groupBy(fn ($payment) => (int) ceil($payment->month / 6));
        $cancelUrl = $this->redirectUrlForLead();
        $locked = $this->planIsLocked();
    @endphp

    <form wire:submit.prevent="savePayments" class="spmm-payment-detail">
        @if ($locked)
            <div class="fi-in-text-message">
                <p style="padding: 0.85rem 1.1rem; border-radius: 0.9rem; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); color: #b91c1c; font-weight: 700; font-size: 0.85rem;">
                    Lead ini belum punya biodata mahasiswa baru. Rencana dan realita pembayaran hanya bisa diedit setelah biodata dibuat — lengkapi biodata terlebih dahulu untuk membuka mode edit.
                </p>
            </div>
        @endif

        <section class="spmm-payment-hero">
            <div>
                <span class="spmm-report-kicker">Ubah Pembayaran Mahasiswa</span>
                <h2>{{ $lead->full_name }}</h2>
                <p>{{ $selectionNumber }} - {{ $lead->studyProgram?->name ?? 'Jurusan belum diisi' }} - {{ $lead->campus?->name ?? 'Kampus belum diisi' }}</p>
            </div>
            <div class="spmm-payment-summary">
                <span>Virtual Account</span>
                <strong>{{ $virtualAccount }}</strong>
                <small>Status: {{ str($lead->payment_status->value ?? $lead->payment_status)->replace('_', ' ')->title() }}</small>
            </div>
        </section>

        <section class="spmm-payment-stats">
            <div>
                <span>No. Seleksi</span>
                <strong>{{ $selectionNumber }}</strong>
            </div>
            <div>
                <span>KLP</span>
                <strong>{{ $lead->studentBiodata?->group ?: ($lead->classTrack?->name ?: '-') }}</strong>
            </div>
            <div>
                <span>JRS</span>
                <strong>{{ $lead->studyProgram?->name ?? '-' }}</strong>
            </div>
        </section>

        <section class="spmm-payment-planning">
            <div>
                <span class="spmm-report-kicker">Jalankan Perencanaan</span>
                <h3>Atur awal kalender pembayaran</h3>
                <p>Pembayaran ke-1 bisa dipakai untuk herregistrasi. Pembayaran ke-2 menjadi awal masa kuliah aktif, lalu bulan berikutnya mengikuti otomatis.</p>
            </div>
            <div class="spmm-payment-planning-fields">
                <label>
                    <span>Pembayaran ke-1</span>
                    <input type="date" wire:model.change="planningFirstPaymentDate" @disabled($locked)>
                </label>
                <label>
                    <span>Pembayaran ke-2</span>
                    <input type="date" wire:model.change="planningSecondPaymentDate" @disabled($locked)>
                </label>
                <button type="button" wire:click="runPlanning" @disabled($locked)>Jalankan Perencanaan</button>
            </div>
        </section>

        <section class="spmm-payment-table-card">
            <header>
                <div>
                    <h3>Tabel Rencana dan Realita Pembayaran</h3>
                    <p>Komponen dengan nominal 0 tidak ditampilkan. Baris pendaftaran hanya menampilkan formulir.</p>
                </div>
                <div class="spmm-payment-savebar spmm-payment-savebar-inline">
                    <a href="{{ $cancelUrl }}">Batal</a>
                    <button type="submit" @disabled($locked)>Simpan Pembayaran</button>
                </div>
            </header>

            <div class="overflow-x-auto">
                <table class="spmm-payment-table spmm-payment-edit-table">
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
                                <td>
                                    <label>Formulir</label>
                                    <input type="number" wire:model.defer="paymentRows.{{ $formPayment->id }}.registration_fee" @disabled($locked)>
                                </td>
                                <td><input type="number" wire:model.defer="paymentRows.{{ $formPayment->id }}.amount" @disabled($locked)></td>
                                <td><input type="date" wire:model.change="paymentRows.{{ $formPayment->id }}.due_date" @disabled($locked)></td>
                                <td>
                                    <select wire:model.defer="paymentRows.{{ $formPayment->id }}.status" @disabled($locked)>
                                        <option value="unpaid">Belum dibayar</option>
                                        <option value="pending">Menunggu verifikasi</option>
                                        <option value="paid">Lunas</option>
                                        <option value="waived">Dibebaskan</option>
                                    </select>
                                </td>
                                <td><input type="datetime-local" wire:model.defer="paymentRows.{{ $formPayment->id }}.paid_at" @disabled($locked)></td>
                                <td><input type="text" wire:model.defer="paymentRows.{{ $formPayment->id }}.notes" @disabled($locked)></td>
                            </tr>
                        @endif

                        @forelse ($semesterGroups as $semester => $payments)
                            <tr class="spmm-payment-semester-row">
                                <td colspan="7">Semester {{ $semester }} - Bulan {{ (($semester - 1) * 6) + 1 }} sampai {{ $semester * 6 }}</td>
                            </tr>

                            @foreach ($payments as $payment)
                                @php
                                    $components = collect([
                                        ['key' => 'development_fee', 'label' => 'Development', 'amount' => $payment->development_fee],
                                        ['key' => 'tuition_fee', 'label' => 'Tuition', 'amount' => $payment->tuition_fee],
                                        ['key' => 'ukt', 'label' => 'UKT', 'amount' => $payment->ukt],
                                    ])->filter(fn ($component) => (int) $component['amount'] > 0);
                                @endphp

                                <tr>
                                    <td class="font-bold">Bulan {{ $payment->month }}</td>
                                    <td>
                                        <div class="spmm-payment-component-stack">
                                            @forelse ($components as $component)
                                                <label>{{ $component['label'] }}</label>
                                                <input type="number" wire:model.defer="paymentRows.{{ $payment->id }}.{{ $component['key'] }}" @disabled($locked)>
                                            @empty
                                                -
                                            @endforelse
                                        </div>
                                    </td>
                                    <td><input type="number" wire:model.defer="paymentRows.{{ $payment->id }}.amount" @disabled($locked)></td>
                                    <td><input type="date" wire:model.change="paymentRows.{{ $payment->id }}.due_date" @disabled($locked)></td>
                                    <td>
                                        <select wire:model.defer="paymentRows.{{ $payment->id }}.status" @disabled($locked)>
                                            <option value="unpaid">Belum dibayar</option>
                                            <option value="pending">Menunggu verifikasi</option>
                                            <option value="paid">Lunas</option>
                                            <option value="waived">Dibebaskan</option>
                                        </select>
                                    </td>
                                    <td><input type="datetime-local" wire:model.defer="paymentRows.{{ $payment->id }}.paid_at" @disabled($locked)></td>
                                    <td><input type="text" wire:model.defer="paymentRows.{{ $payment->id }}.notes" @disabled($locked)></td>
                                </tr>
                            @endforeach
                        @empty
                            @unless ($formPayment)
                                <tr>
                                    <td colspan="7">Belum ada jadwal pembayaran.</td>
                                </tr>
                            @endunless
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </form>
</x-filament-panels::page>
