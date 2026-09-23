<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CareDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = CareDocument::query()
            ->where('patient_id', $request->user()->id)
            ->with(['clinic', 'doctor'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn (CareDocument $document) => $this->payload($document));

        return response()->json(['data' => $documents]);
    }

    public function show(Request $request, CareDocument $careDocument): JsonResponse
    {
        abort_unless($careDocument->isOwnedBy($request->user()), 404);

        $careDocument->load(['clinic', 'doctor', 'booking.serviceType', 'labOrder']);

        return response()->json([
            'data' => $this->payload($careDocument) + [
                'body' => $careDocument->body,
                'medications' => $careDocument->medications(),
                'results' => $careDocument->resultRows(),
                'verification_code' => $careDocument->verification_code,
                'pdf_url' => route('records.pdf', $careDocument),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CareDocument $document): array
    {
        return [
            'id' => $document->id,
            'type' => $document->type->value,
            'title' => $document->title,
            'clinic' => $document->clinic?->name,
            'doctor' => $document->doctor?->name,
            'issued_at' => $document->issued_at?->toIso8601String(),
            'url' => route('records.show', $document),
        ];
    }
}
