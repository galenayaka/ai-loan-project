<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — AI-Loan / CreditScore AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: {
                            950: '#000000',
                            900: '#0a0a0a',
                            850: '#0e0e0e',
                            800: '#141414',
                            700: '#1c1c1c',
                            600: '#252525',
                            500: '#3a3a3a',
                        },
                        paper: {
                            100: '#ffffff',
                            300: '#e5e5e5',
                            500: '#9ca3af',
                            600: '#6b7280',
                            700: '#4b5563',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
                    },
                },
            },
        };
    </script>

    <style>
        body { background-color: #000000; }
    </style>
</head>
<body class="text-paper-100 font-sans antialiased min-h-screen bg-ink-950">
    @auth
    <header class="border-b border-white/10 bg-ink-900/90 backdrop-blur sticky top-0 z-20">
        <div class="max-w-[1280px] mx-auto px-6 lg:px-10 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3.5">
                <div class="h-8 w-8 border border-white/25 flex items-center justify-center">
                    <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6L5.6 18.4" />
                    </svg>
                </div>
                <div class="leading-none">
                    <div class="text-[13px] font-semibold tracking-wide text-white">AI-LOAN <span class="text-paper-600 font-normal">/</span> CREDITSCORE AI</div>
                    <div class="text-[10px] text-paper-600 mt-1.5 uppercase tracking-[0.18em]">Admin Console</div>
                </div>
            </div>

            <nav class="flex items-center gap-6 text-[11px]">
                <a href="{{ route('admin.applications.index') }}" class="text-paper-300 hover:text-white transition-colors">Applications</a>
                <a href="{{ route('dashboard') }}" class="text-paper-300 hover:text-white transition-colors">Public</a>
                <span class="text-paper-600">{{ Auth::user()->name }}</span>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="border border-white/20 hover:border-white/60 px-3.5 py-1.5 transition-colors font-mono text-[11px] text-paper-300">
                        LOGOUT
                    </button>
                </form>
            </nav>
        </div>
    </header>
    @endauth

    <main class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        @if (session('success'))
            <div class="mb-6 border border-white/20 bg-white/5 px-4 py-3 text-sm text-paper-300">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>