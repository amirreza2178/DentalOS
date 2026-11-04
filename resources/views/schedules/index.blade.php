<x-management-layout title="برنامه کارکنان">
    <x-page-header title="برنامه کارکنان" description="حضور متخصص و کادر بالینی فقط وقتی نوبت می‌گیرد که اینجا فعال شده باشد." />
    @if ($errors->any())
        <x-alert tone="warning" role="alert">{{ $errors->first() }}</x-alert>
    @endif
    @if ($staff->isEmpty() || $shifts->isEmpty())
        <x-empty-state title="برنامه هنوز قابل ساخت نیست" description="حداقل یک شیفت و یک متخصص یا کادر بالینی فعال لازم است. فهرست کارکنان فرضی ساخته نشده است." />
    @else
        <form method="POST" action="{{ route('schedules.store') }}" class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-2">
            @csrf
            <x-select-field name="user_id" label="کارکنان" :options="$staff->mapWithKeys(fn ($user) => [$user->id => $user->name.' — '.$user->role->label()])->all()" required placeholder="انتخاب" />
            <x-select-field name="kind" label="نوع" :options="['recurring' => 'تکرار با شیفت', 'date' => 'یک تاریخ خاص', 'day_off' => 'مرخصی یک روز']" required />
            <x-select-field name="work_shift_id" label="شیفت" :options="$shifts->mapWithKeys(fn ($shift) => [$shift->id => $shift->name.' — '.$shift->weekday->label()])->all()" placeholder="برای مرخصی لازم نیست" />
            <x-jalali-date-field name="schedule_date" label="تاریخ برای مورد خاص یا مرخصی" hint="فقط برای نوع «یک تاریخ خاص» یا «مرخصی» لازم است." />
            <x-text-field name="starts_at" type="time" label="شروع، فقط برای تاریخ خاص" />
            <x-text-field name="ends_at" type="time" label="پایان، فقط برای تاریخ خاص" />
            <x-checkbox-field name="is_active" label="فعال" :checked="true" />
            <button type="submit" class="self-end rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت برنامه</button>
        </form>
    @endif

    @if ($schedules->isEmpty())
        <x-empty-state title="برنامه‌ای ثبت نشده است" description="بدون برنامه، متخصص و کادر بالینی برای نوبت جدید در دسترس نیستند." />
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3 text-right font-medium">کارکنان</th><th class="px-4 py-3 text-right font-medium">جزئیات</th><th class="px-4 py-3 text-right font-medium">وضعیت</th><th class="px-4 py-3"></th></tr></thead>
                <tbody>
                    @foreach ($schedules as $schedule)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">{{ $schedule->user?->name }}</td>
                            <td class="px-4 py-3">
                                @if ($schedule->is_unavailable)
                                    مرخصی {{ $calendar->formatDate($schedule->schedule_date) }}
                                @elseif ($schedule->schedule_date)
                                    {{ $calendar->formatDate($schedule->schedule_date) }}
                                    {{ \App\Support\ClockInput::short((string) $schedule->starts_at) }} تا {{ \App\Support\ClockInput::short((string) $schedule->ends_at) }}
                                @else
                                    {{ $schedule->workShift?->name }} — {{ $schedule->workShift?->weekday?->label() }}
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $schedule->is_active ? 'فعال' : 'غیرفعال' }}</td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('schedules.destroy', $schedule) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-rose-800 hover:underline focus:outline-none focus:ring-2 focus:ring-rose-700">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-management-layout>
