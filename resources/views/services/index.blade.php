<x-management-layout title="خدمات">
    <x-page-header title="خدمات درمانی" description="مدت و قیمت هر خدمت قابل تنظیم است.">
        @can('create', App\Models\TreatmentService::class)
            <a href="{{ route('services.create') }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-700">خدمت جدید</a>
        @endcan
    </x-page-header>
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        @foreach (['active' => 'فعال', 'inactive' => 'غیرفعال', 'all' => 'همه'] as $value => $label)
            <button name="status" value="{{ $value }}" class="rounded-lg border px-3 py-2 text-sm {{ $status === $value ? 'border-teal-700 bg-teal-700 text-white' : 'border-slate-300 bg-white' }} focus:outline-none focus:ring-2 focus:ring-teal-700">{{ $label }}</button>
        @endforeach
    </form>
    @if ($services->isEmpty())
        <x-empty-state title="خدمتی ثبت نشده است" description="فهرست قیمت یا مدت نمونه ساخته نشده است." />
    @else
        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600"><tr><th class="px-4 py-3 text-right font-medium">نام</th><th class="px-4 py-3 text-right font-medium">مدت</th><th class="px-4 py-3 text-right font-medium">قیمت</th><th class="px-4 py-3 text-right font-medium">وضعیت</th></tr></thead>
                <tbody>
                    @foreach ($services as $service)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3">
                                @can('update', $service)
                                    <a class="text-teal-800 hover:underline focus:outline-none focus:ring-2 focus:ring-teal-700" href="{{ route('services.edit', $service) }}">{{ $service->name }}</a>
                                @else
                                    {{ $service->name }}
                                @endcan
                            </td>
                            <td class="px-4 py-3">{{ $service->default_duration_minutes }} دقیقه</td>
                            <td class="px-4 py-3">{{ $service->base_price ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $service->is_active ? 'فعال' : 'غیرفعال' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $services->links() }}</div>
    @endif
</x-management-layout>
