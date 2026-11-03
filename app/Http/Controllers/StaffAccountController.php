<?php

namespace App\Http\Controllers;

use App\Actions\Staff\SaveStaffAccount;
use App\Enums\Qualification;
use App\Http\Requests\StaffAccountRequest;
use App\Models\User;
use App\Support\StaffDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffAccountController extends Controller
{
    public function index(Request $request, string $directory): View
    {
        $this->authorize('viewAny', User::class);

        $role = StaffDirectory::role($directory);
        $status = $request->string('status')->toString() ?: 'active';

        abort_unless(
            $request->user()?->can('viewAny', User::class)
            && ($directory !== 'receptionists' || $request->user()->can('create', User::class)),
            403,
        );

        $accounts = User::query()
            ->with('staffProfile')
            ->where('role', $role)
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('staff.index', [
            'accounts' => $accounts,
            'directory' => $directory,
            'title' => StaffDirectory::label($directory),
            'status' => $status,
            'qualifications' => Qualification::cases(),
        ]);
    }

    public function create(string $directory): View
    {
        $this->authorize('create', User::class);
        StaffDirectory::role($directory);

        return view('staff.create', [
            'directory' => $directory,
            'title' => StaffDirectory::label($directory),
            'qualifications' => Qualification::cases(),
        ]);
    }

    public function store(StaffAccountRequest $request, SaveStaffAccount $save, string $directory): RedirectResponse
    {
        $account = $save->create(StaffDirectory::role($directory), $request->validated());

        return redirect()
            ->route('staff.edit', [$directory, $account])
            ->with('status', 'حساب ثبت شد.');
    }

    public function show(string $directory, User $account): View
    {
        $this->ensureDirectory($directory, $account);
        $this->authorize('view', $account);

        return view('staff.show', [
            'account' => $account->load('staffProfile'),
            'directory' => $directory,
            'title' => StaffDirectory::label($directory),
        ]);
    }

    public function edit(string $directory, User $account): View
    {
        $this->ensureDirectory($directory, $account);
        $this->authorize('update', $account);

        return view('staff.edit', [
            'account' => $account->load('staffProfile'),
            'directory' => $directory,
            'title' => StaffDirectory::label($directory),
            'qualifications' => Qualification::cases(),
        ]);
    }

    public function update(StaffAccountRequest $request, SaveStaffAccount $save, string $directory, User $account): RedirectResponse
    {
        $this->ensureDirectory($directory, $account);
        $save->update($account, $request->validated());

        return redirect()
            ->route('staff.edit', [$directory, $account])
            ->with('status', 'حساب به‌روزرسانی شد.');
    }

    private function ensureDirectory(string $directory, User $account): void
    {
        abort_unless($account->role === StaffDirectory::role($directory), 404);
    }
}
