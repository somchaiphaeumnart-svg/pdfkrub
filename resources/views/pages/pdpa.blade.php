@extends('layouts.app')

@section('title', 'นโยบายคุ้มครองข้อมูลส่วนบุคคล (PDPA) — PDFkrub')
@section('description', 'ข้อกำหนดและนโยบายคุ้มครองข้อมูลส่วนบุคคล (PDPA) ของ PDFkrub สำหรับครู นักเรียน และสถานศึกษา ประมวลผลในประเทศไทย และลบไฟล์อัตโนมัติ')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">

    {{-- Header --}}
    <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 bg-white border border-gray-100 shadow-sm px-3.5 py-1.5 rounded-full text-xs text-brand-600 mb-4 border border-brand-200">
            <span class="text-base">🛡️</span>
            พ.ร.บ. คุ้มครองข้อมูลส่วนบุคคล พ.ศ. 2562 (PDPA)
        </div>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-800 mb-4">
            นโยบายคุ้มครองข้อมูลส่วนบุคคล <span class="text-gradient">(PDPA Policy)</span>
        </h1>
        <p class="text-gray-500 text-sm max-w-xl mx-auto">
            PDFkrub ให้ความสำคัญสูงสุดกับความปลอดภัยของข้อมูลครู นักเรียน และบุคลากรทางการศึกษา ปรับปรุงล่าสุด: {{ date('d/m/Y') }}
        </p>
    </div>

    {{-- Highlight Cards --}}
    <div class="grid sm:grid-cols-3 gap-4 mb-12">
        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 border border-success-200 text-center">
            <div class="text-2xl mb-2">🇹🇭</div>
            <h3 class="font-bold text-gray-800 text-sm mb-1">เซิร์ฟเวอร์ในประเทศไทย</h3>
            <p class="text-xs text-gray-500">ข้อมูลและไฟล์เอกสารถูกประมวลผลบน Cloud VPS ในประเทศไทย ไม่มีการโอนย้ายออกนอกประเทศ</p>
        </div>
        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 border border-brand-200 text-center">
            <div class="text-2xl mb-2">⏱️</div>
            <h3 class="font-bold text-gray-800 text-sm mb-1">ลบไฟล์อัตโนมัติ (1 ชม.)</h3>
            <p class="text-xs text-gray-500">ไฟล์ต้นฉบับและผลลัพธ์ของผู้ใช้ฟรีจะถูกทำลายถาวรจากดิสก์อัตโนมัติภายใน 1 ชั่วโมง</p>
        </div>
        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 border border-purple-500/30 text-center">
            <div class="text-2xl mb-2">🔒</div>
            <h3 class="font-bold text-gray-800 text-sm mb-1">ไม่นำข้อมูลไปเทรน AI</h3>
            <p class="text-xs text-gray-500">ไม่มีการนำเอกสาร รูปภาพ บัตรประชาชน หรือข้อมูลนักเรียนไปฝึกฝน AI หรือจำหน่ายให้บุคคลที่สาม</p>
        </div>
    </div>

    {{-- Content Body --}}
    <div class="bg-white border border-gray-100 shadow-sm rounded-3xl p-8 sm:p-12 border border-gray-100 space-y-8 text-gray-600 leading-relaxed text-sm">

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                1. บทบาทของ PDFkrub ภายใต้กฎหมาย PDPA
            </h2>
            <p>
                ในการให้บริการแปลง, รวม, แยก, OCR และจัดการเอกสาร PDF แพลตฟอร์ม <strong>PDFkrub</strong> ทำหน้าที่เป็น <strong>"ผู้ประมวลผลข้อมูลส่วนบุคคล" (Data Processor)</strong> ในนามของผู้ใช้งาน (ครู, ผู้บริหารสถานศึกษา, บุคลากร) ซึ่งถือเป็น <strong>"ผู้ควบคุมข้อมูลส่วนบุคคล" (Data Controller)</strong>
            </p>
            <p>
                PDFkrub จะประมวลผลเอกสารตามคำสั่งโดยตรงของผู้ใช้งานผ่านระบบอัตโนมัติเท่านั้น และไม่มีการเข้าถึงเนื้อหาภายในเอกสารโดยมนุษย์ เว้นแต่จะได้รับคำร้องขอเป็นลายลักษณ์อักษรจากผู้ใช้งานเพื่อวัตถุประสงค์ในการแก้ไขปัญหาทางเทคนิค
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                2. ข้อมูลส่วนบุคคลที่เราอาจพบในเอกสารทางการศึกษา
            </h2>
            <p>เอกสารที่คุณอัปโหลด เช่น เอกสารหลักฐาน ว PA, ใบเสร็จ, ทะเบียนนักเรียน หรือแบบฟอร์มราชการ อาจประกอบด้วยข้อมูลที่มีความอ่อนไหว เช่น:</p>
            <ul class="list-disc list-inside space-y-1.5 pl-2 text-gray-500">
                <li>ชื่อ-นามสกุล, เลขประจำตัวประชาชน 13 หลัก, วันเดือนปีเกิด</li>
                <li>ภาพถ่ายใบหน้า, ลายมือชื่อ (Signature)</li>
                <li>ข้อมูลผลการเรียน, ประวัติการทำงาน, วิทยฐานะ</li>
                <li>ข้อมูลสถานศึกษา, สังกัดเขตพื้นที่การศึกษา</li>
            </ul>
            <p class="bg-brand-50 border border-brand-200 rounded-xl p-3.5 text-xs text-brand-600">
                💡 <strong>คำแนะนำสำหรับคุณครู:</strong> ก่อนการแชร์เอกสารสู่สาธารณะ แนะนำให้ใช้เครื่องมือ "ปิดเลขบัตรประชาชน" หรือ "ประทับสำเนาถูกต้อง" เพื่อปฏิบัติตามแนวทางคุ้มครองข้อมูลของกระทรวงศึกษาธิการ
            </p>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                3. นโยบายการเก็บรักษาและทำลายไฟล์ (Data Retention & Deletion)
            </h2>
            <p>
                เพื่อลดความเสี่ยงจากการรั่วไหลของข้อมูล PDFkrub ใช้ระบบ Cron ทำลายไฟล์อัตโนมัติ (Automated Secure File Purging) โดยมีระยะเวลาดังนี้:
            </p>
            <div class="overflow-x-auto">
                <table class="w-full text-xs border border-gray-200 rounded-xl overflow-hidden">
                    <thead class="bg-gray-50 text-gray-800">
                        <tr>
                            <th class="py-2.5 px-4 text-left">ประเภทผู้ใช้งาน</th>
                            <th class="py-2.5 px-4 text-left">ระยะเวลาเก็บไฟล์</th>
                            <th class="py-2.5 px-4 text-left">วิธีการทำลาย</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-gray-500">
                        <tr>
                            <td class="py-2.5 px-4 text-gray-800 font-medium">ผู้ใช้งานฟรี (Guest & Free)</td>
                            <td class="py-2.5 px-4 text-emerald-400 font-semibold">1 ชั่วโมง</td>
                            <td class="py-2.5 px-4">ลบถาวรจาก Disk (Unlink) ทุก 60 นาที</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-4 text-gray-800 font-medium">ครู (Teacher) / โปร (Pro)</td>
                            <td class="py-2.5 px-4 text-brand-600 font-semibold">7 วัน (หรือกดลบทันที)</td>
                            <td class="py-2.5 px-4">ลบอัตโนมัติ หรือลบเองได้จากหน้า Dashboard</td>
                        </tr>
                        <tr>
                            <td class="py-2.5 px-4 text-gray-800 font-medium">โรงเรียน (School)</td>
                            <td class="py-2.5 px-4 text-purple-400 font-semibold">30 วัน (ปรับแต่งได้)</td>
                            <td class="py-2.5 px-4">แอดมินโรงเรียนสามารถสั่งลบทั้งองค์กรได้</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                4. มาตรการรักษาความมั่นคงปลอดภัย (Security Measures)
            </h2>
            <ul class="list-disc list-inside space-y-2 pl-2 text-gray-500">
                <li><strong class="text-gray-800">การส่งผ่านข้อมูล:</strong> เข้ารหัสผ่านโปรโตคอล TLS 1.3 / HTTPS ระดับ 256-bit</li>
                <li><strong class="text-gray-800">การจัดเก็บ:</strong> ไฟล์ถูกสุ่มชื่อด้วย Hash ป้องกันการสุ่ม URL (Direct URL Guessing)</li>
                <li><strong class="text-gray-800">การดาวน์โหลด:</strong> ลิงก์ดาวน์โหลดเป็นแบบ Signed URL ที่มีอายุใช้งานจำกัด</li>
                <li><strong class="text-gray-800">การควบคุมการเข้าถึง:</strong> มีระบบ Rate Limiting ป้องกันการเข้าถึงข้อมูลโดยไม่ได้รับอนุญาต</li>
            </ul>
        </section>

        <section class="space-y-3">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                5. สิทธิของเจ้าของข้อมูลส่วนบุคคล (Data Subject Rights)
            </h2>
            <p>ภายใต้กฎหมาย PDPA คุณมีสิทธิ:</p>
            <ul class="list-disc list-inside space-y-1 pl-2 text-gray-500">
                <li>สิทธิในการเข้าถึงและขอรับสำเนาข้อมูลส่วนบุคคลของตนเอง</li>
                <li>สิทธิในการขอให้ลบ ทำลาย หรือทำให้ข้อมูลส่วนบุคคลไม่สามารถระบุตัวบุคคลได้</li>
                <li>สิทธิในการระงับการใช้ข้อมูลส่วนบุคคล</li>
                <li>สิทธิในการเพิกถอนความยินยอม</li>
            </ul>
        </section>

        <section class="space-y-3 pt-4 border-t border-gray-200">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-brand-500"></span>
                6. ช่องทางการติดต่อเจ้าหน้าที่คุ้มครองข้อมูล (DPO Contact)
            </h2>
            <p class="text-xs text-gray-500">
                หากท่านมีข้อสงสัย ข้อเสนอแนะ หรือต้องการใช้สิทธิตามกฎหมาย PDPA สามารถติดต่อได้ที่:<br>
                📧 อีเมล: <a href="mailto:dpo@pdfkrub.com" class="text-brand-600 hover:underline">dpo@pdfkrub.com</a> หรือ <a href="mailto:support@pdfkrub.com" class="text-brand-600 hover:underline">support@pdfkrub.com</a><br>
                📍 แพลตฟอร์ม PDFkrub ประเทศไทย
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
