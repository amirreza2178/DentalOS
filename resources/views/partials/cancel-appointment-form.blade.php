@props([
    'appointment',
    'returnTo' => null,
    'returnDate' => null,
    'compact' => false,
])
@can('cancel', $appointment)
    @php
        $gate = app(\App\Support\ReceptionCancellationGate::class);
        $blockReason = $gate->blockReason($appointment);
        $canOfferCancel = $blockReason === null
            && $appointment->status->canTransitionTo(\App\Enums\AppointmentStatus::Cancelled);
    @endphp
    @if ($canOfferCancel)
        <form method="POST" action="{{ route('appointments.cancel', $appointment) }}" class="{{ $compact ? 'space-y-2' : 'mt-4 space-y-3' }}" onsubmit="return confirm('این نوبت با دلیل انتخاب‌شده لغو شود؟')">
            @csrf
            @if ($returnTo)
                <input type="hidden" name="return_to" value="{{ $returnTo }}">
            @endif
            @if ($returnDate)
                <input type="hidden" name="return_date" value="{{ $returnDate }}">
            @endif
            <label class="block space-y-1" for="cancellation-reason-{{ $appointment->id }}{{ $compact ? '-c' : '' }}">
                <span class="text-xs font-medium text-slate-600">دلیل لغو <span class="text-rose-700">*</span></span>
                <select
                    id="cancellation-reason-{{ $appointment->id }}{{ $compact ? '-c' : '' }}"
                    name="cancellation_reason"
                    required
                    class="w-full min-h-11 rounded-lg border border-slate-300 bg-white px-3 text-xs focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30"
                >
                    <option value="" disabled selected>انتخاب کنید</option>
                    @foreach (\App\Enums\CancellationReason::cases() as $reason)
                        <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block space-y-1" for="cancellation-note-{{ $appointment->id }}{{ $compact ? '-c' : '' }}">
                <span class="text-xs font-medium text-slate-600">توضیح کوتاه (اختیاری)</span>
                <input
                    id="cancellation-note-{{ $appointment->id }}{{ $compact ? '-c' : '' }}"
                    name="cancellation_note"
                    type="text"
                    maxlength="200"
                    autocomplete="off"
                    placeholder="بدون جزئیات بالینی"
                    class="w-full min-h-11 rounded-lg border border-slate-300 px-3 text-xs focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30"
                >
            </label>
            <button type="submit" class="{{ $compact ? 'w-full rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-700' : 'rounded-lg border border-rose-300 px-4 py-2 text-sm font-medium text-rose-800 hover:bg-rose-50 focus:outline-none focus:ring-2 focus:ring-rose-700' }}">لغو نوبت</button>
        </form>
    @elseif ($blockReason)
        <p class="{{ $compact ? '' : 'mt-4 ' }}text-xs text-slate-500">{{ $blockReason }}</p>
    @endif
@endcan
