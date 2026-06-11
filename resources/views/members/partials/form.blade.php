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
            <div class="flex flex-col relative select-none" x-data="datepicker({
                name: 'join_date',
                value: '{{ old('join_date', isset($member->join_date) ? \Illuminate\Support\Carbon::parse($member->join_date)->format('Y-m-d') : '') }}',
                required: true
            })" @click.outside="open = false; monthOpen = false; yearOpen = false">
                <label class="text-[10px] font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 select-none">Join Date <span class="text-red-500">*</span></label>
                <input type="hidden" :name="name" x-ref="hiddenInput" :value="value">
                
                <div class="relative">
                    <button 
                        type="button"
                        @click="open = !open"
                        @keydown.escape.prevent="open = false"
                        class="w-full flex items-center justify-between bg-zinc-50/40 dark:bg-zinc-950/20 hover:bg-zinc-100/30 dark:hover:bg-zinc-900/30 text-zinc-800 dark:text-white px-4 py-3 rounded-xl border border-zinc-200 dark:border-zinc-800 text-sm font-medium focus:outline-none focus:border-zinc-950 dark:focus:border-zinc-50 transition-all cursor-pointer text-left select-none"
                        :class="open ? 'border-zinc-950 dark:border-zinc-50' : ''"
                    >
                        <span x-text="displayValue"></span>
                        <i data-lucide="chevron-down" class="w-4 h-4 text-zinc-400 transition-transform duration-200 shrink-0" :class="open ? 'rotate-180 text-zinc-900 dark:text-white' : ''"></i>
                    </button>
                </div>

                <!-- Calendar Popover Panel -->
                <div 
                    x-show="open" 
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute top-[100%] left-0 mt-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl z-50 p-4 flex flex-col gap-3 w-72 shadow-none"
                >
                    <!-- Header month/year picker -->
                    <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
                        <button type="button" @click="prevMonth(); monthOpen = false; yearOpen = false" class="p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        </button>
                        
                        <div class="flex items-center gap-1.5 font-bold text-xs">
                            <!-- Custom Month Dropdown -->
                            <div class="relative select-none">
                                <button type="button" @click.stop="monthOpen = !monthOpen; yearOpen = false" class="flex items-center gap-1 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 px-2 py-1 rounded-lg transition-colors cursor-pointer text-xs font-bold text-zinc-800 dark:text-white">
                                    <span x-text="months[month]"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 dark:text-zinc-500 transition-transform" :class="monthOpen ? 'rotate-180 text-zinc-900 dark:text-white' : ''"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                                
                                <div 
                                    x-show="monthOpen" 
                                    x-cloak
                                    @click.outside="monthOpen = false"
                                    class="absolute top-[100%] left-0 mt-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-lg z-[60] p-1 flex flex-col gap-0.5 max-h-48 overflow-y-auto w-32"
                                >
                                    <template x-for="(m, index) in months" :key="index">
                                        <button 
                                            type="button" 
                                            @click="month = index; generateCalendar(); monthOpen = false"
                                            class="w-full px-2.5 py-1.5 rounded-lg text-[11px] transition-colors font-semibold text-left"
                                            :class="month === index ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50'"
                                        >
                                            <span x-text="m"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            
                            <span class="text-zinc-300 dark:text-zinc-600 font-bold select-none">/</span>
                            
                            <!-- Custom Year Dropdown -->
                            <div class="relative select-none">
                                <button type="button" @click.stop="yearOpen = !yearOpen; monthOpen = false" class="flex items-center gap-1 hover:bg-zinc-100 dark:hover:bg-zinc-800/80 px-2 py-1 rounded-lg transition-colors cursor-pointer text-xs font-bold text-zinc-800 dark:text-white">
                                    <span x-text="year"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400 dark:text-zinc-500 transition-transform" :class="yearOpen ? 'rotate-180 text-zinc-900 dark:text-white' : ''"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </button>
                                
                                <div 
                                    x-show="yearOpen" 
                                    x-cloak
                                    @click.outside="yearOpen = false"
                                    class="absolute top-[100%] left-0 mt-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-lg z-[60] p-1 flex flex-col gap-0.5 max-h-48 overflow-y-auto w-24"
                                >
                                    <template x-for="y in getYears()" :key="y">
                                        <button 
                                            type="button" 
                                            @click="year = y; generateCalendar(); yearOpen = false"
                                            class="w-full px-2.5 py-1.5 rounded-lg text-[11px] transition-colors font-semibold text-left"
                                            :class="year === y ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-800/50'"
                                        >
                                            <span x-text="y"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="nextMonth(); monthOpen = false; yearOpen = false" class="p-1.5 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-600 dark:text-zinc-400 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </button>
                    </div>

                    <!-- Weekdays -->
                    <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 py-1 select-none">
                        <div>Su</div><div>Mo</div><div>Tu</div><div>We</div><div>Th</div><div>Fr</div><div>Sa</div>
                    </div>

                    <!-- Days Grid -->
                    <div class="grid grid-cols-7 gap-1 text-center">
                        <template x-for="(dayObj, index) in days" :key="index">
                            <button 
                                type="button"
                                @click="selectDate(dayObj)"
                                class="h-8 w-8 rounded-lg text-xs font-semibold flex items-center justify-center transition-colors cursor-pointer"
                                :class="{
                                    'bg-zinc-950 dark:bg-zinc-50 text-white dark:text-zinc-950 font-bold': isSelected(dayObj),
                                    'text-zinc-300 dark:text-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-800/50': !dayObj.currentMonth && !isSelected(dayObj),
                                    'text-zinc-800 dark:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800': dayObj.currentMonth && !isSelected(dayObj),
                                    'border border-zinc-200 dark:border-zinc-800': isToday(dayObj) && !isSelected(dayObj)
                                }"
                                x-text="dayObj.day"
                            ></button>
                        </template>
                    </div>
                </div>
            </div>
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

