<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Memastikan log terstruktur ada DAN tidak membocorkan password, token,
 * atau data pribadi.
 */
class LoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_is_logged_as_warning_without_credentials(): void
    {
        Log::spy();
        User::factory()->create(['email' => 'rahasia@example.com', 'password' => 'benar12345']);

        $this->postJson('/api/login', [
            'email' => 'rahasia@example.com',
            'password' => 'salah-total',
        ])->assertUnauthorized();

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
            $flat = json_encode($context);

            return ($context['event'] ?? null) === 'auth.login.failed'
                && ($context['reason'] ?? null) === 'wrong_password'
                && ! array_key_exists('email', $context)
                && ! array_key_exists('password', $context)
                && ! str_contains($flat, 'rahasia@example.com')
                && ! str_contains($flat, 'salah-total');
        })->once();
    }

    public function test_successful_login_is_logged_without_the_token(): void
    {
        Log::spy();
        $user = User::factory()->create(['password' => 'benar12345']);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'benar12345',
        ])->assertOk()->json('token');

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($user, $token): bool {
            $flat = json_encode($context);

            return ($context['event'] ?? null) === 'auth.login.succeeded'
                && (int) $context['user_id'] === $user->id
                && ! array_key_exists('token', $context)
                && ! str_contains($flat, $token)
                && ! str_contains($flat, $user->email);
        })->once();
    }

    public function test_created_expense_is_logged_without_personal_content(): void
    {
        Log::spy();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/expenses', [
            'title' => 'Judul pribadi rahasia',
            'amount' => 123456,
            'category' => 'Makan',
            'spent_on' => '2026-10-20',
            'note' => 'catatan pribadi',
        ])->assertStatus(201)->json('data.id');

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($user, $id): bool {
            $flat = json_encode($context);

            return ($context['event'] ?? null) === 'expense.created'
                && (int) $context['user_id'] === $user->id
                && (int) $context['expense_id'] === $id
                && $context['category'] === 'Makan'
                && ! str_contains($flat, 'Judul pribadi rahasia')
                && ! str_contains($flat, 'catatan pribadi')
                && ! str_contains($flat, '123456');
        })->once();
    }

    public function test_validation_failure_is_logged_with_field_names_only(): void
    {
        Log::spy();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/expenses', [
            'title' => '',
            'amount' => -5,
            'category' => 'Tidak-ada',
            'spent_on' => '2026-10-20',
        ])->assertStatus(422);

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) use ($user): bool {
            return ($context['event'] ?? null) === 'validation.failed'
                && (int) $context['user_id'] === $user->id
                && in_array('title', $context['failed_fields'], true)
                && in_array('amount', $context['failed_fields'], true)
                && in_array('category', $context['failed_fields'], true)
                && ! str_contains(json_encode($context), 'Tidak-ada');
        })->once();
    }

    public function test_access_to_someone_elses_expense_is_logged_as_warning(): void
    {
        Log::spy();
        $me = User::factory()->create();
        $other = User::factory()->create();
        $theirs = \App\Models\Expense::factory()->create(['user_id' => $other->id]);
        Sanctum::actingAs($me);

        $this->getJson("/api/expenses/{$theirs->id}")->assertNotFound();

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) use ($me): bool {
            return ($context['event'] ?? null) === 'expense.not_found'
                && (int) $context['user_id'] === $me->id
                && ($context['reason'] ?? null) === 'not_found_or_not_owned';
        })->once();
    }
}
