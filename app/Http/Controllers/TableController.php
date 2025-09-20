<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TableController extends Controller
{
    public function index()
    {
        $tables = Table::where('user_id', Auth::id())
            ->orderBy('table_number')
            ->get();

        return Inertia::render('POS/Tables/Index', [
            'tables' => $tables
        ]);
    }

    public function create()
    {
        return Inertia::render('POS/Tables/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'table_number' => 'required|string|max:10|unique:tables,table_number',
            'table_name' => 'required|string|max:255',
            'seats' => 'required|integer|min:1|max:20',
            'status' => 'required|in:available,occupied,reserved,maintenance',
            'description' => 'nullable|string|max:500',
            'x_position' => 'nullable|numeric',
            'y_position' => 'nullable|numeric',
        ]);

        $validated['user_id'] = Auth::id();

        Table::create($validated);

        return redirect()->route('pos.tables.index')
            ->with('success', 'Table created successfully.');
    }

    public function show(Table $table)
    {
        $this->authorize('view', $table);

        return Inertia::render('POS/Tables/Show', [
            'table' => $table
        ]);
    }

    public function edit(Table $table)
    {
        $this->authorize('update', $table);

        return Inertia::render('POS/Tables/Edit', [
            'table' => $table
        ]);
    }

    public function update(Request $request, Table $table)
    {
        $this->authorize('update', $table);

        $validated = $request->validate([
            'table_number' => 'required|string|max:10|unique:tables,table_number,' . $table->id,
            'table_name' => 'required|string|max:255',
            'seats' => 'required|integer|min:1|max:20',
            'status' => 'required|in:available,occupied,reserved,maintenance',
            'description' => 'nullable|string|max:500',
            'x_position' => 'nullable|numeric',
            'y_position' => 'nullable|numeric',
        ]);

        $table->update($validated);

        return redirect()->route('pos.tables.index')
            ->with('success', 'Table updated successfully.');
    }

    public function destroy(Table $table)
    {
        $this->authorize('delete', $table);

        $table->delete();

        return redirect()->route('pos.tables.index')
            ->with('success', 'Table deleted successfully.');
    }

    public function updateStatus(Request $request, Table $table)
    {
        $this->authorize('update', $table);

        $validated = $request->validate([
            'status' => 'required|in:available,occupied,reserved,maintenance'
        ]);

        $table->update($validated);

        return response()->json(['message' => 'Table status updated successfully.']);
    }
}
