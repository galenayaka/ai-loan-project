@extends('admin.layout')

@section('title', 'Application #' . $loanApplication->id)

@section('content')
@php($latest = $loanApplication->riskAssessments->first())

<div class="flex items-center justify-between mb-8">
    <div>
        <a href="{{ route('admin.applications.index') }}" class="text-[11px] text-paper-600 hover:text-white transition-colors">&larr; Back to applications</a>
        <h1 class="text-2xl font-semibold text-white mt-2">Application #{{ $loanApplication->id }}</h1>
    </div>
    <div class="flex items-center gap-3">
        <form action="{{ route('admin.applications.reassess', $loanApplication) }}" method="POST">
            @csrf
            <button type="submit" class="border border-white/20 hover:border-white/60 px-4 py-2 text-[11px] font-mono transition-colors">RE-ASSESS</button>
        </form>
        <a href="{{ route('admin.applications.edit', $loanApplication) }}" class="border border-white/20 hover:border-white/60 px-4 py-2 text-[11px] font-mono transition-colors">EDIT</a>
        <form action="{{ route('admin.applications.destroy', $loanApplication) }}" method="POST" onsubmit="return confirm('Delete this application?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="border border-white/20 hover:border-white/60 px-4 py-2 text-[11px] font-mono text-paper-300 transition-colors">DELETE</button>
        </form>
    </div>
</div>

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
            <div class="px-6 py-3 flex justify-between"><dt class="text-paper-600">Status</dt><dd class="text-white font-mono">{{ $loanApplication->status }}</dd></div>
        </dl>
    </section>
</div>

@if ($latest)
<section class="mt-6 border border-white/10 bg-ink-900">
    <div class="px-6 py-4 border-b border-white/10">
        <h2 class="text-xs uppercase tracking-[0.18em] text-paper-600">Latest Risk Assessment</h2>
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
            <div class="text-[10px] uppercase tracking-[0.18em] text-paper-600">Assessment</div>
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
@endsection