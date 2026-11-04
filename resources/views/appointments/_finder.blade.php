<form method="GET" action="{{ $finderAction }}" class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-2">
    @if (! empty($followUp))
        <input type="hidden" name="follow_up_id" value="{{ $followUp->id }}">
    @endif
    @unless ($lockedPatient)
        <label class="block space-y-1 md:col-span-2" for="q">
            <span class="text-sm font-medium">جستجوی بیمار موجود</span>
            <input id="q" name="q" value="{{ $term }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
        </label>
    @endunless
    @if ($patient)
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        <p class="text-sm md:col-span-2">بیمار: <span class="font-semibold">{{ $patient->fullName() }}</span> — {{ $patient->code }}</p>
    @endif
    <label class="block space-y-1" for="service_id">
        <span class="text-sm font-medium">خدمت فعال</span>
        <select id="service_id" name="service_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
            <option value="">انتخاب خدمت</option>
            @foreach ($services as $item)
                <option value="{{ $item->id }}" @selected($service?->id === $item->id)>{{ $item->name }} — {{ $item->default_duration_minutes }} دقیقه</option>
            @endforeach
        </select>
    </label>
    <x-jalali-date-field
        name="date"
        label="تاریخ جلالی"
        :value="$date"
        hint="پس از انتخاب تاریخ، زمان‌های آزاد همان روز از AvailabilityService بارگذاری می‌شود."
    />
    <label class="block space-y-1" for="time">
        <span class="text-sm font-medium">بررسی یک ساعت مشخص</span>
        <input id="time" name="time" type="time" value="{{ $time }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
    </label>
    <button type="submit" class="self-end rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">نمایش زمان‌های آزاد</button>
</form>

@if ($dateError)
    <x-alert tone="warning" role="alert">{{ $dateError }}</x-alert>
@endif

@if ($errors->any())
    <x-alert tone="warning" role="alert">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif

@if ($reasons !== [])
    <x-alert tone="warning" role="alert">
        <p class="font-semibold">این ساعت قابل رزرو نیست.</p>
        <ul class="mt-2 space-y-1">
            @foreach ($reasons as $reason)
                <li>{{ $reason }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif

@if ($patients->isNotEmpty())
    <div class="mb-4 rounded-2xl border border-slate-200 bg-white p-4">
        <h2 class="mb-2 text-sm font-semibold">نتیجه جستجو</h2>
        <ul class="space-y-2 text-sm">
            @foreach ($patients as $found)
                <li>
                    <a class="text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ $finderAction }}?{{ http_build_query(array_filter(['patient_id' => $found->id, 'service_id' => $service?->id, 'date' => $date, 'time' => $time])) }}">{{ $found->fullName() }} — {{ $found->code }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@elseif ($term !== '' && ! $lockedPatient)
    <x-empty-state title="بیماری با این عبارت پیدا نشد" description="نوبت فقط برای پرونده موجود ساخته می‌شود." />
@endif

@if ($patient && $service && $date !== '' && ! $dateError)
    @if ($opening)
        <div class="mb-4">
            <h2 class="mb-2 text-base font-semibold">ساعت بررسی‌شده</h2>
            @include('appointments._slot_form', ['slot' => $opening, 'action' => $action, 'prefix' => 'open'])
        </div>
    @endif
    @if ($slots === [])
        <x-empty-state title="زمان آزادی در این تاریخ پیدا نشد" description="شیفت، تعطیلی، استراحت، یا اشغال منبع را بررسی کنید. تعداد یونیت یا کارکنان فرض نشده است." />
    @else
        <div class="space-y-3">
            <h2 class="text-base font-semibold">زمان‌های آزاد</h2>
            @foreach ($slots as $slot)
                @include('appointments._slot_form', ['slot' => $slot, 'action' => $action, 'prefix' => 'slot-'.$loop->index])
            @endforeach
        </div>
    @endif
@elseif (! $patient)
    <x-empty-state title="اول بیمار را انتخاب کنید" description="جستجو فقط بین پرونده‌های واقعی انجام می‌شود." />
@elseif ($services->isEmpty())
    <x-empty-state title="خدمت فعالی ثبت نشده است" description="تا وقتی خدمت واقعی تعریف نشود، نوبتی ساخته نمی‌شود." />
@elseif ($patient && $date === '')
    <x-empty-state title="تاریخ را انتخاب کنید" description="از تقویم شمسی یک روز را انتخاب کنید تا زمان‌های آزاد همان روز نشان داده شود." />
@endif
