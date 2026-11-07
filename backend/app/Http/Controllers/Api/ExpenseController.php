<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    /** Tampilkan semua pengeluaran milik pengguna yang sedang login. */
    public function index(Request $request): JsonResponse
    {
        $expenses = $request->user()
            ->expenses()
            ->orderByDesc('spent_on')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $expenses]);
    }

    /** Tampilkan satu pengeluaran. */
    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json(['data' => $this->findOwned($request, $id, 'show')]);
    }

    /** Tambah pengeluaran baru (201 Created). */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $expense = $request->user()->expenses()->create($validated);

        // Hanya pengenal dan kategori. Judul, jumlah, dan catatan sengaja tidak dicatat.
        Log::info('Pengeluaran disimpan', [
            'event' => 'expense.created',
            'user_id' => $request->user()->id,
            'expense_id' => $expense->id,
            'category' => $expense->category,
        ]);

        return response()->json([
            'message' => 'Pengeluaran berhasil ditambahkan.',
            'data' => $expense,
        ], 201);
    }

    /** Ubah pengeluaran. */
    public function update(Request $request, string $id): JsonResponse
    {
        $expense = $this->findOwned($request, $id, 'update');

        $expense->update($request->validate($this->rules()));

        Log::info('Pengeluaran diubah', [
            'event' => 'expense.updated',
            'user_id' => $request->user()->id,
            'expense_id' => $expense->id,
        ]);

        return response()->json([
            'message' => 'Pengeluaran berhasil diubah.',
            'data' => $expense->fresh(),
        ]);
    }

    /** Hapus pengeluaran. */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $expense = $this->findOwned($request, $id, 'destroy');
        $expense->delete();

        Log::info('Pengeluaran dihapus', [
            'event' => 'expense.deleted',
            'user_id' => $request->user()->id,
            'expense_id' => (int) $id,
        ]);

        return response()->json(['message' => 'Pengeluaran berhasil dihapus.']);
    }

    /**
     * Mencari pengeluaran HANYA di antara milik pengguna yang login.
     * Bila tidak ada (atau milik orang lain), dicatat sebagai peringatan
     * karena bisa menandakan percobaan mengakses data orang lain.
     */
    private function findOwned(Request $request, string $id, string $action): Expense
    {
        $expense = $request->user()->expenses()->find($id);

        if (! $expense) {
            Log::warning('Pengeluaran tidak ditemukan atau bukan milik pengguna', [
                'event' => 'expense.not_found',
                'user_id' => $request->user()->id,
                'action' => $action,
                'expense_id' => (int) $id,
                'reason' => 'not_found_or_not_owned',
            ]);

            abort(404, 'Pengeluaran tidak ditemukan.');
        }

        return $expense;
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'integer', 'min:1'],
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'spent_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
