@php
    $required = $required ?? false;
    $defaultTime = $defaultTime ?? '08:00';
    $popoverPlacement = $popoverPlacement ?? 'up';
    $popoverPositionClass = $popoverPlacement === 'up' ? 'bottom-full mb-1.5' : 'top-full mt-1.5';
@endphp

<div class="min-w-0">
    <input
        type="hidden"
        name="{{ $inputName }}"
        :disabled="{{ $disabledExpr }}"
        :value="combineDateTime({{ $fieldKey }})"
    >

    <label class="block text-xs font-semibold text-[#263445] mb-1.5">
        {{ $label }}
        @if($required)
            <span class="text-[#C54B4B]">*</span>
        @else
            <span class="font-normal text-[#718096]">(Opsional)</span>
        @endif
    </label>

    <div class="grid grid-cols-[minmax(0,1.3fr)_minmax(0,0.85fr)] gap-2">
        <!-- Input Tanggal (DD/MM/YYYY) + Calendar Popover -->
        <div class="relative min-w-0" @click.outside="closePicker('{{ $fieldKey }}_date')">
            <div class="relative flex items-center">
                <input
                    type="text"
                    inputmode="numeric"
                    maxlength="10"
                    x-model="{{ $fieldKey }}.dateDisplay"
                    @input="onManualDateInput({{ $fieldKey }}, $event.target.value, '{{ $fieldKey }}')"
                    @blur="normalizeDateField({{ $fieldKey }}, '{{ $fieldKey }}')"
                    @focus="openDatePicker('{{ $fieldKey }}_date', {{ $fieldKey }})"
                    placeholder="DD/MM/YYYY"
                    autocomplete="off"
                    class="w-full min-h-[44px] pl-3 pr-9 py-2 bg-[#FAFBFD] hover:bg-white focus:bg-white border rounded-[11px] text-xs sm:text-[13px] text-[#263445] placeholder:text-[#94A3B8] transition-all outline-none"
                    :class="errors['{{ $fieldKey }}_date'] ? 'border-[#C54B4B] bg-[#FDF0F0]/50 focus:border-[#C54B4B] focus:ring-2 focus:ring-[#C54B4B]/15' : 'border-[#D7DFE9] focus:border-[#438FCE] focus:ring-2 focus:ring-[#438FCE]/15'"
                >
                <button
                    type="button"
                    @click.stop="toggleDatePicker('{{ $fieldKey }}_date', {{ $fieldKey }})"
                    class="absolute right-1.5 w-7 h-7 rounded-lg flex items-center justify-center text-[#718096] hover:text-[#438FCE] hover:bg-[#DFEAF9]/50 transition-colors"
                    title="Pilih Tanggal"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </button>
            </div>

            <!-- Calendar Popover -->
            <div
                x-show="activePicker === '{{ $fieldKey }}_date'"
                x-transition.opacity.duration.120ms
                x-cloak
                class="absolute left-0 {{ $popoverPositionClass }} w-[268px] max-w-[calc(100vw-3rem)] bg-white border border-[#E5EAF0] rounded-xl shadow-xl p-3 z-40"
            >
                <div class="flex items-center justify-between mb-2.5">
                    <button
                        type="button"
                        @click.stop="prevMonth()"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] transition-colors"
                        title="Bulan Sebelumnya"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <span class="text-xs font-bold text-[#263445]" x-text="calendarMonthLabel"></span>
                    <button
                        type="button"
                        @click.stop="nextMonth()"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-[#718096] hover:text-[#263445] hover:bg-[#F5F8FC] transition-colors"
                        title="Bulan Berikutnya"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>

                <div class="grid grid-cols-7 gap-1 text-center mb-1">
                    <template x-for="d in ['Sen','Sel','Rab','Kam','Jum','Sab','Min']" :key="d">
                        <span class="text-[10px] font-semibold text-[#718096] py-0.5" x-text="d"></span>
                    </template>
                </div>

                <div class="grid grid-cols-7 gap-1">
                    <template x-for="day in calendarDays" :key="day.iso">
                        <button
                            type="button"
                            @click.stop="selectCalendarDay({{ $fieldKey }}, day.iso, '{{ $fieldKey }}', '{{ $defaultTime }}')"
                            class="h-7 rounded-lg text-[11px] font-medium flex items-center justify-center transition-colors"
                            :class="day.iso === {{ $fieldKey }}.date
                                ? 'bg-[#438FCE] text-white font-bold shadow-2xs'
                                : (day.isToday
                                    ? 'border border-[#438FCE] text-[#438FCE] font-bold hover:bg-[#DFEAF9]/50'
                                    : (day.inCurrentMonth
                                        ? 'text-[#263445] hover:bg-[#F5F8FC]'
                                        : 'text-[#CBD5E1] hover:bg-[#F5F8FC]'))"
                            x-text="day.day"
                        ></button>
                    </template>
                </div>

                <div class="mt-2.5 pt-2 border-t border-[#E5EAF0] flex items-center justify-between text-[11px]">
                    <button
                        type="button"
                        @click.stop="selectToday({{ $fieldKey }}, '{{ $fieldKey }}', '{{ $defaultTime }}')"
                        class="font-semibold text-[#438FCE] hover:text-[#2E6C9E]"
                    >
                        Hari Ini
                    </button>
                    @if(!$required)
                    <button
                        type="button"
                        @click.stop="clearDateTimeField({{ $fieldKey }}, '{{ $fieldKey }}')"
                        class="font-medium text-[#718096] hover:text-[#C54B4B]"
                    >
                        Kosongkan
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Input Jam (HH:MM) + Time Slot Popover -->
        <div class="relative min-w-0" @click.outside="closePicker('{{ $fieldKey }}_time')">
            <div class="relative flex items-center">
                <input
                    type="text"
                    inputmode="numeric"
                    maxlength="5"
                    x-model="{{ $fieldKey }}.time"
                    @input="onManualTimeInput({{ $fieldKey }}, $event.target.value, '{{ $fieldKey }}')"
                    @blur="normalizeTimeField({{ $fieldKey }}, '{{ $fieldKey }}')"
                    @focus="activePicker = '{{ $fieldKey }}_time'"
                    placeholder="HH:MM"
                    autocomplete="off"
                    class="w-full min-h-[44px] pl-3 pr-8 py-2 bg-[#FAFBFD] hover:bg-white focus:bg-white border rounded-[11px] text-xs sm:text-[13px] text-[#263445] placeholder:text-[#94A3B8] transition-all outline-none tabular-nums"
                    :class="errors['{{ $fieldKey }}_time'] ? 'border-[#C54B4B] bg-[#FDF0F0]/50 focus:border-[#C54B4B] focus:ring-2 focus:ring-[#C54B4B]/15' : 'border-[#D7DFE9] focus:border-[#438FCE] focus:ring-2 focus:ring-[#438FCE]/15'"
                >
                <button
                    type="button"
                    @click.stop="togglePicker('{{ $fieldKey }}_time')"
                    class="absolute right-1.5 w-6 h-7 rounded-lg flex items-center justify-center text-[#718096] hover:text-[#438FCE] hover:bg-[#DFEAF9]/50 transition-colors"
                    title="Pilih Jam"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </button>
            </div>

            <!-- Time Popover -->
            <div
                x-show="activePicker === '{{ $fieldKey }}_time'"
                x-transition.opacity.duration.120ms
                x-cloak
                class="absolute right-0 {{ $popoverPositionClass }} w-32 max-h-52 overflow-y-auto bg-white border border-[#E5EAF0] rounded-xl shadow-xl p-1 space-y-0.5 z-40"
            >
                <template x-for="slot in timeSlots" :key="slot">
                    <button
                        type="button"
                        @click.stop="selectTimeSlot({{ $fieldKey }}, slot, '{{ $fieldKey }}')"
                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs tabular-nums transition-colors flex items-center justify-between"
                        :class="{{ $fieldKey }}.time === slot ? 'bg-[#DFEAF9] text-[#2E6C9E] font-bold' : 'text-[#263445] hover:bg-[#F5F8FC]'"
                    >
                        <span x-text="slot"></span>
                        <span class="text-[10px] text-[#718096]">WIB</span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <p
        x-show="errors['{{ $fieldKey }}_date'] || errors['{{ $fieldKey }}_time']"
        x-cloak
        class="mt-1.5 text-[11px] text-[#C54B4B] font-medium"
        x-text="errors['{{ $fieldKey }}_date'] || errors['{{ $fieldKey }}_time']"
    ></p>
</div>

