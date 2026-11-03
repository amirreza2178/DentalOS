<?php

namespace App\Actions\Clinical;

use App\Actions\Reception\RecordOperationalEvent;
use App\Enums\OperationalEventType;
use App\Enums\TreatmentPlanStatus;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SaveTreatmentPlan
{
    public function __construct(private RecordOperationalEvent $events) {}

    /**
     * @param  array{clinical_summary: string, operational_summary?: ?string, status?: string, patient_visit_id?: ?int}  $data
     */
    public function create(Patient $patient, User $actor, array $data): TreatmentPlan
    {
        if (! Gate::forUser($actor)->allows('create', TreatmentPlan::class)) {
            throw new AuthorizationException('ثبت برنامه درمانی مجاز نیست.');
        }

        $visit = $this->resolveVisit($patient, $data['patient_visit_id'] ?? null);

        return DB::transaction(function () use ($patient, $actor, $data, $visit): TreatmentPlan {
            $plan = TreatmentPlan::query()->create([
                'patient_id' => $patient->id,
                'patient_visit_id' => $visit?->id,
                'created_by_user_id' => $actor->id,
                'status' => TreatmentPlanStatus::tryFrom($data['status'] ?? '') ?? TreatmentPlanStatus::Active,
                'clinical_summary' => trim($data['clinical_summary']),
                'operational_summary' => filled($data['operational_summary'] ?? null)
                    ? trim((string) $data['operational_summary'])
                    : null,
            ]);

            $this->events->record(
                OperationalEventType::TreatmentPlanCreated,
                $plan,
                $actor,
                null,
                $plan->status->value,
            );

            return $plan;
        });
    }

    /**
     * @param  array{clinical_summary: string, operational_summary?: ?string, status?: string}  $data
     */
    public function update(TreatmentPlan $plan, User $actor, array $data): TreatmentPlan
    {
        if (! Gate::forUser($actor)->allows('update', $plan)) {
            throw new AuthorizationException('ویرایش برنامه درمانی مجاز نیست.');
        }

        return DB::transaction(function () use ($plan, $actor, $data): TreatmentPlan {
            $from = $plan->status->value;
            $nextStatus = TreatmentPlanStatus::tryFrom($data['status'] ?? $from) ?? $plan->status;

            $plan->fill([
                'clinical_summary' => trim($data['clinical_summary']),
                'operational_summary' => filled($data['operational_summary'] ?? null)
                    ? trim((string) $data['operational_summary'])
                    : null,
                'status' => $nextStatus,
            ]);
            $plan->save();

            $this->events->record(
                $from === $plan->status->value
                    ? OperationalEventType::TreatmentPlanUpdated
                    : OperationalEventType::TreatmentPlanStatusChanged,
                $plan,
                $actor,
                $from,
                $plan->status->value,
            );

            return $plan->refresh();
        });
    }

    private function resolveVisit(Patient $patient, mixed $visitId): ?PatientVisit
    {
        if ($visitId === null || $visitId === '' || (int) $visitId <= 0) {
            return null;
        }

        $visit = PatientVisit::query()->whereKey((int) $visitId)->first();

        if ($visit === null || $visit->patient_id !== $patient->id) {
            throw new AuthorizationException('ویزیت انتخاب‌شده به این بیمار تعلق ندارد.');
        }

        return $visit;
    }
}
