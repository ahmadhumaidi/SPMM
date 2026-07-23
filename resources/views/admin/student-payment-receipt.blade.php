@php
    $campusLogo = $lead->campus?->logo_path ? url(\Illuminate\Support\Facades\Storage::url($lead->campus->logo_path)) : null;
    $kampusMediaLogo = url('images/optimized/kampus-media-logo.png');
    $nim = $lead->studentNumber?->nim ?: '-';
    $selectionNumber = $lead->studentBiodata?->selection_number ?? 'SEL-'.($lead->created_at?->format('Ymd') ?? now()->format('Ymd')).'-'.str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT);
    $receiptUrl = $receiptUrl ?? route('admin.student-payments.receipt', $lead);
    $rawVirtualAccount = $lead->latestInvoice?->va_number ?: $lead->latestInvoice?->gateway_reference;
    $virtualAccount = preg_match('/^\d+$/', (string) $rawVirtualAccount) ? (string) $rawVirtualAccount : '000'.str_pad((string) $lead->id, 7, '0', STR_PAD_LEFT);
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&margin=8&data='.urlencode($receiptUrl);
    $campusAddressParts = array_filter([$lead->campus?->address, $lead->campus?->city, $lead->campus?->province]);
    $campusAddress = $campusAddressParts ? implode(', ', $campusAddressParts) : '-';
    $campusWebsite = $lead->campus ? $lead->campus->publicUrl() : config('spmm.public_url', 'https://kampus.media');
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Pembayaran {{ $receiptNumber }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            background: #e9eef5;
            color: #020b2d;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
        }
        .page {
            width: 900px;
            margin: 0 auto;
            padding: 8px;
        }
        .receipt {
            background: #fff;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid #dbe3ee;
        }
        .header {
            background: #193c63;
            color: #fff;
            padding: 24px 28px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }
        .logo-box {
            width: 116px;
            height: 88px;
            background: rgba(255, 255, 255, .96);
            border-radius: 14px;
            text-align: center;
            line-height: 88px;
            overflow: hidden;
        }
        .logo-box img {
            max-width: 92px;
            max-height: 68px;
            vertical-align: middle;
        }
        .logo-fallback {
            color: #193c63;
            font-size: 26px;
            font-weight: 900;
        }
        .brand {
            text-align: center;
        }
        .brand h1 {
            margin: 0;
            color: #fff;
            font-size: 30px;
            line-height: 1.1;
            font-weight: 900;
        }
        .brand p {
            margin: 6px 0 0;
            color: #fff;
            font-size: 13px;
            font-weight: 800;
        }
        .brand .campus-address {
            max-width: 430px;
            margin-left: auto;
            margin-right: auto;
            color: #dbeafe;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.35;
        }
        .content {
            padding: 34px 28px 26px;
            min-height: 700px;
        }
        .receipt-meta {
            text-align: right;
            margin-bottom: 18px;
        }
        .receipt-meta span {
            display: block;
            color: #60718d;
            font-weight: 800;
            font-size: 13px;
        }
        .receipt-meta strong {
            display: block;
            color: #00113a;
            font-size: 19px;
            margin-top: 5px;
        }
        .receipt-meta small {
            display: block;
            color: #324a6d;
            font-size: 13px;
            margin-top: 6px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 24px;
        }
        .info-table td {
            border: 0;
            width: 50%;
            padding: 0 0 12px;
            vertical-align: top;
        }
        .label {
            display: block;
            color: #60718d;
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: .02em;
        }
        .value {
            display: block;
            margin-top: 5px;
            color: #000a2a;
            font-size: 15px;
            font-weight: 800;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 14px;
        }
        .items-table th {
            background: #f1f5f9;
            color: #0e2445;
            border: 1px solid #dce5f0;
            padding: 12px 13px;
            text-align: left;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: .03em;
        }
        .items-table td {
            border: 1px solid #dce5f0;
            padding: 12px 13px;
            color: #000;
        }
        .amount { text-align: right; white-space: nowrap; }
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
        }
        .bottom-table td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }
        .qr-box {
            width: 166px;
            border: 1px solid #d5e5ff;
            border-radius: 15px;
            padding: 14px 12px 12px;
            text-align: center;
        }
        .qr-box img {
            width: 118px;
            height: 118px;
            display: block;
            margin: 0 auto;
        }
        .qr-box span {
            display: block;
            margin-top: 10px;
            color: #304767;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .total-box {
            width: 292px;
            margin-left: auto;
            border-radius: 15px;
            border: 1px solid #bae6fd;
            background: #ecfeff;
            padding: 18px 18px 16px;
            text-align: right;
        }
        .total-box span {
            display: block;
            color: #1f6f93;
            font-size: 12px;
            font-weight: 900;
            text-transform: uppercase;
        }
        .total-box strong {
            display: block;
            margin-top: 5px;
            color: #00113a;
            font-size: 27px;
            font-weight: 900;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 82px;
        }
        .footer-table td {
            border: 0;
            padding: 0;
            vertical-align: bottom;
        }
        .note {
            width: 570px;
            color: #405a80;
            font-size: 12px;
            line-height: 1.65;
        }
        .signature {
            width: 230px;
            margin-left: auto;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #8da0b8;
            padding-top: 9px;
            color: #00113a;
            font-size: 18px;
            font-weight: 900;
        }
        @page { margin: 8mm; }
    </style>
