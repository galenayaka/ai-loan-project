<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application #{{ $loanApplication->id }} — AI-Loan / CreditScore AI</title>

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
    @php($latest = $loanApplication->riskAssessments->first())

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
                    <div class="text-[10px] text-paper-600 mt-1.5 uppercase tracking-[0.18em]">Application #{{ $loanApplication->id }}</div>
                </div>
            </div>
            <a href="{{ route('v1.loan-applications.index') }}" class="border border-white/20 hover:border-white/60 px-3.5 py-1.5 transition-colors font-mono text-[11px] text-paper-300">&larr; ALL APPLICATIONS</a>
        </div>
    </header>

    <main class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="border border-white/10 bg-ink-900">
                <div class="px-6 py-4 border-b border-white/10">
                    <h2 class="text-xs uppercase tracking-[0.18em] text-paper-600">Applicant</h2>
                </div>
                <dl class="divide-y divide-white/10 text-sm">
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Name</dt><dd class="text-white">{{ $loanApplication->applicant?->full_name }}</dd></div>
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Email</dt><dd class="text-white">{{ $loanApplication->applicant?->email }}</dd></div>
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Monthly Income</dt><dd class="text-white font-mono">${{ number_format((float) $loanApplication->applicant?->monthly_income, 2) }}</dd></div>
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Employment</dt><dd class="text-white">{{ $loanApplication->applicant?->employment_years }} yrs</dd></div>
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Home Ownership</dt><dd class="text-white">{{ $loanApplication->applicant?->home_ownership }}</dd></div>
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Credit History</dt><dd class="text-white">{{ $loanApplication->applicant?->credit_history_length }} yrs</dd></div>
                </dl>
            </section>

            <section class="border border-white/10 bg-ink-900">
                <div class="px-6 py-4 border-b border-white/10">
                    <h2 class="text-xs uppercase tracking-[0.18em] text-paper-600">Loan Terms</h2>
                </div>
                <dl class="divide-y divide-white/10 text-sm">
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Amount</dt><dd class="text-white font-mono">${{ number_format((float) $loanApplication->loan_amount, 2) }}</dd></div>
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Purpose</dt><dd class="text-white">{{ $loanApplication->loan_purpose }}</dd></div>
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Interest Rate</dt><dd class="text-white font-mono">{{ $loanApplication->interest_rate }}%</dd></div>
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Term</dt><dd class="text-white font-mono">{{ $loanApplication->term_months }} months</dd></div>
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">DTI</dt><dd class="text-white font-mono">{{ (float) $loanApplication->debt_to_income_ratio * 100 }}%</dd></div>
                    <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Status</dt><dd class="text-white font-mono">{{ $loanApplication->status ?? 'PENDING' }}</dd></div>
                </dl>
            </section>
        </div>

        @if ($latest)
        <section class="mt-6 border border-white/10 bg-ink-900">
            <div class="px-6 py-4 border-b border-white/10">
                <h2 class="text-xs uppercase tracking-[0.18em] text-paper-600">Risk Assessment</h2>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 divide-x divide-white/10 border-b border-white/10">
                <div class="p-5 text-center">
                    <div class="text-[10px] uppercase tracking-[0.18em] text-paper-600">PD</div>
                    <div class="text-2xl mt-2 font-semibold text-white font-mono">{{ round((float) $latest->default_probability * 100, 2) }}%</div>
                </div>
                <div class="p-5 text-center">
                    <div class="text-[10px] uppercase tracking-[0.18em] text-paper-600">Grade</div>
                    <div class="text-2xl mt-2 font-semibold text-white font-mono">{{ $latest->credit_grade }}</div>
                </div>
                <div class="p-5 text-center">
                    <div class="text-[10px] uppercase tracking-[0.18em] text-paper-600">Signal</div>
                    <div class="text-sm mt-3 font-mono text-paper-300">{{ $latest->approval_signal }}</div>
                </div>
                <div class="p-5 text-center">
                    <div class="text-[10px] uppercase tracking-[0.18em] text-paper-600">Status</div>
                    <div class="text-sm mt-3 font-mono text-paper-300">{{ $latest->status }}</div>
                </div>
            </div>

            @if (!empty($latest->key_risk_drivers) && is_array($latest->key_risk_drivers))
            <div class="p-6">
                <h3 class="text-[10px] uppercase tracking-[0.18em] text-paper-600 mb-4">Risk Factor Breakdown</h3>
                <div class="space-y-4">
                    @foreach ($latest->key_risk_drivers as $driver)
                        @if (isset($driver['factor']))
                        <div class="border border-white/10 bg-ink-850 rounded-sm p-4">
                            <div class="flex items-center justify-between text-[11px] mb-2">
                                <span class="text-paper-300 font-medium">{{ $driver['factor'] }}</span>
                                <span class="font-mono {{ ($driver['direction'] ?? '') === 'negative' ? 'text-paper-500' : 'text-white' }}">
                                    {{ ($driver['direction'] ?? '') === 'negative' ? '▾ -' : '▴ +' }}{{ round(($driver['impact'] ?? 0) * 100) }}%
                                </span>
                            </div>
                            <div class="h-1 w-full bg-white/10 overflow-hidden">
                                <div class="h-full {{ ($driver['direction'] ?? '') === 'negative' ? 'bg-white/30' : 'bg-white' }}" style="width: {{ min(round(($driver['impact'] ?? 0) * 100), 100) }}%"></div>
                            </div>
                            <p class="text-[11px] text-paper-600 mt-2 leading-relaxed">{{ $driver['description'] ?? '' }}</p>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        </section>
        @endif
    </main>
</body>
</html>