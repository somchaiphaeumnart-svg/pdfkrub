@extends('layouts.app')

@section('title', 'ราคา — PDFkrub')
@section('description', 'แผนราคา PDFkrub สำหรับครูและโรงเรียน เริ่มต้นฟรี ครูรายปี 390 บาท โรงเรียน 2,990 บาท/ปี รองรับ PDPA')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">

    {{-- Header --}}
    <div class="text-center mb-16">
        <div class="inline-flex items-center gap-2 glass px-3 py-1.5 rounded-full text-xs text-brand-300 mb-4 border border-brand-500/20">
            <span class="text-base">🏫</span>
            ราคาสำหรับครูและโรงเรียน
        </div>
        <h1 class="text-4xl sm:text-5xl font-bold text-gray-800 mb-4">แผนราคาที่ <span class="text-gradient">ครูเลือกได้</span></h1>
        <p class="text-gray-500 max-w-xl mx-auto">เริ่มต้นฟรี ไม่ต้องใส่บัตรเครดิต · รองรับ PDPA · ประมวลผลในประเทศไทย</p>

        {{-- Billing toggle --}}
        <div class="mt-8 inline-flex items-center glass rounded-full p-1.5 border border-gray-100" x-data="{ yearly: false }">
            <button @click="yearly = false"
                    class="px-5 py-2 text-sm rounded-full transition-all"
                    :class="!yearly ? 'bg-brand-600 text-white font-medium' : 'text-gray-500 hover:text-gray-800'">
                รายเดือน
            </button>
            <button @click="yearly = true"
                    class="px-5 py-2 text-sm rounded-full transition-all flex items-center gap-2"
                    :class="yearly ? 'bg-brand-600 text-white font-medium' : 'text-gray-500 hover:text-gray-800'">
                รายปี
                <span class="text-xs bg-success-500/20 text-success-400 px-2 py-0.5 rounded-full">ประหยัดสูงสุด 40%</span>
            </button>
        </div>
    </div>

    {{-- Main Plans (Free / Pro / Teacher) --}}
    <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto mb-12" x-data="{ yearly: false }">

        @php
        $mainPlans = $plans->whereIn('name', ['free', 'pro', 'teacher'])->sortBy('sort_order');
        @endphp

        @foreach($mainPlans as $plan)
        @php
        $isTeacher = $plan->name === 'teacher';
        $isPro     = $plan->name === 'pro';
        $isFeatured = $isPro;
        $emoji = match($plan->name) {
            'free'    => '🆓',
            'pro'     => '⚡',
            'teacher' => '🎓',
            default   => '📄'
        };
        @endphp

        <div class="glass rounded-2xl p-8 border card-hover relative flex flex-col
                    {{ $isFeatured ? 'border-brand-500/50 glow-blue' : ($isTeacher ? 'border-success-500/30' : 'border-gray-100') }}">

            @if($isFeatured)
            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                <span class="bg-gradient-to-r from-brand-600 to-brand-400 text-gray-800 text-xs font-bold px-4 py-1.5 rounded-full shadow-lg">⭐ แนะนำ</span>
            </div>
            @endif
            @if($isTeacher)
            <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                <span class="bg-gradient-to-r from-success-600 to-success-400 text-gray-800 text-xs font-bold px-4 py-1.5 rounded-full shadow-lg">🎓 สำหรับครู</span>
            </div>
            @endif

            {{-- Plan header --}}
            <div class="mb-6">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-2xl">{{ $emoji }}</span>
                    <h2 class="text-xl font-bold text-gray-800">{{ $plan->display_name_th ?? $plan->display_name }}</h2>
                </div>

                {{-- Price monthly --}}
                <div x-show="!yearly">
                    @if($plan->price_monthly > 0)
                    <div class="flex items-baseline gap-1">
                        <span class="text-4xl font-bold text-gray-800">฿{{ number_format($plan->price_monthly) }}</span>
                        <span class="text-gray-500 text-sm">/เดือน</span>
                    </div>
                    @if($isTeacher)
                    <p class="text-xs text-gray-400 mt-1">*จ่ายรายปี ฿{{ number_format($plan->price_yearly) }} เท่านั้น</p>
                    @endif
                    @else
                    <span class="text-4xl font-bold text-gray-800">ฟรี</span>
                    @endif
                </div>
                {{-- Price yearly --}}
                <div x-show="yearly" style="display:none">
                    @if($plan->price_yearly > 0)
                    <div class="flex items-baseline gap-1">
                        <span class="text-4xl font-bold text-gray-800">฿{{ number_format(round($plan->price_yearly / 12)) }}</span>
                        <span class="text-gray-500 text-sm">/เดือน</span>
                    </div>
                    <p class="text-xs text-success-400 mt-1">฿{{ number_format($plan->price_yearly) }}/ปี · ประหยัด ฿{{ number_format($plan->price_monthly * 12 - $plan->price_yearly) }}</p>
                    @else
                    <span class="text-4xl font-bold text-gray-800">ฟรี</span>
                    @endif
                </div>
            </div>

            {{-- Features --}}
            <ul class="space-y-3 flex-1 mb-8">
                @php
                $featureList = [
                    ['check' => true,  'text' => 'ไฟล์สูงสุด ' . $plan->max_file_size_mb . ' MB'],
                    ['check' => true,  'text' => $plan->daily_conversions === -1 ? 'แปลงไฟล์ไม่จำกัด' : 'แปลงได้ ' . $plan->daily_conversions . ' ครั้ง/วัน'],
                    ['check' => true,  'text' => match(true) {
                        $plan->file_retention_hours >= 720 => 'เก็บไฟล์ 30 วัน',
                        $plan->file_retention_hours >= 168 => 'เก็บไฟล์ 7 วัน',
                        default => 'เก็บไฟล์ ' . $plan->file_retention_hours . ' ชั่วโมง (PDPA)'
                    }],
                    ['check' => $plan->has_ocr,   'text' => 'OCR ภาษาไทย (Google Vision)'],
                    ['check' => $plan->has_esign, 'text' => 'ลงลายเซ็น + สำเนาถูกต้อง'],
                    ['check' => !$plan->has_watermark, 'text' => 'ไม่มี Watermark'],
                    ['check' => $isTeacher || $plan->name === 'business', 'text' => 'รวมหลักฐาน PA ขนาดใหญ่'],
                    ['check' => $isTeacher || $plan->name === 'business', 'text' => 'ลายน้ำโรงเรียน / ตราประทับ'],
                ];
                @endphp

                @foreach($featureList as $feat)
                <li class="flex items-center gap-2.5 text-sm {{ $feat['check'] ? 'text-gray-600' : 'text-gray-300' }}">
                    @if($feat['check'])
                    <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    @else
                    <svg class="w-4 h-4 flex-shrink-0 opacity-25" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    @endif
                    {{ $feat['text'] }}
                </li>
                @endforeach
            </ul>

            {{-- CTA --}}
            @auth
                @if(auth()->user()->getActivePlan()->name === $plan->name)
                <button disabled class="w-full btn-ghost rounded-xl py-3.5 text-sm opacity-50 cursor-not-allowed">แผนปัจจุบัน ✓</button>
                @elseif($plan->price_yearly > 0)
                <a href="{{ route('billing.upgrade', $plan) }}"
                   class="w-full block text-center {{ $isFeatured ? 'btn-primary' : 'btn-ghost' }} rounded-xl py-3.5 text-sm">
                    อัปเกรดเป็น {{ $plan->display_name_th ?? $plan->display_name }}
                </a>
                @else
                <a href="{{ route('billing.downgrade', $plan) }}" class="w-full block text-center btn-ghost rounded-xl py-3.5 text-sm">เปลี่ยนเป็นแผนฟรี</a>
                @endif
            @else
                <a href="{{ route('register') }}"
                   class="w-full block text-center {{ $isFeatured ? 'btn-primary' : ($isTeacher ? 'bg-success-600 hover:bg-success-500 text-gray-800 font-semibold transition-colors' : 'btn-ghost') }} rounded-xl py-3.5 text-sm">
                    {{ $plan->price_monthly > 0 ? ($isTeacher ? 'สมัครแผนครู' : 'เริ่มทดลองใช้') : 'สมัครฟรี' }}
                </a>
            @endauth
        </div>
        @endforeach
    </div>

    {{-- School & Business plans --}}
    <div class="max-w-5xl mx-auto mb-16">
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">แผนสำหรับ <span class="text-gradient">โรงเรียนและองค์กร</span></h2>
            <p class="text-gray-500 text-sm">ใช้งานร่วมกันทั้งโรงเรียน บริหารสิทธิ์ครูได้ รองรับ PDPA ระดับสถาบัน</p>
        </div>

        <div class="grid md:grid-cols-2 gap-6" x-data="{ yearly: true }">
            @php
            $orgPlans = $plans->whereIn('name', ['school', 'business'])->sortBy('sort_order');
            @endphp

            @foreach($orgPlans as $plan)
            @php
            $isSchool = $plan->name === 'school';
            $emoji = $isSchool ? '🏫' : '🏢';
            @endphp

            <div class="glass rounded-2xl p-8 border {{ $isSchool ? 'border-brand-500/30' : 'border-gray-100' }} card-hover relative flex flex-col">
                @if($isSchool)
                <div class="absolute -top-3 right-6">
                    <span class="bg-brand-600/80 backdrop-blur text-gray-800 text-xs font-bold px-3 py-1 rounded-full">🏫 โรงเรียน</span>
                </div>
                @endif

                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">{{ $emoji }}</span>
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">{{ $plan->display_name_th }}</h3>
                        <p class="text-xs text-gray-400">{{ $isSchool ? 'ครูสูงสุด ' . $plan->max_team_members . ' คน' : 'สมาชิกสูงสุด ' . $plan->max_team_members . ' คน' }}</p>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-bold text-gray-800">฿{{ number_format($plan->price_yearly) }}</span>
                        <span class="text-gray-500 text-sm">/ปี</span>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">≈ ฿{{ number_format(round($plan->price_yearly / 12)) }}/เดือน</p>
                    @if($isSchool)
                    <p class="text-xs text-success-400 mt-1">📞 ติดต่อขอราคาพิเศษสำหรับโรงเรียนขนาดใหญ่</p>
                    @endif
                </div>

                <ul class="space-y-2.5 flex-1 mb-8">
                    @foreach($plan->features ?? [] as $feat)
                    <li class="flex items-center gap-2.5 text-sm text-gray-600">
                        <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                        {{ $feat }}
                    </li>
                    @endforeach
                </ul>

                @auth
                <a href="{{ route('billing.upgrade', $plan) }}" class="w-full block text-center {{ $isSchool ? 'btn-primary' : 'btn-ghost' }} rounded-xl py-3.5 text-sm">
                    อัปเกรดเป็น{{ $plan->display_name_th }}
                </a>
                @else
                <a href="{{ route('register') }}" class="w-full block text-center {{ $isSchool ? 'btn-primary' : 'btn-ghost' }} rounded-xl py-3.5 text-sm">
                    {{ $isSchool ? 'สมัครแผนโรงเรียน' : 'ติดต่อทีมขาย' }}
                </a>
                @endauth
            </div>
            @endforeach
        </div>
    </div>

    {{-- PDPA Trust Bar --}}
    <div class="max-w-5xl mx-auto mb-16">
        <div class="glass rounded-2xl p-6 border border-success-500/20 bg-gradient-to-r from-success-500/5 to-brand-500/5">
            <div class="flex flex-col md:flex-row items-center gap-4 text-center md:text-left">
                <div class="text-3xl">🛡️</div>
                <div class="flex-1">
                    <h3 class="text-gray-800 font-bold mb-1">ปลอดภัย รองรับ PDPA · ประมวลผลในประเทศไทย</h3>
                    <p class="text-gray-500 text-sm">เอกสารทุกไฟล์ประมวลผลบนเซิร์ฟเวอร์ไทย ไม่ส่งออกนอกประเทศ ลบอัตโนมัติตามแผน เหมาะสำหรับเอกสารสำคัญ เช่น เลขบัตรประชาชน โปรไฟล์นักเรียน</p>
                </div>
                <div class="flex flex-wrap gap-2 justify-center">
                    <span class="text-xs px-3 py-1.5 rounded-full bg-success-500/10 text-success-400 border border-success-500/20">🔒 AES-256</span>
                    <span class="text-xs px-3 py-1.5 rounded-full bg-brand-500/10 text-brand-400 border border-brand-500/20">🇹🇭 เซิร์ฟเวอร์ไทย</span>
                    <span class="text-xs px-3 py-1.5 rounded-full bg-accent-500/10 text-accent-400 border border-accent-500/20">⏱️ ลบอัตโนมัติ</span>
                </div>
            </div>
        </div>
    </div>

    {{-- FAQ --}}
    <div class="max-w-3xl mx-auto">
        <h2 class="text-2xl font-bold text-gray-800 text-center mb-10">คำถามที่พบบ่อย</h2>

        <div class="space-y-4" x-data="{ open: null }">
            @php
            $faqs = [
                ['q' => 'แผนครู (Teacher) ต่างจากแผนโปร (Pro) อย่างไร?',
                 'a' => 'แผนครูออกแบบมาเพื่อครูโดยเฉพาะ มีฟีเจอร์รวมหลักฐาน PA, ลายน้ำโรงเรียน, ประทับสำเนาถูกต้อง และรวมเกียรติบัตรทีละหลายร้อยไฟล์ ในราคา 390 บาท/ปี (ถูกกว่าโปรรายเดือน)'],
                ['q' => 'แผนโรงเรียน (School) บริหารอย่างไร?',
                 'a' => 'มีระบบ Admin ให้ผู้บริหารจัดการสิทธิ์ครูได้สูงสุด 30 คน ครูแต่ละคนล็อกอินด้วยบัญชีตัวเองและใช้เครื่องมือได้ครบ'],
                ['q' => 'ไฟล์ของฉันปลอดภัยแค่ไหน?',
                 'a' => 'ไฟล์ถูกเข้ารหัส AES-256 ประมวลผลบนเซิร์ฟเวอร์ในประเทศไทย และถูกลบอัตโนมัติตามเวลาที่กำหนดในแต่ละแผน ไม่มีการส่งข้อมูลออกนอกประเทศ รองรับ PDPA'],
                ['q' => 'รองรับการชำระเงินช่องทางไหน?',
                 'a' => 'รองรับ PromptPay QR Code, บัตรเครดิต/เดบิต Visa, Mastercard, JCB (ผ่านระบบ Omise)'],
                ['q' => 'ยกเลิกแผนได้เมื่อไหร่?',
                 'a' => 'ยกเลิกได้ทุกเมื่อ ไม่มีสัญญาผูกมัด หลังยกเลิกยังใช้งานได้จนครบรอบบิล'],
                ['q' => 'OCR ภาษาไทยแม่นยำแค่ไหน?',
                 'a' => 'ใช้ Google Cloud Vision API ซึ่งแม่นยำสูงสุดสำหรับภาษาไทย รองรับทั้งภาษาไทย อังกฤษ และเอกสารผสม เหมาะสำหรับแบบฟอร์มราชการที่สแกนมา'],
            ];
            @endphp

            @foreach($faqs as $i => $faq)
            <div class="glass rounded-xl border border-gray-100 overflow-hidden">
                <button class="w-full flex items-center justify-between px-6 py-4 text-left"
                        @click="open = open === {{ $i }} ? null : {{ $i }}">
                    <span class="font-medium text-gray-800 text-sm">{{ $faq['q'] }}</span>
                    <svg class="w-4 h-4 text-gray-500 flex-shrink-0 transition-transform"
                         :class="open === {{ $i }} ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>
                <div x-show="open === {{ $i }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="px-6 pb-5 text-sm text-gray-500 leading-relaxed border-t border-gray-100 pt-4">
                    {{ $faq['a'] }}
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