</head>
<body>
    <main class="page">
        <section class="receipt">
            <header class="header">
                <table class="header-table">
                    <tr>
                        <td style="width: 140px;">
                            <div class="logo-box">
                                @if ($campusLogo)
                                    <img src="{{ $campusLogo }}" alt="Logo {{ $lead->campus?->name }}">
                                @else
                                    <span class="logo-fallback">{{ mb_substr($lead->campus?->name ?? 'K', 0, 2) }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="brand">
                            <h1>Bukti Pembayaran</h1>
                            <p>Program Kuliah Professional (PKP)</p>
                            <p>{{ $lead->campus?->name ?? '-' }}</p>
                            <p class="campus-address">{{ $campusAddress }}</p>
                        </td>
                        <td style="width: 178px; text-align: right;">
                            <div class="logo-box" style="margin-left: auto; width: 172px;">
                                <img src="{{ $kampusMediaLogo }}" alt="Logo Kampus Media" style="max-width: 138px; max-height: 58px;">
                            </div>
                        </td>
                    </tr>
                </table>
            </header>

            <div class="content">
                <div class="receipt-meta">
                    <span>No. Kwitansi</span>
                    <strong>{{ $receiptNumber }}</strong>
                    <small>{{ now()->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i') }} WIB</small>
                </div>

                <table class="info-table">
                    <tr>
                        <td><span class="label">Nama Mahasiswa</span><span class="value">{{ $lead->full_name }}</span></td>
                        <td><span class="label">No. Seleksi</span><span class="value">{{ $selectionNumber }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="label">Kampus</span><span class="value">{{ $lead->campus?->name ?? '-' }}</span></td>
                        <td><span class="label">Program Studi</span><span class="value">{{ $lead->studyProgram?->name ?? '-' }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="label">Program Perkuliahan</span><span class="value">{{ $lead->classTrack?->name ?? '-' }}</span></td>
                        <td><span class="label">NIM</span><span class="value">{{ $nim }}</span></td>
                    </tr>
                    <tr>
                        <td><span class="label">Virtual Account</span><span class="value">{{ $virtualAccount }}</span></td>
                        <td></td>
                    </tr>
                </table>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item Pembayaran</th>
                            <th>Tanggal Lunas</th>
                            <th>Status</th>
                            <th class="amount">Nominal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($paidPayments as $payment)
                            <tr>
                                <td>{{ $payment->payment_label ?: ((int) $payment->month === 0 ? 'Formulir Pendaftaran' : 'Bulan '.$payment->month) }}</td>
                                <td>{{ $payment->paid_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>{{ $payment->status === 'waived' ? 'Dibebaskan' : 'Lunas' }}</td>
                                <td class="amount">Rp {{ number_format((int) $payment->amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <table class="bottom-table">
                    <tr>
                        <td style="width: 190px;">
                            <div class="qr-box">
                                <img src="{{ $qrUrl }}" alt="QR Kwitansi">
                                <span>Scan Validasi</span>
                            </div>
                        </td>
                        <td></td>
                        <td style="width: 302px;">
                            <div class="total-box">
                                <span>Total Terbayar</span>
                                <strong>Rp {{ number_format($totalPaid, 0, ',', '.') }}</strong>
                            </div>
                        </td>
                    </tr>
                </table>

                <table class="footer-table">
                    <tr>
                        <td>
                            <div class="note">
                                Kwitansi ini diterbitkan secara digital berdasarkan data pembayaran yang telah tercatat lunas di sistem. transaksi pembayaran tidak bisa dibatalkan / tidak berlaku refund.<br>
                                <strong>Kontak layanan:</strong> HP : 0821-9997-6600 | email : info@kampus.media | website : {{ $campusWebsite }}
                            </div>
                        </td>
                        <td style="width: 245px;">
                            <div class="signature">
                                <div class="signature-line">Kampus Media</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </section>
    </main>
</body>
</html>

