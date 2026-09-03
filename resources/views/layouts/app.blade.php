<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'PDFkrub') — แพลตฟอร์มจัดการ PDF สำหรับครูและโรงเรียน</title>
    <meta name="description" content="@yield('description', 'PDFkrub — แพลตฟอร์มจัดการเอกสาร PDF ภาษาไทย สำหรับครูและโรงเรียน รองรับ PDPA ประมวลผลในประเทศไทย')">

    <!-- Open Graph -->
    <meta property="og:title" content="@yield('title', 'PDFkrub') — แพลตฟอร์มจัดการ PDF สำหรับครูไทย">
    <meta property="og:description" content="PDFkrub จัดการ PDF เพื่อครู รวม PA, OCR, บีบอัด, ลงนาม, ประทับ 'สำเนาถูกต้อง' รองรับ PDPA">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">

    <!-- Google Fonts: Noto Sans Thai + Instrument Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="bg-mesh min-h-screen antialiased" x-data>

    <!-- Navigation -->
    <header class="fixed top-0 left-0 right-0 z-50 glass border-b border-white/[0.06]" x-data="mobileNav()">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                    <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-accent-500 rounded-lg flex items-center justify-center shadow-lg transition-transform group-hover:scale-110">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold text-white">PDF<span class="text-gradient">krub</span></span>
                </a>

                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('tools') }}" class="px-3 py-2 text-sm text-slate-300 hover:text-white rounded-lg hover:bg-white/5 transition-all">เครื่องมือ PDF</a>
                    <a href="{{ route('templates') }}" class="px-3 py-2 text-sm text-slate-300 hover:text-white rounded-lg hover:bg-white/5 transition-all">แบบฟอร์มครู</a>
                    <a href="{{ route('pricing') }}" class="px-3 py-2 text-sm text-slate-300 hover:text-white rounded-lg hover:bg-white/5 transition-all">ราคา</a>
                </div>

                <!-- Auth Buttons -->
                <div class="hidden md:flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 text-sm btn-ghost rounded-xl">
                            <img src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=1d4ed8&color=fff&size=32' }}"
                                 alt="{{ auth()->user()->name }}"
                                 class="w-6 h-6 rounded-full">
                            {{ auth()->user()->name }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm text-slate-300 hover:text-white transition-colors">เข้าสู่ระบบ</a>
                        <a href="{{ route('register') }}" class="btn-primary text-sm rounded-xl px-5 py-2">สมัครฟรี</a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <button @click="toggle()" class="md:hidden p-2 text-slate-400 hover:text-white rounded-lg hover:bg-white/5 transition-all" aria-label="เมนู">
                    <svg x-show="!isOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                    <svg x-show="isOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Mobile Menu -->
            <div x-show="isOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="md:hidden py-4 border-t border-white/[0.06] space-y-1">
                <a href="{{ route('tools') }}" class="block px-4 py-2.5 text-sm text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-all" @click="close()">เครื่องมือ PDF</a>
                <a href="{{ route('templates') }}" class="block px-4 py-2.5 text-sm text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-all" @click="close()">คลังแบบฟอร์ม</a>
                <a href="{{ route('pricing') }}" class="block px-4 py-2.5 text-sm text-slate-300 hover:text-white hover:bg-white/5 rounded-lg transition-all" @click="close()">ราคา</a>
                <div class="pt-4 border-t border-white/[0.06] flex flex-col gap-2 px-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary text-sm text-center rounded-xl">แดชบอร์ด</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-ghost text-sm text-center rounded-xl">เข้าสู่ระบบ</a>
                        <a href="{{ route('register') }}" class="btn-primary text-sm text-center rounded-xl">สมัครฟรี</a>
                    @endauth
                </div>
            </div>
        </nav>
    </header>

    <!-- Flash Messages -->
    @if (session('success') || session('error') || session('warning'))
    <div class="fixed top-20 right-4 z-50 space-y-2" x-data x-init="
        setTimeout(() => $el.remove(), 5000)
    ">
        @if(session('success'))
        <div class="glass flex items-center gap-3 px-4 py-3 rounded-xl border border-success-500/30 text-success-500 text-sm font-medium shadow-lg min-w-72">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="glass flex items-center gap-3 px-4 py-3 rounded-xl border border-error-500/30 text-error-500 text-sm font-medium shadow-lg min-w-72">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            {{ session('error') }}
        </div>
        @endif
    </div>
    @endif

    <!-- Main Content -->
    <main class="pt-16">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="mt-24 border-t border-white/[0.06]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
                <!-- Brand -->
                <div class="lg:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 bg-gradient-to-br from-brand-500 to-accent-500 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <span class="text-lg font-bold text-white">PDF<span class="text-gradient">krub</span></span>
                    </div>
                    <p class="text-sm text-slate-400 leading-relaxed">แพลตฟอร์มจัดการเอกสาร PDF สำหรับครูและโรงเรียน รองรับ PDPA ประมวลผลในประเทศไทย</p>
                    <div class="mt-4 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-success-500/10 text-success-400 border border-success-500/20">🛡️ รองรับ PDPA</span>
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-brand-500/10 text-brand-400 border border-brand-500/20">🇹🇭 เซิร์ฟเวอร์ในไทย</span>
                    </div>
                </div>

                <!-- Tools -->
                <div>
                    <h3 class="text-sm font-semibold text-white mb-4">เครื่องมือสำหรับครู</h3>
                    <ul class="space-y-2">
                        @foreach([['PDF เป็น Word', 'tools.pdf-to-word'], ['รวมหลักฐาน PA', 'tools.merge-pdf'], ['บีบอัด PDF', 'tools.compress-pdf'], ['OCR ภาษาไทย', 'tools.ocr-pdf'], ['ลงลายเซ็น', 'tools.sign-pdf']] as [$label, $route])
                        <li><a href="{{ route($route) }}" class="text-sm text-slate-400 hover:text-brand-400 transition-colors">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h3 class="text-sm font-semibold text-white mb-4">บริษัท</h3>
                    <ul class="space-y-2">
                        @foreach([['เกี่ยวกับเรา', 'about'], ['ราคา', 'pricing'], ['บล็อก', 'blog'], ['ติดต่อเรา', 'contact']] as [$label, $route])
                        <li><a href="{{ route($route) }}" class="text-sm text-slate-400 hover:text-brand-400 transition-colors">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h3 class="text-sm font-semibold text-white mb-4">นโยบาย</h3>
                    <ul class="space-y-2">
                        @foreach([['นโยบายความเป็นส่วนตัว', 'privacy'], ['ข้อกำหนดการใช้งาน', 'terms'], ['นโยบายคุกกี้', 'cookies'], ['PDPA', 'pdpa']] as [$label, $route])
                        <li><a href="{{ route($route) }}" class="text-sm text-slate-400 hover:text-brand-400 transition-colors">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="mt-12 pt-8 border-t border-white/[0.06] flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-xs text-slate-500">© {{ date('Y') }} PDFkrub. สงวนลิขสิทธิ์ | ประมวลผลในประเทศไทย · รองรับ PDPA · ไฟล์ถูกลบอัตโนมัติใน 1 ชั่วโมง</p>
                <div class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-success-500 animate-pulse"></span>
                    <span class="text-xs text-slate-500">ระบบทำงานปกติ</span>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
