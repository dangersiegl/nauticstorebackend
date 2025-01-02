@echo off
REM Automatisches Git-Update-Skript
REM Berücksichtigt .gitignore, entfernt bereits hochgeladene ignorierte Dateien und übernimmt Änderungen.

REM Wechsel in das Verzeichnis des Repositories (falls erforderlich)
cd /d %~dp0

REM Entfernt Dateien, die nachträglich in .gitignore hinzugefügt wurden, aus dem Repository
for /f "delims=" %%f in ('git ls-files -i --exclude-standard') do (
    git rm --cached "%%f"
)

REM Füge alle Änderungen hinzu, die nicht durch .gitignore ausgeschlossen sind
git add .

REM Commit mit einer generischen Nachricht
git commit -m "Automatische Übernahme aller Änderungen (inkl. Bereinigung nach .gitignore)"

REM Push zum Remote-Repository
git push origin main

REM Erfolgsmeldung
echo Änderungen erfolgreich gepusht und ignorierte Dateien entfernt!
pause
