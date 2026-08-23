<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLoanApplicationRequest;
use App\Models\LoanApplication;
use App\Services\LoanUnderwritingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLoanApplicationController extends Controller
{
    public function __construct(
        private readonly LoanUnderwritingService $underwriting,
    ) {
    }

    /**
     * List all loan applications for review.
     */
    public function index(Request $request): View
    {
        $applications = LoanApplication::with(['applicant', 'riskAssessments'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->whereHas('applicant', function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.applications.index', compact('applications'));
    }

    /**
     * Show a single application with its risk assessment.
     */
    public function show(LoanApplication $loanApplication): View
    {
        $loanApplication->load(['applicant', 'riskAssessments']);

        return view('admin.applications.show', compact('loanApplication'));
    }

    /**
     * Show the edit form.
     */
    public function edit(LoanApplication $loanApplication): View
    {
        $loanApplication->load(['applicant']);

        return view('admin.applications.edit', compact('loanApplication'));
    }

    /**
     * Update a loan application.
     */
    public function update(UpdateLoanApplicationRequest $request, LoanApplication $loanApplication): RedirectResponse
    {
        $loanApplication->update($request->validated());

        return redirect()
            ->route('admin.applications.show', $loanApplication)
            ->with('success', 'Loan application updated successfully.');
    }

    /**
     * Delete a loan application (cascades to risk assessments).
     */
    public function destroy(LoanApplication $loanApplication): RedirectResponse
    {
        $loanApplication->delete();

        return redirect()
            ->route('admin.applications.index')
            ->with('success', 'Loan application deleted.');
    }

    /**
     * Re-run the ML/scorecard risk assessment for an application.
     */
    public function reassess(LoanApplication $loanApplication): RedirectResponse
    {
        $this->underwriting->assess($loanApplication);

        return redirect()
            ->route('admin.applications.show', $loanApplication)
            ->with('success', 'Risk assessment re-run completed.');
    }
}