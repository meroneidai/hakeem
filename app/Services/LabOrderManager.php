<?php

namespace App\Services;

use App\Enums\CollectionMode;
use App\Enums\LabOrderStatus;
use App\Enums\PaymentMode;
use App\Models\Clinic;
use App\Models\ClinicAddress;
use App\Models\ClinicLabOffering;
use App\Models\LabOrder;
use App\Models\PatientAddress;
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
        private CareDocumentManager $documents,
    ) {}

    /**
     * @param  Collection<int, array{type: string, id: int, qty: int, item: mixed, unit_price: float, line_total: float}>  $lines
     * @param  array{
     *     clinic_id: int,
     *     clinic_address_id?: int|null,
     *     patient_address_id?: int|null,
     *     save_address?: bool,
     *     address_label?: ?string,
     *     collection_mode: CollectionMode|string,
     *     scheduled_at: mixed,
     *     payment_mode: PaymentMode|string,
     *     patient_home_address?: ?string,
     *     latitude?: float|string|null,
     *     longitude?: float|string|null,
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
        $homeAddress = null;
        $patientAddressId = null;
        $latitude = null;
        $longitude = null;

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
        } else {
            $home = $this->resolveHomeAddress($patient, $attributes);
            $homeAddress = $home['line'];
            $patientAddressId = $home['id'];
            $latitude = $home['latitude'];
            $longitude = $home['longitude'];
        }

        return DB::transaction(function () use ($patient, $clinic, $address, $mode, $payment, $priced, $attributes, $homeAddress, $patientAddressId, $latitude, $longitude) {
            $order = LabOrder::query()->create([
                'reference' => $this->reference(),
                'patient_id' => $patient->id,
                'clinic_id' => $clinic->id,
                'clinic_address_id' => $address?->id,
                'patient_address_id' => $patientAddressId,
                'collection_mode' => $mode,
                'scheduled_at' => $attributes['scheduled_at'],
                'status' => LabOrderStatus::Pending,
                'payment_mode' => $payment,
                'payment_status' => 'unpaid',
                'total' => $priced->sum('line_total'),
                'patient_home_address' => $homeAddress,
                'latitude' => $latitude,
                'longitude' => $longitude,
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

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    public function transition(LabOrder $order, LabOrderStatus $status, User $actor, array $results = []): LabOrder
    {
        if (! $order->status->canTransitionTo($status)) {
            throw ValidationException::withMessages([
                'status' => __('clinic.queue.invalid_transition'),
            ]);
        }

        $order->update(['status' => $status]);

        if ($status === LabOrderStatus::Completed) {
            $this->storeItemResults($order, $results);
            $this->documents->recordLabResults($order->fresh(['items.labTest', 'items.labPackage']), $actor);
            $this->loyalty->rewardCompletedService($order->patient);
        }

        $event = match ($status) {
            LabOrderStatus::Confirmed => 'lab_order_confirmed',
            LabOrderStatus::Completed => 'lab_order_completed',
            LabOrderStatus::Cancelled => 'lab_order_cancelled',
            default => 'lab_order_created',
        };

        $this->notify($event, $order);

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

    public function supportsHome(Clinic $clinic, Collection $lines): bool
    {
        foreach ($lines as $line) {
            $offering = $this->offeringFor($clinic, $line['type'], (int) $line['id']);

            if (! $offering || ! $offering->allows_home_collection) {
                return false;
            }
        }

        return $lines->isNotEmpty();
    }

    /**
     * @param  Collection<int, array{type: string, id: int, qty?: int}>  $lines
     * @return array<int, array<string, mixed>>
     */
    public function checkoutQuotes(Collection $clinics, Collection $lines): array
    {
        return $clinics->map(function (Clinic $clinic) use ($lines) {
            $clinicLines = $this->priceLines($clinic, $lines, CollectionMode::Clinic);
            $homeOk = $this->supportsHome($clinic, $lines);
            $homeLines = $homeOk
                ? $this->priceLines($clinic, $lines, CollectionMode::Home)
                : $clinicLines;

            return [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'home' => $homeOk,
                'clinic_total' => (float) $clinicLines->sum('line_total'),
                'home_total' => (float) $homeLines->sum('line_total'),
                'addresses' => $clinic->addresses->map(fn ($address) => [
                    'id' => $address->id,
                    'name' => $address->displayName(),
                ])->values()->all(),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, array{type: string, id: int, qty: int}>  $lines
     * @return Collection<int, array{type: string, id: int, qty: int, unit_price: float, line_total: float}>
     */
    public function priceLines(Clinic $clinic, Collection $lines, CollectionMode $mode): Collection
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

            $unit = $offering->priceFor($mode);
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

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{line: string, id: int|null, latitude: string, longitude: string}
     */
    private function resolveHomeAddress(User $patient, array $attributes): array
    {
        $savedId = (int) ($attributes['patient_address_id'] ?? 0);
        $saved = null;

        if ($savedId > 0) {
            $saved = PatientAddress::query()
                ->where('user_id', $patient->id)
                ->find($savedId);

            if (! $saved) {
                throw ValidationException::withMessages([
                    'patient_address_id' => __('labs.checkout.home_required'),
                ]);
            }
        }

        $latitude = $this->coordinate($attributes['latitude'] ?? $saved?->latitude);
        $longitude = $this->coordinate($attributes['longitude'] ?? $saved?->longitude);

        if ($latitude === null || $longitude === null) {
            throw ValidationException::withMessages([
                'latitude' => __('labs.checkout.map_required'),
            ]);
        }

        $line = trim((string) ($attributes['patient_home_address'] ?? ''));

        if ($saved && $line === '') {
            $line = $saved->line;
        }

        if ($line === '') {
            $line = __('labs.checkout.map_point_line', ['lat' => $latitude, 'lng' => $longitude]);
        }

        $addressId = $saved?->id;

        if ($saved) {
            $saved->update([
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        } elseif (! empty($attributes['save_address'])) {
            $isFirst = ! $patient->addresses()->exists();

            $created = $patient->addresses()->create([
                'label' => filled($attributes['address_label'] ?? null)
                    ? $attributes['address_label']
                    : __('labs.checkout.address_home_label'),
                'line' => $line,
                'city_id' => $patient->city_id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'is_default' => $isFirst,
            ]);

            $addressId = $created->id;
        }

        return [
            'line' => $line,
            'id' => $addressId,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    private function coordinate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, 7, '.', '');
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function storeItemResults(LabOrder $order, array $results): void
    {
        if ($results === []) {
            return;
        }

        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $row = $results[$item->id] ?? $results[(string) $item->id] ?? null;

            if (! is_array($row)) {
                continue;
            }

            $item->update([
                'result_value' => filled($row['value'] ?? null) ? $row['value'] : null,
                'result_unit' => filled($row['unit'] ?? null) ? $row['unit'] : null,
                'result_flag' => filled($row['flag'] ?? null) ? $row['flag'] : null,
                'result_note' => filled($row['note'] ?? null) ? $row['note'] : null,
            ]);
        }
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
