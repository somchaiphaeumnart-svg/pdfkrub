@extends('layouts.app')

@section('title', 'Admin Dashboard — ผู้ดูแลระบบ PDFkrub')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-extrabold text-white">แผงควบคุมระบบ (Admin)</h1>
                <span class="badge-premium text-xs">System Admin</span>
            </div>
            <p class="text-slate-400 text-sm mt-1">ภาพรวมสถิติการใช้งาน ผู้ใช้ทั้งหมด การสมัครสมาชิก และสถานะคิวงาน PDF</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}" class="btn-ghost px-4 py-2 rounded-xl text-xs flex items-center gap-2">
                <span>👤</span> มุมมองสมาชิกทั่วไป
            </a>
            <a href="{{ route('home') }}" class="btn-primary px-4 py-2 rounded-xl text-xs flex items-center gap-2">
                <span>🌐</span> หน้าแรก
            </a>
        </div>
    </div>

    {{-- Top Metric Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        {{-- Total Users --}}
        <div class="glass rounded-2xl p-5 border border-white/[0.06]">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">ผู้ใช้งานทั้งหมด</span>
                <span class="w-8 h-8 rounded-xl bg-brand-500/10 text-brand-400 flex items-center justify-center text-sm">👥</span>
            </div>
            <p class="text-3xl font-bold text-white mb-1">{{ number_format($totalUsers) }}</p>
            <p class="text-xs text-emerald-400 flex items-center gap-1">
                <span>↑ +{{ $newUsersToday }} วันนี้</span>
            </p>
        </div>

        {{-- Active Subscriptions --}}
        <div class="glass rounded-2xl p-5 border border-white/[0.06]">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">สมาชิกพรีเมียม</span>
                <span class="w-8 h-8 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-sm">⚡</span>
            </div>
            <p class="text-3xl font-bold text-white mb-1">{{ number_format($activeSubscriptions) }}</p>
            <p class="text-xs text-slate-400">Pro & Business Active</p>
        </div>

        {{-- Total Jobs --}}
        <div class="glass rounded-2xl p-5 border border-white/[0.06]">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">งานประมวลผล</span>
                <span class="w-8 h-8 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center text-sm">⚙️</span>
            </div>
            <p class="text-3xl font-bold text-white mb-1">{{ number_format($totalJobs) }}</p>
            <p class="text-xs text-emerald-400">อัตราสำเร็จ {{ $successRate }}%</p>
        </div>

        {{-- Total Files / Storage --}}
        <div class="glass rounded-2xl p-5 border border-white/[0.06]">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">พื้นที่จัดเก็บทั้งหมด</span>
                <span class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-sm">💾</span>
            </div>
            @php
                $gb = round($totalStorageBytes / (1024 * 1024 * 1024), 2);
                $mb = round($totalStorageBytes / (1024 * 1024), 1);
                $storageStr = $gb > 0.5 ? "{$gb} GB" : "{$mb} MB";
            @endphp
            <p class="text-3xl font-bold text-white mb-1">{{ $storageStr }}</p>
            <p class="text-xs text-slate-400">{{ number_format($totalFiles) }} ไฟล์ในระบบ</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-8 mb-8">
        {{-- Top Tools Ranking --}}
        <div class="glass rounded-2xl p-6 border border-white/[0.06]">
            <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                <span>🔥</span> เครื่องมือยอดนิยม (Top Tools)
            </h2>
            <div class="space-y-3">
                @forelse($topTools as $tool)
                @php $maxCount = $topTools->first()->count ?? 1; $pct = round(($tool->count / max(1, $maxCount)) * 100); @endphp
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-medium text-slate-200 capitalize">{{ str_replace('-', ' ', $tool->tool_name) }}</span>
                        <span class="text-slate-400">{{ number_format($tool->count) }} ครั้ง</span>
                    </div>
                    <div class="w-full bg-white/5 h-2 rounded-full overflow-hidden">
                        <div class="bg-gradient-to-r from-brand-600 to-brand-400 h-full rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-slate-500 text-xs text-center py-6">ยังไม่มีประวัติการใช้งานเครื่องมือ</p>
                @endforelse
            </div>
        </div>

        {{-- Subscription Plans Distribution --}}
        <div class="glass rounded-2xl p-6 border border-white/[0.06]">
            <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                <span>💳</span> การกระจายแผนสมาชิก (Subscriptions)
            </h2>
            <div class="space-y-4">
                @foreach($plansBreakdown as $plan)
                <div class="glass-light p-3.5 rounded-xl border border-white/[0.04] flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-white">{{ $plan->display_name_th ?? $plan->display_name }}</p>
                        <p class="text-xs text-slate-400">{{ $plan->price_monthly > 0 ? '฿' . number_format($plan->price_monthly) . '/เดือน' : 'แผนฟรี' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xl font-bold text-white">{{ number_format($plan->subscriptions_count) }}</span>
                        <p class="text-[10px] text-slate-500">Active</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Newest Users --}}
        <div class="glass rounded-2xl p-6 border border-white/[0.06]">
            <h2 class="text-base font-bold text-white mb-4 flex items-center gap-2">
                <span>✨</span> สมาชิกใหม่ล่าสุด
            </h2>
            <div class="space-y-3">
                @forelse($recentUsers as $u)
                <div class="flex items-center justify-between text-xs py-1.5 border-b border-white/[0.04] last:border-0">
                    <div class="min-w-0 flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-brand-500/20 text-brand-300 font-bold flex items-center justify-center flex-shrink-0 text-xs">
                            {{ substr($u->name, 0, 1) }}
                        </div>
                        <div class="truncate">
                            <p class="font-medium text-white truncate">{{ $u->name }}</p>
                            <p class="text-slate-500 truncate">{{ $u->email }}</p>
                        </div>
                    </div>
                    <span class="badge-free text-[10px]">{{ $u->getActivePlan()->name }}</span>
                </div>
                @empty
                <p class="text-slate-500 text-xs text-center py-6">ไม่มีข้อมูล</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent Jobs Stream --}}
    <div class="glass rounded-2xl border border-white/[0.06] overflow-hidden">
        <div class="px-6 py-4 border-b border-white/[0.06] flex items-center justify-between">
            <h2 class="font-bold text-white text-base flex items-center gap-2">
                <span>⚡</span> กิจกรรมการแปลงไฟล์ล่าสุด (Live Job Stream)
            </h2>
            <span class="text-xs text-slate-400">15 รายการล่าสุด</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-white/[0.02] border-b border-white/[0.06] text-slate-400 font-semibold">
                    <tr>
                        <th class="py-3 px-6">Job ID</th>
                        <th class="py-3 px-4">ผู้ใช้</th>
                        <th class="py-3 px-4">เครื่องมือ</th>
                        <th class="py-3 px-4">สถานะ</th>
                        <th class="py-3 px-4">เวลาประมวลผล</th>
                        <th class="py-3 px-6 text-right">เวลาที่ทำรายการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    @forelse($recentJobs as $job)
                    <tr class="hover:bg-white/[0.02] transition-colors">
                        <td class="py-3 px-6 font-mono text-slate-400">
                            {{ substr($job->id, 0, 8) }}...
                        </td>
                        <td class="py-3 px-4">
                            @if($job->user)
                            <span class="font-medium text-white">{{ $job->user->name }}</span>
                            @else
                            <span class="text-slate-500">Guest (Session)</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-0.5 rounded-md bg-white/5 text-slate-300 font-mono">{{ $job->tool_name }}</span>
                        </td>
                        <td class="py-3 px-4">
                            @if($job->status === 'done')
                            <span class="text-emerald-400 flex items-center gap-1 font-medium">✓ สำเร็จ</span>
                            @elseif($job->status === 'processing')
                            <span class="text-brand-400 flex items-center gap-1 font-medium">⏳ กำลังทำ</span>
                            @elseif($job->status === 'failed')
                            <span class="text-rose-400 flex items-center gap-1 font-medium">✕ ล้มเหลว</span>
                            @else
                            <span class="text-slate-400">{{ $job->status }}</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-mono text-slate-400">
                            {{ $job->processing_time_ms ? round($job->processing_time_ms / 1000, 2) . 's' : '-' }}
                        </td>
                        <td class="py-3 px-6 text-right text-slate-500 whitespace-nowrap">
                            {{ $job->created_at->diffForHumans() }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-8 text-slate-500">ยังไม่มีงานในระบบ</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
