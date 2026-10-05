@extends('layouts.app')

@section('content')
<x-premium-header 
    title="Manage Session Beneficiaries" 
    subtitle="Assign beneficiaries for {{ $njangiSession->title ?: 'Session #' . $njangiSession->session_number }} ({{ $njangiSession->session_date->format('Y-m-d') }})" 
    back-url="{{ route('njangi-cycles.show', $njangiSession->njangi_cycle_id) }}" 
    back-title="Back to Cycle"
/>

<div class="-mt-3">
    <x-premium-card title="Select Cycle Members">
        <form method="POST" action="{{ route('njangi-sessions.beneficiaries.update', $njangiSession) }}" class="flex flex-col gap-6">
            @csrf

            <!-- Guideline Alert Box -->
            <div class="p-3.5 bg-zinc-50 dark:bg-zinc-950/40 border border-zinc-200/40 dark:border-zinc-800/40 rounded-xl text-xs text-zinc-550 dark:text-zinc-400 flex items-start gap-3">
                <span class="p-1 bg-zinc-200/50 dark:bg-zinc-800 rounded-lg text-zinc-800 dark:text-white shrink-0">
                    <i data-lucide="info" class="w-4 h-4"></i>
                </span>
                @php
                    $minBeneficiaries = isset($appSettings) ? $appSettings->beneficiary_count : 4;
                    $beneficiaryWord = $minBeneficiaries === 1 ? 'beneficiary' : 'beneficiaries';
                @endphp
                <div class="flex flex-col gap-0.5">
                    <span class="font-bold text-zinc-800 dark:text-zinc-200">Rule Notification</span>
                    <span>Please select the beneficiaries for this session. A minimum of <strong>{{ $minBeneficiaries }} {{ $beneficiaryWord }}</strong> is required by the Njangi system to split and record play contributions correctly.</span>
                </div>
            </div>

            <x-premium-table :headers="[
                ['label' => 'Select', 'width' => 'w-16', 'align' => 'center'],
                'Member Name',
                'Member ID',
                ['label' => 'Benefit Position', 'align' => 'center']
            ]">
                @forelse ($cycleMembers as $index => $cm)
                    @php
                        $isSelected = in_array($cm->id, $currentBeneficiaryIds);
                    @endphp
                    <x-premium-table-row :is-even="$index % 2 === 1">
                        <td class="py-2.5 px-3 text-center">
                            <input 
                                type="checkbox" 
                                name="cycle_member_ids[]" 
                                value="{{ $cm->id }}" 
                                id="cm_{{ $cm->id }}"
                                {{ $isSelected ? 'checked' : '' }}
                                class="w-4 h-4 rounded text-zinc-950 border-zinc-300 focus:ring-zinc-900 cursor-pointer dark:bg-zinc-900 dark:border-zinc-800 dark:focus:ring-offset-zinc-900"
                            >
                        </td>
                        <td class="py-2.5 px-3 font-semibold text-zinc-900 dark:text-white">
                            <label for="cm_{{ $cm->id }}" class="cursor-pointer block w-full">
                                {{ $cm->member->first_name }} {{ $cm->member->last_name }}
                            </label>
                        </td>
                        <td class="py-2.5 px-3 font-mono text-zinc-550 dark:text-zinc-400">
                            /{{ $cm->member->member_code }}
                        </td>
                        <td class="py-2.5 px-3 text-center">
                            <span class="inline-block px-2 py-0.5 bg-zinc-100 dark:bg-zinc-800 rounded text-zinc-800 dark:text-zinc-200 font-bold font-mono text-xs">
                                #{{ $cm->benefit_order ?? '-' }}
                            </span>
                        </td>
                    </x-premium-table-row>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-zinc-400 dark:text-zinc-600 py-16">
                            No members added to this cycle yet.
                        </td>
                    </tr>
                @endforelse
            </x-premium-table>

            <div class="flex items-center gap-3 border-t border-zinc-100 dark:border-zinc-800/80 pt-5">
                <x-premium-button type="submit" variant="primary">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Save Beneficiaries</span>
                </x-premium-button>

                <x-premium-button type="button" variant="secondary" href="{{ route('njangi-cycles.show', $njangiSession->njangi_cycle_id) }}">
                    Cancel
                </x-premium-button>
            </div>
        </form>
    </x-premium-card>
</div>
@endsection
