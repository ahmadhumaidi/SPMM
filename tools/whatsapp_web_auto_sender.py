#!/usr/bin/env python3
"""Experimental WhatsApp Web sender for SPMM broadcast queues.

Usage:
  py whatsapp_web_auto_sender.py whatsapp-broadcast-21-queue.json

Requirements:
  py -m pip install pyautogui

Important:
  - Run this on the operator computer, not on the VPS.
  - Login to https://web.whatsapp.com in the default browser before starting.
  - Keep the browser focused while the script is running.
"""

from __future__ import annotations

import argparse
import json
import sys
import time
import urllib.parse
import urllib.request
import webbrowser
from pathlib import Path

try:
    import pyautogui
except ImportError:  # pragma: no cover
    pyautogui = None


def post_signed_url(url: str) -> bool:
    request = urllib.request.Request(url, data=b"", method="POST")
    request.add_header("Accept", "application/json")
    try:
        with urllib.request.urlopen(request, timeout=20) as response:
            return 200 <= response.status < 300
    except Exception as exc:  # noqa: BLE001
        print(f"  ! Gagal menandai status di SPMM: {exc}")
        return False


def open_url_in_active_browser(url: str, first_contact: bool) -> None:
    if first_contact:
        webbrowser.open(url, new=0, autoraise=True)
        return

    # Reuse the already focused browser tab. This is more reliable than
    # webbrowser.open for the second contact on Windows/Chrome.
    pyautogui.hotkey("ctrl", "l")
    time.sleep(0.4)
    pyautogui.write(url, interval=0)
    time.sleep(0.2)
    pyautogui.press("enter")


def click_message_box(x: int | None, y: int | None) -> None:
    if x is not None and y is not None:
        pyautogui.click(x, y)
        return

    width, height = pyautogui.size()
    pyautogui.click(width // 2, max(120, height - 85))


def press_send(max_attempts: int, click_x: int | None, click_y: int | None) -> None:
    for attempt in range(1, max_attempts + 1):
        click_message_box(click_x, click_y)
        time.sleep(0.35)
        pyautogui.press("enter")
        time.sleep(1.2)
        if attempt < max_attempts:
            print(f"  percobaan kirim {attempt}/{max_attempts}, memastikan tombol kirim...")


def load_payload(source: str) -> dict:
    if source.startswith("spmm-wa://"):
        parsed = urllib.parse.urlparse(source)
        query_url = urllib.parse.parse_qs(parsed.query).get("queue", [""])[0]
        if not query_url:
            raise ValueError("URL spmm-wa tidak berisi parameter queue.")
        source = query_url

    if source.startswith("http://") or source.startswith("https://"):
        request = urllib.request.Request(source, headers={"Accept": "application/json"})
        with urllib.request.urlopen(request, timeout=30) as response:
            return json.loads(response.read().decode("utf-8"))

    return json.loads(Path(source).read_text(encoding="utf-8"))


def main() -> int:
    parser = argparse.ArgumentParser(description="SPMM WhatsApp Web auto sender experiment")
    parser.add_argument("queue_json", help="File JSON, URL antrean, atau URL spmm-wa dari SPMM")
    parser.add_argument("--load-seconds", type=int, default=None, help="Waktu tunggu WhatsApp Web memuat chat")
    parser.add_argument("--interval", type=int, default=None, help="Jeda antar kontak")
    parser.add_argument("--send-attempts", type=int, default=2, help="Jumlah percobaan tekan Enter per kontak")
    parser.add_argument("--click-x", type=int, default=None, help="Koordinat X kotak pesan WhatsApp Web jika auto klik kurang pas")
    parser.add_argument("--click-y", type=int, default=None, help="Koordinat Y kotak pesan WhatsApp Web jika auto klik kurang pas")
    parser.add_argument("--dry-run", action="store_true", help="Buka chat tanpa menekan Enter dan tanpa update status")
    args = parser.parse_args()

    if pyautogui is None and not args.dry_run:
        print("pyautogui belum terpasang. Jalankan: py -m pip install pyautogui")
        return 2

    payload = load_payload(args.queue_json)
    recipients = payload.get("recipients", [])
    interval = max(30, int(args.interval or payload.get("interval_seconds") or 45))
    load_seconds = max(10, int(args.load_seconds or payload.get("load_seconds") or 16))
    send_attempts = max(1, int(args.send_attempts or 1))

    print(f"Broadcast: {payload.get('broadcast_name', '-')}")
    print(f"Penerima: {len(recipients)}")
    print(f"Jeda: {interval} detik | Tunggu load: {load_seconds} detik | Percobaan kirim: {send_attempts}")
    print("Pastikan WhatsApp Web sudah login di browser default dan browser jangan diminimize.")

    if not recipients:
        print("Tidak ada penerima di antrean.")
        return 0

    if not args.dry_run:
        confirmation = input("Ketik KIRIM untuk mulai auto-send: ").strip().upper()
        if confirmation != "KIRIM":
            print("Dibatalkan.")
            return 0

    for index, recipient in enumerate(recipients, start=1):
        name = recipient.get("name") or "Tanpa nama"
        phone = recipient.get("phone") or "-"
        url = recipient.get("web_url")

        print(f"[{index}/{len(recipients)}] Membuka {name} ({phone})")
        if args.dry_run:
            webbrowser.open(url, new=0, autoraise=True)
        else:
            open_url_in_active_browser(url, first_contact=index == 1)
        time.sleep(load_seconds)

        if args.dry_run:
            print("  dry-run: tidak menekan Enter dan tidak update status")
        else:
            press_send(send_attempts, args.click_x, args.click_y)
            time.sleep(2)
            if post_signed_url(recipient.get("mark_sent_url", "")):
                print("  terkirim dan status SPMM diperbarui")

        if index < len(recipients):
            time.sleep(interval)

    print("Selesai.")
    return 0


if __name__ == "__main__":
    sys.exit(main())