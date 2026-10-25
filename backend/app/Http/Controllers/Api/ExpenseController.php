<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $expense = $request->user()->expenses()->findOrFail($id);

        return response()->json(['data' => $expense]);
    }

    /** Tambah pengeluaran baru (201 Created). */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $expense = $request->user()->expenses()->create($validated);

        return response()->json([
            'message' => 'Pengeluaran berhasil ditambahkan.',
            'data' => $expense,
        ], 201);
    }

    /** Ubah pengeluaran. */
    public function update(Request $request, string $id): JsonResponse
    {
        $expense = $request->user()->expenses()->findOrFail($id);

        $expense->update($request->validate($this->rules()));

        return response()->json([
            'message' => 'Pengeluaran berhasil diubah.',
            'data' => $expense->fresh(),
        ]);
    }

    /** Hapus pengeluaran. */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()->expenses()->findOrFail($id)->delete();

        return response()->json(['message' => 'Pengeluaran berhasil dihapus.']);
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
