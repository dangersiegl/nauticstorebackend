@echo off
REM Automatisches Git-Update-Skript
REM Dieses Skript fügt neue Dateien hinzu, berücksichtigt Änderungen und entfernt gelöschte Dateien.

REM Wechsel in das Verzeichnis des Repositories (falls erforderlich)
cd /d %~dp0

REM Füge alle Änderungen hinzu (neue Dateien, Änderungen und gelöschte Dateien)
git add --all

REM Commit mit einer generischen Nachricht (kann angepasst werden)
git commit -m "Automatische Übernahme aller Änderungen"

REM Push zum Remote-Repository
git push origin main

REM Erfolgsmeldung
echo Änderungen erfolgreich gepusht!
pause
