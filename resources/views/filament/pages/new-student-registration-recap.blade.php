<x-filament-panels::page>
    @php
        $rows = $this->getRecapRows();
        $tracks = $this->getTrackColumns();
        $totals = $this->getTotals($rows);

        $formatPercentage = fn (float $value): string => rtrim(rtrim(number_format($value, 2), '0'), '.').'%';
    @endphp

    <section class="spmm-registration-recap">
        <header>
            <div>
                <span class="spmm-report-kicker">Mahasiswa Baru</span>
                <h2>Rekapitulasi Pendaftaran Mahasiswa Baru</h2>
                <p>Lead adalah calon mahasiswa yang masuk. Registrasi adalah pembayaran formulir pendaftaran. Herregistrasi adalah pembayaran awal kuliah setelah registrasi.</p>
            </div>
        </header>

        <div class="spmm-registration-recap-filters">
            <label>
                <span>Angkatan</span>
                <select wire:model.live="cohortYear">
                    <option value="">Semua Angkatan</option>
                    @foreach ($this->getCohortOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label>
                <span>Kampus</span>
                <select wire:model.live="campusId">
                    <option value="">Semua Kampus</option>
                    @foreach ($this->getCampusOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="spmm-registration-recap-table-wrap">
            <table class="spmm-registration-recap-table">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>Kode<br>Jurusan</th>
                        <th>Nama Jurusan</th>
                        @foreach ($tracks as $track)
                            <th>Lead<br>{{ $track['label'] }}</th>
                            <th>Registrasi<br>{{ $track['label'] }}</th>
                            <th>Herregistrasi<br>{{ $track['label'] }}</th>
                        @endforeach
                        <th>Total<br>Lead</th>
                        <th>Total<br>Registrasi</th>
                        <th>Total<br>Herregistrasi</th>
                        <th>(%)<br>Registrasi</th>
                        <th>(%)<br>Herregistrasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['code'] }}</td>
                            <td class="spmm-registration-recap-name">{{ $row['name'] }}</td>
                            @foreach ($tracks as $track)
                                <td>{{ $row['tracks'][$track['key']]['lead'] ?? 0 }}</td>
                                <td>{{ $row['tracks'][$track['key']]['registration'] ?? 0 }}</td>
                                <td>{{ $row['tracks'][$track['key']]['herregistration'] ?? 0 }}</td>
                            @endforeach
                            <td>{{ $row['total_lead'] }}</td>
                            <td>{{ $row['total_registration'] }}</td>
                            <td>{{ $row['total_herregistration'] }}</td>
                            <td class="spmm-registration-recap-percent">{{ $formatPercentage($row['registration_percentage']) }}</td>
                            <td class="spmm-registration-recap-percent">{{ $formatPercentage($row['herregistration_percentage']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 8 + (count($tracks) * 3) }}" class="spmm-registration-recap-empty">Belum ada data pendaftaran untuk filter ini.</td>
                        </tr>
                    @endforelse

                    <tr class="spmm-registration-recap-total">
                        <td colspan="3">{{ $totals['name'] }}</td>
                        @foreach ($tracks as $track)
                            <td>{{ $totals['tracks'][$track['key']]['lead'] ?? 0 }}</td>
                            <td>{{ $totals['tracks'][$track['key']]['registration'] ?? 0 }}</td>
                            <td>{{ $totals['tracks'][$track['key']]['herregistration'] ?? 0 }}</td>
                        @endforeach
                        <td>{{ $totals['total_lead'] }}</td>
                        <td>{{ $totals['total_registration'] }}</td>
                        <td>{{ $totals['total_herregistration'] }}</td>
                        <td>{{ $formatPercentage($totals['registration_percentage']) }}</td>
                        <td>{{ $formatPercentage($totals['herregistration_percentage']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</x-filament-panels::page>
