@extends('admin.layout')

@section('title', 'Edit Application #' . $loanApplication->id)

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.applications.show', $loanApplication) }}" class="text-[11px] text-paper-600 hover:text-white transition-colors">&larr; Back to application</a>
    <h1 class="text-2xl font-semibold text-white mt-2">Edit Application #{{ $loanApplication->id }}</h1>
</div>

<form method="POST" action="{{ route('admin.applications.update', $loanApplication) }}" class="max-w-2xl border border-white/10 bg-ink-900">
    @csrf
    @method('PUT')

    <div class="px-6 py-4 border-b border-white/10">
        <h2 class="text-xs uppercase tracking-[0.18em] text-paper-600">Loan Terms</h2>
    </div>

    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-[11px] text-paper-500 mb-1.5">Loan Amount ($)</label>
            <input name="loan_amount" type="number" step="0.01" min="0.01" value="{{ $loanApplication->loan_amount }}" required
                class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none font-mono">
        </div>
        <div>
            <label class="block text-[11px] text-paper-500 mb-1.5">Loan Purpose</label>
            <input name="loan_purpose" type="text" maxlength="120" value="{{ $loanApplication->loan_purpose }}" required
                class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none">
        </div>
        <div>
            <label class="block text-[11px] text-paper-500 mb-1.5">Interest Rate (% APR)</label>
            <input name="interest_rate" type="number" step="0.01" min="0" max="100" value="{{ $loanApplication->interest_rate }}" required
                class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none font-mono">
        </div>
        <div>
            <label class="block text-[11px] text-paper-500 mb-1.5">Term (months)</label>
            <input name="term_months" type="number" min="1" max="480" value="{{ $loanApplication->term_months }}" required
                class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none font-mono">
        </div>
        <div>
            <label class="block text-[11px] text-paper-500 mb-1.5">Debt-to-Income Ratio</label>
            <input name="debt_to_income_ratio" type="number" step="0.001" min="0" max="10" value="{{ $loanApplication->debt_to_income_ratio }}" required
                class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none font-mono">
        </div>
        <div>
            <label class="block text-[11px] text-paper-500 mb-1.5">Status</label>
            <select name="status" class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none">
                @foreach (['PENDING', 'APPROVED', 'REJECTED', 'UNDER_REVIEW'] as $status)
                    <option value="{{ $status }}" @selected($loanApplication->status === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="px-6 py-4 border-t border-white/10 flex justify-end gap-3">
        <a href="{{ route('admin.applications.show', $loanApplication) }}" class="border border-white/20 hover:border-white/60 px-4 py-2 text-[11px] font-mono transition-colors">CANCEL</a>
        <button type="submit" class="bg-white hover:bg-paper-300 text-black font-semibold px-5 py-2 text-[11px] rounded-sm transition-colors">SAVE CHANGES</button>
    </div>
</form>
@endsection