<div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; margin-top: 16px;">
        <thead>
            <tr style="background: #f9fafb;">
                <th style="text-align: left; padding: 12px; border: 1px solid #e5e7eb;">Member ID</th>
                <th style="text-align: left; padding: 12px; border: 1px solid #e5e7eb;">Name</th>
                <th style="text-align: left; padding: 12px; border: 1px solid #e5e7eb;">Email</th>
                <th style="text-align: left; padding: 12px; border: 1px solid #e5e7eb;">Phone</th>
                <th style="text-align: left; padding: 12px; border: 1px solid #e5e7eb;">Rank</th>
                <th style="text-align: left; padding: 12px; border: 1px solid #e5e7eb;">Roles</th>
                <th style="text-align: left; padding: 12px; border: 1px solid #e5e7eb;">Status</th>
                <th style="text-align: left; padding: 12px; border: 1px solid #e5e7eb;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($members as $member)
                <tr>
                    <td style="padding: 12px; border: 1px solid #e5e7eb;">{{ $member->member_code }}</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb;">
                        {{ $member->first_name }} {{ $member->last_name }}
                    </td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb;">{{ $member->email }}</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb;">{{ $member->phone }}</td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb;">
                        {{ $member->rank->name ?? '-' }}
                    </td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb;">
                    @forelse ($member->roles as $role)
                        <span style="
                            display: inline-block;
                            background: #eff6ff;
                            color: #1d4ed8;
                            padding: 4px 8px;
                            border-radius: 9999px;
                            font-size: 12px;
                            margin: 2px 4px 2px 0;
                        ">
                            {{ $role->name }}
                        </span>
                    @empty
                        <span style="color: #6b7280;">None</span>
                    @endforelse
                    </td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb;">
                        <span style="
                            display: inline-block;
                            background: {{ $member->status === 'active' ? '#dcfce7' : '#fee2e2' }};
                            color: {{ $member->status === 'active' ? '#166534' : '#991b1b' }};
                            padding: 4px 10px;
                            border-radius: 9999px;
                            font-size: 12px;
                            font-weight: 600;
                        ">
                            {{ ucfirst($member->status) }}
                        </span>
                    </td>
                    <td style="padding: 12px; border: 1px solid #e5e7eb; white-space: nowrap;">
                        <a href="{{ route('members.show', $member) }}" style="margin-right: 10px;">View</a>
                        <a href="{{ route('members.edit', $member) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="padding: 16px; text-align: center; border: 1px solid #e5e7eb; color: #6b7280;">
                        No members found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>