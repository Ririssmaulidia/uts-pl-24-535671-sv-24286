<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Makan siang',
            'amount' => 15000,
            'category' => 'Makan',
            'spent_on' => '2026-10-20',
            'note' => 'Kantin',
        ], $override);
    }

    public function test_index_returns_only_own_expenses(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        Expense::factory()->count(2)->create(['user_id' => $me->id]);
        Expense::factory()->count(3)->create(['user_id' => $other->id]);

        Sanctum::actingAs($me);

        $this->getJson('/api/expenses')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_store_creates_expense_and_returns_201(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/expenses', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Makan siang')
            ->assertJsonPath('data.amount', 15000);

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'title' => 'Makan siang',
            'amount' => 15000,
        ]);
    }

    public function test_store_with_invalid_data_returns_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/expenses', $this->payload([
            'title' => '',
            'amount' => -5,
            'category' => 'Tidak-ada',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'amount', 'category']);
    }

    public function test_show_returns_one_expense_but_not_someone_elses(): void
    {
        $me = User::factory()->create();
        $mine = Expense::factory()->create(['user_id' => $me->id]);
        $theirs = Expense::factory()->create();

        Sanctum::actingAs($me);

        $this->getJson("/api/expenses/{$mine->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $mine->id);

        $this->getJson("/api/expenses/{$theirs->id}")->assertNotFound();
    }

    public function test_update_changes_the_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->putJson("/api/expenses/{$expense->id}", $this->payload(['title' => 'Judul baru', 'amount' => 99000]))
            ->assertOk()
            ->assertJsonPath('data.title', 'Judul baru');

        $this->assertDatabaseHas('expenses', ['id' => $expense->id, 'title' => 'Judul baru', 'amount' => 99000]);
    }

    public function test_destroy_deletes_the_expense(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->deleteJson("/api/expenses/{$expense->id}")->assertOk();

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }
}
