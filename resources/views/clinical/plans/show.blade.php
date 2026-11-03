<x-management-layout title="برنامه درمانی">
    <x-page-header title="برنامه درمانی #{{ $plan->id }}" description="{{ $plan->patient?->fullName() }} — {{ $plan->status->label() }}">
        <a href="{{ route('clinical.plans.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">فهرست</a>
        @can('create', App\Models\FollowUp::class)
            <a href="{{ route('clinical.follow-ups.create', ['patient' => $plan->patient_id, 'visit_id' => $plan->patient_visit_id]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت پیگیری</a>
        @endcan
    </x-page-header>

    <dl class="mb-4 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 text-sm md:grid-cols-2">
        <div><dt class="text-xs text-slate-500">بیمار</dt><dd class="font-medium">{{ $plan->patient?->fullName() }} — {{ $plan->patient?->code }}</dd></div>
        <div><dt class="text-xs text-slate-500">ثبت‌کننده</dt><dd>{{ $plan->createdBy?->name ?? '—' }}</dd></div>
        <div><dt class="text-xs text-slate-500">وضعیت</dt><dd>{{ $plan->status->label() }}</dd></div>
        <div><dt class="text-xs text-slate-500">ویزیت مرتبط</dt><dd>{{ $plan->patient_visit_id ? '#'.$plan->patient_visit_id : '—' }}</dd></div>
        <div class="md:col-span-2"><dt class="text-xs text-slate-500">خلاصه عملیاتی</dt><dd>{{ $plan->operational_summary ?: '—' }}</dd></div>
        @if ($canSeeClinical)
            <div class="md:col-span-2"><dt class="text-xs text-slate-500">تصمیم بالینی</dt><dd class="whitespace-pre-wrap">{{ $plan->clinical_summary }}</dd></div>
        @endif
    </dl>

    @if ($canEdit)
        <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-4" aria-labelledby="edit-plan-heading">
            <h2 id="edit-plan-heading" class="mb-3 text-base font-semibold">ویرایش برنامه</h2>
            <form method="POST" action="{{ route('clinical.plans.update', $plan) }}" class="space-y-3">
                @csrf
                @method('PUT')
                <x-textarea-field name="clinical_summary" label="تصمیم بالینی" :value="old('clinical_summary', $plan->clinical_summary)" required />
                <x-textarea-field name="operational_summary" label="خلاصه عملیاتی" :value="old('operational_summary', $plan->operational_summary)" />
                <label class="block space-y-1" for="plan-status">
                    <span class="text-sm font-medium">وضعیت</span>
                    <select id="plan-status" name="status" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $plan->status->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">ذخیره</button>
            </form>
        </section>
    @endif

    <section class="mb-6" aria-labelledby="tasks-heading">
        <h2 id="tasks-heading" class="mb-2 text-base font-semibold">وظایف درمانی</h2>
        @if ($plan->tasks->isEmpty())
            <x-empty-state title="وظیفه‌ای ثبت نشده" />
        @else
            <ul class="space-y-2 text-sm">
                @foreach ($plan->tasks as $task)
                    <li class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <a class="font-medium text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('clinical.tasks.show', $task) }}">{{ $task->title }}</a>
                        <span class="text-slate-500"> — {{ $task->status->label() }}</span>
                        <span class="text-slate-500"> · صلاحیت: {{ $task->required_qualification->label() }}</span>
                        <span class="text-slate-500"> · مسئول: {{ $task->assignee?->name ?? 'تخصیص نشده' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($canAssign)
        <section class="rounded-2xl border border-slate-200 bg-white p-4" aria-labelledby="new-task-heading">
            <h2 id="new-task-heading" class="mb-3 text-base font-semibold">ارجاع وظیفه جدید</h2>
            <form method="POST" action="{{ route('clinical.plans.tasks.store', $plan) }}" class="space-y-3">
                @csrf
                <x-text-field name="title" label="عنوان وظیفه" :value="old('title')" required />
                <x-textarea-field name="instructions" label="دستور عملیاتی برای نیروی درمانی" :value="old('instructions')" />
                <label class="block space-y-1" for="required_qualification">
                    <span class="text-sm font-medium">صلاحیت لازم</span>
                    <select id="required_qualification" name="required_qualification" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                        @foreach ($qualifications as $qualification)
                            <option value="{{ $qualification->value }}" @selected(old('required_qualification') === $qualification->value)>{{ $qualification->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block space-y-1" for="assignee_user_id">
                    <span class="text-sm font-medium">نیروی درمانی (اختیاری؛ فقط با صلاحیت منطبق)</span>
                    <select id="assignee_user_id" name="assignee_user_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-700/30">
                        <option value="">بعداً تخصیص داده شود</option>
                        @foreach ($assignees as $assignee)
                            <option value="{{ $assignee->id }}" @selected((string) old('assignee_user_id') === (string) $assignee->id)>
                                {{ $assignee->name }} — {{ $assignee->staffProfile?->qualification?->label() ?? 'بدون صلاحیت' }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">ثبت وظیفه</button>
            </form>
        </section>
    @endif
</x-management-layout>
