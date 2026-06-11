<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberRank;
use App\Models\MemberRole;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        if ($request->filled('state_code')) {
            $query->where('state_code', $request->state_code);
        }

        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [5, 10, 20, 30, 50])) {
            $perPage = 10;
        }

        $members = $query->orderBy('id', 'desc')->paginate($perPage);

        return view('members.index', compact('members'));
    }

    public function create()
    {
        $ranks = MemberRank::orderBy('name')->get();
        $roles = MemberRole::orderBy('name')->get();

        return view('members.create', compact('ranks', 'roles'));
    }

    public function store(Request $request, \App\Services\Member\MemberCodeGenerator $generator)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:members,email'],
            'phone' => ['required', 'string', 'max:50'],
            'rank_id' => ['nullable', 'exists:member_ranks,id'],
            'status' => ['required', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'state_code' => ['required', Rule::in(['MD', 'VA', 'DC'])],
            'join_date' => ['required', 'date'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:50'],
            'next_of_kin_email' => ['nullable', 'email', 'max:255'],
            'next_of_kin_address' => ['nullable', 'string'],
            'participates_in_njangi' => ['nullable', 'boolean'],
            'participates_in_savings' => ['nullable', 'boolean'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['exists:member_roles,id'],
            'password' => ['nullable', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        if (
            !($request->boolean('participates_in_njangi')
            || $request->boolean('participates_in_savings'))
        ) {
            return back()
                ->withErrors([
                    'participation' => 'Select at least one participation type.',
                ])
                ->withInput();
        }

        $organization = Organization::firstOrFail();

        $member = Member::create([
            'organization_id' => $organization->id,
            'member_code' => $generator->generate(
                $validated['state_code'],
                $validated['join_date']
            ),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'rank_id' => $validated['rank_id'] ?? null,
            'status' => $validated['status'],
            'address' => $validated['address'],
            'state_code' => $validated['state_code'],
            'join_date' => $validated['join_date'],
            'next_of_kin_name' => $validated['next_of_kin_name'] ?? null,
            'next_of_kin_phone' => $validated['next_of_kin_phone'] ?? null,
            'next_of_kin_email' => $validated['next_of_kin_email'] ?? null,
            'next_of_kin_address' => $validated['next_of_kin_address'] ?? null,
            'participates_in_njangi' => $request->boolean('participates_in_njangi'),
            'participates_in_savings' => $request->boolean('participates_in_savings'),
        ]);

        $member->roles()->sync($validated['role_ids'] ?? []);

        if (!empty($validated['email']) && $request->filled('password')) {
            \App\Models\User::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                    'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                ]
            );
        }

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
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('members', 'email')->ignore($member->id),
            ],
            'phone' => ['required', 'string', 'max:50'],
            'rank_id' => ['nullable', 'exists:member_ranks,id'],
            'status' => ['required', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'state_code' => ['required', Rule::in(['MD', 'VA', 'DC'])],
            'join_date' => ['required', 'date'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:50'],
            'next_of_kin_email' => ['nullable', 'email', 'max:255'],
            'next_of_kin_address' => ['nullable', 'string'],
            'participates_in_njangi' => ['nullable', 'boolean'],
            'participates_in_savings' => ['nullable', 'boolean'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['exists:member_roles,id'],
            'password' => ['nullable', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
        ]);

        if (
            !($request->boolean('participates_in_njangi')
            || $request->boolean('participates_in_savings'))
        ) {
            return back()
                ->withErrors([
                    'participation' => 'Select at least one participation type.',
                ])
                ->withInput();
        }

        $member->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'],
            'rank_id' => $validated['rank_id'] ?? null,
            'status' => $validated['status'],
            'address' => $validated['address'],
            'state_code' => $validated['state_code'],
            'join_date' => $validated['join_date'],
            'next_of_kin_name' => $validated['next_of_kin_name'] ?? null,
            'next_of_kin_phone' => $validated['next_of_kin_phone'] ?? null,
            'next_of_kin_email' => $validated['next_of_kin_email'] ?? null,
            'next_of_kin_address' => $validated['next_of_kin_address'] ?? null,
            'participates_in_njangi' => $request->boolean('participates_in_njangi'),
            'participates_in_savings' => $request->boolean('participates_in_savings'),
        ]);

        $member->roles()->sync($validated['role_ids'] ?? []);

        if (!empty($validated['email']) && $request->filled('password')) {
            \App\Models\User::updateOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                    'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                ]
            );
        }

        return redirect()
            ->route('members.index')
            ->with('success', 'Member updated successfully');
    }

    public function destroy(Member $member)
    {
        $member->delete();

        return redirect()
            ->route('members.index')
            ->with('success', 'Member deleted successfully');
    }
}