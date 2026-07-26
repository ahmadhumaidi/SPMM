@php
    $campusName = $lead->campus?->name ?? 'Kampus Media';
    $campusInitial = mb_strtoupper(mb_substr($campusName, 0, 2));
    $expiresAt = $invoice->expires_at?->timezone('Asia/Jakarta');
    $issuedAt = $invoice->created_at?->timezone('Asia/Jakarta');
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagihan Pendaftaran {{ $invoice->invoice_number }}</title>
</head>
<body style="margin:0; padding:0; background:#e9eef5; font-family: Arial, Helvetica, sans-serif;">
    <span style="display:none; font-size:1px; color:#e9eef5; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        Rp {{ number_format((int) $invoice->amount, 0, ',', '.') }} &middot; jatuh tempo {{ $expiresAt?->translatedFormat('d F Y, H:i') }} WIB &middot; selesaikan pendaftaran Anda sekarang
    </span>

    <table role="presentation" width="100%" style="background:#e9eef5; padding:28px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" style="max-width:640px; width:100%;">
                    <tr>
                        <td style="background:#ffffff; border-radius:16px; overflow:hidden; border:1px solid #dbe3ee;">
                            <table role="presentation" width="100%">
                                <tr>
                                    <td style="background:#193c63; padding:22px 24px;">
                                        <table role="presentation" width="100%">
                                            <tr>
                                                <td style="width:74px;">
                                                    <table role="presentation">
                                                        <tr>
                                                            <td style="width:74px; height:58px; background:rgba(255,255,255,.96); border-radius:12px; text-align:center; vertical-align:middle; color:#193c63; font-size:20px; font-weight:900;">
                                                                {{ $campusInitial }}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                                <td style="padding-left:16px;">
                                                    <p style="color:#ffffff; font-size:20px; font-weight:900; margin:0; line-height:1.2;">Tagihan Pendaftaran</p>
                                                    <p style="color:#dbeafe; font-size:12px; font-weight:700; margin:4px 0 0;">{{ $campusName }} &middot; via Kampus Media</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:26px 24px 8px;">
                                        <p style="font-size:14px; color:#00113a; line-height:1.6; margin:0 0 18px;">
                                            Halo <b>{{ $lead->full_name }}</b>,<br>
                                            Terima kasih telah mendaftar di <b>{{ $campusName }}</b> melalui Kampus Media. Untuk melanjutkan proses pendaftaran Anda, mohon selesaikan pembayaran formulir pendaftaran berikut ini.
                                        </p>

                                        <table role="presentation" width="100%" style="background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; padding:14px 18px; margin-bottom:20px;">
                                            <tr>
                                                <td style="padding:14px 18px 4px;">
                                                    <table role="presentation" width="100%">
                                                        <tr>
                                                            <td style="padding:3px 0; font-size:13px; color:#b45309; font-weight:700; width:150px;">No. Invoice</td>
                                                            <td style="padding:3px 0; font-size:13px; color:#00113a; font-weight:800;">{{ $invoice->invoice_number }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding:3px 0; font-size:13px; color:#b45309; font-weight:700;">Tanggal terbit</td>
                                                            <td style="padding:3px 0; font-size:13px; color:#00113a; font-weight:800;">{{ $issuedAt?->translatedFormat('d F Y') }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding:3px 0; font-size:13px; color:#b45309; font-weight:700;">Batas waktu bayar</td>
                                                            <td style="padding:3px 0; font-size:13px; color:#b91c1c; font-weight:800;">{{ $expiresAt?->translatedFormat('d F Y, H:i') }} WIB</td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" style="margin-bottom:20px;">
                                            <tr>
                                                <td style="padding:0 0 12px; width:50%; vertical-align:top;">
                                                    <span style="display:block; color:#60718d; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.03em;">Program Studi</span>
                                                    <span style="display:block; margin-top:4px; color:#00113a; font-size:14px; font-weight:700;">{{ $lead->studyProgram?->name ?? '-' }}</span>
                                                </td>
                                                <td style="padding:0 0 12px; width:50%; vertical-align:top;">
                                                    <span style="display:block; color:#60718d; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.03em;">Kampus</span>
                                                    <span style="display:block; margin-top:4px; color:#00113a; font-size:14px; font-weight:700;">{{ $campusName }}</span>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" style="border-collapse:collapse; margin-bottom:20px; font-size:13px;">
                                            <tr>
                                                <th style="background:#f1f5f9; color:#0e2445; border:1px solid #dce5f0; padding:10px 12px; text-align:left; text-transform:uppercase; font-size:11px; letter-spacing:.03em;">Item</th>
                                                <th style="background:#f1f5f9; color:#0e2445; border:1px solid #dce5f0; padding:10px 12px; text-align:left; text-transform:uppercase; font-size:11px; letter-spacing:.03em;">Status</th>
                                                <th style="background:#f1f5f9; color:#0e2445; border:1px solid #dce5f0; padding:10px 12px; text-align:right; text-transform:uppercase; font-size:11px; letter-spacing:.03em;">Nominal</th>
                                            </tr>
                                            <tr>
                                                <td style="border:1px solid #dce5f0; padding:10px 12px; color:#0e2445;">Formulir Pendaftaran</td>
                                                <td style="border:1px solid #dce5f0; padding:10px 12px; color:#0e2445;">
                                                    <span style="display:inline-block; background:#fef3c7; color:#92400e; font-size:11px; font-weight:800; padding:3px 9px; border-radius:999px;">Menunggu Pembayaran</span>
                                                </td>
                                                <td style="border:1px solid #dce5f0; padding:10px 12px; color:#0e2445; text-align:right; white-space:nowrap;">Rp {{ number_format((int) $invoice->amount, 0, ',', '.') }}</td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" style="margin-bottom:22px;">
                                            <tr>
                                                <td style="background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; padding:14px 18px; text-align:right;">
                                                    <span style="color:#d97706; font-size:11px; font-weight:800; text-transform:uppercase;">Total Tagihan</span>
                                                    <div style="color:#00113a; font-size:24px; font-weight:900; margin-top:3px;">Rp {{ number_format((int) $invoice->amount, 0, ',', '.') }}</div>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" style="margin-bottom:22px;">
                                            <tr>
                                                <td style="text-align:center;">
                                                    <a href="{{ $invoice->payment_url }}" style="display:inline-block; background:#d97706; color:#ffffff; font-size:15px; font-weight:800; text-decoration:none; padding:13px 34px; border-radius:999px;">Bayar Sekarang</a>
                                                    <div style="margin-top:10px; font-size:12px; color:#60718d;">Klik tombol di atas untuk membayar via Virtual Account / QRIS</div>
                                                </td>
                                            </tr>
                                        </table>

                                        @if ($invoice->va_number)
                                            <table role="presentation" width="100%" style="margin-bottom:22px;">
                                                <tr>
                                                    <td style="border:1px dashed #c9d3e0; border-radius:10px; padding:12px 16px; text-align:center;">
                                                        <span style="font-size:11px; color:#60718d; font-weight:700; text-transform:uppercase;">Virtual Account</span>
                                                        <div style="font-size:18px; font-weight:900; color:#00113a; letter-spacing:.04em; margin-top:4px;">{{ $invoice->va_number }}</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:16px 24px 22px; border-top:1px solid #edf1f6;">
                                        <p style="font-size:11.5px; color:#60718d; line-height:1.6; margin:0;">
                                            Tagihan ini akan <strong style="color:#324a6d;">kedaluwarsa pada {{ $expiresAt?->translatedFormat('d F Y, H:i') }} WIB</strong>. Jika belum dibayar hingga batas waktu, invoice akan otomatis dibatalkan dan Anda perlu mengulang proses pendaftaran.<br><br>
                                            <strong style="color:#324a6d;">Butuh bantuan?</strong> Hubungi 0821-9997-6600 atau info@kampus.media.<br>
                                            Email ini dikirim otomatis oleh sistem Kampus Media, mohon tidak membalas email ini.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
