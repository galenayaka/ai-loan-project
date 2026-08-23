<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI-Loan / CreditScore AI — Underwriting Dashboard</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        // Derive the API base path from the current page URL so requests work
        // at the domain root (e.g. http://localhost/) and in a sub-directory
        // (e.g. XAMPP serving from /ai-loan-project/public/). The dashboard is
        // the app root, so the page pathname IS the base path.
        (function () {
            var base = window.location.pathname.replace(/\/+$/, '');
            window.App = {
                urls: {
                    loanApplicationsIndex: base + '/api/v1/loan-applications',
                    loanApplicationsStore: base + '/api/v1/loan-applications',
                    adminLogin: base + '/admin/login',
                },
            };
        })();
    </script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ink: {
                            950: '#000000',
                            900: '#0a0a0a',
                            850: '#0e0e0e',
                            800: '#141414',
                            700: '#1c1c1c',
                            600: '#252525',
                            500: '#3a3a3a',
                        },
                        paper: {
                            100: '#ffffff',
                            300: '#e5e5e5',
                            500: '#9ca3af',
                            600: '#6b7280',
                            700: '#4b5563',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'SFMono-Regular', 'Menlo', 'monospace'],
                    },
                },
            },
        };
    </script>

    <style>
        [x-cloak] { display: none !important; }
        body { background-color: #000000; }

        .stripes {
            background-image: repeating-linear-gradient(
                45deg,
                transparent 0 5px,
                rgba(255, 255, 255, 0.08) 5px 10px
            );
        }
        .stripes-light {
            background-image: repeating-linear-gradient(
                45deg,
                transparent 0 5px,
                rgba(255, 255, 255, 0.35) 5px 10px
            );
        }

        /* Grayscale risk track — dark (low risk) to white (high risk). */
        .risk-meter-track {
            background: conic-gradient(
                from -90deg,
                #1a1a1a 0deg,
                #3a3a3a 70deg,
                #6b7280 140deg,
                #9ca3af 180deg,
                #e5e5e5 240deg,
                #ffffff 340deg,
                #ffffff 360deg
            );
            -webkit-mask: radial-gradient(farthest-side, transparent calc(100% - 12px), #000 calc(100% - 11px));
            mask: radial-gradient(farthest-side, transparent calc(100% - 12px), #000 calc(100% - 11px));
        }

        @keyframes pulse-glow {
            0%, 100% { opacity: 0.55; }
            50% { opacity: 1; }
        }
        .animate-pulse-glow { animation: pulse-glow 2.2s ease-in-out infinite; }
    </style>
</head>
<body class="text-paper-100 font-sans antialiased min-h-screen bg-ink-950">
    <div x-data="underwritingDashboard()" x-cloak class="min-h-screen bg-ink-950">

        <!-- Top navigation -->
        <header class="border-b border-white/10 bg-ink-900/90 backdrop-blur sticky top-0 z-20">
            <div class="max-w-[1280px] mx-auto px-6 lg:px-10 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="h-8 w-8 border border-white/25 flex items-center justify-center">
                        <svg class="h-4 w-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18M3 12h18M5.6 5.6l12.8 12.8M18.4 5.6L5.6 18.4" />
                        </svg>
                    </div>
                    <div class="leading-none">
                        <div class="text-[13px] font-semibold tracking-wide text-white">
                            AI-LOAN <span class="text-paper-600 font-normal">/</span> CREDITSCORE&nbsp;AI
                        </div>
                        <div class="text-[10px] text-paper-600 mt-1.5 uppercase tracking-[0.18em]">
                            Automated risk underwriting
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-6 text-[11px]">
                    <span class="hidden sm:flex items-center gap-2 text-paper-500">
                        <span class="h-1.5 w-1.5 rounded-full bg-white animate-pulse-glow"></span>
                        ML Engine <span class="text-white font-mono">:8002</span>
                    </span>
                    <a :href="window.App.urls.loanApplicationsIndex" target="_blank"
                       class="border border-white/20 hover:border-white/60 px-3.5 py-1.5 transition-colors font-mono text-[11px] text-paper-300">
                        API
                    </a>
                    <a :href="window.App.urls.adminLogin"
                       class="border border-white/20 hover:border-white/60 px-3.5 py-1.5 transition-colors font-mono text-[11px] text-paper-300">
                        LOGIN
                    </a>
                </div>
            </div>
        </header>

        <main class="max-w-[1280px] mx-auto px-6 lg:px-10 py-10">

            <!-- Key figures -->
            <div class="grid grid-cols-2 lg:grid-cols-4 border border-white/10 bg-ink-900 divide-x divide-y lg:divide-y-0 divide-white/10">
                <template x-for="metric in metrics" :key="metric.label">
                    <div class="p-6">
                        <div class="text-[10px] uppercase tracking-[0.18em] text-paper-600" x-text="metric.label"></div>
                        <div class="text-2xl mt-3 font-semibold text-white font-mono" x-text="metric.value"></div>
                    </div>
                </template>
            </div>

            <div class="mt-8 grid grid-cols-1 lg:grid-cols-12 gap-8">

                <!-- Left column: application / calculator -->
                <div class="lg:col-span-7">
                    <section class="border border-white/10 bg-ink-900">
                        <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between">
                            <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-white">Loan Application</h2>
                            <span class="text-[10px] uppercase tracking-[0.18em] text-paper-600">Live DTI</span>
                        </div>

                        <form @submit.prevent="submitApplication" class="p-6 space-y-8">

                            <!-- Applicant -->
                            <fieldset>
                                <legend class="text-[10px] uppercase tracking-[0.18em] text-paper-600 mb-4">Applicant Profile</legend>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div class="sm:col-span-2">
                                        <label class="block text-[11px] text-paper-500 mb-1.5">Full Name</label>
                                        <input x-model="form.full_name" type="text" required
                                            class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white placeholder:text-paper-700 rounded-sm outline-none transition-colors"
                                            placeholder="Jane Doe">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[11px] text-paper-500 mb-1.5">Email</label>
                                        <input x-model="form.email" type="email" required
                                            class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white placeholder:text-paper-700 rounded-sm outline-none transition-colors"
                                            placeholder="jane@example.com">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-paper-500 mb-1.5">Monthly Income ($)</label>
                                        <input x-model.number="form.monthly_income" type="number" min="0" step="0.01" required
                                            class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none transition-colors font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-paper-500 mb-1.5">Employment (years)</label>
                                        <input x-model.number="form.employment_years" type="number" min="0" step="0.5" required
                                            class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none transition-colors font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-paper-500 mb-1.5">Home Ownership</label>
                                        <select x-model="form.home_ownership"
                                            class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none transition-colors">
                                            <option value="RENT">RENT</option>
                                            <option value="OWN">OWN</option>
                                            <option value="MORTGAGE">MORTGAGE</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-paper-500 mb-1.5">Credit History (years)</label>
                                        <input x-model.number="form.credit_history_length" type="number" min="0" max="80" required
                                            class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none transition-colors font-mono">
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Loan terms -->
                            <fieldset>
                                <legend class="text-[10px] uppercase tracking-[0.18em] text-paper-600 mb-4">Loan Terms</legend>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                    <div>
                                        <label class="block text-[11px] text-paper-500 mb-1.5">Loan Amount ($)</label>
                                        <input x-model.number="form.loan_amount" type="number" min="0.01" step="0.01" required
                                            class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none transition-colors font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-paper-500 mb-1.5">Interest Rate (% APR)</label>
                                        <input x-model.number="form.interest_rate" type="number" min="0" max="100" step="0.01" required
                                            class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none transition-colors font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-paper-500 mb-1.5">Term (months)</label>
                                        <input x-model.number="form.term_months" type="number" min="1" max="480" required
                                            class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none transition-colors font-mono">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-paper-500 mb-1.5">Loan Purpose</label>
                                        <input x-model="form.loan_purpose" type="text" required maxlength="120"
                                            class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white placeholder:text-paper-700 rounded-sm outline-none transition-colors"
                                            placeholder="Debt consolidation">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-paper-500 mb-1.5">Existing Monthly Debt ($)</label>
                                        <input x-model.number="form.monthly_debt" type="number" min="0" step="0.01"
                                            class="w-full bg-ink-800 border border-white/10 focus:border-white/50 px-3.5 py-2.5 text-sm text-white rounded-sm outline-none transition-colors font-mono">
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Live ratios -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-white/10 border border-white/10 bg-ink-850">
                                <div class="p-5">
                                    <div class="text-[10px] uppercase tracking-[0.18em] text-paper-600">Est. Payment</div>
                                    <div class="text-xl mt-2 text-white font-mono font-medium" x-text="'$' + payment.toFixed(2)"></div>
                                </div>
                                <div class="p-5">
                                    <div class="text-[10px] uppercase tracking-[0.18em] text-paper-600">Debt-to-Income</div>
                                    <div class="text-xl mt-2 text-white font-mono font-medium" x-text="(dti * 100).toFixed(1) + '%'"></div>
                                    <div class="h-1 w-full bg-white/10 mt-3 overflow-hidden">
                                        <div class="h-full bg-white transition-all" :style="`width:${Math.min(dti * 100, 100)}%`"></div>
                                    </div>
                                </div>
                                <div class="p-5">
                                    <div class="text-[10px] uppercase tracking-[0.18em] text-paper-600">Payment-to-Income</div>
                                    <div class="text-xl mt-2 text-white font-mono font-medium" x-text="(pti * 100).toFixed(1) + '%'"></div>
                                </div>
                            </div>

                            <button type="submit" :disabled="loading"
                                class="w-full bg-white hover:bg-paper-300 disabled:opacity-40 text-black font-semibold py-3.5 text-sm tracking-wide transition-colors rounded-sm"
                                x-text="loading ? 'PROCESSING…' : 'RUN UNDERWRITING ASSESSMENT'"></button>

                            <p x-show="error" x-text="error" class="text-sm text-paper-300 border border-white/20 bg-white/5 px-4 py-3"></p>
                        </form>
                    </section>
                </div>

                <!-- Right column: risk assessment -->
                <div class="lg:col-span-5 space-y-8">
                    <section class="border border-white/10 bg-ink-900">
                        <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between">
                            <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-white">Risk Assessment</h2>
                            <span x-show="assessment" class="text-[10px] font-mono px-2.5 py-1 border" :class="signalBadgeClass"
                                  x-text="assessment ? assessment.approval_signal : ''"></span>
                        </div>

                        <!-- Empty -->
                        <div x-show="!assessment && !loading" class="px-6 py-16 text-center">
                            <div class="h-12 w-12 mx-auto border border-white/15 flex items-center justify-center">
                                <svg class="h-5 w-5 text-paper-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 15l3-4 3 2 4-6" />
                                </svg>
                            </div>
                            <p class="text-paper-500 text-sm mt-5">No assessment yet</p>
                            <p class="text-paper-600 text-xs mt-2 leading-relaxed">Submit an application to generate a<br>real-time credit risk readout.</p>
                        </div>

                        <!-- Loading -->
                        <div x-show="loading" class="px-6 py-16 text-center">
                            <div class="inline-block h-9 w-9 border-2 border-white/15 border-t-white rounded-full animate-spin"></div>
                            <p class="text-paper-500 text-xs mt-5 tracking-wide uppercase">Querying ML engine…</p>
                        </div>

                        <!-- Result -->
                        <div x-show="assessment && !loading" class="p-6 space-y-8">
                            <!-- Gauge -->
                            <div class="flex flex-col items-center">
                                <div class="relative h-52 w-52">
                                    <div class="risk-meter-track absolute inset-0 rounded-full"></div>
                                    <div class="absolute inset-[12px] rounded-full bg-ink-900 border border-white/10 flex flex-col items-center justify-center">
                                        <span class="text-4xl font-semibold text-white font-mono"
                                              x-text="assessment ? (assessment.default_probability_percent ?? (assessment.default_probability * 100)).toFixed(1) + '%' : ''"></span>
                                        <span class="text-[9px] text-paper-600 mt-2 uppercase tracking-[0.22em]">Default&nbsp;Probability</span>
                                    </div>
                                    <!-- Needle -->
                                    <div class="absolute inset-0"
                                         :style="`transform: rotate(${(assessment ? (assessment.default_probability_percent ?? assessment.default_probability * 100) : 0) * 3.6 - 270}deg)`">
                                        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-full h-[42%] w-px bg-white origin-bottom"></div>
                                    </div>
                                    <div class="absolute left-1/2 top-1/2 h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white"></div>
                                </div>

                                <div class="mt-6 grid grid-cols-2 w-full border border-white/10 divide-x divide-white/10">
                                    <div class="p-5 text-center">
                                        <div class="text-[10px] uppercase tracking-[0.18em] text-paper-600">Credit Grade</div>
                                        <div class="text-3xl mt-2 font-semibold text-white font-mono" x-text="assessment ? assessment.credit_grade : ''"></div>
                                    </div>
                                    <div class="p-5 text-center">
                                        <div class="text-[10px] uppercase tracking-[0.18em] text-paper-600">Approval Signal</div>
                                        <div class="text-sm mt-3 font-mono tracking-wide" :class="signalTextClass" x-text="assessment ? assessment.approval_signal : ''"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Risk drivers -->
                            <div x-show="assessment && assessment.key_risk_drivers && assessment.key_risk_drivers.length">
                                <h3 class="text-[10px] uppercase tracking-[0.18em] text-paper-600 mb-4">Risk Factor Breakdown</h3>
                                <div class="space-y-4">
                                    <template x-for="driver in assessment.key_risk_drivers" :key="driver.factor + driver.description">
                                        <div class="border border-white/10 bg-ink-850 rounded-sm p-4">
                                            <div class="flex items-center justify-between text-[11px] mb-2">
                                                <span class="text-paper-300 font-medium" x-text="driver.factor"></span>
                                                <span class="font-mono" :class="driver.direction === 'negative' ? 'text-paper-500' : 'text-white'"
                                                      x-text="driver.direction === 'negative' ? '▾ -' + (driver.impact * 100).toFixed(0) + '%' : '▴ +' + (driver.impact * 100).toFixed(0) + '%'"></span>
                                            </div>
                                            <div class="h-1 w-full bg-white/10 overflow-hidden">
                                                <div class="h-full" :class="driver.direction === 'negative' ? 'stripes bg-white/30' : 'bg-white'"
                                                     :style="`width:${Math.min(driver.impact * 100, 100)}%`"></div>
                                            </div>
                                            <p class="text-[11px] text-paper-600 mt-2 leading-relaxed" x-text="driver.description"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Recent applications -->
                    <section class="border border-white/10 bg-ink-900">
                        <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between">
                            <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-white">Recent Applications</h2>
                            <span class="text-[10px] font-mono text-paper-600" x-text="recent.length + ' RECORDS'"></span>
                        </div>
                        <ul class="divide-y divide-white/10">
                            <template x-for="app in recent" :key="app.id">
                                <li @click="openDetails(app.id)" role="button" tabindex="0"
                                    class="px-6 py-4 flex items-center justify-between gap-4 cursor-pointer hover:bg-ink-850 transition-colors">
                                    <div class="min-w-0">
                                        <p class="text-sm text-white font-medium truncate" x-text="app.applicant?.full_name"></p>
                                        <p class="text-[11px] text-paper-600 mt-1 font-mono truncate"
                                           x-text="'$' + Number(app.loan_amount).toLocaleString() + ' · ' + app.loan_purpose"></p>
                                    </div>
                                    <span class="text-[10px] font-mono px-2 py-1 border shrink-0" :class="statusClass(app.status)" x-text="app.status"></span>
                                </li>
                            </template>
                            <li x-show="!recent.length" class="px-6 py-10 text-center text-paper-600 text-xs">
                                No applications submitted.
                            </li>
                        </ul>
                    </section>
                </div>
            </div>

            <!-- Footer -->
            <footer class="mt-10 pt-6 border-t border-white/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-paper-600">
                <span>AI-LOAN / CREDITSCORE AI — Automated credit risk underwriting platform</span>
                <span class="font-mono">XGBoost PD · Heuristic Scorecard · SHAP Attribution</span>
            </footer>
        </main>

        <!-- Application details modal -->
        <div x-show="selectedApplication !== null || selectedLoading" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
             @click.self="closeDetails()">
            <div class="w-full max-w-lg border border-white/15 bg-ink-900 shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between sticky top-0 bg-ink-900">
                    <h3 class="text-xs uppercase tracking-[0.18em] text-white font-semibold">Application Details</h3>
                    <button @click="closeDetails()" class="text-paper-500 hover:text-white text-xl leading-none">&times;</button>
                </div>

                <div x-show="selectedLoading" class="p-10 text-center">
                    <div class="inline-block h-8 w-8 border-2 border-white/15 border-t-white rounded-full animate-spin"></div>
                </div>

                <div x-show="selectedApplication && !selectedLoading" class="p-6 space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white font-semibold" x-text="selectedApplication?.applicant?.full_name"></p>
                            <p class="text-[11px] text-paper-600 font-mono" x-text="selectedApplication?.applicant?.email"></p>
                        </div>
                        <span class="text-[10px] font-mono px-2 py-1 border shrink-0" :class="statusClass(selectedApplication?.status)" x-text="selectedApplication?.status"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-px bg-white/10 border border-white/10">
                        <div class="bg-ink-900 p-4">
                            <div class="text-[10px] uppercase tracking-widest text-paper-600">Loan Amount</div>
                            <div class="text-lg mt-1 font-mono text-white" x-text="'$' + Number(selectedApplication?.loan_amount).toLocaleString()"></div>
                        </div>
                        <div class="bg-ink-900 p-4">
                            <div class="text-[10px] uppercase tracking-widest text-paper-600">Purpose</div>
                            <div class="text-lg mt-1 font-mono text-white" x-text="selectedApplication?.loan_purpose"></div>
                        </div>
                        <div class="bg-ink-900 p-4">
                            <div class="text-[10px] uppercase tracking-widest text-paper-600">Rate</div>
                            <div class="text-lg mt-1 font-mono text-white" x-text="selectedApplication?.interest_rate + '%'"></div>
                        </div>
                        <div class="bg-ink-900 p-4">
                            <div class="text-[10px] uppercase tracking-widest text-paper-600">Term</div>
                            <div class="text-lg mt-1 font-mono text-white" x-text="selectedApplication?.term_months + ' mo'"></div>
                        </div>
                        <div class="bg-ink-900 p-4">
                            <div class="text-[10px] uppercase tracking-widest text-paper-600">Home</div>
                            <div class="text-lg mt-1 font-mono text-white" x-text="selectedApplication?.applicant?.home_ownership"></div>
                        </div>
                        <div class="bg-ink-900 p-4">
                            <div class="text-[10px] uppercase tracking-widest text-paper-600">Credit History</div>
                            <div class="text-lg mt-1 font-mono text-white" x-text="selectedApplication?.applicant?.credit_history_length + ' yrs'"></div>
                        </div>
                    </div>

                    <div x-show="selectedApplication?.risk_assessments?.length">
                        <h4 class="text-[10px] uppercase tracking-[0.18em] text-paper-600 mb-3">Risk Details</h4>
                        <template x-for="risk in selectedApplication.risk_assessments" :key="risk.id">
                            <div class="border border-white/10 bg-ink-850 rounded-sm p-4 mb-3">
                                <div class="flex items-center justify-between text-[11px] mb-2">
                                    <span class="text-paper-300 font-medium">Grade <span class="text-white font-mono" x-text="risk.credit_grade"></span></span>
                                    <span class="text-paper-500 font-mono" x-text="risk.approval_signal"></span>
                                </div>
                                <div class="text-2xl font-mono text-white"
                                     x-text="(risk.default_probability_percent ?? risk.default_probability * 100).toFixed(2) + '% PD'"></div>
                                <div class="text-[11px] text-paper-600 mt-1" x-text="risk.status"></div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function underwritingDashboard() {
            return {
                loading: false,
                error: null,
                assessment: null,
                recent: [],
                selectedApplication: null,
                selectedLoading: false,
                form: {
                    full_name: '',
                    email: '',
                    monthly_income: 5000,
                    employment_years: 3,
                    home_ownership: 'RENT',
                    credit_history_length: 5,
                    loan_amount: 20000,
                    interest_rate: 9.5,
                    term_months: 60,
                    loan_purpose: 'Debt consolidation',
                    monthly_debt: 1000,
                },
                metrics: [
                    { label: 'Model', value: 'XGBoost' },
                    { label: 'Credit Scale', value: '300–850' },
                    { label: 'Grades', value: 'AAA → D' },
                    { label: 'Engine', value: ':8002' },
                ],

                get payment() {
                    const p = Number(this.form.loan_amount) || 0;
                    const r = ((Number(this.form.interest_rate) || 0) / 100) / 12;
                    const n = Number(this.form.term_months) || 0;
                    if (p <= 0 || n <= 0) return 0;
                    if (r === 0) return p / n;
                    return p * ((r * Math.pow(1 + r, n)) / (Math.pow(1 + r, n) - 1));
                },

                get existingDti() {
                    const income = Number(this.form.monthly_income) || 0;
                    if (income <= 0) return 0;
                    return (Number(this.form.monthly_debt) || 0) / income;
                },

                get dti() {
                    const income = Number(this.form.monthly_income) || 0;
                    if (income <= 0) return 0;
                    return ((Number(this.form.monthly_debt) || 0) + this.payment) / income;
                },

                get pti() {
                    const income = Number(this.form.monthly_income) || 0;
                    if (income <= 0) return 0;
                    return this.payment / income;
                },

                // Monochrome state treatment: solid / outline / hatch.
                get signalBadgeClass() {
                    const s = this.assessment?.approval_signal;
                    if (s === 'AUTO_APPROVE') return 'bg-white text-black border-white';
                    if (s === 'MANUAL_REVIEW') return 'border-white text-white';
                    if (s === 'AUTO_REJECT') return 'border-white text-white stripes-light';
                    return 'border-white/20 text-paper-500';
                },

                get signalTextClass() {
                    const s = this.assessment?.approval_signal;
                    if (s === 'AUTO_APPROVE') return 'text-white font-semibold';
                    if (s === 'MANUAL_REVIEW') return 'text-paper-300';
                    if (s === 'AUTO_REJECT') return 'text-paper-300';
                    return 'text-paper-500';
                },

                statusClass(status) {
                    const map = {
                        PENDING: 'text-paper-500 border-white/20',
                        APPROVED: 'bg-white text-black border-white',
                        REJECTED: 'border-white text-white stripes-light',
                        UNDER_REVIEW: 'border-white/50 text-paper-300',
                    };
                    return map[status] || 'text-paper-500 border-white/20';
                },

                async init() {
                    await this.loadRecent();
                },

                async submitApplication() {
                    this.loading = true;
                    this.error = null;

                    const payload = {
                        full_name: this.form.full_name,
                        email: this.form.email,
                        monthly_income: Number(this.form.monthly_income),
                        employment_years: Number(this.form.employment_years),
                        home_ownership: this.form.home_ownership,
                        credit_history_length: Number(this.form.credit_history_length),
                        loan_amount: Number(this.form.loan_amount),
                        loan_purpose: this.form.loan_purpose,
                        interest_rate: Number(this.form.interest_rate),
                        term_months: Number(this.form.term_months),
                        debt_to_income_ratio: Number(this.existingDti.toFixed(4)),
                    };

                    try {
                        const res = await fetch(window.App.urls.loanApplicationsStore, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await res.json();
                        if (!res.ok) {
                            throw new Error(data.message || 'Request failed');
                        }

                        const assessments = data.data?.risk_assessments || [];
                        this.assessment = assessments[assessments.length - 1] || null;
                        if (this.assessment && this.assessment.default_probability != null) {
                            this.assessment.default_probability_percent = Number(this.assessment.default_probability) * 100;
                        }

                        await this.loadRecent();
                    } catch (e) {
                        this.error = 'Underwriting failed: ' + e.message;
                    } finally {
                        this.loading = false;
                    }
                },

                async loadRecent() {
                    try {
                        const res = await fetch(window.App.urls.loanApplicationsIndex, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await res.json();
                        this.recent = data.data || [];
                    } catch (e) {
                        // Non-blocking.
                    }
                },

                async openDetails(id) {
                    this.selectedLoading = true;
                    this.selectedApplication = null;
                    try {
                        const res = await fetch(window.App.urls.loanApplicationsStore + '/' + id, {
                            headers: { 'Accept': 'application/json' },
                        });
                        const data = await res.json();
                        this.selectedApplication = data.data || null;
                    } catch (e) {
                        this.selectedApplication = { error: 'Unable to load application details.' };
                    } finally {
                        this.selectedLoading = false;
                    }
                },

                closeDetails() {
                    this.selectedApplication = null;
                },
            };
        }
    </script>
</body>
</html>