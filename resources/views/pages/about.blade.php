@extends('layouts.app')

@section('title', 'เกี่ยวกับเรา — PDFkrub')
@section('description', 'PDFkrub แพลตฟอร์มจัดการเอกสาร PDF ภาษาไทย เพื่อช่วยเหลือครูและบุคลากรทางการศึกษาไทย ลดภาระงานเอกสาร')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-12">
        <a href="{{ route('home') }}" class="inline-block mb-6 transition-transform hover:scale-105">
            <img src="{{ asset('images/logo.png') }}" alt="PDFkrub Logo" class="w-32 h-32 sm:w-40 sm:h-40 mx-auto object-contain rounded-3xl shadow-md border border-gray-100 bg-white p-2">
        </a>
        <div class="inline-flex items-center gap-2 bg-white border border-gray-100 shadow-sm px-3.5 py-1.5 rounded-full text-xs text-brand-600 mb-4 border border-brand-200">
            <span>🏫</span> เกี่ยวกับ PDFkrub
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-800 mb-4">
            พันธกิจของเราเพื่อ <span class="text-gradient">ครูไทย</span>
        </h1>
        <p class="text-gray-500 text-sm max-w-xl mx-auto">
            เทคโนโลยีที่พัฒนาขึ้นเพื่อลดภาระงานเอกสารของคุณครู เพื่อให้ครูมีเวลาทุ่มเทกับการสอนนักเรียนได้อย่างเต็มที่
        </p>
    </div>

    <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-8 sm:p-12 border border-gray-100 space-y-8 text-gray-600 leading-relaxed text-sm">
        <section class="space-y-3">
            <h2 class="text-xl font-bold text-gray-800">จุดเริ่มต้นของ PDFkrub</h2>
            <p>
                ในระบบการศึกษาไทย คุณครูต้องเผชิญกับภาระงานเอกสารจำนวนมหาศาล ทั้งเอกสารประเมินวิทยฐานะ (ว PA), รายงาน SAR, การจัดทำเกียรติบัตร, และงานสารบรรณโรงเรียน ซึ่งส่วนใหญ่เป็นไฟล์ PDF ที่แก้ไขยาก ไฟล์มีขนาดใหญ่เกินกว่าที่ระบบราชการจะรับได้ หรือเป็นเอกสารสแกนที่ไม่สามารถคัดลอกข้อความได้
            </p>
            <p>
                <strong>PDFkrub</strong> ถือกำเนิดขึ้นจากความตั้งใจที่จะสร้างเครื่องมือจัดการ PDF ที่เข้าใจภาษาไทย 100% มีระบบ OCR ภาษาไทยที่แม่นยำ และมีฟังก์ชันเฉพาะทางสำหรับงานของครู เช่น การรวมหลักฐาน PA, การบีบอัดไฟล์ให้ผ่านเกณฑ์ DPA, และการประทับสำเนาถูกต้อง
            </p>
        </section>

        <div class="grid sm:grid-cols-3 gap-4 pt-4">
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 border border-gray-200 text-center">
                <div class="text-2xl mb-2">⚡</div>
                <h3 class="font-bold text-gray-800 text-sm mb-1">ลดเวลาทำงาน</h3>
                <p class="text-xs text-gray-500">จาก 30 นาที เหลือ 30 วินาที</p>
            </div>
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 border border-gray-200 text-center">
                <div class="text-2xl mb-2">🇹🇭</div>
                <h3 class="font-bold text-gray-800 text-sm mb-1">ภาษาไทย 100%</h3>
                <p class="text-xs text-gray-500">ตัดคำไทยแม่นยำ ฟอนต์ไม่เพี้ยน</p>
            </div>
            <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 border border-gray-200 text-center">
                <div class="text-2xl mb-2">🛡️</div>
                <h3 class="font-bold text-gray-800 text-sm mb-1">ความปลอดภัย PDPA</h3>
                <p class="text-xs text-gray-500">ประมวลผลบนเซิร์ฟเวอร์ไทย ลบใน 1 ชม.</p>
            </div>
        </div>
    </div>

    <div class="text-center mt-10">
        <a href="{{ route('home') }}" class="btn-ghost px-6 py-2.5 rounded-xl text-sm inline-flex items-center gap-2">
            ← กลับหน้าหลัก
        </a>
    </div>
</div>
@endsection
