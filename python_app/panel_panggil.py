#!/usr/bin/env python3
"""
Panel Pemanggil Antrian Farmasi (Tkinter versi skala 60% + Kontrol Display)
"""

import tkinter as tk
from tkinter import ttk, messagebox, simpledialog
import threading, time, json, os

try:
    import requests
except ImportError:
    messagebox.showerror("Error", "Module 'requests' belum terinstal.\nJalankan: pip install requests")
    raise

# ======================
# KONFIGURASI DASAR
# ======================
CONFIG_FILE = "config_server.json"
DEFAULT_URL = "http://localhost/farmasiV4/public"

def load_server_url():
    if os.path.exists(CONFIG_FILE):
        try:
            with open(CONFIG_FILE, "r", encoding="utf-8") as f:
                data = json.load(f)
                return data.get("SERVER_URL", DEFAULT_URL)
        except Exception:
            pass
    return DEFAULT_URL

def save_server_url(url):
    try:
        with open(CONFIG_FILE, "w", encoding="utf-8") as f:
            json.dump({"SERVER_URL": url}, f, indent=2)
        return True
    except Exception as e:
        print("Gagal simpan config:", e)
        return False

SERVER_URL = load_server_url()
REFRESH_INTERVAL = 3

# ======================
# SUARA
# ======================
sound_available = False
SOUND_FILE = "tingtong.mp3"
try:
    import pygame
    pygame.mixer.init()
    sound_available = True
except Exception:
    try:
        import winsound
        sound_available = True
    except Exception:
        sound_available = False

def play_sound():
    if not sound_available: return
    try:
        if "pygame" in globals() and pygame:
            if os.path.exists(SOUND_FILE):
                pygame.mixer.music.load(SOUND_FILE)
                pygame.mixer.music.play()
        else:
            winsound.Beep(800, 200)
    except Exception:
        pass

# ======================
# API FUNGSI
# ======================
def api_get_waiting(jenis="all"):
    try:
        r = requests.get(f"{SERVER_URL}/waiting_data.php", params={"jenis": jenis}, timeout=4)
        return r.json()
    except Exception:
        return []

def api_panggil_next(loket, jenis):
    try:
        r = requests.post(f"{SERVER_URL}/panggil_next.php", data={"loket": loket, "jenis": jenis}, timeout=6)
        return r.json()
    except Exception as e:
        return {"status": "error", "msg": str(e)}

def api_panggil_ulang(loket, jenis):
    try:
        r = requests.post(f"{SERVER_URL}/panggil_ulang.php", data={"loket": loket, "jenis": jenis}, timeout=6)
        return r.json()
    except Exception as e:
        return {"status": "error", "msg": str(e)}

def api_trigger_tts(no, loket):
    try:
        r = requests.post(f"{SERVER_URL}/trigger_tts.php", data={"no": no, "loket": loket}, timeout=4)
        return r.json()
    except Exception:
        return None

def api_control_display(action, val=None):
    """Mengirim kontrol ke display/streamV5.3.php melalui API"""
    try:
        params = {"action": action}
        if val is not None:
            params["val"] = val
        # disesuaikan agar akses ke /display/api/display_control.php
        r = requests.get(f"{SERVER_URL}/../display/api/display_control.php", params=params, timeout=4)
        return r.json()
    except Exception as e:
        return {"status": "error", "msg": str(e)}


