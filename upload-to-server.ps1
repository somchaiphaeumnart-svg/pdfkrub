# ==============================================================================
# PDFkrub — Script อัปโหลดและทดสอบบนโฮสต์จริง (147.50.255.136)
# การใช้งาน:
#   .\upload-to-server.ps1          (เลือกโหมดในเมนู)
#   .\upload-to-server.ps1 -Mode 1  (อัปโหลดด่วน: views + js assets + routes)
#   .\upload-to-server.ps1 -Mode 2  (อัปโหลดโค้ดทั้งหมด Full Project)
#   .\upload-to-server.ps1 -Mode 3  (Git Push + สั่ง Deploy บนเซิร์ฟเวอร์)
# ==============================================================================

param (
    [string]$ServerUser = "root",
    [string]$ServerHost = "147.50.255.136",
    [string]$RemoteDir   = "/var/www/pdfkrub",
    [string]$Mode        = ""
)

$ErrorActionPreference = "Stop"

# ตั้งค่าฟอนต์และสี Console
$host.UI.RawUI.WindowTitle = "PDFkrub - Upload to 147.50.255.136"

function Write-Header {
    Write-Host ""
    Write-Host "╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
    Write-Host "║        🚀  PDFkrub Deploy & Upload to Server 147.50.255.136  ║" -ForegroundColor Cyan
    Write-Host "╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
    Write-Host ""
}

Write-Header

# ตรวจสอบว่าอยู่ในไดเรกทอรีโปรเจกต์หรือไม่
if (-not (Test-Path "artisan")) {
    Write-Host "❌ กรุณารันสคริปต์นี้ที่โฟลเดอร์หลักของโปรเจกต์ (d:\pdf2word)" -ForegroundColor Red
    exit 1
}

# แสดงเมนูเลือกโหมดหากไม่ได้ระบุพารามิเตอร์
if ([string]::IsNullOrWhiteSpace($Mode)) {
    Write-Host "กรุณาเลือกรูปแบบการอัปโหลด:" -ForegroundColor Yellow
    Write-Host "  [1] อัปโหลดด่วน (Fast) - ส่ง resources, public/build, app, routes (แนะนำ รวดเร็วใน 5 วินาที)" -ForegroundColor Green
    Write-Host "  [2] อัปโหลดทั้งโปรเจกต์ (Full) - แพ็กไฟล์ทั้งหมดขึ้นโฮสต์ (ยกเว้น vendor, node_modules)" -ForegroundColor White
    Write-Host "  [3] Git Push + รัน deploy.sh - พุชโค้ดขึ้น GitHub แล้วสั่งให้เซิร์ฟเวอร์ดึงข้อมูล" -ForegroundColor Cyan
    Write-Host "  [Q] ยกเลิก" -ForegroundColor DarkGray
    Write-Host ""
    $choice = Read-Host "เลือกตัวเลือก [1, 2, 3 หรือ Q] (ค่าเริ่มต้น 1)"
    if ([string]::IsNullOrWhiteSpace($choice)) { $choice = "1" }
    if ($choice -eq "Q" -or $choice -eq "q") { exit 0 }
    $Mode = $choice
}

# ─── ขั้นตอนที่ 1: Build Frontend Assets ล่าสุด ──────────────
Write-Host "🎨 [1/4] กำลังคอมไพล์ Frontend Assets (npm run build)..." -ForegroundColor Yellow
try {
    npm run build
    Write-Host "   ✅ Assets คอมไพล์เรียบร้อยสมบูรณ์" -ForegroundColor Green
} catch {
    Write-Host "   ⚠️ การ build assets มีข้อผิดพลาด กรุณาตรวจสอบโค้ด" -ForegroundColor Red
    exit 1
}

$tarArchive = "pdfkrub-upload-tmp.tar.gz"

