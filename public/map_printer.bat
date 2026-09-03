@echo off
REM Lepas mapping lama kalau ada
net use LPT1: /delete /y

REM Mapping ulang printer USB yang dishare ke LPT1
net use LPT1: \\DESKTOP-23MOL20\ThermalPOS /persistent:yes

REM Info ke user
echo Printer ThermalPOS sudah dimapping ke LPT1
pause
