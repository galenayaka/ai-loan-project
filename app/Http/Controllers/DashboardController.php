<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Render the AI-Loan / CreditScore AI underwriting dashboard.
     */
    public function index(): View
    {
        return view('dashboard');
    }
}