@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto space-y-8 pb-16">

    <!-- Header Banner -->
    <div class="relative overflow-hidden bg-gradient-to-br from-zinc-900 via-zinc-950 to-zinc-900 border border-zinc-800 rounded-3xl p-6 sm:p-8 text-white shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold tracking-wider uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    Demonstration & UAT Reset Tool
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight font-display text-white">
                    Clean-Slate Database Wipe & Comprehensive Seeder
                </h1>
                <p class="text-zinc-400 text-sm max-w-2xl leading-relaxed">
                    Safely wipes all current transactional records and historical test data to avoid state collisions, then provisions a rich, realistic, interconnected dataset across every module and every status.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-zinc-800 hover:bg-zinc-700 text-zinc-200 border border-zinc-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.tools') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-semibold bg-brand-primary hover:bg-brand-primary/90 text-white shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    System Tools
                </a>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 flex items-start gap-3 shadow-xs">
            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <h4 class="font-bold text-base">{{ session('success') }}</h4>
                <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-1">All tables were cleaned and rebuilt with fresh interconnected data. Review the credentials and modules below.</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-5 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-red-900 dark:text-red-200 flex items-start gap-3 shadow-xs">
            <svg class="w-6 h-6 text-red-600 dark:text-red-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
                <h4 class="font-bold text-base">{{ session('error') }}</h4>
            </div>
        </div>
    @endif

    <!-- Data Specification Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Members & Roles Card -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 space-y-3">
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <h3 class="font-bold text-zinc-900 dark:text-white text-base">30 Members & Roles</h3>
            <p class="text-xs text-zinc-500 leading-relaxed">
                1 Admin, 1 Treasurer, 1 Financial Secretary, 1 General Secretary, 2 Loan Committee, 1 Loan Officer, 1 Lead Nformi, plus 22 General Members.
            </p>
            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                Password: <code class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-brand-primary font-mono font-bold">Password123!</code>
            </div>
        </div>

        <!-- Njangi Cycles Card -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 space-y-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <h3 class="font-bold text-zinc-900 dark:text-white text-base">Njangi Assemblies</h3>
            <p class="text-xs text-zinc-500 leading-relaxed">
                Active 2026 Cycle with 10 past completed sessions + Nov 7 upcoming open session + 2 upcoming 2027 cycles. All submission statuses seeded.
            </p>
            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                Next Session: <span class="text-emerald-600 dark:text-emerald-400 font-bold">Sat, Nov 7, 2026</span>
            </div>
        </div>

        <!-- Savings Card -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 space-y-3">
            <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="font-bold text-zinc-900 dark:text-white text-base">Savings & Audit Trail</h3>
            <p class="text-xs text-zinc-500 leading-relaxed">
                Every member has deposits with previous and new balances. 15 documented adjustments, 90+ transactions, plus deposit & withdrawal requests across all statuses (15+ per status).
            </p>
            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                Statuses: <span class="text-purple-600 dark:text-purple-400 font-bold">Pending, Disbursed, Rejected, Approved</span>
            </div>
        </div>

        <!-- Loans Card -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 space-y-3">
            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="font-bold text-zinc-900 dark:text-white text-base">Loans & Guarantors</h3>
            <p class="text-xs text-zinc-500 leading-relaxed">
                15 Pending Guarantors, 15 Committee Review, 15 Approved, 15 Active, 15 Completed, 15 Defaulted, 15 Rejected loans (105 loans total) with complete guarantor records.
            </p>
            <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800/80 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                Coverage: <span class="text-amber-600 dark:text-amber-400 font-bold">All 7 Status Queues Covered (15 each)</span>
            </div>
        </div>
    </div>

    <!-- Security Confirmation Box -->
    <div class="bg-white dark:bg-zinc-900 border-2 border-red-200 dark:border-red-900/60 rounded-3xl p-6 sm:p-8 space-y-6 shadow-sm">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </div>
            <div class="space-y-1">
                <h3 class="text-lg font-bold text-zinc-950 dark:text-white">
                    Execute Clean Database Wipe & Re-seed
                </h3>
                <p class="text-xs text-zinc-500 leading-relaxed">
                    This irreversible operation truncates all database tables, removes conflicting historical receipts, resets foreign keys, and freshly provisions the complete demonstration dataset.
                </p>
            </div>
        </div>

        <form action="{{ route('system.demonstration-wipe.execute', ['open' => 'KhulJaChimChim']) }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="open" value="KhulJaChimChim">
            <div>
                <label for="secret_code" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-2">
                    Enter Secret Security Confirmation Code:
                </label>
                <div class="relative">
                    <input 
                        type="password" 
                        name="secret_code" 
                        id="secret_code" 
                        required 
                        autocomplete="off"
                        placeholder="Enter secret confirmation code..." 
                        class="w-full px-4 py-3 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-white text-sm font-mono focus:ring-2 focus:ring-red-500 focus:border-red-500 transition"
                    >
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <button 
                    type="submit" 
                    onclick="return confirm('WARNING: Are you sure you want to permanently wipe the database and re-seed all demonstration records?')"
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-bold bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-600/20 transition cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Wipe Database & Seed Fresh Data
                </button>
                <span class="text-xs text-zinc-400">Execution takes ~2 seconds</span>
            </div>
        </form>
    </div>

    <!-- Seeded Accounts & Credentials Directory Table -->
    @if(isset($seededMembers) && $seededMembers->isNotEmpty())
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-3xl p-6 sm:p-8 space-y-5 shadow-xs">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-zinc-950 dark:text-white">Demonstration User Accounts & Credentials</h3>
                    <p class="text-xs text-zinc-500">All {{ $seededMembers->count() }} accounts use the standard password: <code class="font-bold text-brand-primary">Password123!</code></p>
                </div>
                <div class="text-xs px-3 py-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 font-medium">
                    Total Active Members: <span class="font-bold text-brand-primary">{{ $seededMembers->count() }}</span>
                </div>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-800">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-zinc-500 uppercase tracking-wider font-semibold border-b border-zinc-200 dark:border-zinc-800">
                        <tr>
                            <th class="py-3 px-4">Member</th>
                            <th class="py-3 px-4">Login Email</th>
                            <th class="py-3 px-4">System Role</th>
                            <th class="py-3 px-4">Traditional Title</th>
                            <th class="py-3 px-4">Member Code</th>
                            <th class="py-3 px-4 text-right">Quick Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60 font-medium text-zinc-800 dark:text-zinc-200">
                        @foreach($seededMembers as $m)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-zinc-900 dark:text-white">{{ $m->name }}</div>
                                    <div class="text-[11px] text-zinc-400">{{ $m->phone }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <code class="px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 font-mono text-xs select-all">
                                        {{ $m->user?->email }}
                                    </code>
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $primaryRole = $m->user?->roles->first()?->name ?? 'member';
                                        $isOfficer = !in_array(strtolower($primaryRole), ['member', '']);
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $isOfficer ? 'bg-brand-primary/10 text-brand-primary border border-brand-primary/20' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                        {{ $m->user?->officer_role_name ?? 'Member' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    @if($m->rank)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                            {{ $m->rank->name }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400 text-[11px]">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-zinc-500">
                                    {{ $m->member_code }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a href="{{ route('members.show', $m) }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-brand-primary hover:underline">
                                        View Profile
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

</div>
@endsection
