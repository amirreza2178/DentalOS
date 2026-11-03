<?php

namespace App\Http\Controllers;

use App\Actions\Patients\FindPatientDuplicates;
use App\Actions\Patients\SavePatient;
use App\Http\Requests\PatientFormRequest;
use App\Models\Patient;
use App\Support\JalaliCalendar;
use App\Support\SpecialistPatientScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Patient::class);

        $term = trim($request->string('q')->toString());
        $archived = $request->boolean('archived');

        $patients = Patient::query()
            ->when($archived, fn ($query) => $query->onlyTrashed())
            ->when($term !== '', fn ($query) => $query->matchingSearch($term))
            ->tap(fn ($query) => SpecialistPatientScope::constrainPatients($query, $request->user()))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('patients.index', [
            'patients' => $patients,
            'term' => $term,
            'archived' => $archived,
            'specialistScoped' => SpecialistPatientScope::isSpecialistOnly($request->user()),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Patient::class);

        return view('patients.create');
    }

    public function store(PatientFormRequest $request, SavePatient $save, FindPatientDuplicates $duplicates): RedirectResponse
    {
        $patient = $save->create($request->validated());

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', 'پرونده بیمار ثبت شد.')
            ->with('duplicate_notice', $this->duplicateNotice($duplicates, $patient));
    }

    public function show(Patient $patient, FindPatientDuplicates $duplicates, JalaliCalendar $calendar): View
    {
        $this->authorize('view', $patient);

        return view('patients.show', [
            'patient' => $patient,
            'birthDate' => $calendar->formatDate($patient->birth_date),
            'duplicates' => $duplicates->sharingMobile($patient),
            'appointments' => $patient->appointments()->with('treatmentService')->orderByDesc('starts_at')->limit(8)->get(),
            'calendar' => $calendar,
        ]);
    }

    public function edit(Patient $patient, JalaliCalendar $calendar): View
    {
        $this->authorize('update', $patient);

        return view('patients.edit', [
            'patient' => $patient,
            'birthDate' => $calendar->formatDate($patient->birth_date),
        ]);
    }

    public function update(PatientFormRequest $request, Patient $patient, SavePatient $save, FindPatientDuplicates $duplicates): RedirectResponse
    {
        $patient = $save->update($patient, $request->validated());

        return redirect()
            ->route('patients.show', $patient)
            ->with('status', 'پرونده بیمار به‌روزرسانی شد.')
            ->with('duplicate_notice', $this->duplicateNotice($duplicates, $patient));
    }

    public function archive(Patient $patient): RedirectResponse
    {
        $this->authorize('archive', $patient);
        $patient->delete();

        return redirect()
            ->route('patients.index')
            ->with('status', 'پرونده بایگانی شد و حذف دائمی نشد.');
    }

    public function restore(int $patient): RedirectResponse
    {
        $record = Patient::onlyTrashed()->findOrFail($patient);
        $this->authorize('restore', $record);
        $record->restore();

        return redirect()
            ->route('patients.show', $record)
            ->with('status', 'پرونده از بایگانی بازگردانده شد.');
    }

    public function clinicalNotes(Patient $patient): View
    {
        $this->authorize('viewClinicalNotes', $patient);

        return view('patients.clinical-notes', [
            'patient' => $patient,
        ]);
    }

    private function duplicateNotice(FindPatientDuplicates $duplicates, Patient $patient): ?string
    {
        $count = $duplicates->sharingMobile($patient)->count();

        if ($count === 0) {
            return null;
        }

        return 'شماره موبایل با '.$count.' پرونده دیگر مشترک است. پرونده‌ها ادغام نشدند.';
    }
}
