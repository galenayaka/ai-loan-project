@extends('admin.layout')

@section('title', 'Applications')

@section('content')
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-2xl font-semibold text-white">Loan Applications</h1>
        <p class="text-sm text-paper-600 mt-1">Review, update, re-assess, and delete applications.</p>
    </div>
</div>

<form method="GET" action="{{ route('admin.applications.index') }}" class="flex flex-col sm:flex-row gap-3 mb-6">
    <input name="search" value="{{ request('search') }}" type="text" placeholder="Search name or email…"
        class="flex-1 bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none">
    <select name="status" class="bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none">
        <option value="">All Statuses</option>
        @foreach (['PENDING', 'APPROVED', 'REJECTED', 'UNDER_REVIEW'] as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
        @endforeach
    </select>
    <button type="submit" class="bg-white hover:bg-paper-300 text-black font-semibold px-5 py-2.5 text-sm rounded-sm">
        FILTER
    </button>
</form>

<div class="border border-white/10 bg-ink-900">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left border-b border-white/10 text-paper-600 text-[10px] uppercase tracking-widest">
                <th class="px-5 py-4 font-normal">Applicant</th>
                <th class="px-5 py-4 font-normal">Loan</th>
                <th class="px-5 py-4 font-normal">Grade</th>
                <th class="px-5 py-4 font-normal">Signal</th>
                <th class="px-5 py-4 font-normal">Status</th>
                <th class="px-5 py-4 font-normal text-right">Actions</th>
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
                    <td class="px-5 py-4 font-mono text-paper-300">
                        ${{ number_format((float) $application->loan_amount, 2) }}
                    </td>
                    <td class="px-5 py-4 font-mono text-white">{{ $latest->credit_grade ?? '—' }}</td>
                    <td class="px-5 py-4 font-mono text-[11px] text-paper-300">{{ $latest->approval_signal ?? '—' }}</td>
                    <td class="px-5 py-4">
                        <span class="text-[10px] font-mono px-2 py-1 border
                            @if($application->status === 'APPROVED') bg-white text-black border-white
                            @elseif($application->status === 'REJECTED') border-white text-white
                            @elseif($application->status === 'UNDER_REVIEW') border-white/50 text-paper-300
                            @else text-paper-500 border-white/20 @endif">
                            {{ $application->status }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.applications.show', $application) }}" class="text-[11px] border border-white/20 hover:border-white/60 px-2.5 py-1 transition-colors">VIEW</a>
                            <a href="{{ route('admin.applications.edit', $application) }}" class="text-[11px] border border-white/20 hover:border-white/60 px-2.5 py-1 transition-colors">EDIT</a>
                            <form action="{{ route('admin.applications.destroy', $application) }}" method="POST" onsubmit="return confirm('Delete this application?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] border border-white/20 hover:border-white/60 px-2.5 py-1 transition-colors text-paper-300">DELETE</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center text-paper-600">No applications found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{ $applications->links() }}
</div>
@endsection