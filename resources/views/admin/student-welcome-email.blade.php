@php
    $campusName = $lead->campus?->name ?? 'Kampus Media';
    $campusInitial = mb_strtoupper(mb_substr($campusName, 0, 2));
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun Mahasiswa</title>
</head>
<body style="margin:0; padding:0; background:#e9eef5; font-family: Arial, Helvetica, sans-serif;">
    <span style="display:none; font-size:1px; color:#e9eef5; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        Verifikasi email dan akses akun mahasiswa kamu di Kampus Media
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
                                                    <p style="color:#ffffff; font-size:20px; font-weight:900; margin:0; line-height:1.2;">Pendaftaran Diterima</p>
                                                    <p style="color:#dbeafe; font-size:12px; font-weight:700; margin:4px 0 0;">{{ $campusName }} &middot; via Kampus Media</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:26px 24px 8px;">
                                        <p style="font-size:14px; color:#00113a; line-height:1.6; margin:0 0 20px;">
                                            Halo <b>{{ $lead->full_name }}</b>,<br>
                                            Terima kasih sudah mendaftar di <b>{{ $campusName }}</b> melalui Kampus Media. Satu langkah lagi &mdash; verifikasi email kamu untuk mengaktifkan akun mahasiswa.
                                        </p>

                                        <table role="presentation" width="100%" style="margin-bottom:24px;">
                                            <tr>
                                                <td style="text-align:center;">
                                                    <a href="{{ $verificationUrl }}" style="display:inline-block; background:#193c63; color:#ffffff; font-size:15px; font-weight:800; text-decoration:none; padding:13px 34px; border-radius:999px;">Verifikasi Email Saya</a>
                                                    <div style="margin-top:10px; font-size:12px; color:#60718d;">Atau salin tautan berikut ke browser kamu:</div>
                                                    <div style="margin-top:4px; font-size:11.5px; color:#2563eb; word-break:break-all;">{{ $verificationUrl }}</div>
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" style="background:#f4f7fb; border:1px solid #dbe3ee; border-radius:12px; margin-bottom:22px;">
                                            <tr>
                                                <td style="padding:16px 18px;">
                                                    <span style="display:block; color:#60718d; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.03em; margin-bottom:10px;">Akses Login Mahasiswa</span>
                                                    <table role="presentation" width="100%">
                                                        <tr>
                                                            <td style="padding:3px 0; font-size:13px; color:#60718d; font-weight:700; width:110px;">URL</td>
                                                            <td style="padding:3px 0; font-size:13px; color:#00113a; font-weight:800;">
                                                                <a href="{{ $loginUrl }}" style="color:#2563eb; text-decoration:none;">{{ $loginUrl }}</a>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding:3px 0; font-size:13px; color:#60718d; font-weight:700;">Email</td>
                                                            <td style="padding:3px 0; font-size:13px; color:#00113a; font-weight:800;">{{ $lead->email }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td style="padding:3px 0; font-size:13px; color:#60718d; font-weight:700;">Password sementara</td>
                                                            <td style="padding:3px 0;">
                                                                <span style="display:inline-block; background:#fff7ed; color:#b45309; border:1px dashed #fed7aa; border-radius:8px; padding:4px 10px; font-size:14px; font-weight:900; letter-spacing:.04em;">{{ $temporaryPassword }}</span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="font-size:12.5px; color:#60718d; font-weight:800; text-transform:uppercase; letter-spacing:.03em; margin:0 0 10px;">Langkah selanjutnya</p>
                                        <table role="presentation" width="100%" style="margin-bottom:22px;">
                                            @foreach ([
                                                'Buka inbox email atau folder spam/promosi.',
                                                'Klik tombol atau tautan verifikasi email di atas.',
                                                'Login ke akun mahasiswa menggunakan email dan password sementara.',
                                                'Setelah login, lengkapi biodata, upload berkas, cek pembayaran, dan cetak kwitansi.',
                                            ] as $index => $step)
                                                <tr>
                                                    <td style="padding:6px 0; vertical-align:top; width:26px;">
                                                        <span style="display:inline-block; width:20px; height:20px; background:#193c63; color:#ffffff; border-radius:999px; font-size:11px; font-weight:800; text-align:center; line-height:20px;">{{ $index + 1 }}</span>
                                                    </td>
                                                    <td style="padding:6px 0 6px 10px; font-size:13px; color:#00113a; line-height:1.5;">{{ $step }}</td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:16px 24px 22px; border-top:1px solid #edf1f6;">
                                        <p style="font-size:11.5px; color:#60718d; line-height:1.6; margin:0;">
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
