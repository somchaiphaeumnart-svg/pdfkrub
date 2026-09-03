@extends('layouts.app')

@section('title', 'แดชบอร์ด')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white">สวัสดี, {{ auth()->user()->name }} 👋</h1>
            <p class="text-slate-400 text-sm mt-1">
                แผน: <span class="text-brand-400 font-medium">{{ $stats['plan']->display_name_th ?? $stats['plan']->display_name }}</span>
                @if(!$stats['plan']->isFree())
                <span class="text-slate-600 mx-1">·</span>
                <a href="{{ route('billing.index') }}" class="text-slate-400 hover:text-white transition-colors text-xs">จัดการแผน</a>
                @else
                <span class="text-slate-600 mx-1">·</span>
                <a href="{{ route('pricing') }}" class="text-accent-400 hover:text-accent-300 transition-colors text-xs font-medium">อัปเกรดเป็น Pro →</a>
                @endif
            </p>
        </div>
        <a href="{{ route('tools') }}" class="btn-primary text-sm px-5 py-2.5 rounded-xl flex items-center gap-2 flex-shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            แปลง PDF ใหม่
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
        @php
        $statCards = [
            ['label' => 'งานทั้งหมด', 'value' => number_format($stats['total_jobs']), 'icon' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z', 'color' => 'text-brand-400', 'bg' => 'bg-brand-500/10'],
            ['label' => 'สำเร็จ', 'value' => number_format($stats['completed_jobs']), 'icon' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'color' => 'text-success-500', 'bg' => 'bg-success-500/10'],
            ['label' => 'พื้นที่ใช้ไป', 'value' => $stats['storage_used_formatted'], 'icon' => 'M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 2.25v2.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V8.625m16.5 2.25v2.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125v-2.625', 'color' => 'text-accent-400', 'bg' => 'bg-accent-500/10'],
            ['label' => 'โควต้าวันนี้', 'value' => $stats['daily_remaining'], 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z', 'color' => 'text-purple-400', 'bg' => 'bg-purple-500/10'],
        ];
        @endphp

        @foreach($statCards as $card)
        <div class="glass rounded-xl p-5 border border-white/[0.06]">
            <div class="{{ $card['bg'] }} {{ $card['color'] }} w-10 h-10 rounded-xl flex items-center justify-center mb-3">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/>
                </svg>
            </div>
            <p class="text-2xl font-bold text-white">{{ $card['value'] }}</p>
            <p class="text-xs text-slate-500 mt-1">{{ $card['label'] }}</p>
        </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        {{-- Recent Jobs --}}
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-lg font-bold text-white">งานล่าสุด</h2>
                <a href="{{ route('dashboard.files') }}" class="text-sm text-brand-400 hover:text-brand-300 transition-colors">ดูทั้งหมด →</a>
            </div>

            @if($recentJobs->isEmpty())
            <div class="glass rounded-2xl border border-white/[0.06] p-12 text-center">
                <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                </div>
                <p class="text-slate-400 font-medium">ยังไม่มีประวัติการแปลงไฟล์</p>
                <p class="text-slate-600 text-sm mt-1">เริ่มต้นแปลง PDF ได้เลย</p>
                <a href="{{ route('tools') }}" class="btn-primary text-sm px-6 py-2.5 rounded-xl inline-block mt-4">เลือกเครื่องมือ</a>
            </div>
            @else
            <div class="glass rounded-2xl border border-white/[0.06] overflow-hidden">
                <div class="divide-y divide-white/[0.05]">
                    @foreach($recentJobs as $job)
                    <div class="flex items-center gap-4 px-5 py-4 hover:bg-white/[0.02] transition-colors">
                        {{-- Status icon --}}
                        <div class="flex-shrink-0">
                            @if($job->isComplete())
                            <div class="w-9 h-9 bg-success-500/10 rounded-xl flex items-center justify-center">
                                <svg class="w-4 h-4 text-success-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            </div>
                            @elseif($job->isFailed())
                            <div class="w-9 h-9 bg-error-500/10 rounded-xl flex items-center justify-center">
                                <svg class="w-4 h-4 text-error-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </div>
                            @else
                            <div class="w-9 h-9 bg-brand-500/10 rounded-xl flex items-center justify-center">
                                <svg class="w-4 h-4 text-brand-400 processing-pulse" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                            </div>
                            @endif
                        </div>

                        {{-- Details --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ $job->tool_name }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $job->created_at->diffForHumans() }}</p>
                        </div>

                        {{-- Download --}}
                        @if($job->isComplete() && $job->outputFile)
                        <a href="{{ $job->outputFile->getTemporaryUrl() }}"
                           class="flex-shrink-0 text-xs btn-ghost px-3 py-1.5 rounded-lg flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                            ดาวน์โหลด
                        </a>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Quick Tools --}}
        <div>
            <h2 class="text-lg font-bold text-white mb-5">เครื่องมือด่วน</h2>
            <div class="space-y-3">
                @foreach([['PDF to Word', 'pdf-to-word', 'from-blue-600 to-blue-500', '📄'], ['รวม PDF', 'merge-pdf', 'from-purple-600 to-purple-500', '🔗'], ['บีบอัด PDF', 'compress-pdf', 'from-green-600 to-green-500', '🗜️'], ['OCR ไทย', 'ocr-pdf', 'from-orange-600 to-orange-500', '🔍'], ['เซ็นเอกสาร', 'sign-pdf', 'from-indigo-600 to-indigo-500', '✍️']] as [$name, $slug, $color, $icon])
                <a href="{{ route('tools.'.$slug) }}" class="flex items-center gap-3 glass-light rounded-xl p-3.5 border border-white/[0.05] hover:border-brand-500/30 transition-all group">
                    <div class="w-9 h-9 rounded-lg bg-gradient-to-br {{ $color }} flex items-center justify-center text-base flex-shrink-0 group-hover:scale-110 transition-transform">{{ $icon }}</div>
                    <span class="text-sm font-medium text-slate-300 group-hover:text-white transition-colors">{{ $name }}</span>
                    <svg class="w-4 h-4 text-slate-600 ml-auto group-hover:text-brand-400 transition-colors" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                </a>
                @endforeach
            </div>

            {{-- Storage usage bar --}}
            <div class="mt-6 glass rounded-xl p-4 border border-white/[0.06]">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs text-slate-400">พื้นที่จัดเก็บ</span>
                    <span class="text-xs text-slate-500">{{ $stats['storage_used_formatted'] }}</span>
                </div>
                @php $pct = min(100, ($stats['storage_used'] / max(1, ($stats['plan']->max_file_size_mb * 1024 * 1024) * 20)) * 100); @endphp
                <div class="progress-bar h-1.5">
                    <div class="progress-fill h-full" style="width: {{ $pct }}%"></div>
                </div>
                @if($stats['plan']->isFree())
                <p class="text-xs text-slate-600 mt-2">อัปเกรดเพื่อรับพื้นที่จัดเก็บเพิ่ม</p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function formatBytes(bytes) {
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes, unit = 0;
    while (size >= 1024 && unit < units.length - 1) { size /= 1024; unit++; }
    return `${size.toFixed(1)} ${units[unit]}`;
}
</script>
@endpush
@endsection
