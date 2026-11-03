<?php

namespace App\Http\Controllers;

use App\Actions\Booking\ApproveOnlineAppointment;
use App\Actions\Booking\RejectOnlineAppointment;
use App\Enums\AppointmentSource;
use App\Enums\AppointmentStatus;
use App\Exceptions\Scheduling\InvalidAppointmentTransitionException;
use App\Exceptions\Scheduling\OnlineBookingConflictException;
use App\Http\Requests\RejectOnlineBookingRequest;
use App\Models\Appointment;
use App\Models\Patient;
use App\Support\JalaliCalendar;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnlineBookingRequestController extends Controller
{
    public function index(Request $request, JalaliCalendar $calendar): View
    {
        $this->authorize('viewOnlineRequests', Appointment::class);

        $tab = $request->string('tab')->toString() === 'decided' ? 'decided' : 'pending';

        $base = Appointment::query()
            ->where('source', AppointmentSource::Online)
            ->with([
                'patient:id,first_name,last_name,mobile,identity_status,mobile_normalized',
                'treatmentService:id,name,default_duration_minutes',
                'approvedBy:id,name',
            ])
            ->orderByDesc('starts_at');

        if ($tab === 'pending') {
            $appointments = (clone $base)
                ->where('status', AppointmentStatus::PendingApproval)
                ->orderBy('starts_at')
                ->paginate(20)
                ->withQueryString();
        } else {
            $appointments = (clone $base)
                ->whereIn('status', [AppointmentStatus::Confirmed, AppointmentStatus::Cancelled])
                ->whereNotNull('decided_at')
                ->orderByDesc('decided_at')
                ->paginate(20)
                ->withQueryString();
        }

        $mobileHints = $this->mobileDuplicateHints($appointments->getCollection());

        return view('booking.requests.index', [
            'appointments' => $appointments,
            'tab' => $tab,
            'calendar' => $calendar,
            'mobileHints' => $mobileHints,
        ]);
    }

    public function show(Appointment $appointment, JalaliCalendar $calendar): View
    {
        $this->authorize('viewOnlineRequest', $appointment);

        abort_unless($appointment->source === AppointmentSource::Online, 404);

        $appointment->load([
            'patient:id,code,first_name,last_name,mobile,mobile_normalized,identity_status,notes_admin',
            'treatmentService:id,name,default_duration_minutes,buffer_minutes',
            'approvedBy:id,name',
            'allocations',
        ]);

        $duplicateCount = 0;
        if ($appointment->patient?->mobile_normalized) {
            $duplicateCount = Patient::query()
                ->where('mobile_normalized', $appointment->patient->mobile_normalized)
                ->whereKeyNot($appointment->patient_id)
                ->count();
        }

        return view('booking.requests.show', [
            'appointment' => $appointment,
            'calendar' => $calendar,
            'duplicateCount' => $duplicateCount,
        ]);
    }

    public function approve(Request $request, Appointment $appointment, ApproveOnlineAppointment $approve): RedirectResponse
    {
        $this->authorize('decideOnline', $appointment);

        try {
            $approve->approve($appointment, $request->user());
        } catch (OnlineBookingConflictException|InvalidAppointmentTransitionException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        }

        return $this->decisionRedirect(
            $request,
            $appointment,
            'درخواست تأیید شد. منابع از قبل رزرو شده بودند و دوباره تخصیص نشدند.',
        );
    }

    public function reject(RejectOnlineBookingRequest $request, Appointment $appointment, RejectOnlineAppointment $reject): RedirectResponse
    {
        try {
            $reject->reject(
                $appointment,
                $request->user(),
                $request->validated('decision_reason'),
            );
        } catch (OnlineBookingConflictException|InvalidAppointmentTransitionException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        }

        return $this->decisionRedirect(
            $request,
            $appointment,
            'درخواست رد شد و منابع آزاد شدند.',
        );
    }

    private function decisionRedirect(Request $request, Appointment $appointment, string $status): RedirectResponse
    {
        if ($request->string('return_to')->toString() === 'reception.day') {
            $date = trim($request->string('return_date')->toString());

            return redirect()
                ->route('reception.day', array_filter(['date' => $date !== '' ? $date : null]))
                ->with('status', $status)
                ->withFragment('online-heading');
        }

        return redirect()
            ->route('booking.requests.show', $appointment)
            ->with('status', $status);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Appointment>  $appointments
     * @return array<int, int>
     */
    private function mobileDuplicateHints($appointments): array
    {
        $normalized = $appointments
            ->pluck('patient.mobile_normalized')
            ->filter()
            ->unique()
            ->values();

        if ($normalized->isEmpty()) {
            return [];
        }

        $counts = Patient::query()
            ->selectRaw('mobile_normalized, COUNT(*) as aggregate')
            ->whereIn('mobile_normalized', $normalized->all())
            ->groupBy('mobile_normalized')
            ->pluck('aggregate', 'mobile_normalized');

        $hints = [];
        foreach ($appointments as $appointment) {
            $key = $appointment->patient?->mobile_normalized;
            if ($key === null) {
                continue;
            }
            $total = (int) ($counts[$key] ?? 0);
            if ($total > 1) {
                $hints[$appointment->id] = $total;
            }
        }

        return $hints;
    }
}
