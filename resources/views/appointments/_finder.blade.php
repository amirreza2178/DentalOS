@php
    $step = ! $patient ? 1 : (($service && $date !== '' && ! $dateError) ? 3 : 2);
@endphp

<nav aria-label="مراحل ثبت نوبت" class="mb-4 flex flex-wrap items-center gap-2 text-xs text-slate-600">
    <span class="inline-flex min-h-8 items-center rounded-full px-3 {{ $step === 1 ? 'bg-teal-700 text-white' : 'bg-slate-100 text-slate-700' }}">۱. بیمار</span>
    <span class="text-slate-300" aria-hidden="true">←</span>
    <span class="inline-flex min-h-8 items-center rounded-full px-3 {{ $step === 2 ? 'bg-teal-700 text-white' : ($step > 2 ? 'bg-slate-100 text-slate-700' : 'bg-slate-50 text-slate-400') }}">۲. خدمت و تاریخ</span>
    <span class="text-slate-300" aria-hidden="true">←</span>
    <span class="inline-flex min-h-8 items-center rounded-full px-3 {{ $step === 3 ? 'bg-teal-700 text-white' : 'bg-slate-50 text-slate-400' }}">۳. تأیید زمان آزاد</span>
</nav>

@if ($patient)
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-teal-200 bg-teal-50/70 px-4 py-3" data-sticky-patient>
        <div>
            <p class="text-xs font-medium text-teal-900">بیمار انتخاب‌شده</p>
            <p class="text-sm font-semibold text-[color:var(--dos-navy)]">{{ $patient->fullName() }} — {{ $patient->code }}</p>
        </div>
        @unless ($lockedPatient)
            <a
                href="{{ $finderAction }}?{{ http_build_query(array_filter(['service_id' => $service?->id, 'date' => $date, 'time' => $time, 'follow_up_id' => $followUp->id ?? null, 'change_patient' => 1])) }}"
                class="inline-flex min-h-10 items-center rounded-lg border border-teal-700/30 bg-white px-3 text-xs font-medium text-teal-900 hover:bg-white focus:outline-none focus:ring-2 focus:ring-teal-700"
            >تغییر بیمار</a>
        @endunless
    </div>
@endif

<form
    method="GET"
    action="{{ $finderAction }}"
    class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-2"
    data-booking-finder
    @if ($patient) data-booking-finder-auto="1" @endif
>
    @if (! empty($followUp))
        <input type="hidden" name="follow_up_id" value="{{ $followUp->id }}">
    @endif
    @unless ($lockedPatient || $patient)
        <label class="block space-y-1 md:col-span-2" for="q">
            <span class="text-sm font-medium">جستجوی بیمار موجود</span>
            <input id="q" name="q" value="{{ $term }}" autofocus class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30" autocomplete="off" placeholder="نام، موبایل یا کد پرونده">
        </label>
    @endunless
    @if ($patient)
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
    @endif
    <label class="block space-y-1" for="service_id">
        <span class="text-sm font-medium">خدمت فعال</span>
        <select id="service_id" name="service_id" data-booking-finder-trigger class="w-full min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30" @disabled(! $patient)>
            <option value="">انتخاب خدمت</option>
            @foreach ($services as $item)
                <option value="{{ $item->id }}" @selected($service?->id === $item->id)>{{ $item->name }} — {{ $item->default_duration_minutes }} دقیقه</option>
            @endforeach
        </select>
    </label>
    <div @class(['opacity-60' => ! $patient])>
        <x-jalali-date-field
            name="date"
            label="تاریخ جلالی"
            :value="$date"
            :auto-submit="(bool) $patient"
            hint="با انتخاب تاریخ، زمان‌های آزاد همان روز از AvailabilityService بارگذاری می‌شود."
        />
    </div>
    <details class="md:col-span-2 rounded-xl border border-slate-100 bg-slate-50/80 px-3 py-2">
        <summary class="cursor-pointer text-xs font-medium text-slate-600">بررسی یک ساعت مشخص (اختیاری)</summary>
        <label class="mt-2 block space-y-1" for="time">
            <span class="sr-only">ساعت</span>
            <input id="time" name="time" type="time" value="{{ $time }}" class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
        </label>
    </details>
    <noscript>
        <button type="submit" class="self-end rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">نمایش زمان‌های آزاد</button>
    </noscript>
    <button type="submit" class="self-end rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700 md:col-start-2">{{ $patient && $service && $date !== '' ? 'به‌روزرسانی زمان‌های آزاد' : 'نمایش زمان‌های آزاد' }}</button>
