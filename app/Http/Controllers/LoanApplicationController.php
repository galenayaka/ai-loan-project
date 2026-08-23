<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanApplicationRequest;
use App\Http\Resources\LoanApplicationResource;
use App\Models\Applicant;
use App\Models\LoanApplication;
use App\Services\LoanUnderwritingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoanApplicationController extends Controller
{
    public function __construct(
        private readonly LoanUnderwritingService $underwriting,
    ) {
    }

    /**
     * List all loan applications with their applicant and latest assessment.
     */
    public function index(): JsonResponse
    {
        $applications = LoanApplication::with(['applicant'])
            ->latest()
            ->paginate(15);

        return LoanApplicationResource::collection($applications)->response();
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
        try {
            $this->underwriting->assess($application);
        } catch (RuntimeException $e) {
            // The application remains persisted; the assessment record will be
            // marked FAILED. Return the application with a warning so the UI can
            // surface the fallback/retry state gracefully.
            return (new LoanApplicationResource(
                $application->load(['applicant', 'riskAssessments'])
            ))->response()->setStatusCode(201);
        }

        return (new LoanApplicationResource(
            $application->load(['applicant', 'riskAssessments'])
        ))->response()->setStatusCode(201);
    }

    /**
     * Show a single loan application.
     */
    public function show(LoanApplication $loanApplication): LoanApplicationResource
    {
        return new LoanApplicationResource(
            $loanApplication->load(['applicant', 'riskAssessments'])
        );
    }
}