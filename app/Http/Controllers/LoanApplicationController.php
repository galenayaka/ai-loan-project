<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanApplicationRequest;
use App\Http\Resources\LoanApplicationResource;
use App\Models\Applicant;
use App\Models\LoanApplication;
use App\Services\LoanUnderwritingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanApplicationController extends Controller
{
    public function __construct(
        private readonly LoanUnderwritingService $underwriting,
    ) {
    }

    /**
     * List all loan applications with their applicant and latest assessment.
     *
     * Returns JSON for API clients (Accept: application/json) and a
     * human-friendly HTML page when opened directly in a browser.
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View
    {
        $applications = LoanApplication::with(['applicant', 'riskAssessments'])
            ->latest()
            ->paginate(15);

        if ($request->wantsJson()) {
            return LoanApplicationResource::collection($applications)->response();
        }

        return view('api.applications.index', compact('applications'));
    }

    /**
     * Create an applicant and loan application in a single flow,
     * then dispatch the ML risk assessment.
     */
    public function store(StoreLoanApplicationRequest $request): JsonResponse
    {
        $applicantData = $request->applicantData();

        $loanData = $request->only([
            'loan_amount',
            'loan_purpose',
            'interest_rate',
            'term_months',
            'debt_to_income_ratio',
        ]);

        // Default the status to PENDING unless explicitly supplied.
        $loanData['status'] = $request->input('status', LoanApplication::STATUS_PENDING);

        $application = DB::transaction(function () use ($request, $loanData, $applicantData) {
            if ($request->filled('applicant_id')) {
                $applicant = Applicant::findOrFail($request->integer('applicant_id'));
            } else {
                $applicant = Applicant::create($applicantData);
            }

            /** @var LoanApplication $application */
            $application = $applicant->loanApplications()->create($loanData);

            return $application;
        });

        // Kick off the ML assessment (outside the DB transaction to avoid
        // holding a lock during a network round-trip).
        $this->underwriting->assess($application);

        return (new LoanApplicationResource(
            $application->load(['applicant', 'riskAssessments'])
        ))->response()->setStatusCode(201);
    }

    /**
     * Show a single loan application.
     *
     * Returns JSON for API clients and a human-friendly HTML page when
     * opened directly in a browser.
     */
    public function show(Request $request, LoanApplication $loanApplication): LoanApplicationResource|\Illuminate\View\View
    {
        $loanApplication->load(['applicant', 'riskAssessments']);

        if ($request->wantsJson()) {
            return new LoanApplicationResource($loanApplication);
        }

        return view('api.applications.show', compact('loanApplication'));
    }
}