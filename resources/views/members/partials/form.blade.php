<!-- Form content -->
<div class="flex flex-col gap-6 w-full">

    <!-- Validation Errors Alert Block -->
    @if ($errors->any() || $errors->has('participation'))
        <div class="p-3.5 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800/60 rounded-xl text-red-800 dark:text-red-400 text-xs font-semibold flex flex-col gap-1.5 mb-2 select-none">
            <div class="flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-red-600 dark:text-red-550 shrink-0"></i>
                <span class="font-bold">Please correct the following errors:</span>
            </div>
            <ul class="list-disc list-inside pl-1 text-[11px] font-medium flex flex-col gap-0.5 mt-1 text-red-700 dark:text-red-400/90">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @if ($errors->has('participation'))
                    <li>{{ $errors->first('participation') }}</li>
                @endif
            </ul>
        </div>
    @endif

    <!-- Card 1: Personal Information -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 md:p-6 flex flex-col gap-5 shadow-none">
        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white select-none">
            Personal Information
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- First Name -->
            <div class="flex flex-col">
                <label for="first_name" class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">First Name <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="text" name="first_name" id="first_name"
                        value="{{ old('first_name', $member->first_name ?? '') }}" 
                        placeholder="e.g. John"
                        class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all"
                        required
                    >
                </div>
            </div>

            <!-- Last Name -->
            <div class="flex flex-col">
                <label for="last_name" class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Last Name <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="text" name="last_name" id="last_name"
                        value="{{ old('last_name', $member->last_name ?? '') }}" 
                        placeholder="e.g. Doe"
                        class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all"
                        required
                    >
                </div>
            </div>

            <!-- Email -->
            <div class="flex flex-col">
                <label for="email" class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Email Address</label>
                <div class="relative">
                    <input type="email" name="email" id="email"
                        value="{{ old('email', $member->email ?? '') }}" 
                        placeholder="e.g. john@example.com"
                        class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all"
                    >
                </div>
            </div>

            <!-- Phone -->
            <div class="flex flex-col">
                <label for="phone" class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Phone Number <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="text" name="phone" id="phone"
                        value="{{ old('phone', $member->phone ?? '') }}" 
                        placeholder="e.g. (240) 555-0199"
                        class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all"
                        required
                    >
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Organization Profile -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 md:p-6 flex flex-col gap-5 shadow-none">
        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white select-none">
            Organization Profile
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Rank / Traditional Title Dropdown -->
            <div class="flex flex-col relative select-none" x-data="customSelect({
                value: '{{ old('rank_id', $member->rank_id ?? '') }}',
                defaultLabel: 'Warrior (Default)',
                options: [
                    { value: '', label: 'Warrior (Default)' },
                    @foreach ($ranks as $rank)
                        { value: '{{ $rank->id }}', label: '{{ addslashes($rank->name) }}' },
                    @endforeach
                ]
            })" @click.outside="close()">
                <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Traditional Title</label>
                <input type="hidden" name="rank_id" x-ref="hiddenInput" :value="value">
                
                <div class="relative">
                    <button 
                        type="button"
                        @click="toggle()"
                        @keydown.down.prevent="focusNext()"
                        @keydown.up.prevent="focusPrev()"
                        @keydown.enter.prevent="selectActive()"
                        @keydown.escape.prevent="close()"
                        class="w-full flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left select-none"
                        :class="open ? 'border-zinc-950 dark:border-zinc-50' : ''"
                    >
                        <span x-text="label"></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180 text-zinc-900 dark:text-white' : ''"></i>
                    </button>
                </div>
                
                <div 
                    x-show="open" 
                    x-cloak
                    x-ref="optionsList"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute top-[100%] left-0 right-0 mt-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl z-50 p-1 flex flex-col gap-0.5 max-h-48 overflow-y-auto"
                >
                    <template x-for="(opt, idx) in options" :key="opt.value">
                        <button 
                            type="button" 
                            @click="select(opt.value)"
                            @mouseenter="activeIndex = idx"
                            class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-colors font-semibold text-left"
                            :class="{
                                'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white': activeIndex === idx || value === opt.value,
                                'text-zinc-700 dark:text-zinc-300': activeIndex !== idx && value !== opt.value
                            }"
                        >
                            <span x-text="opt.label"></span>
                            <span x-show="value === opt.value" class="text-zinc-900 dark:text-white shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Status Dropdown -->
            <div class="flex flex-col relative select-none" x-data="customSelect({
                value: '{{ old('status', $member->status ?? 'active') }}',
                defaultLabel: 'Active',
                options: [
                    { value: 'active', label: 'Active' },
                    { value: 'inactive', label: 'Inactive' },
                    { value: 'suspended', label: 'Suspended' }
                ]
            })" @click.outside="close()">
                <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Account Status <span class="text-red-500">*</span></label>
                <input type="hidden" name="status" x-ref="hiddenInput" :value="value">
                
                <div class="relative">
                    <button 
                        type="button"
                        @click="toggle()"
                        @keydown.down.prevent="focusNext()"
                        @keydown.up.prevent="focusPrev()"
                        @keydown.enter.prevent="selectActive()"
                        @keydown.escape.prevent="close()"
                        class="w-full flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left select-none"
                        :class="open ? 'border-zinc-950 dark:border-zinc-50' : ''"
                    >
                        <span x-text="label"></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180 text-zinc-900 dark:text-white' : ''"></i>
                    </button>
                </div>
                
                <div 
                    x-show="open" 
                    x-cloak
                    x-ref="optionsList"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute top-[100%] left-0 right-0 mt-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl z-50 p-1 flex flex-col gap-0.5"
                >
                    <template x-for="(opt, idx) in options" :key="opt.value">
                        <button 
                            type="button" 
                            @click="select(opt.value)"
                            @mouseenter="activeIndex = idx"
                            class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-colors font-semibold text-left"
                            :class="{
                                'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white': activeIndex === idx || value === opt.value,
                                'text-zinc-700 dark:text-zinc-300': activeIndex !== idx && value !== opt.value
                            }"
                        >
                            <span x-text="opt.label"></span>
                            <span x-show="value === opt.value" class="text-zinc-900 dark:text-white shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Join Date Calendar Component -->
            <x-premium-datepicker 
                label="Join Date" 
                name="join_date" 
                required="true"
                value="{{ old('join_date', isset($member->join_date) ? \Illuminate\Support\Carbon::parse($member->join_date)->format('Y-m-d') : '') }}" 
            />
        </div>
    </div>

    <!-- Card 3: Location Details -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 md:p-6 flex flex-col gap-5 shadow-none">
        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white select-none">
            Location Details
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Address -->
            <div class="flex flex-col md:col-span-3">
                <label for="address" class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Street Address <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="text" name="address" id="address"
                        value="{{ old('address', $member->address ?? '') }}" 
                        placeholder="e.g. 1200 Constitution Ave NW"
                        class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all"
                        required
                    >
                </div>
            </div>

            <!-- State Code Dropdown -->
            <div class="flex flex-col relative select-none" x-data="customSelect({
                value: '{{ old('state_code', $member->state_code ?? '') }}',
                defaultLabel: 'Select State',
                options: [
                    { value: 'MD', label: 'Maryland (MD)' },
                    { value: 'VA', label: 'Virginia (VA)' },
                    { value: 'DC', label: 'DC (DC)' }
                ]
            })" @click.outside="close()">
                <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">State <span class="text-red-500">*</span></label>
                <input type="hidden" name="state_code" x-ref="hiddenInput" :value="value" required>
                
                <div class="relative">
                    <button 
                        type="button"
                        @click="toggle()"
                        @keydown.down.prevent="focusNext()"
                        @keydown.up.prevent="focusPrev()"
                        @keydown.enter.prevent="selectActive()"
                        @keydown.escape.prevent="close()"
                        class="w-full flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left select-none"
                        :class="open ? 'border-zinc-950 dark:border-zinc-50' : ''"
                    >
                        <span x-text="label"></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180 text-zinc-900 dark:text-white' : ''"></i>
                    </button>
                </div>
                
                <div 
                    x-show="open" 
                    x-cloak
                    x-ref="optionsList"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute top-[100%] left-0 right-0 mt-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-xl z-50 p-1 flex flex-col gap-0.5"
                >
                    <template x-for="(opt, idx) in options" :key="opt.value">
                        <button 
                            type="button" 
                            @click="select(opt.value)"
                            @mouseenter="activeIndex = idx"
                            class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs transition-colors font-semibold text-left"
                            :class="{
                                'bg-zinc-100 dark:bg-zinc-800 text-zinc-950 dark:text-white': activeIndex === idx || value === opt.value,
                                'text-zinc-700 dark:text-zinc-300': activeIndex !== idx && value !== opt.value
                            }"
                        >
                            <span x-text="opt.label"></span>
                            <span x-show="value === opt.value" class="text-zinc-900 dark:text-white shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            </span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Emergency & Next of Kin Information -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 md:p-6 flex flex-col gap-5 shadow-none">
        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white select-none">
            Emergency & Next of Kin Information
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Next of Kin Name -->
            <div class="flex flex-col">
                <label for="next_of_kin_name" class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Next of Kin Name</label>
                <div class="relative">
                    <input type="text" name="next_of_kin_name" id="next_of_kin_name"
                        value="{{ old('next_of_kin_name', $member->next_of_kin_name ?? '') }}" 
                        placeholder="e.g. Mary Doe"
                        class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all"
                    >
                </div>
            </div>

            <!-- Next of Kin Phone -->
            <div class="flex flex-col">
                <label for="next_of_kin_phone" class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Next of Kin Phone</label>
                <div class="relative">
                    <input type="text" name="next_of_kin_phone" id="next_of_kin_phone"
                        value="{{ old('next_of_kin_phone', $member->next_of_kin_phone ?? '') }}" 
                        placeholder="e.g. (240) 555-0188"
                        class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all"
                    >
                </div>
            </div>

            <!-- Next of Kin Email -->
            <div class="flex flex-col">
                <label for="next_of_kin_email" class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Next of Kin Email</label>
                <div class="relative">
                    <input type="email" name="next_of_kin_email" id="next_of_kin_email"
                        value="{{ old('next_of_kin_email', $member->next_of_kin_email ?? '') }}" 
                        placeholder="e.g. mary@example.com"
                        class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all"
                    >
                </div>
            </div>

            <!-- Next of Kin Address -->
            <div class="flex flex-col md:col-span-3">
                <label for="next_of_kin_address" class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Next of Kin Address</label>
                <div class="relative">
                    <textarea name="next_of_kin_address" id="next_of_kin_address" rows="2" placeholder="e.g. 1400 Constitution Ave NW, Washington, DC" 
                        class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all resize-none"
                    >{{ old('next_of_kin_address', $member->next_of_kin_address ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 5: Administrative & Board Roles -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 md:p-6 flex flex-col gap-5 shadow-none">
        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white select-none">
            Administrative & Board Roles
        </h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            @foreach ($roles as $role)
                <label 
                    x-data="{ checked: {{ in_array($role->id, old('role_ids', isset($member) ? $member->roles->pluck('id')->toArray() : [])) ? 'true' : 'false' }} }"
                    class="group relative flex items-center justify-between p-3.5 rounded-xl border cursor-pointer select-none transition-all duration-200"
                    :class="checked ? 'border-zinc-900 bg-zinc-50/40 dark:border-zinc-100 dark:bg-zinc-900/40' : 'border-zinc-200 dark:border-zinc-800/80 bg-zinc-50/10 dark:bg-zinc-950/10 hover:bg-zinc-100/40 dark:hover:bg-zinc-900/30'"
                >
                    <div class="flex items-center gap-3">
                        <input
                            type="checkbox"
                            name="role_ids[]"
                            value="{{ $role->id }}"
                            @change="checked = $el.checked"
                            class="rounded border-zinc-300 dark:border-zinc-800 text-zinc-950 focus:ring-0 focus:ring-offset-0 size-4 dark:bg-zinc-950 transition-colors cursor-pointer"
                            {{ in_array($role->id, old('role_ids', isset($member) ? $member->roles->pluck('id')->toArray() : [])) ? 'checked' : '' }}
                        >
                        <span class="text-xs font-bold leading-none transition-colors" :class="checked ? 'text-zinc-950 dark:text-white' : 'text-zinc-700 dark:text-zinc-300'">
                            {{ $role->name }}
                        </span>
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    <!-- Card 6: Participation & Active Programs -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 md:p-6 flex flex-col gap-5 shadow-none">
        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white select-none">
            Participation & Active Programs
        </h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- Program 1: Njangi -->
            <label 
                x-data="{ checked: {{ old('participates_in_njangi', $member->participates_in_njangi ?? false) ? 'true' : 'false' }} }"
                class="group relative flex items-center justify-between p-3.5 rounded-xl border cursor-pointer select-none transition-all duration-200"
                :class="checked ? 'border-zinc-900 bg-zinc-50/40 dark:border-zinc-100 dark:bg-zinc-900/40' : 'border-zinc-200 dark:border-zinc-800/80 bg-zinc-50/10 dark:bg-zinc-950/10 hover:bg-zinc-100/40 dark:hover:bg-zinc-900/30'"
            >
                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        name="participates_in_njangi"
                        value="1"
                        @change="checked = $el.checked"
                        class="rounded border-zinc-300 dark:border-zinc-800 text-zinc-950 focus:ring-0 focus:ring-offset-0 size-4 dark:bg-zinc-950 transition-colors cursor-pointer"
                        {{ old('participates_in_njangi', $member->participates_in_njangi ?? false) ? 'checked' : '' }}
                    >
                    <span class="text-xs font-bold leading-none transition-colors" :class="checked ? 'text-zinc-950 dark:text-white' : 'text-zinc-700 dark:text-zinc-300'">
                        Njangi Rotations
                    </span>
                </div>
            </label>

            <!-- Program 2: Savings -->
            <label 
                x-data="{ checked: {{ old('participates_in_savings', $member->participates_in_savings ?? false) ? 'true' : 'false' }} }"
                class="group relative flex items-center justify-between p-3.5 rounded-xl border cursor-pointer select-none transition-all duration-200"
                :class="checked ? 'border-zinc-900 bg-zinc-50/40 dark:border-zinc-100 dark:bg-zinc-900/40' : 'border-zinc-200 dark:border-zinc-800/80 bg-zinc-50/10 dark:bg-zinc-950/10 hover:bg-zinc-100/40 dark:hover:bg-zinc-900/30'"
            >
                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        name="participates_in_savings"
                        value="1"
                        @change="checked = $el.checked"
                        class="rounded border-zinc-300 dark:border-zinc-800 text-zinc-950 focus:ring-0 focus:ring-offset-0 size-4 dark:bg-zinc-950 transition-colors cursor-pointer"
                        {{ old('participates_in_savings', $member->participates_in_savings ?? false) ? 'checked' : '' }}
                    >
                    <span class="text-xs font-bold leading-none transition-colors" :class="checked ? 'text-zinc-950 dark:text-white' : 'text-zinc-700 dark:text-zinc-300'">
                        Savings Account
                    </span>
                </div>
            </label>
        </div>
    </div>

    <!-- Card 7: Account Password -->
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800/80 rounded-2xl p-5 md:p-6 flex flex-col gap-5 shadow-none">
        <h3 class="text-xs font-bold uppercase tracking-wider text-zinc-900 dark:text-white select-none">
            Account Password
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Password -->
            <div class="flex flex-col">
                <label for="password" class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">
                    Password {{ isset($member) ? '(Leave blank to keep current)' : '(Optional)' }}
                </label>
                <div class="relative">
                    <input type="password" name="password" id="password"
                        placeholder="••••••••"
                        class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all"
                    >
                </div>
            </div>

            <!-- Password Confirmation -->
            <div class="flex flex-col">
                <label for="password_confirmation" class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">
                    Confirm Password
                </label>
                <div class="relative">
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        placeholder="••••••••"
                        class="w-full bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 focus:bg-white dark:focus:bg-zinc-900 text-zinc-800 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all"
                    >
                </div>
            </div>
        </div>
    </div>

</div>
