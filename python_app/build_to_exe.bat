@echo off
cd /d %~dp0
echo ===============================================
echo 🔧 Membuat file EXE Panel Pemanggil Antrian Farmasi
echo ===============================================

:: Install dependencies (otomatis, jika belum ada)
echo > requirements.txt pygame requests pyinstaller
pip install -r requirements.txt >nul

:: Build EXE
pyinstaller --onefile --noconsole panel_panggil.py

echo.
echo ✅ Selesai! File EXE tersedia di folder "dist"
echo -----------------------------------------------
echo Lokasi: %~dp0dist\panel_panggil.exe
echo -----------------------------------------------
pause