</form>

@if ($dateError)
    <x-alert tone="warning" role="alert">{{ $dateError }}</x-alert>
@endif

@if ($errors->any())
    <x-alert tone="warning" role="alert">
        <p class="font-semibold">نوبت ثبت نشد — زمان یا منبع در دسترس نیست.</p>
        <ul class="mt-2 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <p class="mt-2 text-xs text-slate-600">خدمت و تاریخ حفظ شده‌اند؛ زمان آزاد دیگری انتخاب کنید.</p>
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
                    <a class="inline-flex min-h-10 items-center text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ $finderAction }}?{{ http_build_query(array_filter(['patient_id' => $found->id, 'service_id' => $service?->id, 'date' => $date, 'time' => $time, 'follow_up_id' => $followUp->id ?? null])) }}">{{ $found->fullName() }} — {{ $found->code }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@elseif ($term !== '' && ! $lockedPatient && ! $patient)
    <x-empty-state title="بیماری با این عبارت پیدا نشد" description="نوبت فقط برای پرونده موجود ساخته می‌شود." />
@endif

@if ($patient && $service && $date !== '' && ! $dateError)
    @if ($opening)
        <div class="mb-4">
            <h2 class="mb-2 text-base font-semibold">ساعت انتخاب‌شده — تأیید نهایی</h2>
            @include('appointments._slot_form', ['slot' => $opening, 'action' => $action, 'prefix' => 'open', 'emphasize' => true])
        </div>
    @endif
    @if ($slots === [])
        <x-empty-state title="زمان آزادی در این تاریخ پیدا نشد" description="شیفت، تعطیلی، استراحت، یا اشغال منبع را بررسی کنید. تعداد یونیت یا کارکنان فرض نشده است." />
    @else
        <div class="space-y-3">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <h2 class="text-base font-semibold">زمان‌های آزاد ({{ count($slots) }})</h2>
                <p class="text-xs text-slate-500">منبع حقیقت: AvailabilityService — تداخل بی‌صدا ثبت نمی‌شود.</p>
            </div>
            <div class="flex flex-wrap gap-2" role="list" aria-label="میان‌بر انتخاب ساعت">
                @foreach ($slots as $slot)
                    @php $slotClock = $slot->startsAt->timezone(config('app.timezone'))->format('H:i'); @endphp
                    <a
                        role="listitem"
                        href="{{ $finderAction }}?{{ http_build_query(array_filter(['patient_id' => $patient->id, 'service_id' => $service->id, 'date' => $date, 'time' => $slotClock, 'follow_up_id' => $followUp->id ?? null])) }}#slot-confirm"
                        class="inline-flex min-h-11 min-w-[4.5rem] items-center justify-center rounded-lg border px-3 text-sm font-semibold tabular-nums focus:outline-none focus:ring-2 focus:ring-teal-700 {{ $time === $slotClock ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-300 bg-white text-slate-800 hover:border-teal-600 hover:bg-teal-50' }}"
                    >{{ $slotClock }}</a>
                @endforeach
            </div>
            @unless ($opening)
                <div class="space-y-3" id="slot-confirm">
                    @foreach ($slots as $slot)
                        @include('appointments._slot_form', ['slot' => $slot, 'action' => $action, 'prefix' => 'slot-'.$loop->index])
                    @endforeach
                </div>
            @else
                <p class="text-xs text-slate-500">برای دیدن همه فرم‌های رزرو، ساعت را از میان‌بر بالا پاک کنید یا «به‌روزرسانی زمان‌های آزاد» را بزنید.</p>
            @endif
        </div>
    @endif
@elseif (! $patient)
    <x-empty-state title="اول بیمار را انتخاب کنید" description="جستجو فقط بین پرونده‌های واقعی انجام می‌شود. پس از انتخاب، بیمار در مراحل بعد ثابت می‌ماند." />
@elseif ($services->isEmpty())
    <x-empty-state title="خدمت فعالی ثبت نشده است" description="تا وقتی خدمت واقعی تعریف نشود، نوبتی ساخته نمی‌شود." />
@elseif ($patient && $date === '')
    <x-empty-state title="تاریخ را انتخاب کنید" description="از تقویم شمسی یک روز را انتخاب کنید تا زمان‌های آزاد همان روز نشان داده شود." />
@elseif ($patient && ! $service)
    <x-empty-state title="خدمت را انتخاب کنید" description="پس از انتخاب خدمت، زمان‌های آزاد همان تاریخ بارگذاری می‌شود." />
@endif
