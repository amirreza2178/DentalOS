<x-management-layout title="وظیفه درمانی">
    <x-page-header title="{{ $task->title }}" description="{{ $task->status->label() }} — {{ $task->required_qualification->label() }}">
        <a href="{{ route('clinical.tasks.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">فهرست وظایف</a>
        @can('view', $task->plan)
            <a href="{{ route('clinical.plans.show', $task->treatment_plan_id) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">برنامه</a>
        @endcan
    </x-page-header>

    <dl class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm md:grid-cols-2">
        <div><dt class="text-xs text-slate-500">بیمار</dt><dd class="font-medium">{{ $task->plan?->patient?->fullName() }} — {{ $task->plan?->patient?->code }}</dd></div>
        <div><dt class="text-xs text-slate-500">مسئول</dt><dd>{{ $task->assignee?->name ?? 'تخصیص نشده' }}</dd></div>
        <div><dt class="text-xs text-slate-500">ارجاع‌دهنده</dt><dd>{{ $task->assignedBy?->name ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">وضعیت</dt><dd>{{ $task->status->label() }}</dd></div>
        <div class="md:col-span-2"><dt class="text-xs text-slate-500">دستور عملیاتی</dt><dd class="whitespace-pre-wrap">{{ $task->instructions ?: '—' }}</dd></div>
        @if ($task->completion_note)
            <div class="md:col-span-2"><dt class="text-xs text-slate-500">نتیجه ثبت‌شده</dt><dd class="whitespace-pre-wrap">{{ $task->completion_note }}</dd></div>
        @endif
        @if ($canSeeClinical)
            <div class="md:col-span-2"><dt class="text-xs text-slate-500">خلاصه بالینی برنامه</dt><dd class="whitespace-pre-wrap">{{ $task->plan?->clinical_summary }}</dd></div>
        @else
            <div class="md:col-span-2"><dt class="text-xs text-slate-500">خلاصه عملیاتی برنامه</dt><dd>{{ $task->plan?->operational_summary ?: '—' }}</dd></div>
        @endif
    </dl>

    @if ($canAssign && ! $task->status->isTerminal())
        <section class="mb-4 rounded-2xl border border-slate-200 bg-white p-4" aria-labelledby="assign-heading">
            <h2 id="assign-heading" class="mb-3 text-base font-semibold">تخصیص / تغییر مسئول</h2>
            <form method="POST" action="{{ route('clinical.tasks.assign', $task) }}" class="space-y-3">
                @csrf
                <label class="block space-y-1" for="assignee_user_id">
                    <span class="text-sm font-medium">نیروی واجد صلاحیت {{ $task->required_qualification->label() }}</span>
                    <select id="assignee_user_id" name="assignee_user_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                        <option value="">انتخاب کنید</option>
                        @foreach ($assignees as $assignee)
                            <option value="{{ $assignee->id }}" @selected($task->assignee_user_id === $assignee->id)>{{ $assignee->name }}</option>
                        @endforeach
                    </select>
                </label>
                @if ($assignees->isEmpty())
                    <p class="text-sm text-amber-800">نیروی فعالی با این صلاحیت ثبت نشده است.</p>
                @endif
                <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700" @disabled($assignees->isEmpty())>تخصیص</button>
            </form>
        </section>
    @endif

    @if ($canUpdateStatus && $nextStatuses !== [])
        <section class="rounded-2xl border border-slate-200 bg-white p-4" aria-labelledby="status-heading">
            <h2 id="status-heading" class="mb-3 text-base font-semibold">تغییر وضعیت</h2>
            <form method="POST" action="{{ route('clinical.tasks.status', $task) }}" class="space-y-3">
                @csrf
                <label class="block space-y-1" for="status">
                    <span class="text-sm font-medium">وضعیت بعدی</span>
                    <select id="status" name="status" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                        @foreach ($nextStatuses as $status)
                            @continue($status === \App\Enums\TreatmentTaskStatus::Cancelled && ! $canCancel)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <x-textarea-field name="completion_note" label="یادداشت نتیجه (برای تکمیل الزامی است)" :value="old('completion_note')" />
                <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت وضعیت</button>
            </form>
        </section>
    @endif
</x-management-layout>
