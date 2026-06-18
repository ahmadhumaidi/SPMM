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
                <p>Data dikelompokkan berdasarkan program studi dan program perkuliahan yang tersimpan di database.</p>
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
                            <th>Reg<br>{{ $track['label'] }}</th>
                            <th>Her<br>{{ $track['label'] }}</th>
                        @endforeach
                        <th>Total<br>Registrasi</th>
                        <th>Total Her<br>Registrasi</th>
                        <th>(%) Her<br>Registrasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['code'] }}</td>
                            <td class="spmm-registration-recap-name">{{ $row['name'] }}</td>
                            @foreach ($tracks as $track)
                                <td>{{ $row['tracks'][$track['key']]['reg'] ?? 0 }}</td>
                                <td>{{ $row['tracks'][$track['key']]['her'] ?? 0 }}</td>
                            @endforeach
                            <td>{{ $row['total_reg'] }}</td>
                            <td>{{ $row['total_her'] }}</td>
                            <td class="spmm-registration-recap-percent">{{ $formatPercentage($row['percentage']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + (count($tracks) * 2) }}" class="spmm-registration-recap-empty">Belum ada data pendaftaran untuk filter ini.</td>
                        </tr>
                    @endforelse

                    <tr class="spmm-registration-recap-total">
                        <td colspan="3">{{ $totals['name'] }}</td>
                        @foreach ($tracks as $track)
                            <td>{{ $totals['tracks'][$track['key']]['reg'] ?? 0 }}</td>
                            <td>{{ $totals['tracks'][$track['key']]['her'] ?? 0 }}</td>
                        @endforeach
                        <td>{{ $totals['total_reg'] }}</td>
                        <td>{{ $totals['total_her'] }}</td>
                        <td>{{ $formatPercentage($totals['percentage']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</x-filament-panels::page>
