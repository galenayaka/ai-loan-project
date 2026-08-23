<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loan Applications API — AI-Loan / CreditScore AI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: { 950: '#000000', 900: '#0a0a0a', 850: '#0e0e0e', 800: '#141414', 700: '#1c1c1c', 600: '#252525' },
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
<body class="text-paper-100 font-sans antialiased min-h-screen bg-ink-950">
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
                    <div class="text-[10px] text-paper-600 mt-1.5 uppercase tracking-[0.18em]">Loan Applications Endpoint</div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ url('/') }}" class="border border-white/20 hover:border-white/60 px-3.5 py-1.5 transition-colors font-mono text-[11px] text-paper-300">DASHBOARD</a>
                <span class="hidden sm:inline text-[10px] font-mono text-paper-600">GET /api/v1/loan-applications</span>
            </div>
        </div>
    </header>

    <main class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-white">Loan Applications</h1>
            <p class="text-sm text-paper-600 mt-1">Human-readable overview of submitted applications. Append a JSON <code class="font-mono text-paper-300">Accept</code> header to receive raw API data.</p>
        </div>

        <div class="border border-white/10 bg-ink-900 overflow-x-auto">
            <table class="w-full text-sm min-w-[760px]">
                <thead>
                    <tr class="text-left border-b border-white/10 text-paper-600 text-[10px] uppercase tracking-widest">
                        <th class="px-5 py-4 font-normal">Applicant</th>
                        <th class="px-5 py-4 font-normal">Loan</th>
                        <th class="px-5 py-4 font-normal">Purpose</th>
                        <th class="px-5 py-4 font-normal">Grade</th>
                        <th class="px-5 py-4 font-normal">PD</th>
                        <th class="px-5 py-4 font-normal">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse ($applications as $application)
                        @php($latest = $application->riskAssessments->first())
                        <tr class="hover:bg-ink-850 transition-colors">
                            <td class="px-5 py-4">
                                <div class="text-white font-medium">{{ $application->applicant?->full_name }}</div>
                                <div class="text-[11px] text-paper-600">{{ $application->applicant?->email }}</div>
                            </td>
                            <td class="px-5 py-4 font-mono text-paper-300">${{ number_format((float) $application->loan_amount, 2) }}</td>
                            <td class="px-5 py-4 text-paper-300">{{ $application->loan_purpose }}</td>
                            <td class="px-5 py-4 font-mono text-white">{{ $latest->credit_grade ?? '—' }}</td>
                            <td class="px-5 py-4 font-mono text-paper-300">
                                @if ($latest)
                                    {{ round((float) $latest->default_probability * 100, 2) }}%
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-[10px] font-mono px-2 py-1 border
                                    @if($application->status === 'APPROVED') bg-white text-black border-white
                                    @elseif($application->status === 'REJECTED') border-white text-white
                                    @elseif($application->status === 'UNDER_REVIEW') border-white/50 text-paper-300
                                    @else text-paper-500 border-white/20 @endif">
                                    {{ $application->status ?? 'PENDING' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center text-paper-600">No applications submitted yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex items-center justify-between text-[11px] text-paper-600">
            <span>Showing {{ $applications->count() }} of {{ $applications->total() }} applications</span>
            @if ($applications->hasPages())
                <div class="flex gap-2">
                    @if ($applications->previousPageUrl())
                        <a href="{{ $applications->previousPageUrl() }}" class="border border-white/20 hover:border-white/60 px-3 py-1 transition-colors">&larr; Prev</a>
                    @endif
                    @if ($applications->nextPageUrl())
                        <a href="{{ $applications->nextPageUrl() }}" class="border border-white/20 hover:border-white/60 px-3 py-1 transition-colors">Next &rarr;</a>
                    @endif
                </div>
            @endif
        </div>
    </main>
</body>
</html>