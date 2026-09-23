<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\CareDocumentType;
use App\Models\Booking;
use App\Models\CareDocument;
use App\Models\LabOrder;
use App\Models\User;
use Illuminate\Support\Str;

class CareDocumentManager
{
    public function __construct(private NotificationDispatcher $notifications) {}

    public function recordConsultation(Booking $booking, ?User $actor = null): CareDocument
    {
        $booking->loadMissing(['patient', 'clinic', 'doctor', 'serviceType']);

        $existing = CareDocument::query()
            ->where('booking_id', $booking->id)
            ->where('type', CareDocumentType::Consultation)
            ->first();

        if ($existing) {
            return $existing;
        }

        $title = trim(implode(' — ', array_filter([
            $booking->serviceType?->name ?: __('records.types.consultation'),
            $booking->doctor?->name,
        ])));

        return $this->store([
            'patient_id' => $booking->patient_id,
            'clinic_id' => $booking->clinic_id,
            'doctor_id' => $booking->doctor_id,
            'issued_by_user_id' => $actor?->id,
            'booking_id' => $booking->id,
            'type' => CareDocumentType::Consultation,
            'title' => $title,
            'body' => $booking->evaluation_notes ?: $booking->notes,
            'payload' => [
                'service' => $booking->serviceType?->name,
                'scheduled_at' => $booking->scheduled_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function issueForBooking(Booking $booking, User $actor, array $attributes): CareDocument
    {
        $booking->loadMissing(['patient', 'clinic', 'doctor', 'serviceType']);

        abort_unless(in_array($booking->status, [BookingStatus::InProgress, BookingStatus::Completed], true), 404);

        $type = $attributes['type'] instanceof CareDocumentType
            ? $attributes['type']
            : CareDocumentType::from($attributes['type']);

        $document = $this->store([
            'patient_id' => $booking->patient_id,
            'clinic_id' => $booking->clinic_id,
            'doctor_id' => $booking->doctor_id,
            'issued_by_user_id' => $actor->id,
            'booking_id' => $booking->id,
            'type' => $type,
            'title' => $attributes['title'] ?: $type->label(),
            'body' => $attributes['body'] ?? null,
            'payload' => $attributes['payload'] ?? [],
            'valid_from' => $attributes['valid_from'] ?? null,
            'valid_until' => $attributes['valid_until'] ?? null,
        ]);

        $event = match ($type) {
            CareDocumentType::Prescription => 'prescription_issued',
            CareDocumentType::LabResult => 'lab_result_ready',
            default => null,
        };

        if ($event) {
            $this->notify($event, $document);
        }

        return $document;
    }

    public function recordLabResults(LabOrder $order, ?User $actor = null): CareDocument
    {
        $order->loadMissing(['patient', 'clinic', 'items.labTest', 'items.labPackage']);

        $results = $order->items->map(function ($item) {
            return [
                'name' => $item->catalogItem()?->name,
                'value' => $item->result_value,
                'unit' => $item->result_unit,
                'flag' => $item->result_flag,
                'note' => $item->result_note,
            ];
        })->all();

        $existing = CareDocument::query()
            ->where('lab_order_id', $order->id)
            ->where('type', CareDocumentType::LabResult)
            ->first();

        $attributes = [
            'patient_id' => $order->patient_id,
            'clinic_id' => $order->clinic_id,
            'issued_by_user_id' => $actor?->id,
            'lab_order_id' => $order->id,
            'type' => CareDocumentType::LabResult,
            'title' => __('records.lab_title', ['reference' => $order->reference]),
            'body' => $order->notes,
            'payload' => [
                'reference' => $order->reference,
                'collection_mode' => $order->collection_mode->value,
                'results' => $results,
            ],
        ];

        if ($existing) {
            $existing->update($attributes);

            return $existing->refresh();
        }

        $document = $this->store($attributes);
        $this->notify('lab_result_ready', $document);

        return $document;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function store(array $attributes): CareDocument
    {
        $attributes['verification_code'] ??= $this->verificationCode();
        $attributes['issued_at'] ??= now();
        $attributes['payload'] ??= [];

        return CareDocument::query()->create($attributes);
    }

    private function verificationCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (CareDocument::query()->where('verification_code', $code)->exists());

        return $code;
    }

    private function notify(string $event, CareDocument $document): void
    {
        $document->loadMissing(['patient', 'clinic']);

        $this->notifications->send($event, [
            'care_document_id' => $document->id,
            'patient_id' => $document->patient_id,
            'clinic_id' => $document->clinic_id,
            'clinic' => $document->clinic?->name,
            'name' => $document->patient?->name,
            'title' => $document->title,
            'code' => $document->verification_code,
            'reference' => $document->payload['reference'] ?? $document->verification_code,
        ]);
    }
}
