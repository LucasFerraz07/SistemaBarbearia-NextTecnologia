<?php

namespace App\Services;

use App\Mail\NewSchedulingNotification;
use App\Mail\SchedulingCancelledNotification;
use App\Mail\SchedulingUpdatedNotification;
use App\Models\Scheduling;
use App\Models\User;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SchedulingService
{
    public function index(): Collection
    {
        return Scheduling::select('id', 'start_date', 'end_date')->get();
    }

    public function show(int $id): ?Scheduling
    {
        return Scheduling::with('client.user')->find($id);
    }

    public function store(array $data): Scheduling
    {
        $endDate = Carbon::parse($data['start_date'])->addMinutes(90);

        $scheduling = DB::transaction(function () use ($data, $endDate) {
            $conflict = Scheduling::lockForUpdate()
                ->where('start_date', '<', $endDate)
                ->where('end_date', '>', $data['start_date'])
                ->exists();

            if ($conflict) {
                abort(422, 'Horário já está reservado');
            }

            return Scheduling::create([
                'client_id'  => $data['client_id'],
                'start_date' => $data['start_date'],
                'end_date'   => $endDate,
            ]);
        });

        $scheduling->load('client.user');

        $this->notifyAdmins('new', $scheduling);

        return $scheduling;
    }

    public function update(int $id, array $data): ?Scheduling
    {
        $scheduling = Scheduling::find($id);

        if (!$scheduling) {
            return null;
        }

        DB::transaction(function () use ($data, $scheduling, $id) {
            if (isset($data['start_date'])) {
                $endDate = Carbon::parse($data['start_date'])->addMinutes(90);

                $conflict = Scheduling::lockForUpdate()
                    ->where('id', '!=', $id)
                    ->where('start_date', '<', $endDate)
                    ->where('end_date', '>', $data['start_date'])
                    ->exists();

                if ($conflict) {
                    abort(422, 'Horário já está reservado');
                }

                $scheduling->update([
                    'start_date' => $data['start_date'],
                    'end_date'   => $endDate,
                ]);
            }
        });

        $updated = $scheduling->fresh()->load('client.user');

        $this->notifyAdmins('updated', $updated);

        return $updated;
    }

    public function destroy(int $id): bool
    {
        $scheduling = Scheduling::with('client.user')->find($id);

        if (!$scheduling) {
            return false;
        }

        $this->notifyAdmins('cancelled', $scheduling);

        $scheduling->delete();

        return true;
    }

    private function notifyAdmins(string $event, Scheduling $scheduling): void
    {
        $adminType = UserType::where('name', 'administrador')->firstOrFail();
        $admins = User::where('user_type_id', $adminType->id)->get();

        $mail = match ($event) {
            'new'       => fn ($admin) => Mail::to($admin->email)->send(new NewSchedulingNotification($scheduling)),
            'updated'   => fn ($admin) => Mail::to($admin->email)->send(new SchedulingUpdatedNotification($scheduling)),
            'cancelled' => fn ($admin) => Mail::to($admin->email)->send(new SchedulingCancelledNotification($scheduling)),
        };

        foreach ($admins as $admin) {
            $mail($admin);
        }
    }
}
