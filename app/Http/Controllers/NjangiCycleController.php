<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\NjangiCycle;
use App\Models\Organization;
use App\Services\Njangi\GenerateNjangiSessions;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class NjangiCycleController extends Controller
{
    public function index()
    {
        $cycles = NjangiCycle::with('organization')
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->paginate(10);

        return view('njangi.cycles.index', compact('cycles'));
    }

    public function create()
    {
        $organizations = Organization::orderBy('name')->get();

        return view('njangi.cycles.create', compact('organizations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'digits:4'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['draft', 'active', 'closed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        NjangiCycle::create($validated);

        return redirect()
            ->route('njangi-cycles.index')
            ->with('success', 'Njangi cycle created successfully.');
    }

    public function show(NjangiCycle $njangiCycle)
    {
        $njangiCycle->load([
            'organization',
            'cycleMembers.member',
            'sessions',
        ]);

        return view('njangi.cycles.show', compact('njangiCycle'));
    }

    public function edit(NjangiCycle $njangiCycle)
    {
        if ($njangiCycle->sessions()->exists()) {
            return redirect()
                ->route('njangi-cycles.show', $njangiCycle)
                ->with('error', 'Cannot edit a cycle after sessions have been generated.');
        }

        $organizations = Organization::orderBy('name')->get();

        return view('njangi.cycles.edit', compact('njangiCycle', 'organizations'));
    }

    public function update(Request $request, NjangiCycle $njangiCycle)
    {
        if ($njangiCycle->sessions()->exists()) {
            return redirect()
                ->route('njangi-cycles.show', $njangiCycle)
                ->with('error', 'Cannot update a cycle after sessions have been generated.');
        }

        $validated = $request->validate([
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'digits:4'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['draft', 'active', 'closed', 'cancelled'])],
            'notes' => ['nullable', 'string'],
        ]);

        $njangiCycle->update($validated);

        return redirect()
            ->route('njangi-cycles.index')
            ->with('success', 'Njangi cycle updated successfully.');
    }

    public function addMembers(NjangiCycle $njangiCycle)
    {
        if ($njangiCycle->sessions()->exists()) {
            return redirect()
                ->route('njangi-cycles.show', $njangiCycle)
                ->with('error', 'Cannot add members after sessions have been generated.');
        }

        $existingMemberIds = $njangiCycle->cycleMembers()
            ->pluck('member_id')
            ->toArray();

        $members = Member::where('organization_id', $njangiCycle->organization_id)
            ->whereNotIn('id', $existingMemberIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        if ($members->isEmpty()) {
            return redirect()
                ->route('njangi-cycles.show', $njangiCycle)
                ->with('error', 'No available members to add to this cycle.');
        }

        foreach ($members as $member) {
            $njangiCycle->cycleMembers()->create([
                'organization_id' => $njangiCycle->organization_id,
                'member_id' => $member->id,
                'benefit_order' => null,
            ]);
        }

        return redirect()
            ->route('njangi-cycles.show', $njangiCycle)
            ->with('success', 'Members added to the cycle successfully.');
    }

    public function generateSessions(NjangiCycle $njangiCycle, GenerateNjangiSessions $generator)
    {
        try {
            $generator->execute($njangiCycle);

            return redirect()
                ->route('njangi-cycles.show', $njangiCycle)
                ->with('success', 'Njangi sessions generated successfully.');
        } catch (RuntimeException $e) {
            return redirect()
                ->route('njangi-cycles.show', $njangiCycle)
                ->with('error', $e->getMessage());
        }
    }
public function assignBenefitOrder(NjangiCycle $njangiCycle)
{
    if ($njangiCycle->sessions()->exists()) {
        return redirect()
            ->route('njangi-cycles.show', $njangiCycle)
            ->with('error', 'Cannot assign benefit order after sessions have been generated.');
    }

    $cycleMembers = $njangiCycle->cycleMembers()
        ->orderBy('id')
        ->get();

    if ($cycleMembers->isEmpty()) {
        return redirect()
            ->route('njangi-cycles.show', $njangiCycle)
            ->with('error', 'No members in this cycle.');
    }

    $order = 1;

    foreach ($cycleMembers as $cycleMember) {
        $cycleMember->update([
            'benefit_order' => $order,
        ]);

        $order++;
    }

    return redirect()
        ->route('njangi-cycles.show', $njangiCycle)
        ->with('success', 'Benefit order assigned successfully.');
}
    public function destroy(NjangiCycle $njangiCycle)
    {
        if ($njangiCycle->sessions()->exists()) {
            return redirect()
                ->route('njangi-cycles.show', $njangiCycle)
                ->with('error', 'Cannot delete a cycle after sessions have been generated.');
        }

        $njangiCycle->delete();

        return redirect()
            ->route('njangi-cycles.index')
            ->with('success', 'Njangi cycle deleted successfully.');
    }
}