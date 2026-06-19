<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\NjangiCycle;
use App\Models\Organization;
use App\Services\Njangi\GenerateNjangiSessions;
use App\Models\NjangiCycleMember;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

class NjangiCycleController extends Controller
{
    public function index(Request $request)
    {
        $query = NjangiCycle::with('organization');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('year', 'like', "%{$search}%")
                  ->orWhereHas('organization', function($orgQuery) use ($search) {
                      $orgQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $perPage = $request->input('per_page', 10);
        $cycles = $query->orderByDesc('year')
            ->orderByDesc('id')
            ->paginate($perPage);

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
            'cycleMembers' => function ($query) {
                $query->orderByRaw('CASE WHEN benefit_order IS NULL THEN 1 ELSE 0 END')
                      ->orderBy('benefit_order')
                      ->orderBy('id');
            },
            'cycleMembers.member',
            'sessions',
        ]);

        $settings = Setting::first();

        $existingMemberIds = $njangiCycle->cycleMembers()
            ->pluck('member_id')
            ->toArray();

        $availableMembers = Member::where('organization_id', $njangiCycle->organization_id)
            ->whereNotIn('id', $existingMemberIds)
            ->where('participates_in_njangi', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('njangi.cycles.show', compact('njangiCycle', 'settings', 'availableMembers'));
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
            $settings = Setting::first();
            if (!$settings || !$settings->allow_mid_cycle_enrollment) {
                return redirect()
                    ->route('njangi-cycles.show', $njangiCycle)
                    ->with('error', 'Cannot add members after sessions have been generated.');
            }
        }

        $existingMemberIds = $njangiCycle->cycleMembers()
            ->pluck('member_id')
            ->toArray();

        $members = Member::where('organization_id', $njangiCycle->organization_id)
            ->whereNotIn('id', $existingMemberIds)
            ->where('participates_in_njangi', true)
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
                'status' => 'active',
            ]);
        }

        return redirect()
            ->route('njangi-cycles.show', $njangiCycle)
            ->with('success', 'Members added to the cycle successfully.');
    }

    public function addSingleMember(Request $request, NjangiCycle $njangiCycle)
    {
        if ($njangiCycle->sessions()->exists()) {
            $settings = Setting::first();
            if (!$settings || !$settings->allow_mid_cycle_enrollment) {
                return redirect()
                    ->route('njangi-cycles.show', $njangiCycle)
                    ->with('error', 'Mid-cycle enrollment is disabled in system settings.');
            }
        }

        $validated = $request->validate([
            'member_id' => [
                'required',
                Rule::exists('members', 'id')->where(function ($q) use ($njangiCycle) {
                    $q->where('organization_id', $njangiCycle->organization_id)
                      ->where('participates_in_njangi', true);
                }),
            ],
            'benefit_order' => [
                'nullable',
                'integer',
                'min:1',
                Rule::unique('njangi_cycle_members', 'benefit_order')->where(function ($q) use ($njangiCycle) {
                    $q->where('njangi_cycle_id', $njangiCycle->id);
                }),
            ],
        ]);

        $alreadyEnrolled = $njangiCycle->cycleMembers()->where('member_id', $validated['member_id'])->exists();
        if ($alreadyEnrolled) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Member is already enrolled in this cycle.');
        }

        $njangiCycle->cycleMembers()->create([
            'organization_id' => $njangiCycle->organization_id,
            'member_id' => $validated['member_id'],
            'benefit_order' => $validated['benefit_order'] ?? null,
            'status' => 'active',
        ]);

        return redirect()
            ->route('njangi-cycles.show', $njangiCycle)
            ->with('success', 'Member added to cycle successfully.');
    }

    public function bulkUpdateMembers(Request $request, NjangiCycle $njangiCycle)
    {
        $validated = $request->validate([
            'members' => ['required', 'array'],
            'members.*.benefit_order' => ['nullable', 'integer', 'min:1'],
            'members.*.status' => ['required', Rule::in(['active', 'inactive', 'withdrawn', 'suspended'])],
        ]);

        $membersData = $validated['members'];

        // Collect all non-null benefit orders and check for duplicates within the input
        $orders = [];
        foreach ($membersData as $id => $data) {
            $order = $data['benefit_order'] ?? null;
            if ($order !== null && $order !== '') {
                $orders[] = (int)$order;
            }
        }

        if (count($orders) !== count(array_unique($orders))) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Duplicate benefit orders are not allowed within the same cycle.');
        }

        DB::transaction(function () use ($njangiCycle, $membersData) {
            // Temporarily set all to null to avoid unique constraint checks on intermediate states during swap
            $njangiCycle->cycleMembers()->update(['benefit_order' => null]);

            foreach ($membersData as $id => $data) {
                $cycleMember = NjangiCycleMember::where('njangi_cycle_id', $njangiCycle->id)->find($id);
                if ($cycleMember) {
                    $cycleMember->update([
                        'benefit_order' => ($data['benefit_order'] !== '' && $data['benefit_order'] !== null) ? (int)$data['benefit_order'] : null,
                        'status' => $data['status'],
                    ]);
                }
            }
        });

        return redirect()
            ->route('njangi-cycles.show', $njangiCycle)
            ->with('success', 'Cycle members updated successfully.');
    }

    public function removeMember(NjangiCycle $njangiCycle, NjangiCycleMember $njangiCycleMember)
    {
        if ($njangiCycle->sessions()->exists()) {
            $settings = Setting::first();
            if (!$settings || !$settings->allow_mid_cycle_removal) {
                return redirect()
                    ->route('njangi-cycles.show', $njangiCycle)
                    ->with('error', 'Mid-cycle removal is disabled in system settings.');
            }
        }

        if ($njangiCycleMember->disbursements()->exists()) {
            return redirect()
                ->route('njangi-cycles.show', $njangiCycle)
                ->with('error', 'Cannot remove this member because they have already benefited in this cycle. Please change their status to Inactive, Withdrawn, or Suspended instead.');
        }

        DB::transaction(function () use ($njangiCycleMember) {
            $njangiCycleMember->beneficiarySessions()->delete();
            $njangiCycleMember->delete();
        });

        return redirect()
            ->route('njangi-cycles.show', $njangiCycle)
            ->with('success', 'Member removed from cycle successfully.');
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

    DB::transaction(function () use ($njangiCycle, $cycleMembers) {
        // Temporarily clear benefit orders to avoid unique key conflicts during assignment
        $njangiCycle->cycleMembers()->update(['benefit_order' => null]);

        $order = 1;
        foreach ($cycleMembers as $cycleMember) {
            $cycleMember->update([
                'benefit_order' => $order,
            ]);

            $order++;
        }
    });

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