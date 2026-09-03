@extends('layouts.app')

@section('title', 'นโยบายความเป็นส่วนตัว — PDFkrub')
@section('description', 'นโยบายความเป็นส่วนตัวของแพลตฟอร์ม PDFkrub สำหรับผู้ใช้งาน ครู และสถานศึกษา')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    {{-- Header --}}
    <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 glass px-3.5 py-1.5 rounded-full text-xs text-brand-300 mb-4 border border-brand-500/20">
            <span>🔒</span> ความเป็นส่วนตัวและความปลอดภัย
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-800 mb-4">
            นโยบายความเป็นส่วนตัว <span class="text-gradient">(Privacy Policy)</span>
        </h1>
        <p class="text-gray-500 text-sm max-w-xl mx-auto">
            มีผลบังคับใช้ตั้งแต่วันที่ 1 มกราคม 2567 | ปรับปรุงล่าสุด: {{ date('d/m/Y') }}
        </p>
    </div>

    {{-- Body --}}
    <div class="glass rounded-3xl p-8 sm:p-12 border border-gray-100 space-y-8 text-gray-600 leading-relaxed text-sm">

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                1. ข้อมูลที่เราจัดเก็บ
            </h2>
            <p>เมื่อท่านใช้บริการ PDFkrub เราอาจจัดเก็บข้อมูลตามความจำเป็นดังต่อไปนี้:</p>
            <ul class="list-disc list-inside space-y-1 pl-2 text-gray-500">
                <li><strong class="text-gray-800">ข้อมูลบัญชีผู้ใช้:</strong> ชื่อ นามสกุล ที่อยู่อีเมล และรหัสผ่านที่เข้ารหัสแล้ว (เมื่อสมัครสมาชิก)</li>
                <li><strong class="text-gray-800">ข้อมูลการชำระเงิน:</strong> ข้อมูลการสั่งซื้อผ่าน Omise Gateway (เราไม่เก็บเลขบัตรเครดิตฉบับเต็มบนเซิร์ฟเวอร์ของเรา)</li>
                <li><strong class="text-gray-800">ไฟล์เอกสารที่อัปโหลด:</strong> เพื่อดำเนินการแปลง แก้ไข หรือประมวลผลตามคำสั่งของท่าน</li>
                <li><strong class="text-gray-800">ข้อมูลเชิงเทคนิค:</strong> ที่อยู่ IP, ชนิดของเบราว์เซอร์ และบันทึกเวลาการเข้าถึง เพื่อความปลอดภัยของระบบ</li>
            </ul>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                2. วัตถุประสงค์การใช้ข้อมูล
            </h2>
            <p>เราใช้ข้อมูลของท่านเพื่อ:</p>
            <ul class="list-disc list-inside space-y-1 pl-2 text-gray-500">
                <li>ประมวลผลไฟล์ตามเครื่องมือที่ท่านเลือก เช่น แปลง PDF เป็น Word, OCR ภาษาไทย, หรือรวมไฟล์</li>
                <li>บริหารจัดการบัญชีผู้ใช้งานและสถานะการสมัครสมาชิก (Subscription)</li>
                <li>ส่งการแจ้งเตือนเกี่ยวกับบริการ เช่น การยืนยันชำระเงิน หรือการปรับปรุงระบบ</li>
                <li>ตรวจสอบและป้องกันการใช้งานที่ผิดกฎหมาย หรือการโจมตีระบบรักษาความปลอดภัย</li>
            </ul>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                3. การลบไฟล์เอกสารอัตโนมัติ
            </h2>
            <p>
                PDFkrub มีนโยบาย <strong class="text-gray-800">ไม่เก็บรักษาไฟล์เอกสารของท่านเกินความจำเป็น</strong> โดยไฟล์เอกสารที่อัปโหลดจะถูกลบทำลายอย่างถาวรจากเซิร์ฟเวอร์โดยอัตโนมัติ:
            </p>
            <ul class="list-disc list-inside space-y-1 pl-2 text-gray-500">
                <li><strong class="text-emerald-400">ผู้ใช้งานทั่วไป / ฟรี:</strong> ลบไฟล์อัตโนมัติภายใน <strong>1 ชั่วโมง</strong> หลังการประมวลผล</li>
                <li><strong class="text-brand-400">สมาชิกรายปีครู / โปร:</strong> เก็บรักษาไฟล์ <strong>7 วัน</strong> เพื่ออำนวยความสะดวกในการดาวน์โหลดซ้ำ (ท่านสามารถกดลบเองได้ทันที)</li>
                <li><strong class="text-purple-400">สถาบันการศึกษา / โรงเรียน:</strong> เก็บรักษา <strong>30 วัน</strong></li>
            </ul>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                4. การไม่เปิดเผยข้อมูลแก่บุคคลภายนอก
            </h2>
            <p>
                เราจะไม่ขาย ให้เช่า หรือเผยแพร่ข้อมูลส่วนบุคคลและเนื้อหาในเอกสารของท่านแก่บุคคลภายนอกอย่างเด็ดขาด เว้นแต่ในกรณีที่มีคำสั่งศาลหรือกฎหมายกำหนดให้ต้องเปิดเผยต่อเจ้าพนักงานตามกฎหมาย
            </p>
        </section>

        <section class="space-y-3 pt-4 border-t border-gray-200">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                5. ติดต่อเรา
            </h2>
            <p class="text-xs text-gray-500">
                หากท่านมีข้อสงสัยเกี่ยวกับนโยบายความเป็นส่วนตัวนี้ สามารถติดต่อเราได้ที่:<br>
                📧 อีเมล: <a href="mailto:support@pdfkrub.com" class="text-brand-400 hover:underline">support@pdfkrub.com</a><br>
                🇹🇭 PDFkrub — แพลตฟอร์มจัดการเอกสาร PDF ภาษาไทย
            </p>
        </section>

    </div>

    <div class="text-center mt-10">
        <a href="{{ route('home') }}" class="btn-ghost px-6 py-2.5 rounded-xl text-sm inline-flex items-center gap-2">
            ← กลับหน้าหลัก
        </a>
    </div>

</div>
@endsection
