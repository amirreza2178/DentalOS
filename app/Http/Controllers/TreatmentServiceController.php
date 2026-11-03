<?php

namespace App\Http\Controllers;

use App\Http\Requests\TreatmentServiceRequest;
use App\Models\TreatmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreatmentServiceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', TreatmentService::class);

        $status = $request->string('status')->toString() ?: 'active';

        $services = TreatmentService::query()
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('services.index', [
            'services' => $services,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', TreatmentService::class);

        return view('services.create');
    }

    public function store(TreatmentServiceRequest $request): RedirectResponse
    {
        TreatmentService::query()->create($request->validated());

        return redirect()->route('services.index')->with('status', 'خدمت ثبت شد.');
    }

    public function edit(TreatmentService $service): View
    {
        $this->authorize('update', $service);

        return view('services.edit', ['service' => $service]);
    }

    public function update(TreatmentServiceRequest $request, TreatmentService $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()->route('services.index')->with('status', 'خدمت به‌روزرسانی شد.');
    }
}
