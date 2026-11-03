<x-management-layout title="وظایف درمانی">
    <x-page-header
        title="{{ $mineOnly ? 'وظایف من' : 'وظایف درمانی' }}"
        description="{{ $mineOnly ? 'فقط وظایف محول‌شده به شما نمایش داده می‌شود.' : 'وضعیت اقدامات بالینی محول‌شده.' }}"
    />

    @if ($tasks->isEmpty())
        <x-empty-state title="وظیفه‌ای برای نمایش نیست" description="تا وقتی وظیفه‌ای تخصیص داده نشود، این فهرست خالی است." />
    @else
        <ul class="space-y-2 text-sm">
            @foreach ($tasks as $task)
                <li class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <a class="font-semibold text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('clinical.tasks.show', $task) }}">{{ $task->title }}</a>
                    <p class="mt-1 text-slate-600">
                        {{ $task->status->label() }}
                        · بیمار {{ $task->plan?->patient?->fullName() ?? '—' }}
                        · صلاحیت {{ $task->required_qualification->label() }}
                    </p>
                </li>
            @endforeach
        </ul>
        <div class="mt-4">{{ $tasks->links() }}</div>
    @endif
</x-management-layout>
