<x-management-layout title="درخواست‌های آنلاین">
    <x-page-header title="درخواست‌های رزرو آنلاین" description="تأیید یا رد درخواست‌های در انتظار. منابع در حالت انتظار تأیید اشغال می‌مانند.">
        <a href="{{ route('reception.day') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">مرکز فرمان</a>
    </x-page-header>

    <div class="mb-4 flex gap-2" role="tablist" aria-label="وضعیت درخواست‌ها">
        <a href="{{ route('booking.requests.index', ['tab' => 'pending']) }}" @if($tab === 'pending') aria-current="page" @endif class="rounded-lg px-4 py-2 text-sm font-medium {{ $tab === 'pending' ? 'bg-slate-900 text-white' : 'border border-slate-300 bg-white hover:bg-slate-50' }}">در انتظار</a>
        <a href="{{ route('booking.requests.index', ['tab' => 'decided']) }}" @if($tab === 'decided') aria-current="page" @endif class="rounded-lg px-4 py-2 text-sm font-medium {{ $tab === 'decided' ? 'bg-slate-900 text-white' : 'border border-slate-300 bg-white hover:bg-slate-50' }}">تعیین‌تکلیف‌شده</a>
    </div>

    @if ($appointments->isEmpty())
        <x-empty-state title="درخواستی نیست" description="{{ $tab === 'pending' ? 'درخواست آنلاینی در انتظار تأیید نیست.' : 'هنوز درخواست تعیین‌تکلیف‌شده‌ای ثبت نشده است.' }}" />
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-3 text-right font-medium">زمان نوبت</th>
                        <th class="px-4 py-3 text-right font-medium">خدمت</th>
                        <th class="px-4 py-3 text-right font-medium">نام</th>
                        <th class="px-4 py-3 text-right font-medium">موبایل</th>
                        <th class="px-4 py-3 text-right font-medium">وضعیت</th>
                        <th class="px-4 py-3 text-right font-medium">اقدام</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($appointments as $appointment)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3 whitespace-nowrap">{{ $calendar->formatDate($appointment->starts_at) }} {{ $appointment->starts_at->timezone(config('app.timezone'))->format('H:i') }}</td>
                            <td class="px-4 py-3">{{ $appointment->treatmentService?->name }}</td>
                            <td class="px-4 py-3">
                                {{ $appointment->patient?->fullName() }}
                                @if (($mobileHints[$appointment->id] ?? 0) > 1)
                                    <span class="mt-1 block text-xs text-amber-700">موبایل مشترک با پرونده‌های دیگر — بدون اتصال خودکار</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 dir-ltr text-left">{{ $appointment->patient?->mobile }}</td>
                            <td class="px-4 py-3">{{ $appointment->status->label() }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('booking.requests.show', $appointment) }}" class="font-medium text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700">جزئیات</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $appointments->links() }}</div>
    @endif
</x-management-layout>
