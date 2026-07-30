<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Runner Broadcast WhatsApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900">
    <main class="mx-auto flex min-h-screen max-w-4xl flex-col gap-6 px-4 py-8">
        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm font-bold uppercase tracking-wide text-red-700">Broadcast WhatsApp Web</p>
            <h1 class="mt-2 text-2xl font-black">{{ $broadcast->name }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ $recipients->count() }} penerima antrean. Interval {{ $broadcast->interval_seconds ?? 45 }} detik.
            </p>
            <div class="mt-5 rounded-xl bg-red-50 p-4 text-sm font-semibold text-red-900">
                WhatsApp Web akan dibuka per kontak dengan pesan sudah terisi. Setelah pesan terkirim, klik tandai terkirim. Jika nomor tidak bisa dibuka, tandai nomor invalid.
            </div>
            <div class="mt-6 flex flex-wrap gap-3">
                <button id="startButton" class="rounded-xl bg-red-600 px-5 py-3 text-sm font-black text-white transition hover:bg-[#7A1220]">
                    Mulai Broadcast
                </button>
                <button id="sentButton" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-black text-white transition hover:bg-slate-700" disabled>
                    Tandai Terkirim & Lanjut
                </button>
                <button id="invalidButton" class="rounded-xl bg-red-100 px-5 py-3 text-sm font-black text-red-800 transition hover:bg-red-200" disabled>
                    Nomor Invalid & Lanjut
                </button>
                <a href="{{ route('filament.admin.resources.whatsapp-broadcasts.index') }}" class="rounded-xl bg-white px-5 py-3 text-sm font-black text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-50">
                    Kembali
                </a>
            </div>
        </section>

        <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-slate-500">Kontak aktif</p>
                    <h2 id="currentName" class="mt-1 text-xl font-black">Belum mulai</h2>
                    <p id="currentPhone" class="mt-1 text-sm text-slate-500">-</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-slate-500">Progress</p>
                    <p id="progressText" class="mt-1 text-xl font-black">0 / {{ $recipients->count() }}</p>
                </div>
            </div>
            <p id="statusText" class="mt-5 rounded-xl bg-slate-100 p-4 text-sm font-semibold text-slate-700">
                Klik Mulai Broadcast untuk membuka kontak pertama.
            </p>
        </section>

        <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-200 px-6 py-4 text-sm font-black text-slate-700">Antrean</div>
            <div class="divide-y divide-slate-100">
                @forelse ($recipients as $recipient)
                    <div class="flex items-center justify-between gap-4 px-6 py-4 text-sm" data-recipient-row="{{ $recipient['id'] }}">
                        <div>
                            <p class="font-bold">{{ $recipient['name'] }}</p>
                            <p class="text-slate-500">{{ $recipient['phone'] }}</p>
                        </div>
                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-bold text-red-700" data-recipient-status="{{ $recipient['id'] }}">Tidak terkirim</span>
                    </div>
                @empty
                    <div class="px-6 py-8 text-sm font-semibold text-slate-500">Tidak ada penerima yang belum terkirim.</div>
                @endforelse
            </div>
        </section>
    </main>

    <script>
        const recipients = @json($recipients);
        const intervalSeconds = {{ (int) ($broadcast->interval_seconds ?? 45) }};
        const sentUrlTemplate = @json(route('admin.whatsapp-broadcasts.recipients.sent', [$broadcast, '__RECIPIENT__']));
        const invalidUrlTemplate = @json(route('admin.whatsapp-broadcasts.recipients.invalid', [$broadcast, '__RECIPIENT__']));
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        let index = 0;
        let whatsappWindow = null;

        const startButton = document.getElementById('startButton');
        const sentButton = document.getElementById('sentButton');
        const invalidButton = document.getElementById('invalidButton');
        const currentName = document.getElementById('currentName');
        const currentPhone = document.getElementById('currentPhone');
        const progressText = document.getElementById('progressText');
        const statusText = document.getElementById('statusText');

        function renderCurrent() {
            const recipient = recipients[index];
            progressText.textContent = `${Math.min(index + 1, recipients.length)} / ${recipients.length}`;

            if (!recipient) {
                currentName.textContent = 'Selesai';
                currentPhone.textContent = '-';
                statusText.textContent = 'Semua penerima sudah diproses.';
                sentButton.disabled = true;
                invalidButton.disabled = true;
                return;
            }

            currentName.textContent = recipient.name;
            currentPhone.textContent = recipient.phone;
            statusText.textContent = 'WhatsApp Web dibuka. Setelah kirim pesan, klik Tandai Terkirim & Lanjut. Jika nomor tidak valid, klik Nomor Invalid & Lanjut.';
        }

        function openCurrent() {
            const recipient = recipients[index];

            if (!recipient) {
                renderCurrent();
                return;
            }

            renderCurrent();
            if (whatsappWindow && !whatsappWindow.closed) {
                whatsappWindow.location.href = recipient.url;
                whatsappWindow.focus();
            } else {
                whatsappWindow = window.open(recipient.url, 'spmm_whatsapp_runner');
            }
            sentButton.disabled = false;
            invalidButton.disabled = false;
        }

        function waitingPageUrl(nextRecipient, seconds) {
            const html = `<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Menunggu kontak berikutnya</title>
<style>
body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#f8fafc;font-family:Arial,sans-serif;color:#111827}
.box{max-width:560px;margin:24px;padding:28px;border-radius:20px;background:#fff;box-shadow:0 18px 45px rgba(15,23,42,.12);text-align:center}
.badge{display:inline-block;border-radius:999px;background:#fee2e2;color:#7A1220;padding:8px 14px;font-weight:800;font-size:13px}
h1{margin:18px 0 8px;font-size:28px;color:#7A1220}
p{line-height:1.6;color:#475569}
</style>
</head>
<body>
<div class="box">
<span class="badge">SPMM Broadcast</span>
<h1>Menunggu ${seconds} detik</h1>
<p>Kontak berikutnya: <strong>${nextRecipient.name}</strong><br>${nextRecipient.phone}</p>
</div>
</body>
</html>`;

            return 'data:text/html;charset=utf-8,' + encodeURIComponent(html);
        }

        function updateRecipientStatus(recipient, label, className) {
            const status = document.querySelector(`[data-recipient-status="${recipient.id}"]`);
            if (!status) {
                return;
            }

            status.textContent = label;
            status.className = className;
        }

        async function markAndContinue(urlTemplate, waitingLabel) {
            const recipient = recipients[index];
            if (!recipient) {
                return;
            }

            sentButton.disabled = true;
            invalidButton.disabled = true;
            statusText.textContent = waitingLabel;

            const nextRecipient = recipients[index + 1] || null;
            if (nextRecipient) {
                if (whatsappWindow && !whatsappWindow.closed) {
                    whatsappWindow.location.href = waitingPageUrl(nextRecipient, intervalSeconds);
                    whatsappWindow.focus();
                } else {
                    whatsappWindow = window.open(waitingPageUrl(nextRecipient, intervalSeconds), 'spmm_whatsapp_runner');
                }
            }

            const response = await fetch(urlTemplate.replace('__RECIPIENT__', recipient.id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                statusText.textContent = 'Gagal menyimpan status. Coba klik lagi atau refresh halaman.';
                sentButton.disabled = false;
                invalidButton.disabled = false;
                return;
            }

            if (urlTemplate === sentUrlTemplate) {
                updateRecipientStatus(recipient, 'Terkirim', 'rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700');
            } else {
                updateRecipientStatus(recipient, 'Nomor invalid', 'rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700');
            }

            index += 1;

            if (index >= recipients.length) {
                renderCurrent();
                return;
            }

            setTimeout(openCurrent, intervalSeconds * 1000);
        }

        function markSentAndContinue() {
            return markAndContinue(sentUrlTemplate, `Menunggu ${intervalSeconds} detik sebelum kontak berikutnya...`);
        }

        function markInvalidAndContinue() {
            return markAndContinue(invalidUrlTemplate, `Nomor ditandai invalid. Menunggu ${intervalSeconds} detik sebelum kontak berikutnya...`);
        }

        startButton.addEventListener('click', () => {
            if (recipients.length === 0) {
                statusText.textContent = 'Tidak ada antrean untuk dikirim.';
                return;
            }

            startButton.disabled = true;
            openCurrent();
        });

        sentButton.addEventListener('click', markSentAndContinue);
        invalidButton.addEventListener('click', markInvalidAndContinue);
    </script>
</body>
</html>
