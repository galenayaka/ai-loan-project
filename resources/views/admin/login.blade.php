<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — AI-Loan / CreditScore AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: { 950: '#000000', 900: '#0a0a0a', 850: '#0e0e0e', 800: '#141414', 700: '#1c1c1c' },
                        paper: { 100: '#ffffff', 300: '#e5e5e5', 500: '#9ca3af', 600: '#6b7280', 700: '#4b5563' },
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
                    },
                },
            },
        };
    </script>

    <style> body { background-color: #000000; } </style>
</head>
<body class="text-paper-100 font-sans antialiased min-h-screen bg-ink-950 flex items-center justify-center px-6">
    <div class="w-full max-w-md border border-white/10 bg-ink-900">
        <div class="px-8 py-4 border-b border-white/10 flex items-center gap-3">
            <div class="h-8 w-8 border border-white/25 flex items-center justify-center">
                <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6L5.6 18.4" />
                </svg>
            </div>
            <div class="text-[13px] font-semibold tracking-wide text-white">AI-LOAN / CREDITSCORE AI</div>
        </div>

        <div class="p-8">
            <h1 class="text-xs uppercase tracking-[0.18em] text-paper-600 mb-1">Admin Console</h1>
            <p class="text-2xl font-semibold text-white mb-6">Sign in</p>

            @if ($errors->any())
                <div class="mb-6 border border-white/20 bg-white/5 px-4 py-3 text-sm text-paper-300">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-[11px] text-paper-500 mb-1.5">Email</label>
                    <input name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none transition-colors">
                </div>

                <div>
                    <label class="block text-[11px] text-paper-500 mb-1.5">Password</label>
                    <input name="password" type="password" required
                        class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none transition-colors">
                </div>

                <label class="flex items-center gap-2 text-[11px] text-paper-500">
                    <input type="checkbox" name="remember" value="1">
                    Remember me
                </label>

                <button type="submit"
                    class="w-full bg-white hover:bg-paper-300 text-black font-semibold py-3 text-sm tracking-wide transition-colors rounded-sm">
                    SIGN IN
                </button>
            </form>

            <p class="mt-6 text-[11px] text-paper-700 font-mono">Default: admin@example.com / password</p>
        </div>
    </div>
</body>
</html>