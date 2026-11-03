<?php

namespace App\Actions\Clinical;

use App\Actions\Reception\RecordOperationalEvent;
use App\Enums\FollowUpStatus;
use App\Enums\OperationalEventType;
use App\Models\FollowUp;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\TreatmentService;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class CreateFollowUpRequest
{
    public function __construct(private RecordOperationalEvent $events) {}

    /**
     * @param  array{proposed_window_start: string, proposed_window_end: string, treatment_service_id?: ?int, operational_note?: ?string, patient_visit_id?: ?int}  $data
     */
    public function handle(Patient $patient, User $actor, array $data): FollowUp
    {
        if (! Gate::forUser($actor)->allows('create', FollowUp::class)) {
            throw new AuthorizationException('ثبت پیگیری مجاز نیست.');
        }

        $start = $data['proposed_window_start'];
        $end = $data['proposed_window_end'];

        if ($end < $start) {
            throw new InvalidArgumentException('پایان بازه پیشنهادی نمی‌تواند قبل از شروع باشد.');
        }

        $serviceId = isset($data['treatment_service_id']) && (int) $data['treatment_service_id'] > 0
            ? (int) $data['treatment_service_id']
            : null;

        if ($serviceId !== null && ! TreatmentService::query()->whereKey($serviceId)->where('is_active', true)->exists()) {
            throw new InvalidArgumentException('خدمت پیگیری معتبر نیست.');
        }

        $visit = $this->resolveVisit($patient, $data['patient_visit_id'] ?? null);

        return DB::transaction(function () use ($patient, $actor, $data, $start, $end, $serviceId, $visit): FollowUp {
            $followUp = FollowUp::query()->create([
                'patient_id' => $patient->id,
                'patient_visit_id' => $visit?->id,
                'treatment_service_id' => $serviceId,
                'proposed_window_start' => $start,
                'proposed_window_end' => $end,
                'status' => FollowUpStatus::Proposed,
                'operational_note' => filled($data['operational_note'] ?? null)
                    ? trim((string) $data['operational_note'])
                    : null,
                'created_by' => $actor->id,
            ]);

            $this->events->record(
                OperationalEventType::FollowUpCreated,
                $followUp,
                $actor,
                null,
                $followUp->status->value,
            );

            return $followUp;
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
