@extends('layouts.app')

@section('title', 'ข้อกำหนดการใช้งาน — PDFkrub')
@section('description', 'ข้อกำหนดและเงื่อนไขการใช้บริการแพลตฟอร์ม PDFkrub สำหรับผู้ใช้งาน ครู และสถานศึกษา')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 glass px-3.5 py-1.5 rounded-full text-xs text-brand-300 mb-4 border border-brand-500/20">
            <span>📜</span> ข้อกำหนดและเงื่อนไข
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-4">
            ข้อกำหนดการใช้งาน <span class="text-gradient">(Terms of Service)</span>
        </h1>
        <p class="text-slate-400 text-sm max-w-xl mx-auto">
            โปรดอ่านข้อกำหนดเหล่านี้อย่างละเอียดก่อนการใช้บริการ ปรับปรุงล่าสุด: {{ date('d/m/Y') }}
        </p>
    </div>

    <div class="glass rounded-3xl p-8 sm:p-12 border border-white/[0.08] space-y-8 text-slate-300 leading-relaxed text-sm">
        <section class="space-y-3">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                1. การยอมรับข้อกำหนด
            </h2>
            <p>
                การเข้าถึงหรือใช้งานเว็บไซต์และบริการของ <strong>PDFkrub</strong> ถือว่าท่านตกลงผูกพันตามข้อกำหนดและเงื่อนไขการใช้งานเหล่านี้ รวมถึงนโยบายคุ้มครองข้อมูลส่วนบุคคล (PDPA) ของเรา
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                2. ขอบเขตการให้บริการ
            </h2>
            <p>
                PDFkrub ให้บริการเครื่องมือแปลง แปลงไฟล์ PDF เป็น Word, OCR ภาษาไทย, รวมและแยกหน้าเอกสาร, เซ็นเอกสารดิจิทัล, และแบบฟอร์มเอกสารทางการศึกษา โดยระบบทำงานผ่านเบราว์เซอร์และระบบคลาวด์แบบอัตโนมัติ
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                3. ข้อห้ามในการใช้งาน (Acceptable Use)
            </h2>
            <p>ผู้ใช้งานตกลงว่าจะไม่ใช้วิธีการใดๆ ในการ:</p>
            <ul class="list-disc list-inside space-y-1.5 pl-2 text-slate-400">
                <li>อัปโหลดไฟล์ที่มีไวรัส มัลแวร์ หรือโค้ดที่เป็นอันตรายต่อระบบ</li>
                <li>อัปโหลดเอกสารที่ผิดกฎหมาย ละเมิดลิขสิทธิ์ หรือข้อมูลที่ละเมิดความมั่นคงของชาติ</li>
                <li>พยายามเจาะระบบ ส่งคำขอเกินปริมาณปกติ (DDoS/Spam) หรือรบกวนการทำงานของเซิร์ฟเวอร์</li>
            </ul>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                4. ความเป็นเจ้าของในเอกสาร (Ownership of Documents)
            </h2>
            <p>
                ท่านยังคงเป็นเจ้าของสิทธิในไฟล์เอกสารทั้งหมดที่ท่านอัปโหลด PDFkrub ไม่ได้รับสิทธิความเป็นเจ้าของในเนื้อหาของเอกสาร และไม่มีการเผยแพร่เนื้อหาของท่านสู่สาธารณะ
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