# ======================
# GUI UTAMA (Skala 60%)
# ======================
class PanelApp:
    def __init__(self, root):
        self.root = root
        self.root.title("Panel Pemanggil Antrian Farmasi")
        self.root.configure(bg="#27ae60")
        self.root.geometry("420x660")  # diperbesar sedikit
        self.root.minsize(380, 550)
        self.root.resizable(True, True)
        self.root.attributes("-topmost", True)

        self.server_url = SERVER_URL

        # Header
        tk.Label(
            root,
            text="📢 PANEL PEMANGGIL ANTRIAN FARMASI",
            font=("Segoe UI", 12, "bold"),
            bg="#27ae60",
            fg="white",
        ).pack(pady=(15, 5))

        frame = tk.Frame(root, bg="#27ae60")
        frame.pack(expand=True)

        control_width = 20

        # Dropdown Jenis
        tk.Label(frame, text="Jenis Antrian:", bg="#27ae60", fg="white", font=("Segoe UI", 9)).pack(pady=2)
        self.jenis_var = tk.StringVar(value="all")
        self.jenis_combo = ttk.Combobox(
            frame, textvariable=self.jenis_var, values=["all", "obat", "racikan"],
            width=control_width, font=("Segoe UI", 10)
        )
        self.jenis_combo.pack(pady=(0, 6))

        # Dropdown Loket
        tk.Label(frame, text="Pilih Loket:", bg="#27ae60", fg="white", font=("Segoe UI", 9)).pack(pady=2)
        self.loket_var = tk.StringVar(value="Loket 1")
        self.loket_combo = ttk.Combobox(
            frame, textvariable=self.loket_var, values=["Loket 1", "Loket 2", "Loket 3"],
            width=control_width, font=("Segoe UI", 10)
        )
        self.loket_combo.pack(pady=(0, 12))

        # Tombol besar
        self.btn_next = tk.Button(
            frame, text="📢 PANGGIL BERIKUTNYA", bg="#f1c40f", fg="#2c3e50",
            font=("Segoe UI", 11, "bold"), relief="flat", height=2, width=control_width,
            command=self.on_panggil_next
        )
        self.btn_next.pack(pady=6)

        self.btn_ulang = tk.Button(
            frame, text="🔁 PANGGIL ULANG", bg="#f39c12", fg="white",
            font=("Segoe UI", 11, "bold"), relief="flat", height=2, width=control_width,
            command=self.on_panggil_ulang
        )
        self.btn_ulang.pack(pady=6)

        # Label notif & tabel
        self.notif = tk.Label(frame, text="", bg="#27ae60", fg="white", font=("Segoe UI", 9))
        self.notif.pack(pady=8)

        self.wait_count = tk.Label(frame, text="Menunggu: 0 pasien", bg="#27ae60", fg="#ffe082", font=("Segoe UI", 9))
        self.wait_count.pack(pady=(0, 10))

        self.table = ttk.Treeview(frame, columns=("no", "jenis", "waktu"), show="headings", height=8)
        self.table.heading("no", text="No")
        self.table.heading("jenis", text="Jenis")
        self.table.heading("waktu", text="Waktu")
        for col, width in zip(("no", "jenis", "waktu"), (90, 70, 110)):
            self.table.column(col, anchor="center", width=width)
        self.table.pack(pady=5, expand=True, fill="both")

        style = ttk.Style()
        style.configure("Treeview", rowheight=22, font=("Segoe UI", 9))
        style.configure("Treeview.Heading", font=("Segoe UI", 9, "bold"))

        # === Kontrol Display ===
        tk.Label(frame, text="🎬 Kontrol Display:", bg="#27ae60", fg="white", font=("Segoe UI", 9, "bold")).pack(pady=(15, 5))
        ctrl_frame = tk.Frame(frame, bg="#27ae60")
        ctrl_frame.pack(pady=5)

        self.volume_var = tk.DoubleVar(value=0.6)
        tk.Scale(ctrl_frame, from_=0, to=1, orient="horizontal", resolution=0.1,
                 variable=self.volume_var, label="Volume", bg="#27ae60", fg="white",
                 highlightthickness=0, troughcolor="#2ecc71",
                 command=self.set_volume).pack(pady=5, fill="x")

        tk.Button(ctrl_frame, text="🔇 Mute", bg="#e74c3c", fg="white",
                  font=("Segoe UI", 9, "bold"), width=10,
                  command=self.mute_video).pack(side="left", padx=4)

        tk.Button(ctrl_frame, text="🔊 Unmute", bg="#27ae60", fg="white",
                  font=("Segoe UI", 9, "bold"), width=10,
                  command=self.unmute_video).pack(side="left", padx=4)

        tk.Button(ctrl_frame, text="♻️ Reset Display", bg="#f1c40f", fg="#2c3e50",
                  font=("Segoe UI", 9, "bold"), width=14,
                  command=self.reset_display).pack(side="left", padx=4)

        # === Footer ===
        bottom_frame = tk.Frame(root, bg="#27ae60")
        bottom_frame.pack(pady=5)
        self.server_label = tk.Label(
            bottom_frame, text=f"Server: {self.server_url}", bg="#27ae60", fg="#fff", font=("Segoe UI", 8)
        )
        self.server_label.pack(side="left", padx=(10, 15))
        self.btn_server = tk.Button(
            bottom_frame, text="⚙️ Atur Server", bg="#2ecc71", fg="white",
            font=("Segoe UI", 8, "bold"), relief="flat", command=self.change_server_url
        )
        self.btn_server.pack(side="left")

        # Thread background
        self.last_called = None
        self.running = True
        self.refresh_thread = threading.Thread(target=self._refresh_loop, daemon=True)
        self.refresh_thread.start()
        self.root.protocol("WM_DELETE_WINDOW", self.on_close)

    # ===================
    # Refresh Data
    # ===================
    def _refresh_loop(self):
        while self.running:
            try:
                self.load_waiting()
            except Exception:
                pass
            time.sleep(REFRESH_INTERVAL)

    def load_waiting(self):
        jenis = self.jenis_var.get() or "all"
        data = api_get_waiting(jenis)
        self.root.after(0, lambda: self._apply_waiting(data))

    def _apply_waiting(self, data):
        self.table.delete(*self.table.get_children())
        for row in data:
            no = row.get("no_antrian", "-")
            jenis = row.get("jenis", "obat")
            waktu = row.get("waktu_ambil", "-")
            tag = "racikan" if jenis == "racikan" else "obat"
            self.table.insert("", "end", values=(no, jenis.upper(), waktu), tags=(tag,))
        self.table.tag_configure("racikan", background="#e3f2fd")
        self.table.tag_configure("obat", background="#fff8e6")
        self.wait_count.config(text=f"Menunggu: {len(data)} pasien")

    # ===================
    # Panggil Fungsi
    # ===================
    def on_panggil_next(self):
        threading.Thread(target=self._panggil_next, daemon=True).start()

    def _panggil_next(self):
        self._set_notif("⏳ Memanggil nomor berikutnya...", "#ffeb3b")
        jenis = self.jenis_var.get()
        loket = self.loket_var.get()
        res = api_panggil_next(loket, jenis)
        if res.get("status") == "ok":
            self.last_called = res
            play_sound()
            self._set_notif(f"✅ {res['no']} ({res['jenis']}) dipanggil", "white")
            api_trigger_tts(res["no"], res["loket"])
            self.load_waiting()
        elif res.get("status") == "empty":
            self._set_notif("⚠️ Tidak ada antrian menunggu.", "orange")
        else:
            self._set_notif("❌ Gagal memanggil.", "red")

    def on_panggil_ulang(self):
        threading.Thread(target=self._panggil_ulang, daemon=True).start()

    def _panggil_ulang(self):
        self._set_notif("🔁 Memanggil ulang...", "#ffeb3b")
        jenis = self.jenis_var.get()
        loket = self.loket_var.get()
        res = api_panggil_ulang(loket, jenis)
        if res.get("status") == "ok":
            self.last_called = res
            play_sound()
            self._set_notif(f"🔁 {res['no']} dipanggil ulang", "white")
            api_trigger_tts(res["no"], res["loket"])
            self.load_waiting()
        else:
            self._set_notif("⚠️ Belum ada nomor yang dipanggil.", "orange")

    # ===================
    # Kontrol Display
    # ===================
    def mute_video(self):
        api_control_display("mute")
        self._set_notif("🔇 Display dimute", "orange")

    def unmute_video(self):
        api_control_display("unmute")
        self._set_notif("🔊 Display dinyalakan", "white")

    def set_volume(self, val):
        try:
            val = float(val)
            api_control_display("set_volume", val)
            self._set_notif(f"🔉 Volume diatur ke {val:.1f}", "white")
        except:
            pass

    def reset_display(self):
        if messagebox.askyesno("Konfirmasi", "Yakin ingin me-reset Display (refresh Chrome & SSE)?"):
            api_control_display("reset_display")
            self._set_notif("♻️ Display direset", "orange")

    # ===================
    # Server Config
    # ===================
    def _set_notif(self, text, color="white"):
        self.notif.config(text=text, fg=color)

    def change_server_url(self):
        new_url = simpledialog.askstring("Atur Server", "Masukkan URL Server baru:", initialvalue=self.server_url)
        if not new_url:
            return
        new_url = new_url.strip()
        if not new_url.startswith("http"):
            messagebox.showwarning("Format Salah", "URL harus diawali http:// atau https://")
            return
        if save_server_url(new_url):
            global SERVER_URL
            SERVER_URL = new_url
            self.server_url = new_url
            self.server_label.config(text=f"Server: {new_url}")
            messagebox.showinfo("Berhasil", "Alamat server telah disimpan!")
        else:
            messagebox.showerror("Gagal", "Tidak dapat menyimpan konfigurasi server.")

    def on_close(self):
        self.running = False
        try:
            pygame.mixer.quit()
        except Exception:
            pass
        self.root.destroy()


def main():
    root = tk.Tk()
    app = PanelApp(root)
    root.mainloop()


if __name__ == "__main__":
    main()
