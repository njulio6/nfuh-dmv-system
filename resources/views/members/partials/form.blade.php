<style>
    input, select, textarea {
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

    .checkbox-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 8px;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f9fafb;
    }

    .checkbox-item input[type="checkbox"] {
        width: auto;
        margin: 0;
    }

    .error-text {
        color: red;
        font-size: 14px;
        margin-top: 6px;
    }
</style>

<div style="max-width: 700px;">

    @if ($errors->any())
        <div class="mb-3" style="padding: 12px; border: 1px solid #f5c2c7; background: #f8d7da; border-radius: 8px;">
            <strong>Please fix the following errors:</strong>
            <ul style="margin: 8px 0 0 18px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
            value="{{ old('phone', $member->phone ?? '') }}" required>
    </div>

    <div class="mb-3">
        <label for="rank_id">Title</label>
        <select name="rank_id" id="rank_id">
            <option value="">Warrior</option>
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
                    <label class="role-item" style="margin: 0; display: flex; align-items: center; gap: 10px;">
                        <input
                            type="checkbox"
                            name="role_ids[]"
                            value="{{ $role->id }}"
                            style="margin: 0; width: auto;"
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
            value="{{ old('address', $member->address ?? '') }}" required>
    </div>

    <div class="mb-3">
        <label for="state_code">State</label>
        <select name="state_code" id="state_code" required>
            <option value="">Select State</option>
            <option value="MD" {{ old('state_code', $member->state_code ?? '') == 'MD' ? 'selected' : '' }}>Maryland (MD)</option>
            <option value="VA" {{ old('state_code', $member->state_code ?? '') == 'VA' ? 'selected' : '' }}>Virginia (VA)</option>
            <option value="DC" {{ old('state_code', $member->state_code ?? '') == 'DC' ? 'selected' : '' }}>District of Columbia (DC)</option>
        </select>
    </div>

    <div class="mb-3">
        <label for="join_date">Join Date</label>
        <input type="date" name="join_date" id="join_date"
            value="{{ old('join_date', isset($member->join_date) ? \Illuminate\Support\Carbon::parse($member->join_date)->format('Y-m-d') : '') }}" required>
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

    <div class="mb-3">
        <label for="next_of_kin_email">Next of Kin Email</label>
        <input type="email" name="next_of_kin_email" id="next_of_kin_email"
            value="{{ old('next_of_kin_email', $member->next_of_kin_email ?? '') }}">
    </div>

    <div class="mb-3">
        <label for="next_of_kin_address">Next of Kin Address</label>
        <textarea name="next_of_kin_address" id="next_of_kin_address" rows="3">{{ old('next_of_kin_address', $member->next_of_kin_address ?? '') }}</textarea>
    </div>

    <div class="mb-3">
        <label>Participation Type</label>

        <div class="checkbox-group">
            <label class="checkbox-item">
                <input
                    type="checkbox"
                    name="participates_in_njangi"
                    value="1"
                    {{ old('participates_in_njangi', $member->participates_in_njangi ?? false) ? 'checked' : '' }}
                >
                <span>Participates in Njangi</span>
            </label>

            <label class="checkbox-item">
                <input
                    type="checkbox"
                    name="participates_in_savings"
                    value="1"
                    {{ old('participates_in_savings', $member->participates_in_savings ?? false) ? 'checked' : '' }}
                >
                <span>Participates in Savings</span>
            </label>

            <label class="checkbox-item">
                <input
                    type="checkbox"
                    name="participates_in_cultural"
                    value="1"
                    {{ old('participates_in_cultural', $member->participates_in_cultural ?? false) ? 'checked' : '' }}
                >
                <span>Cultural Association Member</span>
            </label>
        </div>

        @error('participation')
            <div class="error-text">{{ $message }}</div>
        @enderror
    </div>

</div>