<!-- Alpine Registration Scripts for Premium Datepicker and Dropdowns -->
<script>
    (function() {
        function registerAlpineComponents() {
            if (window.AlpineCustomSelectRegistered) return;
            window.AlpineCustomSelectRegistered = true;

            // Custom Select component
            Alpine.data('customSelect', (config) => ({
                open: false,
                value: config.value || '',
                label: config.label || '',
                options: config.options || [],
                activeIndex: -1,

                init() {
                    if (!this.label) {
                        const opt = this.options.find(o => String(o.value) === String(this.value));
                        if (opt) {
                            this.label = opt.label;
                        } else if (this.value === '') {
                            this.label = config.defaultLabel || 'Select Option';
                        }
                    }
                    this.$watch('value', (val) => {
                        const opt = this.options.find(o => String(o.value) === String(val));
                        if (opt) {
                            this.label = opt.label;
                        } else if (val === '') {
                            this.label = config.defaultLabel || 'Select Option';
                        }
                        this.$refs.hiddenInput.value = val;
                        this.$refs.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                    });
                },
                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.activeIndex = this.options.findIndex(o => String(o.value) === String(this.value));
                        if (this.activeIndex === -1) this.activeIndex = 0;
                        this.$nextTick(() => {
                            this.scrollToActive();
                        });
                    }
                },
                close() {
                    this.open = false;
                    this.activeIndex = -1;
                },
                select(val) {
                    this.value = val;
                    this.close();
                },
                selectActive() {
                    if (this.activeIndex >= 0 && this.activeIndex < this.options.length) {
                        this.select(this.options[this.activeIndex].value);
                    }
                },
                focusNext() {
                    if (!this.open) {
                        this.toggle();
                        return;
                    }
                    this.activeIndex = (this.activeIndex + 1) % this.options.length;
                    this.scrollToActive();
                },
                focusPrev() {
                    if (!this.open) {
                        this.toggle();
                        return;
                    }
                    this.activeIndex = (this.activeIndex - 1 + this.options.length) % this.options.length;
                    this.scrollToActive();
                },
                scrollToActive() {
                    this.$nextTick(() => {
                        const activeEl = this.$refs.optionsList.children[this.activeIndex];
                        if (activeEl) {
                            activeEl.scrollIntoView({ block: 'nearest' });
                        }
                    });
                }
            }));

            // Custom Datepicker component
            Alpine.data('datepicker', (config) => ({
                value: config.value || '',
                name: config.name || '',
                required: config.required || false,
                open: false,
                monthOpen: false,
                yearOpen: false,
                month: null, // 0-11
                year: null,
                days: [],
                months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                
                init() {
                    let initialDate = new Date();
                    if (this.value) {
                        const parts = this.value.split('-');
                        if (parts.length === 3) {
                            initialDate = new Date(parts[0], parts[1] - 1, parts[2]);
                        } else {
                            initialDate = new Date(this.value);
                        }
                    }
                    this.month = initialDate.getMonth();
                    this.year = initialDate.getFullYear();
                    this.generateCalendar();
                    
                    this.$watch('value', (val) => {
                        if (val) {
                            const parts = val.split('-');
                            if (parts.length === 3) {
                                const y = parseInt(parts[0], 10);
                                const m = parseInt(parts[1], 10) - 1;
                                if (!isNaN(y) && !isNaN(m)) {
                                    this.month = m;
                                    this.year = y;
                                    this.generateCalendar();
                                }
                            }
                        }
                    });
                },
                
                get displayValue() {
                    if (!this.value) return 'Select Date';
                    const parts = this.value.split('-');
                    if (parts.length === 3) {
                        const d = new Date(parts[0], parts[1] - 1, parts[2]);
                        if (!isNaN(d.getTime())) {
                            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        }
                    }
                    return 'Select Date';
                },
                
                getYears() {
                    const currentYear = new Date().getFullYear();
                    const initialYear = this.value ? parseInt(this.value.split('-')[0], 10) : currentYear;
                    const minYear = Math.min(currentYear - 50, initialYear - 10);
                    const maxYear = Math.max(currentYear + 10, initialYear + 10);
                    const years = [];
                    for (let y = minYear; y <= maxYear; y++) {
                        years.push(y);
                    }
                    return years;
                },
                
                generateCalendar() {
                    const firstDayOfMonth = new Date(this.year, this.month, 1).getDay();
                    const daysInMonth = new Date(this.year, this.month + 1, 0).getDate();
                    const daysInPrevMonth = new Date(this.year, this.month, 0).getDate();
                    
                    const days = [];
                    
                    // Previous month trailing days
                    for (let i = firstDayOfMonth - 1; i >= 0; i--) {
                        days.push({
                            day: daysInPrevMonth - i,
                            month: this.month === 0 ? 11 : this.month - 1,
                            year: this.month === 0 ? this.year - 1 : this.year,
                            currentMonth: false
                        });
                    }
                    
                    // Current month days
                    for (let i = 1; i <= daysInMonth; i++) {
                        days.push({
                            day: i,
                            month: this.month,
                            year: this.year,
                            currentMonth: true
                        });
                    }
                    
                    // Next month leading days
                    const totalCells = 42;
                    const nextMonthDays = totalCells - days.length;
                    for (let i = 1; i <= nextMonthDays; i++) {
                        days.push({
                            day: i,
                            month: this.month === 11 ? 0 : this.month + 1,
                            year: this.month === 11 ? this.year + 1 : this.year,
                            currentMonth: false
                        });
                    }
                    
                    this.days = days;
                },
                
                prevMonth() {
                    if (this.month === 0) {
                        this.month = 11;
                        this.year--;
                    } else {
                        this.month--;
                    }
                    this.generateCalendar();
                },
                
                nextMonth() {
                    if (this.month === 11) {
                        this.month = 0;
                        this.year++;
                    } else {
                        this.month++;
                    }
                    this.generateCalendar();
                },
                
                selectDate(dateObj) {
                    const pad = (n) => String(n).padStart(2, '0');
                    this.value = `${dateObj.year}-${pad(dateObj.month + 1)}-${pad(dateObj.day)}`;
                    this.open = false;
                    this.monthOpen = false;
                    this.yearOpen = false;
                },
                
                isSelected(dateObj) {
                    if (!this.value) return false;
                    const parts = this.value.split('-');
                    if (parts.length === 3) {
                        return parseInt(parts[2], 10) === dateObj.day &&
                               (parseInt(parts[1], 10) - 1) === dateObj.month &&
                               parseInt(parts[0], 10) === dateObj.year;
                    }
                    return false;
                },
                
                isToday(dateObj) {
                    const today = new Date();
                    return today.getDate() === dateObj.day &&
                           today.getMonth() === dateObj.month &&
                           today.getFullYear() === dateObj.year;
                }
            }));
        }

        if (window.Alpine) {
            registerAlpineComponents();
        } else {
            document.addEventListener('alpine:init', registerAlpineComponents);
        }
    })();
</script>