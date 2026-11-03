<x-management-layout title="ویرایش شیفت">
    <x-page-header title="ویرایش شیفت" description="استراحت داخل همین شیفت ثبت می‌شود و زمان نوبت نباید با آن تداخل داشته باشد." />
    @if ($errors->any())
        <x-alert tone="warning" role="alert">{{ $errors->first() }}</x-alert>
    @endif
    <form method="POST" action="{{ route('shifts.update', $shift) }}" class="mb-6 space-y-4 rounded-2xl border border-slate-200 bg-white p-4">
        @csrf
        @method('PUT')
        @include('shifts._form')
        <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-teal-700">ذخیره</button>
    </form>

    <section class="rounded-2xl border border-slate-200 bg-white p-4">
        <h2 class="mb-3 text-base font-semibold">زمان‌های استراحت</h2>
        @if ($shift->breaks->isEmpty())
            <p class="mb-4 text-sm text-slate-600">برای این شیفت استراحتی ثبت نشده است.</p>
        @else
            <ul class="mb-4 space-y-2 text-sm">
                @foreach ($shift->breaks as $shiftBreak)
                    <li class="flex items-center justify-between gap-3 border-t border-slate-100 pt-2">
                        <span>{{ $shiftBreak->name ?: 'استراحت' }} — {{ \App\Support\ClockInput::short((string) $shiftBreak->starts_at) }} تا {{ \App\Support\ClockInput::short((string) $shiftBreak->ends_at) }}</span>
                        <form method="POST" action="{{ route('shifts.breaks.destroy', [$shift, $shiftBreak]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg border border-rose-300 px-3 py-1 text-xs text-rose-800 focus:outline-none focus:ring-2 focus:ring-rose-700">حذف</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
        <form method="POST" action="{{ route('shifts.breaks.store', $shift) }}" class="grid gap-3 md:grid-cols-4">
            @csrf
            <x-text-field name="name" label="عنوان" />
            <x-text-field name="starts_at" type="time" label="شروع" required />
            <x-text-field name="ends_at" type="time" label="پایان" required />
            <button type="submit" class="self-end rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">افزودن استراحت</button>
        </form>
    </section>
</x-management-layout>
