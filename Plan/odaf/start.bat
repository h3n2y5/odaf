@echo off
REM ===========================================================================
REM  Start server aplikasi ODAF (Docker Compose) dari CMD.
REM  Meneruskan argumen ke start.ps1. Contoh:
REM     start.bat
REM     start.bat -Rebuild -Compile
REM     start.bat -Fresh
REM ===========================================================================
setlocal
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0start.ps1" %*
set "RC=%ERRORLEVEL%"
if not "%RC%"=="0" (
    echo.
    echo [odaf] start.ps1 keluar dengan kode %RC%.
)
endlocal & exit /b %RC%
