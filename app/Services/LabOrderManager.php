<?php

namespace App\Services;

use App\Enums\CollectionMode;
use App\Enums\LabOrderStatus;
use App\Enums\PaymentMode;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\ClinicLabOffering;
use App\Models\LabOrder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LabOrderManager
{
    public function __construct(
        private NotificationDispatcher $notifications,
        private LoyaltyProgram $loyalty,
    ) {}

    /**
     * @param  Collection<int, array{type: string, id: int, qty: int, item: mixed, unit_price: float, line_total: float}>  $lines
     * @param  array{
     *     clinic_id: int,
     *     clinic_address_id?: int|null,
     *     collection_mode: CollectionMode|string,
     *     scheduled_at: mixed,
     *     payment_mode: PaymentMode|string,
     *     patient_home_address?: ?string,
     *     notes?: ?string
     * }  $attributes
     */
    public function create(User $patient, Collection $lines, array $attributes): LabOrder
    {
        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => __('labs.checkout.empty'),
            ]);
        }

        $clinic = Clinic::query()->listable()->with(['labOfferings' => fn ($query) => $query->active()])->findOrFail($attributes['clinic_id']);
        $mode = $attributes['collection_mode'] instanceof CollectionMode
            ? $attributes['collection_mode']
            : CollectionMode::from($attributes['collection_mode']);
        $payment = $attributes['payment_mode'] instanceof PaymentMode
            ? $attributes['payment_mode']
            : PaymentMode::from($attributes['payment_mode']);

        $priced = $this->priceLines($clinic, $lines, $mode);

        $address = null;

        if ($mode === CollectionMode::Clinic) {
            $address = ClinicAddress::query()
                ->whereKey($attributes['clinic_address_id'] ?? 0)
                ->where('clinic_id', $clinic->id)
                ->where('is_active', true)
                ->first();

            if (! $address) {
                throw ValidationException::withMessages([
                    'clinic_address_id' => __('labs.checkout.branch_required'),
                ]);
            }
        } elseif (! filled($attributes['patient_home_address'] ?? null)) {
            throw ValidationException::withMessages([
                'patient_home_address' => __('labs.checkout.home_required'),
            ]);
        }

        return DB::transaction(function () use ($patient, $clinic, $address, $mode, $payment, $priced, $attributes) {
            $order = LabOrder::query()->create([
                'reference' => $this->reference(),
                'patient_id' => $patient->id,
                'clinic_id' => $clinic->id,
                'clinic_address_id' => $address?->id,
                'collection_mode' => $mode,
                'scheduled_at' => $attributes['scheduled_at'],
                'status' => LabOrderStatus::Pending,
                'payment_mode' => $payment,
                'payment_status' => 'unpaid',
                'total' => $priced->sum('line_total'),
                'patient_home_address' => $mode === CollectionMode::Home ? ($attributes['patient_home_address'] ?? null) : null,
                'notes' => $attributes['notes'] ?? null,
            ]);

            foreach ($priced as $line) {
                $order->items()->create([
                    'item_type' => $line['type'],
                    'lab_test_id' => $line['type'] === 'test' ? $line['id'] : null,
                    'lab_package_id' => $line['type'] === 'package' ? $line['id'] : null,
                    'qty' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                ]);
            }

            $this->notify('lab_order_created', $order);

            return $order;
        });
    }

    public function transition(LabOrder $order, LabOrderStatus $status, User $actor): LabOrder
    {
        if (! $order->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => __('clinic.queue.invalid_transition'),
            ]);
        }

        $order->update(['status' => $status]);

        $event = match ($status) {
            LabOrderStatus::Confirmed => 'lab_order_confirmed',
            LabOrderStatus::Completed => 'lab_order_completed',
            LabOrderStatus::Cancelled => 'lab_order_cancelled',
            default => 'lab_order_created',
        };

        $this->notify($event, $order);

        if ($status === LabOrderStatus::Completed) {
            $this->loyalty->rewardCompletedService($order->patient);
        }

        return $order->refresh();
    }

    public function markPayment(LabOrder $order, string $paymentStatus): LabOrder
    {
        $order->update(['payment_status' => $paymentStatus]);

        return $order->refresh();
    }

    /**
     * Clinics that can fulfil every cart line, optionally at home.
     *
     * @param  Collection<int, array{type: string, id: int}>  $lines
     * @return Collection<int, Clinic>
     */
    public function matchingClinics(Collection $lines, bool $home = false)
    {
        if ($lines->isEmpty()) {
            return collect();
        }

        $clinics = Clinic::query()
            ->listable()
            ->with(['addresses' => fn ($query) => $query->active(), 'labOfferings' => fn ($query) => $query->active()])
            ->whereHas('labOfferings', fn ($query) => $query->active())
            ->get();

        return $clinics->filter(function (Clinic $clinic) use ($lines, $home) {
            foreach ($lines as $line) {
                $offering = $this->offeringFor($clinic, $line['type'], (int) $line['id']);

                if (! $offering) {
                    return false;
                }

                if ($home && ! $offering->allows_home_collection) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /**
     * @param  Collection<int, array{type: string, id: int, qty: int}>  $lines
     * @return Collection<int, array{type: string, id: int, qty: int, unit_price: float, line_total: float}>
     */
    private function priceLines(Clinic $clinic, Collection $lines, CollectionMode $mode): Collection
    {
        return $lines->map(function (array $line) use ($clinic, $mode) {
            $offering = $this->offeringFor($clinic, $line['type'], (int) $line['id']);

            if (! $offering) {
                throw ValidationException::withMessages([
                    'clinic_id' => __('labs.checkout.clinic_missing_item'),
                ]);
            }

            if ($mode === CollectionMode::Home && ! $offering->allows_home_collection) {
                throw ValidationException::withMessages([
                    'collection_mode' => __('labs.checkout.home_not_offered'),
                ]);
            }

            $unit = $offering->effectivePrice();
            $qty = max(1, (int) $line['qty']);

            return [
                'type' => $line['type'],
                'id' => (int) $line['id'],
                'qty' => $qty,
                'unit_price' => $unit,
                'line_total' => $unit * $qty,
            ];
        });
    }

    private function offeringFor(Clinic $clinic, string $type, int $id): ?ClinicLabOffering
    {
        $column = $type === 'package' ? 'lab_package_id' : 'lab_test_id';

        return $clinic->labOfferings
            ->first(fn (ClinicLabOffering $offering) => $offering->item_type === $type
                && (int) $offering->{$column} === $id
                && $offering->is_active);
    }

    private function reference(): string
    {
        do {
            $reference = 'LAB-'.strtoupper(Str::random(8));
        } while (LabOrder::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function notify(string $event, LabOrder $order): void
    {
        $order->loadMissing(['patient', 'clinic']);

        $this->notifications->send($event, [
            'lab_order_id' => $order->id,
            'clinic_id' => $order->clinic_id,
            'patient_id' => $order->patient_id,
            'status' => $order->status->value,
            'scheduled_at' => $order->scheduled_at?->toIso8601String(),
            'reference' => $order->reference,
            'name' => $order->patient?->name,
            'clinic' => $order->clinic?->name,
            'date' => $order->scheduled_at?->format('Y-m-d H:i'),
            'title' => $order->reference,
        ]);
    }
}
