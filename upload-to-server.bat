@echo off
chcp 65001 >nul
title PDFkrub - Upload to 147.50.255.136
cd /d "%~dp0"
powershell -ExecutionPolicy Bypass -NoProfile -File "%~dp0upload-to-server.ps1"
if %ERRORLEVEL% neq 0 (
    echo.
    echo ❌ เกิดข้อผิดพลาดในการอัปโหลด
    pause
) else (
    echo.
    echo กดปุ่มใดๆ เพื่อปิดหน้าต่างนี้...
    pause >nul
)
