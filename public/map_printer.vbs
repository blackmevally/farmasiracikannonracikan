Set WshShell = CreateObject("WScript.Shell")

' Lepas mapping lama
WshShell.Run "cmd /c net use LPT1: /delete /y", 0, True

' Mapping ulang printer USB ke LPT1
WshShell.Run "cmd /c net use LPT1: \\DESKTOP-23MOL20\ThermalPOS /persistent:yes", 0, True
