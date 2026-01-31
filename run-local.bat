@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo 행복안심동행 로컬 서버 시작 (PHP 내장 서버)
echo.
echo 브라우저에서 http://localhost:8000/ 를 열어주세요.
echo 종료하려면 이 창에서 Ctrl+C 를 누르세요.
echo.
start "" "http://localhost:8000/"
c:\xampp\php\php.exe -S localhost:8000 router.php
pause
