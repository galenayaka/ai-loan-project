<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoanApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create([
            'role' => 'admin',
        ]);
    }

    private function makeApplication(): LoanApplication
    {
        $applicant = Applicant::create([
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'monthly_income' => 5000,
            'employment_years' => 3,
            'home_ownership' => 'RENT',
            'credit_history_length' => 5,
        ]);

        return $applicant->loanApplications()->create([
            'loan_amount' => 20000,
            'loan_purpose' => 'Debt consolidation',
            'interest_rate' => 9.5,
            'term_months' => 60,
            'debt_to_income_ratio' => 0.20,
            'status' => 'PENDING',
        ]);
    }

    public function test_admin_can_view_applications_index(): void
    {
        $this->makeApplication();

        $response = $this->actingAs($this->adminUser())->get('/admin/applications');

        $response->assertOk();
        $response->assertSee('Jane Doe');
    }

    public function test_non_admin_cannot_access_admin_area(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/applications')->assertForbidden();
    }

    public function test_admin_can_login_via_form(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_authenticated_admin_visiting_login_is_redirected_to_dashboard(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get('/admin/login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_guest_accessing_admin_area_is_redirected_to_login(): void
    {
        $this->get('/admin/applications')
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_update_application(): void
    {
        $application = $this->makeApplication();

        $response = $this->actingAs($this->adminUser())
            ->put("/admin/applications/{$application->id}", [
                'loan_amount' => 25000,
                'loan_purpose' => 'Home renovation',
                'interest_rate' => 8.0,
                'term_months' => 48,
                'debt_to_income_ratio' => 0.25,
                'status' => 'APPROVED',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('loan_applications', [
            'id' => $application->id,
            'loan_amount' => 25000,
            'status' => 'APPROVED',
        ]);
    }

    public function test_admin_can_delete_application(): void
    {
        $application = $this->makeApplication();

        $this->actingAs($this->adminUser())
            ->delete("/admin/applications/{$application->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('loan_applications', ['id' => $application->id]);
    }
}