@echo off
color 0A
echo ===================================================
echo    MENYALAKAN MESIN WAYDASH PROVISIONING
echo ===================================================
echo.
echo 1. Menyalakan Server Python (Uvicorn)...
start "Mesin Python WayDash" cmd /k "python -m uvicorn main:app --reload"

timeout /t 3 /nobreak > NUL

echo 2. Menyalakan Jembatan Ngrok...
start "Jembatan Ngrok" cmd /k "ngrok http 127.0.0.1:8000"

echo.
echo SEMUA SISTEM BERJALAN! JANGAN TUTUP DUA JENDELA HITAM YANG MUNCUL.
echo Silakan buka web waydash.rf.gd di browser.
pause