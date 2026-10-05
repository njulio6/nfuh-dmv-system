<?php

namespace App\Http\Controllers;

use App\Models\MemberRank;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TitleController extends Controller
{
    /**
     * Display a listing of traditional titles.
     */
    public function index(Request $request)
    {
        $query = MemberRank::withCount('members');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $titles = $query->orderBy('level', 'desc')
            ->orderBy('name')
            ->paginate($perPage);

        return view('titles.index', compact('titles'));
    }

    /**
     * Display the specified traditional title.
     */
    public function show(MemberRank $title)
    {
        $title->load(['members' => function ($query) {
            $query->orderBy('first_name')->orderBy('last_name');
        }]);

        return view('titles.show', compact('title'));
    }

    /**
     * Show the form for creating a new traditional title.
     */
    public function create()
    {
        return view('titles.create');
    }

    /**
     * Store a newly created traditional title in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255', 'unique:member_ranks,name'],
            'level' => ['required', 'integer', 'min:0'],
        ]);

        MemberRank::create($validated);

        return redirect()
            ->route('titles.index')
            ->with('success', 'Traditional title created successfully.');
    }

    /**
     * Show the form for editing the specified traditional title.
     */
    public function edit(MemberRank $title)
    {
        return view('titles.edit', compact('title'));
    }

    /**
     * Update the specified traditional title in storage.
     */
    public function update(Request $request, MemberRank $title)
    {
        $validated = $request->validate([
            'name'  => [
                'required', 
                'string', 
                'max:255', 
                Rule::unique('member_ranks', 'name')->ignore($title->id)
            ],
            'level' => ['required', 'integer', 'min:0'],
        ]);

        $title->update($validated);

        return redirect()
            ->route('titles.index')
            ->with('success', 'Traditional title updated successfully.');
    }

    /**
     * Remove the specified traditional title from storage.
     */
    public function destroy(MemberRank $title)
    {
        $title->delete();

        return redirect()
            ->route('titles.index')
            ->with('success', 'Traditional title deleted successfully.');
    }
}
