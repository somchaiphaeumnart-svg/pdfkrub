@extends('layouts.app')

@section('title', 'จัดการบัญชีและการสมัครสมาชิก')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-2xl font-bold text-white mb-8">การสมัครสมาชิก</h1>

    @php $user = auth()->user(); $plan = $user->getActivePlan(); $sub = $user->activeSubscription; @endphp

    {{-- Current Plan --}}
    <div class="glass rounded-2xl p-6 border border-white/[0.06] mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-slate-400 text-sm mb-1">แผนปัจจุบัน</p>
                <h2 class="text-2xl font-bold text-white">{{ $plan->display_name_th ?? $plan->display_name }}</h2>
                @if($sub && $sub->isOnTrial())
                <p class="text-accent-400 text-sm mt-1">🎁 ช่วงทดลองใช้ — สิ้นสุด {{ $sub->trial_ends_at->format('d/m/Y') }}</p>
                @elseif($sub)
                <p class="text-slate-400 text-sm mt-1">รอบถัดไป {{ $sub->current_period_end?->format('d/m/Y') ?? 'N/A' }}</p>
                @endif
            </div>
            @if($plan->isFree())
            <a href="{{ route('pricing') }}" class="btn-primary text-sm px-5 py-2.5 rounded-xl flex-shrink-0">อัปเกรด →</a>
            @else
            <div class="text-right">
                <p class="text-2xl font-bold text-white">฿{{ number_format($plan->price_monthly) }}<span class="text-slate-400 text-sm font-normal">/เดือน</span></p>
                <a href="{{ route('pricing') }}" class="text-xs text-slate-500 hover:text-brand-400 transition-colors mt-1 block">เปลี่ยนแผน</a>
            </div>
            @endif
        </div>

        {{-- Plan features --}}
        <div class="mt-5 pt-5 border-t border-white/[0.06] grid sm:grid-cols-3 gap-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-white">{{ $plan->max_file_size_mb }} MB</p>
                <p class="text-xs text-slate-500 mt-1">ขนาดไฟล์สูงสุด</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-white">{{ $plan->daily_conversions === -1 ? '∞' : $plan->daily_conversions }}</p>
                <p class="text-xs text-slate-500 mt-1">แปลงได้ต่อวัน</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-white">
                    @if($plan->file_retention_hours >= 720) 30 วัน
                    @elseif($plan->file_retention_hours >= 168) 7 วัน
                    @else {{ $plan->file_retention_hours }} ชม.
                    @endif
                </p>
                <p class="text-xs text-slate-500 mt-1">เก็บไฟล์</p>
            </div>
        </div>
    </div>

    {{-- Upgrade prompt for free users --}}
    @if($plan->isFree())
    <div class="glass rounded-2xl p-6 border border-brand-500/20 glow-blue mb-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
            <div class="flex-1">
                <h3 class="text-lg font-bold text-white mb-1">อัปเกรดเป็น Pro</h3>
                <p class="text-slate-400 text-sm">ไฟล์ 200 MB · ไม่จำกัดการแปลง · OCR ภาษาไทย · เซ็นเอกสาร</p>
            </div>
            <a href="{{ route('billing.upgrade', \App\Models\Plan::where('name','pro')->first()) }}"
               class="btn-primary text-sm px-6 py-3 rounded-xl flex-shrink-0">เริ่มทดลองฟรี 7 วัน</a>
        </div>
    </div>
    @endif

    {{-- Cancel subscription --}}
    @if($sub && $sub->isActive() && !$plan->isFree())
    <div class="glass rounded-xl p-5 border border-white/[0.05]">
        <h3 class="font-semibold text-white mb-2">ยกเลิกการสมัครสมาชิก</h3>
        <p class="text-slate-400 text-sm mb-4">หลังยกเลิก คุณยังสามารถใช้งานได้จนครบรอบบิล</p>
        <button onclick="if(confirm('ยืนยันการยกเลิก?')) { /* TODO: POST /billing/cancel */ }"
                class="text-sm text-error-500 hover:text-error-400 transition-colors border border-error-500/30 hover:border-error-500/50 px-4 py-2 rounded-xl">
            ยกเลิกการสมัครสมาชิก
        </button>
    </div>
    @endif
</div>
@endsection
