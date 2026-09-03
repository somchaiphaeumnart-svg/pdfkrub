@extends('layouts.app')

@section('title', 'จัดการไฟล์ของฉัน — PDFkrub')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="{ searchQuery: '', selectedType: 'all' }">
    {{-- Header & Breadcrumb --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <nav class="flex items-center gap-2 text-sm text-slate-500 mb-2">
                <a href="{{ route('dashboard') }}" class="hover:text-brand-400 transition-colors">แดชบอร์ด</a>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                <span class="text-slate-300">ไฟล์ของฉัน</span>
            </nav>
            <h1 class="text-3xl font-bold text-white">คลังไฟล์ของฉัน</h1>
            <p class="text-slate-400 text-sm mt-1">ไฟล์ที่ผ่านการประมวลผลและอัปโหลดทั้งหมด พร้อมระยะเวลาเก็บรักษาตามแผนของคุณ</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('tools') }}" class="btn-primary px-5 py-2.5 rounded-xl text-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                แปลงไฟล์ใหม่
            </a>
        </div>
    </div>

    {{-- Filters & Search bar --}}
    <div class="glass rounded-2xl p-4 border border-white/[0.06] mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        {{-- Search input --}}
        <div class="relative w-full md:w-80">
            <svg class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
            <input type="text" x-model="searchQuery" placeholder="ค้นหาตามชื่อไฟล์..."
                   class="w-full bg-white/5 border border-white/10 text-white placeholder-slate-500 rounded-xl pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/50">
        </div>

        {{-- Type Filter Pills --}}
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
            <button @click="selectedType = 'all'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all"
                    :class="selectedType === 'all' ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/20' : 'text-slate-400 hover:text-white bg-white/5'">
                ทั้งหมด ({{ $files->total() }})
            </button>
            <button @click="selectedType = 'pdf'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all"
                    :class="selectedType === 'pdf' ? 'bg-red-500 text-white' : 'text-slate-400 hover:text-white bg-white/5'">
                PDF
            </button>
            <button @click="selectedType = 'docx'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all"
                    :class="selectedType === 'docx' ? 'bg-blue-500 text-white' : 'text-slate-400 hover:text-white bg-white/5'">
                Word
            </button>
            <button @click="selectedType = 'image'"
                    class="px-3.5 py-1.5 rounded-xl text-xs font-medium transition-all"
                    :class="selectedType === 'image' ? 'bg-emerald-500 text-white' : 'text-slate-400 hover:text-white bg-white/5'">
                รูปภาพ
            </button>
        </div>
    </div>

    {{-- File Table / List --}}
    <div class="glass rounded-2xl border border-white/[0.06] overflow-hidden">
        @if($files->isEmpty())
        <div class="py-20 text-center">
            <div class="w-16 h-16 bg-white/5 rounded-2xl flex items-center justify-center mx-auto mb-4 text-slate-500">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
            </div>
            <h3 class="text-white font-semibold text-lg mb-1">ยังไม่มีไฟล์ในคลังของคุณ</h3>
            <p class="text-slate-400 text-sm max-w-sm mx-auto mb-6">ไฟล์ที่คุณทำการแปลงหรืออัปโหลดจะปรากฏที่นี่โดยอัตโนมัติ</p>
            <a href="{{ route('tools') }}" class="btn-primary px-6 py-2.5 rounded-xl text-sm inline-flex items-center gap-2">
                เริ่มต้นแปลงไฟล์แรก
            </a>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-white/[0.02] border-b border-white/[0.06] text-xs font-semibold text-slate-400">
                    <tr>
                        <th class="py-4 px-6">ชื่อไฟล์</th>
                        <th class="py-4 px-4">ขนาด</th>
                        <th class="py-4 px-4">วันที่สร้าง</th>
                        <th class="py-4 px-4">หมดอายุใน</th>
                        <th class="py-4 px-6 text-right">การจัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/[0.04]">
                    @foreach($files as $file)
                    @php
                        $ext = pathinfo($file->original_name, PATHINFO_EXTENSION);
                        $typeGroup = match(strtolower($ext)) {
                            'pdf' => 'pdf',
                            'doc', 'docx' => 'docx',
                            'jpg', 'jpeg', 'png', 'webp', 'gif' => 'image',
                            default => 'other'
                        };
                    @endphp
                    <tr class="hover:bg-white/[0.02] transition-colors"
                        x-show="(selectedType === 'all' || selectedType === '{{ $typeGroup }}') && ('{{ strtolower($file->original_name) }}'.includes(searchQuery.toLowerCase()))">
                        <td class="py-4 px-6">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                                    {{ match($typeGroup) {
                                        'pdf' => 'bg-red-500/10 text-red-400 border border-red-500/20',
                                        'docx' => 'bg-blue-500/10 text-blue-400 border border-blue-500/20',
                                        'image' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                                        default => 'bg-slate-500/10 text-slate-400 border border-slate-500/20'
                                    } }}">
                                    <span class="text-[10px] font-bold uppercase">{{ substr($ext ?: 'FILE', 0, 4) }}</span>
                                </div>
                                <div class="min-w-0 max-w-xs md:max-w-md">
                                    <p class="font-medium text-white truncate">{{ $file->original_name }}</p>
                                    <p class="text-xs text-slate-500">{{ $file->mime_type }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-4 px-4 whitespace-nowrap text-slate-400">
                            {{ $file->getFileSizeForHumans() }}
                        </td>
                        <td class="py-4 px-4 whitespace-nowrap text-slate-400">
                            {{ $file->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="py-4 px-4 whitespace-nowrap">
                            @if($file->expires_at)
                                @if($file->isExpired())
                                <span class="badge-danger text-xs">หมดอายุแล้ว</span>
                                @else
                                <span class="text-xs text-slate-400 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    {{ $file->expires_at->diffForHumans() }}
                                </span>
                                @endif
                            @else
                            <span class="text-xs text-slate-500">ไม่มีกำหนด</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ $file->getTemporaryUrl() }}" download="{{ $file->original_name }}"
                                   class="p-2 rounded-lg bg-white/5 hover:bg-brand-500/20 text-slate-400 hover:text-brand-300 transition-all"
                                   title="ดาวน์โหลดไฟล์">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                </a>

                                <form action="{{ route('dashboard.files.delete', $file) }}" method="POST"
                                      onsubmit="return confirm('คุณต้องการลบไฟล์นี้ใช่หรือไม่?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 rounded-lg bg-white/5 hover:bg-error-500/20 text-slate-400 hover:text-error-400 transition-all"
                                            title="ลบไฟล์">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($files->hasPages())
        <div class="px-6 py-4 border-t border-white/[0.06]">
            {{ $files->links() }}
        </div>
        @endif
        @endif
    </div>
</div>
@endsection
