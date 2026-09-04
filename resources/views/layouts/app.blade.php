<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'PDFkrub') — แพลตฟอร์มจัดการ PDF สำหรับครูและโรงเรียน</title>
    <meta name="description" content="@yield('description', 'PDFkrub — แพลตฟอร์มจัดการเอกสาร PDF ภาษาไทย สำหรับครูและโรงเรียน รองรับ PDPA ประมวลผลในประเทศไทย')">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

    <!-- Open Graph -->
    <meta property="og:title" content="@yield('title', 'PDFkrub') — แพลตฟอร์มจัดการ PDF สำหรับครูไทย">
    <meta property="og:description" content="PDFkrub จัดการ PDF เพื่อครู รวม PA, OCR, บีบอัด, ลงนาม, ประทับ 'สำเนาถูกต้อง' รองรับ PDPA">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">

    <!-- Google Fonts: Noto Sans Thai + Instrument Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Canonical & Alternate -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "WebApplication",
      "name": "PDFkrub",
      "url": "{{ url('/') }}",
      "applicationCategory": "Productivity",
      "operatingSystem": "All",
      "description": "แพลตฟอร์มจัดการเอกสาร PDF ภาษาไทย สำหรับครูและโรงเรียน รองรับ PDPA",
      "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "THB"
      }
    }
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="bg-mesh min-h-screen antialiased text-gray-800" x-data>

    <!-- Navigation -->
    <header class="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-sm" style="box-shadow:0 1px 0 rgba(22,50,79,0.08),0 2px 12px rgba(22,50,79,0.04)" x-data="mobileNav()">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                    <img src="{{ asset('images/logo-mascot.png') }}" alt="PDFkrub" class="w-9 h-9 object-contain rounded-xl shadow-sm border border-gray-100 transition-transform group-hover:scale-105">
                    <span class="text-lg font-bold" style="color:#16324f">PDF<span class="text-gradient">krub</span></span>
                </a>

                <!-- Desktop Nav -->
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('tools') }}" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-red-600 rounded-lg hover:bg-red-50 transition-all">เครื่องมือ PDF</a>
                    <a href="{{ route('templates') }}" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-red-600 rounded-lg hover:bg-red-50 transition-all">แบบฟอร์มครู</a>
                    <a href="{{ route('pricing') }}" class="px-3 py-2 text-sm font-medium text-gray-600 hover:text-red-600 rounded-lg hover:bg-red-50 transition-all">ราคา</a>
                </div>

                <!-- Auth Buttons -->
                <div class="hidden md:flex items-center gap-3">
                    @auth
                        @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.index') }}" class="px-3 py-1.5 text-xs font-semibold rounded-xl transition-all flex items-center gap-1.5" style="background:rgba(22,50,79,0.08);color:#16324f;border:1px solid rgba(22,50,79,0.18)">
                            <span>⚙️</span> แอดมิน
                        </a>
                        @endif

                        <!-- User Profile Dropdown -->
                        <div class="relative" x-data="{ userMenuOpen: false }">
                            <button @click="userMenuOpen = !userMenuOpen"
                                    @click.away="userMenuOpen = false"
                                    class="flex items-center gap-2 px-3 py-1.5 text-sm rounded-xl border border-gray-200 hover:border-gray-300 bg-white hover:bg-gray-50 transition-all cursor-pointer shadow-sm">
                                <img src="{{ auth()->user()->avatar_url ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=e63946&color=fff&size=32' }}"
                                     alt="{{ auth()->user()->name }}"
                                     class="w-6 h-6 rounded-full">
                                <span class="max-w-36 truncate font-semibold text-gray-700 text-xs">{{ auth()->user()->name }}</span>
                                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform" :class="userMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div x-show="userMenuOpen"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                 class="absolute right-0 mt-2 w-60 rounded-2xl p-2 z-50 divide-y"
                                 style="display:none;background:#fff;border:1px solid #e2e8f0;box-shadow:0 8px 32px rgba(22,50,79,0.12);divide-color:#f1f5f9">
                                
                                <!-- User Info -->
                                <div class="px-3 py-2.5">
                                    <p class="text-xs font-bold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                                    <p class="text-[11px] text-gray-500 truncate">{{ auth()->user()->email }}</p>
                                    <div class="mt-1.5">
                                        <span class="badge-premium text-[10px]">
                                            {{ auth()->user()->getActivePlan()->display_name_th ?? auth()->user()->getActivePlan()->name }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Menu Links -->
                                <div class="py-1">
                                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-xl transition-colors font-medium">
                                        <span>📊</span> แดชบอร์ด
                                    </a>
                                    <a href="{{ route('dashboard.files') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-xl transition-colors font-medium">
                                        <span>📁</span> คลังไฟล์ของฉัน
                                    </a>
                                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-xl transition-colors font-medium">
                                        <span>👤</span> จัดการโปรไฟล์ / รหัสผ่าน
                                    </a>
                                    <a href="{{ route('billing.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-xl transition-colors font-medium">
                                        <span>💳</span> จัดการการสมัครสมาชิก
                                    </a>
                                    @if(auth()->user()->is_admin)
                                    <a href="{{ route('admin.index') }}" class="flex items-center gap-2 px-3 py-2 text-xs font-semibold rounded-xl transition-colors" style="color:#16324f" onmouseover="this.style.background='rgba(22,50,79,0.06)'" onmouseout="this.style.background='transparent'">
                                        <span>⚙️</span> แผงควบคุมระบบ (Admin)
                                    </a>
                                    @endif
                                </div>

                                <!-- Logout Action -->
                                <div class="pt-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-xs text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-xl transition-colors text-left font-medium cursor-pointer">
                                            <span>🚪</span> ออกจากระบบ
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Direct Logout Icon Button -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" title="ออกจากระบบ" class="p-2 text-slate-400 hover:text-rose-400 rounded-xl hover:bg-rose-500/10 transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
                                </svg>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">เข้าสู่ระบบ</a>
                        <a href="{{ route('register') }}" class="btn-primary text-sm rounded-xl px-5 py-2">สมัครฟรี</a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <button @click="toggle()" class="md:hidden p-2 text-gray-500 hover:text-gray-900 rounded-lg hover:bg-gray-100 transition-all" aria-label="เมนู">
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
                 class="md:hidden py-4 border-t border-gray-100 space-y-1">
                <a href="{{ route('tools') }}" class="block px-4 py-2.5 text-sm font-medium text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" @click="close()">เครื่องมือ PDF</a>
                <a href="{{ route('templates') }}" class="block px-4 py-2.5 text-sm font-medium text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" @click="close()">คลังแบบฟอร์ม</a>
                <a href="{{ route('pricing') }}" class="block px-4 py-2.5 text-sm font-medium text-gray-600 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" @click="close()">ราคา</a>
                <div class="pt-4 border-t border-gray-100 flex flex-col gap-2 px-2">
                    @auth
                        @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.index') }}" class="btn-ghost text-sm text-center rounded-xl font-semibold" style="color:#16324f;border-color:rgba(22,50,79,0.25)">⚙️ แผงควบคุมแอดมิน</a>
                        @endif
                        <a href="{{ route('dashboard') }}" class="btn-primary text-sm text-center rounded-xl">แดชบอร์ด</a>
                        <a href="{{ route('profile.edit') }}" class="btn-ghost text-sm text-center rounded-xl font-medium">👤 จัดการโปรไฟล์ / รหัสผ่าน</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full btn-ghost text-sm text-center rounded-xl py-2.5 font-semibold" style="color:#e63946;border-color:rgba(230,57,70,0.3)">
                                🚪 ออกจากระบบ
                            </button>
                        </form>
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
    <div class="fixed top-20 right-4 z-50 space-y-3 max-w-sm" x-data x-init="setTimeout(() => $el.remove(), 5000)">
        @if(session('success'))
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold shadow-lg" style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold shadow-lg" style="background:#fff5f5;border:1px solid #fbd5d8;color:#e63946">
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
    <footer class="mt-24" style="background:#16324f">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
                <!-- Brand -->
                <div class="lg:col-span-1">
                    <div class="flex items-center gap-2.5 mb-4">
                        <img src="{{ asset('images/logo-mascot.png') }}" alt="PDFkrub" class="w-9 h-9 object-contain rounded-xl bg-white p-0.5 shadow-md">
                        <span class="text-xl font-bold text-white tracking-tight">PDFkrub</span>
                    </div>
                    <p class="text-sm leading-relaxed" style="color:rgba(255,255,255,0.65)">แพลตฟอร์มจัดการเอกสาร PDF สำหรับครูและโรงเรียน รองรับ PDPA ประมวลผลในประเทศไทย</p>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full" style="background:rgba(22,163,74,0.15);color:#4ade80;border:1px solid rgba(74,222,128,0.2)">🛡️ รองรับ PDPA</span>
                        <span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full" style="background:rgba(58,134,255,0.15);color:#93c5fd;border:1px solid rgba(58,134,255,0.25)">🇹🇭 เซิร์ฟเวอร์ในไทย</span>
                    </div>
                </div>

                <!-- Tools -->
                <div>
                    <h3 class="text-sm font-semibold text-white mb-4">เครื่องมือสำหรับครู</h3>
                    <ul class="space-y-2">
                        @foreach([['PDF เป็น Word', 'tools.pdf-to-word'], ['รวมหลักฐาน PA', 'tools.merge-pdf'], ['บีบอัด PDF', 'tools.compress-pdf'], ['OCR ภาษาไทย', 'tools.ocr-pdf'], ['ลงลายเซ็น', 'tools.sign-pdf']] as [$label, $route])
                        <li><a href="{{ route($route) }}" class="text-sm transition-colors" style="color:rgba(255,255,255,0.6)" onmouseover="this.style.color='#f47c84'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h3 class="text-sm font-semibold text-white mb-4">บริษัท</h3>
                    <ul class="space-y-2">
                        @foreach([['เกี่ยวกับเรา', 'about'], ['ราคา', 'pricing'], ['บล็อก', 'blog'], ['ติดต่อเรา', 'contact']] as [$label, $route])
                        <li><a href="{{ route($route) }}" class="text-sm transition-colors" style="color:rgba(255,255,255,0.6)" onmouseover="this.style.color='#93c5fd'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h3 class="text-sm font-semibold text-white mb-4">นโยบาย</h3>
                    <ul class="space-y-2">
                        @foreach([['นโยบายความเป็นส่วนตัว', 'privacy'], ['ข้อกำหนดการใช้งาน', 'terms'], ['นโยบายคุกกี้', 'cookies'], ['PDPA', 'pdpa']] as [$label, $route])
                        <li><a href="{{ route($route) }}" class="text-sm transition-colors" style="color:rgba(255,255,255,0.6)" onmouseover="this.style.color='#93c5fd'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="mt-12 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4" style="border-top:1px solid rgba(255,255,255,0.1)">
                <p class="text-xs" style="color:rgba(255,255,255,0.45)">© {{ date('Y') }} PDFkrub. สงวนลิขสิทธิ์ | ประมวลผลในประเทศไทย · รองรับ PDPA · ไฟล์ถูกลบอัตโนมัติใน 1 ชั่วโมง</p>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full animate-pulse" style="background:#4ade80"></span>
                    <span class="text-xs" style="color:rgba(255,255,255,0.45)">ระบบทำงานปกติ</span>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
