@extends('layouts.app')

@section('title', 'ติดต่อเรา — PDFkrub')
@section('description', 'ติดต่อทีมงาน PDFkrub สำหรับความช่วยเหลือ ข้อเสนอแนะ หรือติดต่อแพ็กเกจโรงเรียนและสถานศึกษา')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
    <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 bg-white border border-gray-100 shadow-sm px-3.5 py-1.5 rounded-full text-xs text-brand-600 mb-4 border border-brand-200">
            <span>💬</span> ติดต่อทีมงาน
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-800 mb-4">
            ติดต่อ <span class="text-gradient">PDFkrub</span>
        </h1>
        <p class="text-gray-500 text-sm max-w-md mx-auto">
            มีคำถาม ปัญหาการใช้งาน หรือต้องการใบเสนอราคาสำหรับโรงเรียน ติดต่อเราได้ทันที
        </p>
    </div>

    <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-8 sm:p-10 border border-gray-100 space-y-6">
        <div class="grid sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5 text-center">
                <div class="text-2xl mb-2">📧</div>
                <h3 class="font-bold text-gray-800 text-sm mb-1">อีเมลติดต่อ</h3>
                <p class="text-xs text-brand-600">support@pdfkrub.com</p>
                <p class="text-[11px] text-gray-400 mt-1">ตอบกลับภายใน 24 ชม.</p>
            </div>
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5 text-center">
                <div class="text-2xl mb-2">🏫</div>
                <h3 class="font-bold text-gray-800 text-sm mb-1">ประสานงานโรงเรียน</h3>
                <p class="text-xs text-brand-600">school@pdfkrub.com</p>
                <p class="text-[11px] text-gray-400 mt-1">สำหรับขอใบเสนอราคา / หัก ณ ที่จ่าย</p>
            </div>
        </div>

        <form class="space-y-4" onsubmit="event.preventDefault(); alert('ขอบคุณสำหรับข้อความ ทีมงานจะติดต่อกลับโดยเร็วครับ');">
            {{-- Anti-spam bot honeypot --}}
            <div class="hidden" aria-hidden="true" style="display:none">
                <input type="text" name="phone_fax" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">ชื่อผู้ติดต่อ / โรงเรียน</label>
                <input type="text" required placeholder="คุณครูสมใจ / โรงเรียนบ้านหนองบัว"
                       class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-slate-500 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/50">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">อีเมลสำหรับติดต่อกลับ</label>
                <input type="email" required placeholder="teacher@school.ac.th"
                       class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-slate-500 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/50">
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1.5">ข้อความ / รายละเอียด</label>
                <textarea rows="4" required placeholder="ระบุคำถาม หรือรายละเอียดที่ต้องการให้ช่วยเหลือ..."
                          class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-slate-500 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/50"></textarea>
            </div>

            <button type="submit" class="w-full btn-primary py-3.5 rounded-xl text-sm font-semibold">
                ส่งข้อความ
            </button>
        </form>
    </div>

    <div class="text-center mt-10">
        <a href="{{ route('home') }}" class="btn-ghost px-6 py-2.5 rounded-xl text-sm inline-flex items-center gap-2">
            ← กลับหน้าหลัก
        </a>
    </div>
</div>
@endsection
