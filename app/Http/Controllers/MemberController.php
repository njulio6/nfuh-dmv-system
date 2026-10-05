<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MemberRank;
use App\Models\MemberRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::with(['rank', 'roles', 'user']);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('member_code', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
                    // email is on users table now — search by joining if needed
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

    /**
     * One form → creates both User (auth) and Member (profile) in a single transaction.
     */
    public function store(Request $request, \App\Services\Member\MemberCodeGenerator $generator)
    {
        $validated = $request->validate([
            // These go to BOTH tables
            'email'                => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'             => ['required', 'confirmed', Password::defaults()],
            // Member profile fields
            'first_name'           => ['required', 'string', 'max:255'],
            'last_name'            => ['required', 'string', 'max:255'],
            'phone'                => ['required', 'string', 'max:50'],
            'rank_id'              => ['nullable', 'exists:member_ranks,id'],
            'status'               => ['required', 'string'],
            'address'              => ['required', 'string', 'max:255'],
            'state_code'           => ['required', Rule::in(['MD', 'VA', 'DC'])],
            'join_date'            => ['required', 'date'],
            'next_of_kin_name'     => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone'    => ['nullable', 'string', 'max:50'],
            'next_of_kin_email'    => ['nullable', 'email', 'max:255'],
            'next_of_kin_address'  => ['nullable', 'string'],
            'participates_in_njangi'  => ['nullable', 'boolean'],
            'participates_in_savings' => ['nullable', 'boolean'],
            'role_ids'             => ['nullable', 'array'],
            'role_ids.*'           => ['exists:member_roles,id'],
        ]);

        if (
            !($request->boolean('participates_in_njangi')
            || $request->boolean('participates_in_savings'))
        ) {
            return back()
                ->withErrors(['participation' => 'Select at least one participation type.'])
                ->withInput();
        }

        $organization = Organization::firstOrFail();

        DB::transaction(function () use ($validated, $request, $generator, $organization) {
            // 1. Create the User account first
            $user = User::create([
                'name'              => $validated['first_name'] . ' ' . $validated['last_name'],
                'email'             => $validated['email'],
                'password'          => Hash::make($validated['password']),
                'organization_id'   => $organization->id,
            ]);

            // 2. Create the Member profile and link it to the User
            $member = Member::create([
                'user_id'              => $user->id,
                'organization_id'      => $organization->id,
                'member_code'          => $generator->generate(
                    $validated['state_code'],
                    $validated['join_date']
                ),
                'first_name'           => $validated['first_name'],
                'last_name'            => $validated['last_name'],
                // email is NOT stored here — it lives only on users.email
                'phone'                => $validated['phone'],
                'rank_id'              => $validated['rank_id'] ?? null,
                'status'               => $validated['status'],
                'address'              => $validated['address'],
                'state_code'           => $validated['state_code'],
                'join_date'            => $validated['join_date'],
                'next_of_kin_name'     => $validated['next_of_kin_name'] ?? null,
                'next_of_kin_phone'    => $validated['next_of_kin_phone'] ?? null,
                'next_of_kin_email'    => $validated['next_of_kin_email'] ?? null,
                'next_of_kin_address'  => $validated['next_of_kin_address'] ?? null,
                'participates_in_njangi'  => $request->boolean('participates_in_njangi'),
                'participates_in_savings' => $request->boolean('participates_in_savings'),
            ]);

            $member->roles()->sync($validated['role_ids'] ?? []);
        });

        return redirect()
            ->route('members.index')
            ->with('success', 'Member registered and login account created successfully.');
    }

    public function show(Member $member)
    {
        $member->load(['rank', 'roles', 'user']);

        return view('members.show', compact('member'));
    }

    public function edit(Member $member)
    {
        $member->load(['roles', 'user']);

        $ranks = MemberRank::orderBy('name')->get();
        $roles = MemberRole::orderBy('name')->get();

        return view('members.edit', compact('member', 'ranks', 'roles'));
    }

    /**
     * Update member profile only.
     * Email/password changes go through account settings — not here.
     */
    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'first_name'           => ['required', 'string', 'max:255'],
            'last_name'            => ['required', 'string', 'max:255'],
            // Email updates go to users table — validate uniqueness there
            'email'                => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($member->user_id),
            ],
            'phone'                => ['required', 'string', 'max:50'],
            'rank_id'              => ['nullable', 'exists:member_ranks,id'],
            'status'               => ['required', 'string'],
            'address'              => ['required', 'string', 'max:255'],
            'state_code'           => ['required', Rule::in(['MD', 'VA', 'DC'])],
            'join_date'            => ['required', 'date'],
            'next_of_kin_name'     => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone'    => ['nullable', 'string', 'max:50'],
            'next_of_kin_email'    => ['nullable', 'email', 'max:255'],
            'next_of_kin_address'  => ['nullable', 'string'],
            'participates_in_njangi'  => ['nullable', 'boolean'],
            'participates_in_savings' => ['nullable', 'boolean'],
            'role_ids'             => ['nullable', 'array'],
            'role_ids.*'           => ['exists:member_roles,id'],
            // Password is optional on edit — blank = keep current
            'password'             => ['nullable', 'confirmed', Password::defaults()],
        ]);

        if (
            !($request->boolean('participates_in_njangi')
            || $request->boolean('participates_in_savings'))
        ) {
            return back()
                ->withErrors(['participation' => 'Select at least one participation type.'])
                ->withInput();
        }

        $member->update([
            'first_name'           => $validated['first_name'],
            'last_name'            => $validated['last_name'],
            // email is NOT updated here — email lives on users table only
            'phone'                => $validated['phone'],
            'rank_id'              => $validated['rank_id'] ?? null,
            'status'               => $validated['status'],
            'address'              => $validated['address'],
            'state_code'           => $validated['state_code'],
            'join_date'            => $validated['join_date'],
            'next_of_kin_name'     => $validated['next_of_kin_name'] ?? null,
            'next_of_kin_phone'    => $validated['next_of_kin_phone'] ?? null,
            'next_of_kin_email'    => $validated['next_of_kin_email'] ?? null,
            'next_of_kin_address'  => $validated['next_of_kin_address'] ?? null,
            'participates_in_njangi'  => $request->boolean('participates_in_njangi'),
            'participates_in_savings' => $request->boolean('participates_in_savings'),
        ]);

        $member->roles()->sync($validated['role_ids'] ?? []);

        // Update the linked user account (email + optional password)
        if ($member->user) {
            $userUpdate = ['email' => $validated['email']];

            if ($request->filled('password')) {
                $userUpdate['password'] = Hash::make($validated['password']);
            }

            $member->user->update($userUpdate);
        }

        return redirect()
            ->route('members.index')
            ->with('success', 'Member updated successfully.');
    }

    public function destroy(Member $member)
    {
        $member->delete();

        return redirect()
            ->route('members.index')
            ->with('success', 'Member deleted successfully.');
    }
}