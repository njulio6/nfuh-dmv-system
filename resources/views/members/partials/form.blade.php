<style>
    input, select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-top: 4px;
    }

    label {
        font-weight: 600;
    }

    .mb-3 {
        margin-bottom: 16px;
    }
</style>

<div style="max-width: 700px;">

    <div class="mb-3">
        <label for="first_name">First Name</label>
        <input type="text" name="first_name" id="first_name"
            value="{{ old('first_name', $member->first_name ?? '') }}" required>
    </div>

    <div class="mb-3">
        <label for="last_name">Last Name</label>
        <input type="text" name="last_name" id="last_name"
            value="{{ old('last_name', $member->last_name ?? '') }}" required>
    </div>

    <div class="mb-3">
        <label for="email">Email</label>
        <input type="email" name="email" id="email"
            value="{{ old('email', $member->email ?? '') }}">
    </div>

    <div class="mb-3">
        <label for="phone">Phone</label>
        <input type="text" name="phone" id="phone"
            value="{{ old('phone', $member->phone ?? '') }}">
    </div>

    <div class="mb-3">
        <label for="rank_id">Rank</label>
        <select name="rank_id" id="rank_id" required>
            <option value="">Select Rank</option>
            @foreach ($ranks as $rank)
                <option value="{{ $rank->id }}"
                    {{ old('rank_id', $member->rank_id ?? '') == $rank->id ? 'selected' : '' }}>
                    {{ $rank->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="mb-3">
        <label>Roles</label>

        <div style="
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        ">
            @foreach ($roles as $role)
                <div style="
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    padding: 12px;
                    background: #f9fafb;
                ">
                    <label class="role-item" style="margin: 0;">
                        <input
                            type="checkbox"
                            name="role_ids[]"
                            value="{{ $role->id }}"
                            style="margin: 0;"
                            {{ in_array($role->id, old('role_ids', isset($member) ? $member->roles->pluck('id')->toArray() : [])) ? 'checked' : '' }}
                        >
                        <span style="font-weight: 500;">
                            {{ $role->name }}
                        </span>
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mb-3">
        <label for="status">Status</label>
        <select name="status" id="status" required>
            <option value="active" {{ old('status', $member->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status', $member->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="suspended" {{ old('status', $member->status ?? '') == 'suspended' ? 'selected' : '' }}>Suspended</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="address">Address</label>
        <input type="text" name="address" id="address"
            value="{{ old('address', $member->address ?? '') }}">
    </div>

    <div class="mb-3">
        <label for="next_of_kin_name">Next of Kin Name</label>
        <input type="text" name="next_of_kin_name" id="next_of_kin_name"
            value="{{ old('next_of_kin_name', $member->next_of_kin_name ?? '') }}">
    </div>

    <div class="mb-3">
        <label for="next_of_kin_phone">Next of Kin Phone</label>
        <input type="text" name="next_of_kin_phone" id="next_of_kin_phone"
            value="{{ old('next_of_kin_phone', $member->next_of_kin_phone ?? '') }}">
    </div>

</div>