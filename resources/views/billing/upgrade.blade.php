@extends('layouts.app')

@section('title', 'อัปเกรด — ' . ($plan->display_name_th ?? $plan->display_name))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16" x-data="billingPage({{ $plan->id }})">
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">อัปเกรดเป็น {{ $plan->display_name_th ?? $plan->display_name }}</h1>
        <p class="text-gray-500">เลือกวิธีชำระเงินที่สะดวกและปลอดภัย</p>
    </div>

    <div class="grid md:grid-cols-2 gap-8 items-start">
        {{-- Plan Summary --}}
        <div class="glass rounded-2xl p-6 border {{ $plan->name === 'pro' ? 'border-brand-500/30' : 'border-gray-100' }}">
            <h2 class="font-bold text-gray-800 text-lg mb-5">สรุปแผน</h2>

            <div class="flex items-baseline gap-2 mb-6">
                <span class="text-4xl font-bold text-gray-800" x-text="period === 'monthly' ? '฿{{ number_format($plan->price_monthly) }}' : '฿{{ number_format(round($plan->price_yearly / 12)) }}'"></span>
                <span class="text-gray-500">/เดือน</span>
            </div>

            @if($plan->price_yearly > 0)
            <div class="glass-light rounded-xl px-4 py-3 mb-6 border border-success-500/20" x-show="period === 'yearly'">
                <p class="text-sm text-success-400 font-medium">💡 จ่ายรายปี ฿{{ number_format($plan->price_yearly) }} — ประหยัด ฿{{ number_format($plan->price_monthly * 12 - $plan->price_yearly) }}</p>
            </div>
            @endif

            <ul class="space-y-3">
                @foreach([
                    'ไฟล์สูงสุด ' . $plan->max_file_size_mb . ' MB',
                    $plan->daily_conversions === -1 ? 'แปลงไฟล์ไม่จำกัด' : 'แปลง ' . $plan->daily_conversions . ' ครั้ง/วัน',
                    $plan->has_ocr ? 'OCR ภาษาไทย (Google Cloud Vision)' : null,
                    $plan->has_esign ? 'เซ็นเอกสารดิจิทัล' : null,
                    !$plan->has_watermark ? 'ไม่มี Watermark' : null,
                    $plan->has_api_access ? 'REST API Access' : null,
                ] as $feature)
                @if($feature)
                <li class="flex items-center gap-2 text-sm text-gray-600">
                    <svg class="w-4 h-4 text-success-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    {{ $feature }}
                </li>
                @endif
                @endforeach
            </ul>

            <div class="mt-6 pt-5 border-t border-gray-100 text-xs text-gray-400 space-y-1">
                <p>✓ ยกเลิกได้ทุกเมื่อ ไม่มีสัญญาผูกมัด</p>
                <p>✓ ทดลองใช้ฟรี 7 วัน (สำหรับผู้ใช้ใหม่)</p>
                <p>✓ รองรับมาตรฐาน PDPA ปกป้องข้อมูลส่วนตัว</p>
            </div>
        </div>

        {{-- Payment Methods & Form --}}
        <div class="space-y-4">
            {{-- Billing period --}}
            <div class="glass rounded-2xl p-5 border border-gray-100" x-show="!promptPayQrUrl">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">รอบการชำระเงิน</h3>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" @click="period = 'monthly'"
                            class="py-3 rounded-xl border text-sm transition-all text-center"
                            :class="period === 'monthly' ? 'border-brand-500 bg-brand-500/10 text-gray-800 font-medium' : 'border-gray-200 text-gray-500'">
                        <div class="font-semibold">รายเดือน</div>
                        <div class="text-xs opacity-75">฿{{ number_format($plan->price_monthly) }}/เดือน</div>
                    </button>
                    @if($plan->price_yearly > 0)
                    <button type="button" @click="period = 'yearly'"
                            class="py-3 rounded-xl border text-sm transition-all text-center relative"
                            :class="period === 'yearly' ? 'border-success-500 bg-success-500/10 text-gray-800 font-medium' : 'border-gray-200 text-gray-500'">
                        <div class="absolute -top-2.5 right-2 bg-success-500 text-gray-800 text-[10px] font-bold px-2 py-0.5 rounded-full">ประหยัด 20%</div>
                        <div class="font-semibold">รายปี</div>
                        <div class="text-xs opacity-75">฿{{ number_format($plan->price_yearly) }}/ปี</div>
                    </button>
                    @endif
                </div>
            </div>

            {{-- Payment method selector --}}
            <div class="glass rounded-2xl p-5 border border-gray-100" x-show="!promptPayQrUrl">
                <h3 class="text-sm font-semibold text-gray-800 mb-3">วิธีชำระเงิน</h3>
                <div class="space-y-2">
                    <button type="button" @click="method = 'promptpay'"
                            class="w-full flex items-center gap-3 p-3.5 rounded-xl border text-sm transition-all"
                            :class="method === 'promptpay' ? 'border-brand-500 bg-brand-500/10' : 'border-gray-200 hover:border-gray-200'">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 text-gray-800 text-xs font-bold">P</div>
                        <div class="text-left flex-1">
                            <div class="font-medium text-gray-800 text-sm">PromptPay / QR Code</div>
                            <div class="text-xs text-gray-500">สแกน QR ผ่านทุกแอปธนาคารไทย</div>
                        </div>
                        <div class="w-4 h-4 rounded-full border-2 flex-shrink-0"
                             :class="method === 'promptpay' ? 'border-brand-400 bg-brand-500' : 'border-gray-200'"></div>
                    </button>

                    <button type="button" @click="method = 'card'"
                            class="w-full flex items-center gap-3 p-3.5 rounded-xl border text-sm transition-all"
                            :class="method === 'card' ? 'border-brand-500 bg-brand-500/10' : 'border-gray-200 hover:border-gray-200'">
                        <div class="w-8 h-8 flex-shrink-0">
                            <svg viewBox="0 0 38 24" fill="none" class="w-full h-full">
                                <rect width="38" height="24" rx="4" fill="#1a1f36"/>
                                <rect x="2" y="8" width="34" height="4" fill="#383e5a"/>
                                <rect x="4" y="15" width="8" height="3" rx="1" fill="#6c7280"/>
                                <rect x="14" y="15" width="5" height="3" rx="1" fill="#6c7280"/>
                            </svg>
                        </div>
                        <div class="text-left flex-1">
                            <div class="font-medium text-gray-800 text-sm">บัตรเครดิต/เดบิต</div>
                            <div class="text-xs text-gray-500">Visa, Mastercard, JCB</div>
                        </div>
                        <div class="w-4 h-4 rounded-full border-2 flex-shrink-0"
                             :class="method === 'card' ? 'border-brand-400 bg-brand-500' : 'border-gray-200'"></div>
                    </button>
                </div>
            </div>

            {{-- Card Form --}}
            <div x-show="method === 'card' && !promptPayQrUrl" class="glass rounded-2xl p-5 border border-gray-100 space-y-4">
                <h3 class="text-sm font-semibold text-gray-800">ข้อมูลบัตร</h3>

                <div>
                    <label class="text-xs text-gray-500 block mb-1.5">หมายเลขบัตร</label>
                    <input type="text" x-model="card.number" placeholder="1234 5678 9012 3456" maxlength="19"
                           @input="formatCardNumber($event)"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-slate-600 rounded-xl px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 block mb-1.5">วันหมดอายุ</label>
                        <input type="text" x-model="card.expiry" placeholder="MM/YY" maxlength="5"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-slate-600 rounded-xl px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all">
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1.5">CVV</label>
                        <input type="password" x-model="card.cvv" placeholder="123" maxlength="4"
                               class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-slate-600 rounded-xl px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all">
                    </div>
                </div>

                <div>
                    <label class="text-xs text-gray-500 block mb-1.5">ชื่อบนบัตร</label>
                    <input type="text" x-model="card.name" placeholder="SOMCHAI JAIDEE"
                           class="w-full bg-gray-50 border border-gray-200 text-gray-800 placeholder-slate-600 rounded-xl px-4 py-3 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-brand-500/50 focus:border-brand-500 transition-all">
                </div>
            </div>

            {{-- PromptPay QR Code Display --}}
            <div x-show="promptPayQrUrl" class="glass rounded-2xl p-6 border border-brand-500/40 text-center space-y-4" style="display:none">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                        <span class="text-xs font-semibold text-emerald-400">รอรับชำระเงิน</span>
                    </div>
                    <span class="text-xs text-gray-500" x-text="`หมดอายุใน: ${formatTimer(timeLeft)}`"></span>
                </div>

                <div class="bg-white p-4 rounded-2xl inline-block mx-auto shadow-2xl">
                    <img :src="promptPayQrUrl" alt="PromptPay QR Code" class="w-56 h-56 mx-auto object-contain">
                    <p class="text-[11px] font-bold text-slate-800 mt-2 tracking-wider">PROMPTPAY THAILAND</p>
                </div>

                <div class="space-y-1">
                    <p class="text-xl font-bold text-gray-800" x-text="`฿${chargeAmount}`"></p>
                    <p class="text-xs text-gray-500">เปิดแอปธนาคาร แล้วเลือก สแกน QR Code</p>
                </div>

                <div class="pt-2 flex items-center justify-center gap-2 text-xs text-brand-400">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    ระบบจะอัปเกรดอัตโนมัติทันทีที่ชำระเสร็จ
                </div>

                <button type="button" @click="cancelPromptPay()" class="text-xs text-gray-400 hover:text-gray-800 transition-colors underline pt-2">
                    เปลี่ยนวิธีชำระเงิน
                </button>
            </div>

            {{-- Error Message --}}
            <div x-show="errorMessage" class="bg-error-500/10 border border-error-500/30 text-error-400 px-4 py-3 rounded-xl text-sm" x-text="errorMessage" style="display:none"></div>

            {{-- Submit Button --}}
            <div x-show="!promptPayQrUrl">
                <button type="button" @click="submitPayment()"
                        :disabled="isLoading"
                        class="w-full btn-primary py-4 rounded-2xl text-base font-semibold flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="!isLoading">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
                            </svg>
                            <span x-text="method === 'promptpay' ? 'สร้าง QR Code PromptPay' : 'ชำระผ่านบัตร'"></span>
                            <span>฿<span x-text="period === 'monthly' ? '{{ number_format($plan->price_monthly) }}' : '{{ number_format($plan->price_yearly) }}'"></span></span>
                        </span>
                    </template>
                    <template x-if="isLoading">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            กำลังสร้างรายการชำระ...
                        </span>
                    </template>
                </button>
            </div>

            <p class="text-center text-xs text-gray-300">
                🔒 ชำระเงินผ่านระบบ Omise Payment Gateway · เข้ารหัส TLS 1.3 · มาตรฐาน PCI DSS Level 1
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function billingPage(planId) {
    return {
        planId: planId,
        period: 'monthly',
        method: 'promptpay',
        isLoading: false,
        errorMessage: null,

        // PromptPay state
        promptPayQrUrl: null,
        chargeId: null,
        chargeAmount: 0,
        timeLeft: 900,
        timerInterval: null,
        pollInterval: null,

        // Card state
        card: {
            number: '',
            expiry: '',
            cvv: '',
            name: '',
        },

        formatCardNumber(event) {
            let val = event.target.value.replace(/\D/g, '').substring(0, 16);
            event.target.value = val.replace(/(.{4})/g, '$1 ').trim();
            this.card.number = event.target.value;
        },

        formatTimer(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return `${m}:${s < 10 ? '0' : ''}${s}`;
        },

        async submitPayment() {
            this.isLoading = true;
            this.errorMessage = null;

            try {
                let cardToken = null;
                if (this.method === 'card') {
                    // For test mode, generate a mock token
                    cardToken = 'tokn_test_' + Math.random().toString(36).substring(2);
                }

                const response = await fetch('{{ route("billing.charge") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        plan_id: this.planId,
                        billing_interval: this.period,
                        payment_method: this.method,
                        card_token: cardToken,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'เกิดข้อผิดพลาดในการประมวลผล');
                }

                if (this.method === 'promptpay' && data.status === 'pending') {
                    this.promptPayQrUrl = data.qr_url;
                    this.chargeId = data.charge_id;
                    this.chargeAmount = data.amount;
                    this.timeLeft = data.expires_in_seconds || 900;
                    this.startPolling();
                    this.startTimer();
                } else if (data.status === 'successful') {
                    window.location.href = data.redirect_url || '{{ route("dashboard") }}';
                }
            } catch (err) {
                this.errorMessage = err.message;
            } finally {
                this.isLoading = false;
            }
        },

        startPolling() {
            if (this.pollInterval) clearInterval(this.pollInterval);
            this.pollInterval = setInterval(async () => {
                if (!this.chargeId) return;
                try {
                    const res = await fetch(`/billing/charge/${this.chargeId}/status`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await res.json();
                    if (data.status === 'successful') {
                        clearInterval(this.pollInterval);
                        clearInterval(this.timerInterval);
                        window.location.href = data.redirect_url || '{{ route("dashboard") }}';
                    }
                } catch (e) {
                    console.error('Polling status error', e);
                }
            }, 2500);
        },

        startTimer() {
            if (this.timerInterval) clearInterval(this.timerInterval);
            this.timerInterval = setInterval(() => {
                if (this.timeLeft > 0) {
                    this.timeLeft--;
                } else {
                    this.cancelPromptPay();
                    this.errorMessage = 'QR Code หมดอายุแล้ว กรุณาสร้างรายการใหม่';
                }
            }, 1000);
        },

        cancelPromptPay() {
            this.promptPayQrUrl = null;
            this.chargeId = null;
            if (this.pollInterval) clearInterval(this.pollInterval);
            if (this.timerInterval) clearInterval(this.timerInterval);
        },
    };
}
</script>
@endpush
@endsection
