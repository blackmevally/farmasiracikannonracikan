@echo off
echo ===============================
echo Mapping Printer POS ke LPT1 ...
echo ===============================

:: Ganti "POS58" dengan nama share printer kamu
:: misalnya "ThermalPrinter" atau "POS-USB"
net use LPT1: \\localhost\POS58 /persistent:yes

echo.
echo Mapping selesai!
echo Pastikan printer menyala dan driver OK.
pause
