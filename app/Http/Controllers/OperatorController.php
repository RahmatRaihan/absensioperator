<?php

namespace App\Http\Controllers;

use App\Models\Operator;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OperatorController extends Controller
{
    public function index(Request $request)
    {
        $query = Operator::query();

        // Search filter
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('id_operator', 'like', "%{$search}%");
            });
        }

        // Unit filter
        if ($request->has('unit') && $request->unit != '') {
            $query->where('unit', $request->unit);
        }

        // Group Shift filter
        if ($request->has('grup_shift') && $request->grup_shift != '') {
            $query->where('grup_shift', $request->grup_shift);
        }

        // Status filter
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $operators = $query->orderBy('id_operator', 'asc')->paginate(10)->withQueryString();

        return Inertia::render('Operator/Index', [
            'operators' => $operators,
            'filters' => $request->only(['search', 'unit', 'grup_shift', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'unit' => 'required|in:BTG,C&AHS,Power Distribution',
            'grup_shift' => 'required|in:A,B,C,D',
            'status' => 'required|in:Aktif,Nonaktif',
            'npk' => 'required|string|max:50|unique:operators,npk',
            'keterangan' => 'nullable|string',
        ]);

        // Auto-generate operator ID (e.g. OP001, OP002)
        $latestOperator = Operator::orderBy('id_operator', 'desc')->first();
        if ($latestOperator) {
            // Get number part
            $number = (int) substr($latestOperator->id_operator, 2);
            $newNumber = $number + 1;
            $newId = 'OP' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        } else {
            $newId = 'OP001';
        }

        Operator::create([
            'id_operator' => $newId,
            'nama' => $request->nama,
            'unit' => $request->unit,
            'grup_shift' => $request->grup_shift,
            'status' => $request->status,
            'npk' => $request->npk,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('operator.index')->with('success', "Operator {$request->nama} berhasil ditambahkan dengan ID {$newId}.");
    }

    public function update(Request $request, Operator $operator)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'unit' => 'required|in:BTG,C&AHS,Power Distribution',
            'grup_shift' => 'required|in:A,B,C,D',
            'status' => 'required|in:Aktif,Nonaktif',
            'npk' => 'required|string|max:50|unique:operators,npk,' . $operator->id,
            'keterangan' => 'nullable|string',
        ]);

        $operator->update([
            'nama' => $request->nama,
            'unit' => $request->unit,
            'grup_shift' => $request->grup_shift,
            'status' => $request->status,
            'npk' => $request->npk,
            'keterangan' => $request->keterangan,
        ]);

        return redirect()->route('operator.index')->with('success', "Data operator {$operator->nama} berhasil diperbarui.");
    }

    public function toggleStatus(Operator $operator)
    {
        $newStatus = $operator->status === 'Aktif' ? 'Nonaktif' : 'Aktif';
        $operator->update(['status' => $newStatus]);

        return redirect()->route('operator.index')->with('success', "Status operator {$operator->nama} diubah menjadi {$newStatus}.");
    }
}
