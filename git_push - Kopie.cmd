@echo off
REM Automatisches Git-Update-Skript
REM Dieses Skript berücksichtigt Änderungen, ohne Dateien einzuschließen, die durch .gitignore ausgeschlossen sind.

REM Wechsel in das Verzeichnis des Repositories (falls erforderlich)
cd /d %~dp0

REM Bereinigt den Cache, um sicherzustellen, dass .gitignore-Regeln korrekt angewendet werden
git rm -r --cached .

REM Füge alle Dateien hinzu, die nicht durch .gitignore ausgeschlossen sind
git add .

REM Commit mit einer generischen Nachricht (kann angepasst werden)
git commit -m "Automatische Übernahme aller Änderungen (respektiert .gitignore)"

REM Push zum Remote-Repository
git push origin main

REM Erfolgsmeldung
echo Änderungen erfolgreich gepusht!
pause
