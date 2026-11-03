<form method="POST" action="{{ $action }}" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-4">
    @csrf
    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
    <input type="hidden" name="treatment_service_id" value="{{ $service->id }}">
    <input type="hidden" name="appointment_date" value="{{ $date }}">
    <input type="hidden" name="appointment_time" value="{{ $slot->startsAt->timezone(config('app.timezone'))->format('H:i') }}">
    <p class="text-sm font-semibold text-slate-900">{{ $calendar->formatDate($slot->startsAt) }}، {{ $slot->startsAt->timezone(config('app.timezone'))->format('H:i') }} تا {{ $slot->endsAt->timezone(config('app.timezone'))->format('H:i') }}</p>
    <p class="text-xs leading-5 text-slate-500">مدت از خدمت خوانده می‌شود: {{ $service->default_duration_minutes }} دقیقه.@if ($service->buffer_minutes > 0) منبع تا {{ $service->buffer_minutes }} دقیقه بعد از پایان نوبت آزاد نمی‌شود.@endif</p>
    <div class="grid gap-3 md:grid-cols-2">
        @if ($slot->specialistIds !== [])
            <label class="block space-y-1" for="specialist-{{ $prefix }}">
                <span class="text-sm font-medium">متخصص</span>
                <select id="specialist-{{ $prefix }}" name="specialist_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                    @foreach ($slot->specialistIds as $id)
                        <option value="{{ $id }}">{{ $people[$id]->name ?? 'متخصص' }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        @if ($service->required_clinical_staff_count > 0)
            @for ($index = 0; $index < $service->required_clinical_staff_count; $index++)
                <label class="block space-y-1" for="staff-{{ $prefix }}-{{ $index }}">
                    <span class="text-sm font-medium">کادر بالینی {{ $index + 1 }}</span>
                    <select id="staff-{{ $prefix }}-{{ $index }}" name="clinical_staff_ids[]" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                        @foreach ($slot->clinicalStaffIds as $id)
                            <option value="{{ $id }}" @selected($loop->index === $index)>{{ $people[$id]->name ?? 'کادر بالینی' }}</option>
                        @endforeach
                    </select>
                </label>
            @endfor
        @endif
        @if ($slot->chairIds !== [])
            <label class="block space-y-1" for="chair-{{ $prefix }}">
                <span class="text-sm font-medium">یونیت</span>
                <select id="chair-{{ $prefix }}" name="chair_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                    @foreach ($slot->chairIds as $id)
                        <option value="{{ $id }}">{{ $chairs[$id]->name ?? 'یونیت' }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        <div class="md:col-span-2">
            <label class="block space-y-1" for="notes-{{ $prefix }}">
                <span class="text-sm font-medium">یادداشت پذیرش (غیر بالینی)</span>
                <input id="notes-{{ $prefix }}" name="patient_notes" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
            </label>
        </div>
    </div>
    <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت این نوبت</button>
</form>