if ($Mode -eq "1" -or $Mode -eq "fast") {
    # ─── โหมด 1: Fast Direct Upload ───────────────────────────
    Write-Host ""
    Write-Host "📦 [2/4] กำลังแพ็กไฟล์ส่วนแก้ไข (resources, public/build, app, routes, scripts)..." -ForegroundColor Yellow
    
    if (Test-Path $tarArchive) { Remove-Item $tarArchive -Force }
    
    tar.exe -czf $tarArchive resources public/build app routes scripts
    
    $fileSize = (Get-Item $tarArchive).Length / 1MB
    Write-Host ("   ✅ แพ็กไฟล์สำเร็จ: {0:N2} MB" -f $fileSize) -ForegroundColor Green

    Write-Host ""
    Write-Host "📤 [3/4] กำลังส่งไฟล์ไปยังเซิร์ฟเวอร์ ($ServerUser@$ServerHost)..." -ForegroundColor Yellow
    Write-Host "   (หากระบบถามรหัสผ่าน ให้กรอกรหัสผ่านของเซิร์ฟเวอร์)" -ForegroundColor DarkGray
    
    scp.exe -o ConnectTimeout=15 $tarArchive "${ServerUser}@${ServerHost}:/tmp/${tarArchive}"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ เกิดข้อผิดพลาดในการส่งไฟล์ผ่าน SCP" -ForegroundColor Red
        if (Test-Path $tarArchive) { Remove-Item $tarArchive -Force }
        exit 1
    }

    Write-Host ""
    Write-Host "⚡ [4/4] กำลังแตกไฟล์และเคลียร์แคชบนเซิร์ฟเวอร์..." -ForegroundColor Yellow
    $remoteCmd = "tar -xzf /tmp/$tarArchive -C $RemoteDir/ && rm -f /tmp/$tarArchive && cd $RemoteDir && php artisan optimize:clear && chown -R www-data:www-data storage bootstrap/cache"
    ssh.exe -o ConnectTimeout=15 "${ServerUser}@${ServerHost}" $remoteCmd

} elseif ($Mode -eq "2" -or $Mode -eq "full") {
    # ─── โหมด 2: Full Project Upload ───────────────────────────
    Write-Host ""
    Write-Host "📦 [2/4] กำลังแพ็กโค้ดทั้งโปรเจกต์ (ยกเว้น vendor, node_modules, .git, storage/app)..." -ForegroundColor Yellow
    
    if (Test-Path $tarArchive) { Remove-Item $tarArchive -Force }
    
    tar.exe -czf $tarArchive --exclude=vendor --exclude=node_modules --exclude=.git --exclude=.env --exclude=storage/app/private --exclude=storage/framework/cache app bootstrap config database public resources routes scripts composer.json package.json deploy.sh artisan
    
    $fileSize = (Get-Item $tarArchive).Length / 1MB
    Write-Host ("   ✅ แพ็กไฟล์สำเร็จ: {0:N2} MB" -f $fileSize) -ForegroundColor Green

    Write-Host ""
    Write-Host "📤 [3/4] กำลังส่งไฟล์ไปยังเซิร์ฟเวอร์ ($ServerUser@$ServerHost)..." -ForegroundColor Yellow
    scp.exe -o ConnectTimeout=15 $tarArchive "${ServerUser}@${ServerHost}:/tmp/${tarArchive}"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ เกิดข้อผิดพลาดในการส่งไฟล์ผ่าน SCP" -ForegroundColor Red
        if (Test-Path $tarArchive) { Remove-Item $tarArchive -Force }
        exit 1
    }

    Write-Host ""
    Write-Host "⚡ [4/4] กำลังแตกไฟล์และอัปเดตระบบบนเซิร์ฟเวอร์..." -ForegroundColor Yellow
    $remoteCmd = "tar -xzf /tmp/$tarArchive -C $RemoteDir/ && rm -f /tmp/$tarArchive && cd $RemoteDir && php artisan optimize:clear && php artisan view:cache && chown -R www-data:www-data storage bootstrap/cache"
    ssh.exe -o ConnectTimeout=15 "${ServerUser}@${ServerHost}" $remoteCmd

} elseif ($Mode -eq "3" -or $Mode -eq "git") {
    # ─── โหมด 3: Git Push + Remote Deploy ─────────────────────
    Write-Host ""
    Write-Host "🐙 [2/4] กำลังเตรียม Commit และ Push ไปยัง GitHub..." -ForegroundColor Yellow
    $commitMsg = Read-Host "ใส่ข้อความ Commit (ค่าเริ่มต้น: 'update: pdf-editor in-place text edit')"
    if ([string]::IsNullOrWhiteSpace($commitMsg)) { $commitMsg = "update: pdf-editor in-place text edit" }
    
    git add resources/js/app.js resources/views/tools/pdf-editor.blade.php public/build/
    git commit -m $commitMsg
    git push origin main
    
    Write-Host ""
    Write-Host "🚀 [3/4] กำลังสั่งรัน deploy.sh บนเซิร์ฟเวอร์ผ่าน SSH..." -ForegroundColor Yellow
    ssh.exe -o ConnectTimeout=15 "${ServerUser}@${ServerHost}" "cd $RemoteDir && bash deploy.sh"
}

# ลบไฟล์ archive ชั่วคราวบนเครื่อง
if (Test-Path $tarArchive) {
    Remove-Item $tarArchive -Force
}

Write-Host ""
Write-Host "╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║   🎉  อัปโหลดและอัปเดตข้อมูลบนเซิร์ฟเวอร์สำเร็จเรียบร้อย!   ║" -ForegroundColor Green
Write-Host "╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 คุณสามารถเข้าทดสอบเครื่องมือได้ที่:" -ForegroundColor Cyan
Write-Host "   👉 http://$ServerHost/pdf-editor" -ForegroundColor Yellow
Write-Host "   👉 https://pdfkrub.com/pdf-editor (หากมีโดเมนผูกไว้)" -ForegroundColor Yellow
Write-Host ""
