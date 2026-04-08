<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberRank;
use App\Models\MemberRole;
use App\Models\Organization;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::with(['rank', 'roles']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('member_code', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $members = $query->orderBy('id', 'desc')->paginate(10);

        return view('members.index', compact('members'));
    }

    public function create()
    {
        $ranks = MemberRank::orderBy('name')->get();
        $roles = MemberRole::orderBy('name')->get();

        return view('members.create', compact('ranks', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'rank_id' => ['required', 'exists:member_ranks,id'],
            'status' => ['required', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:50'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['exists:member_roles,id'],
        ]);

        $organization = Organization::firstOrFail();

        $member = Member::create([
            'organization_id' => $organization->id,
            'member_code' => 'TEMP',
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'rank_id' => $validated['rank_id'],
            'status' => $validated['status'],
            'address' => $validated['address'] ?? null,
            'next_of_kin_name' => $validated['next_of_kin_name'] ?? null,
            'next_of_kin_phone' => $validated['next_of_kin_phone'] ?? null,
        ]);

        $member->update([
            'member_code' => 'NFUH-' . str_pad($member->id, 4, '0', STR_PAD_LEFT),
        ]);

        $member->roles()->sync($validated['role_ids'] ?? []);

        return redirect()
            ->route('members.index')
            ->with('success', 'Member created successfully');
    }

    public function show(Member $member)
    {
        $member->load(['rank', 'roles']);

        return view('members.show', compact('member'));
    }

    public function edit(Member $member)
    {
        $member->load('roles');

        $ranks = MemberRank::orderBy('name')->get();
        $roles = MemberRole::orderBy('name')->get();

        return view('members.edit', compact('member', 'ranks', 'roles'));
    }

    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'rank_id' => ['required', 'exists:member_ranks,id'],
            'status' => ['required', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:50'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['exists:member_roles,id'],
        ]);

        $member->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'rank_id' => $validated['rank_id'],
            'status' => $validated['status'],
            'address' => $validated['address'] ?? null,
            'next_of_kin_name' => $validated['next_of_kin_name'] ?? null,
            'next_of_kin_phone' => $validated['next_of_kin_phone'] ?? null,
        ]);

        $member->roles()->sync($validated['role_ids'] ?? []);

        return redirect()
            ->route('members.index')
            ->with('success', 'Member updated successfully');
    }
}