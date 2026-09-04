@extends('layouts.app')

@section('title', 'จัดการผู้ใช้งานทั้งหมด — ผู้ดูแลระบบ PDFkrub')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10" x-data="adminUsers()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-extrabold text-gray-800">จัดการผู้ใช้งานทั้งหมด</h1>
                <span class="badge-premium text-xs">User Management</span>
            </div>
            <p class="text-gray-500 text-sm mt-1">ค้นหา, แก้ไขสิทธิ์, กำหนดแผน Pro & Business และลบบัญชีผู้ใช้งานในระบบ</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.index') }}" class="btn-ghost px-4 py-2 rounded-xl text-xs flex items-center gap-2 border border-gray-200">
                <span>📊</span> กลับแผงควบคุม
            </a>
            <a href="{{ route('dashboard') }}" class="btn-ghost px-4 py-2 rounded-xl text-xs flex items-center gap-2">
                <span>👤</span> มุมมองสมาชิกทั่วไป
            </a>
            <a href="{{ route('home') }}" class="btn-primary px-4 py-2 rounded-xl text-xs flex items-center gap-2">
                <span>🌐</span> หน้าแรก
            </a>
        </div>
    </div>

    {{-- Admin Nav Tabs --}}
    <div class="flex items-center gap-2 mb-8 border-b border-gray-200/80 pb-3">
        <a href="{{ route('admin.index') }}" class="px-4 py-2 text-sm font-medium rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors flex items-center gap-2">
            <span>📊</span> แดชบอร์ดภาพรวม
        </a>
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm font-semibold rounded-xl bg-brand-50 text-brand-700 border border-brand-200 shadow-xs flex items-center gap-2">
            <span>👥</span> จัดการผู้ใช้งานทั้งหมด
            <span class="px-2 py-0.5 text-xs bg-brand-200 text-brand-800 rounded-full font-bold">{{ number_format($totalCount) }}</span>
        </a>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
    <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">✕</button>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
            </svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700">✕</button>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-xs">
        <p class="font-bold mb-1">เกิดข้อผิดพลาดในการบันทึกข้อมูล:</p>
        <ul class="list-disc list-inside space-y-1 text-xs">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Overview Stat Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-4">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-gray-500 font-medium">สมาชิกทั้งหมด</span>
                <span class="text-xs">👥</span>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ number_format($totalCount) }} <span class="text-xs font-normal text-gray-400">คน</span></p>
        </div>

        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-4">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-purple-600 font-medium">Pro & Business Active</span>
                <span class="text-xs">⚡</span>
            </div>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($premiumCount) }} <span class="text-xs font-normal text-gray-400">คน</span></p>
        </div>

        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-4">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-gray-500 font-medium">สมาชิกแผนฟรี</span>
                <span class="text-xs">🌱</span>
            </div>
            <p class="text-2xl font-bold text-gray-700">{{ number_format($freeCount) }} <span class="text-xs font-normal text-gray-400">คน</span></p>
        </div>

        <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-4">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-brand-600 font-medium">ผู้ดูแลระบบ (Admin)</span>
                <span class="text-xs">🛡️</span>
            </div>
            <p class="text-2xl font-bold text-brand-600">{{ number_format($adminCount) }} <span class="text-xs font-normal text-gray-400">คน</span></p>
        </div>
    </div>

    {{-- Filter & Search Form --}}
    <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-4 mb-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col md:flex-row items-center gap-3">
            {{-- Search Box --}}
            <div class="relative flex-1 w-full">
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="ค้นหาชื่อ, อีเมล..."
                       class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </div>

            {{-- Plan Filter --}}
            <div class="w-full md:w-48">
                <select name="plan" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                    <option value="">แผนทั้งหมด</option>
                    @foreach($plans as $p)
                    <option value="{{ $p->name }}" {{ request('plan') === $p->name ? 'selected' : '' }}>
                        {{ $p->display_name_th }} ({{ $p->name }})
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Role Filter --}}
            <div class="w-full md:w-40">
                <select name="role" class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                    <option value="">บทบาททั้งหมด</option>
                    <option value="member" {{ request('role') === 'member' ? 'selected' : '' }}>สมาชิกทั่วไป</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>ผู้ดูแลระบบ (Admin)</option>
                </select>
            </div>

            {{-- Buttons --}}
            <div class="flex items-center gap-2 w-full md:w-auto">
                <button type="submit" class="btn-primary px-5 py-2.5 rounded-xl text-sm whitespace-nowrap shadow-sm flex items-center gap-1.5 w-full md:w-auto justify-center">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    <span>ค้นหา</span>
                </button>
                @if(request()->hasAny(['search', 'plan', 'role']))
                <a href="{{ route('admin.users.index') }}" class="btn-ghost px-3 py-2.5 rounded-xl text-xs text-gray-500 hover:text-gray-700 whitespace-nowrap">
                    ล้างตัวกรอง
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Users Table --}}
    <div class="bg-white border border-gray-100 shadow-sm rounded-2xl overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-gray-600">
                <thead class="bg-gray-50 border-b border-gray-100 uppercase text-[11px] text-gray-500 tracking-wider">
                    <tr>
                        <th class="py-3.5 px-6">ผู้ใช้งาน</th>
                        <th class="py-3.5 px-4">บทบาท (Role)</th>
                        <th class="py-3.5 px-4">แผนปัจจุบัน</th>
                        <th class="py-3.5 px-4">กำหนดแผนทันที (Quick Plan)</th>
                        <th class="py-3.5 px-4">การใช้งาน</th>
                        <th class="py-3.5 px-4">วันที่สมัคร</th>
                        <th class="py-3.5 px-6 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50/70 transition-colors">
                        {{-- User Info --}}
                        <td class="py-3.5 px-6">
                            <div class="flex items-center gap-3">
                                <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=e63946&color=fff&size=36' }}"
                                     alt="{{ $user->name }}"
                                     class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <p class="font-bold text-gray-800 text-sm truncate max-w-44">{{ $user->name }}</p>
                                        @if($user->provider === 'google')
                                        <span class="inline-flex items-center px-1.5 py-0.2 bg-blue-50 text-blue-600 rounded text-[10px] font-semibold" title="เข้าสู่ระบบด้วย Google">G</span>
                                        @endif
                                        @if($user->id === auth()->id())
                                        <span class="px-1.5 py-0.2 bg-brand-50 text-brand-600 rounded text-[10px] font-bold">คุณ</span>
                                        @endif
                                    </div>
                                    <p class="text-gray-400 text-xs truncate max-w-48">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>

                        {{-- Role --}}
                        <td class="py-3.5 px-4">
                            @if($user->is_admin)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                <span>🛡️</span> แอดมิน
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-600">
                                <span>👤</span> สมาชิก
                            </span>
                            @endif
                        </td>

                        {{-- Plan --}}
                        <td class="py-3.5 px-4">
                            @php
                                $plan = $user->getActivePlan();
                                $isPremium = $plan && !$plan->isFree();
                            @endphp
                            <div>
                                @if($isPremium)
                                <span class="badge-premium inline-flex items-center gap-1">
                                    <span>⚡</span> {{ $plan->display_name_th }}
                                </span>
                                @else
                                <span class="badge-free inline-flex items-center gap-1">
                                    {{ $plan->display_name_th }}
                                </span>
                                @endif

                                @if($isPremium)
                                <p class="text-[10px] text-emerald-600 font-semibold mt-1">● Active Pro</p>
                                @else
                                <p class="text-[10px] text-gray-400 mt-1">5 ครั้ง/วัน</p>
                                @endif
                            </div>
                        </td>

                        {{-- Quick Assign Plan --}}
                        <td class="py-3.5 px-4">
                            <form method="POST" action="{{ route('admin.users.assign-plan', $user) }}" class="inline-block">
                                @csrf
                                <select name="plan_id"
                                        onchange="if(confirm('ต้องการเปลี่ยนแผนของ {{ $user->name }} เป็น ' + this.options[this.selectedIndex].text + ' หรือไม่?')) { this.form.submit(); } else { this.value = '{{ $user->plan_id ?? 1 }}'; }"
                                        class="px-2.5 py-1.5 bg-white border border-gray-200 rounded-xl text-xs text-gray-700 font-medium focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all cursor-pointer shadow-xs">
                                    @foreach($plans as $p)
                                    <option value="{{ $p->id }}" {{ ($user->plan_id == $p->id) || (!$user->plan_id && $p->name === 'free') ? 'selected' : '' }}>
                                        {{ $p->display_name_th }} ({{ $p->price_monthly > 0 ? '฿'.$p->price_monthly.'/ด' : 'ฟรี' }})
                                    </option>
                                    @endforeach
                                </select>
                            </form>
                        </td>

                        {{-- Usage Stats --}}
                        <td class="py-3.5 px-4 font-mono text-gray-500">
                            <div>
                                <span class="text-gray-800 font-semibold">{{ number_format($user->pdf_jobs_count) }}</span> งาน
                            </div>
                            <div class="text-[10px] text-gray-400">
                                {{ number_format($user->uploaded_files_count) }} ไฟล์
                            </div>
                        </td>

                        {{-- Joined Date --}}
                        <td class="py-3.5 px-4 text-gray-400 whitespace-nowrap">
                            <p class="text-gray-700">{{ $user->created_at->format('d/m/Y') }}</p>
                            <p class="text-[10px]">{{ $user->created_at->diffForHumans() }}</p>
                        </td>

                        {{-- Actions --}}
                        <td class="py-3.5 px-6 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Edit Button --}}
                                <button type="button"
                                        @click="openEditModal({
                                            id: '{{ $user->id }}',
                                            name: '{{ addslashes($user->name) }}',
                                            email: '{{ addslashes($user->email) }}',
                                            is_admin: {{ $user->is_admin ? 'true' : 'false' }},
                                            plan_id: '{{ $user->plan_id ?? 1 }}',
                                            update_url: '{{ route('admin.users.update', $user) }}'
                                        })"
                                        class="px-2.5 py-1.5 bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-xs font-semibold transition-all flex items-center gap-1 shadow-xs">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10"/>
                                    </svg>
                                    แก้ไข
                                </button>

                                {{-- Delete Button --}}
                                @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.delete', $user) }}" onsubmit="return confirm('ยืนยันการลบบัญชีผู้ใช้ &quot;{{ $user->name }}&quot; ออกจากระบบหรือไม่? (ข้อมูลงานและไฟล์จะถูกลบด้วย)');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 rounded-lg text-xs font-semibold transition-all shadow-xs" title="ลบผู้ใช้">
                                        ลบ
                                    </button>
                                </form>
                                @else
                                <span class="px-2.5 py-1.5 text-gray-300 text-xs font-semibold" title="ไม่สามารถลบตัวเองได้">ลบ</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400">
                            <p class="text-3xl mb-2">🔍</p>
                            <p class="text-sm font-semibold text-gray-700">ไม่พบข้อมูลผู้ใช้งาน</p>
                            <p class="text-xs text-gray-400 mt-1">ลองเปลี่ยนคำค้นหาหรือตัวกรองใหม่อีกครั้ง</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
            {{ $users->links() }}
        </div>
        @endif
    </div>

    {{-- Edit User Modal (Alpine.js) --}}
    <div x-show="isEditModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xs"
         style="display: none;">
        
        <div @click.away="closeEditModal()"
             x-show="isEditModalOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
             class="bg-white rounded-2xl shadow-xl border border-gray-100 w-full max-w-lg overflow-hidden">
            
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <span>✏️</span> แก้ไขข้อมูลผู้ใช้งาน
                </h3>
                <button type="button" @click="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition-colors">✕</button>
            </div>

            {{-- Modal Form --}}
            <form :action="editForm.update_url" method="POST" class="p-6 space-y-4">
                @csrf
                @method('PUT')

                {{-- Name --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">ชื่อ-นามสกุล</label>
                    <input type="text"
                           name="name"
                           x-model="editForm.name"
                           required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">อีเมล</label>
                    <input type="email"
                           name="email"
                           x-model="editForm.email"
                           required
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                </div>

                {{-- Plan Assignment --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">แผนสมาชิก (Plan & Subscription)</label>
                    <select name="plan_id"
                            x-model="editForm.plan_id"
                            required
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                        @foreach($plans as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->display_name_th }} ({{ $p->name }}) — {{ $p->price_monthly > 0 ? '฿'.$p->price_monthly.'/ด' : 'แผนฟรี 5 ครั้ง/วัน' }}
                        </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mt-1">เลือก Pro หรือ Business เพื่อเปิดใช้งานสถานะ Active ให้ผู้ใช้ทันที</p>
                </div>

                {{-- Admin Role --}}
                <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-gray-800">สิทธิ์ผู้ดูแลระบบ (Admin)</p>
                        <p class="text-[11px] text-gray-500">สามารถเข้าถึงแผงควบคุมและจัดการผู้ใช้ทั้งหมดได้</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_admin" value="1" x-model="editForm.is_admin" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                {{-- New Password (Optional) --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                        รหัสผ่านใหม่ <span class="text-gray-400 font-normal">(เว้นว่างไว้หากไม่ต้องการเปลี่ยน)</span>
                    </label>
                    <input type="password"
                           name="password"
                           placeholder="••••••••"
                           class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all">
                </div>

                {{-- Submit Buttons --}}
                <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                    <button type="button" @click="closeEditModal()" class="btn-ghost px-5 py-2.5 rounded-xl text-xs">
                        ยกเลิก
                    </button>
                    <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-xs font-semibold shadow-sm">
                        บันทึกการเปลี่ยนแปลง
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function adminUsers() {
    return {
        isEditModalOpen: false,
        editForm: {
            id: '',
            name: '',
            email: '',
            is_admin: false,
            plan_id: '1',
            update_url: ''
        },
        openEditModal(data) {
            this.editForm = { ...data };
            this.isEditModalOpen = true;
        },
        closeEditModal() {
            this.isEditModalOpen = false;
        }
    }
}
</script>
@endsection
