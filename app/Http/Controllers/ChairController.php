<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChairRequest;
use App\Models\Chair;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChairController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Chair::class);

        $status = $request->string('status')->toString() ?: 'active';

        $chairs = Chair::query()
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('code')
            ->paginate(20)
            ->withQueryString();

        return view('chairs.index', [
            'chairs' => $chairs,
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Chair::class);

        return view('chairs.create');
    }

    public function store(ChairRequest $request): RedirectResponse
    {
        Chair::query()->create($request->validated());

        return redirect()->route('chairs.index')->with('status', 'یونیت ثبت شد.');
    }

    public function edit(Chair $chair): View
    {
        $this->authorize('update', $chair);

        return view('chairs.edit', ['chair' => $chair]);
    }

    public function update(ChairRequest $request, Chair $chair): RedirectResponse
    {
        $chair->update($request->validated());

        return redirect()->route('chairs.index')->with('status', 'یونیت به‌روزرسانی شد.');
    }
}
