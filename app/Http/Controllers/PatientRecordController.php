<?php

namespace App\Http\Controllers;

use App\Models\CareDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PatientRecordController extends Controller
{
    public function index(Request $request): View
    {
        $documents = CareDocument::query()
            ->where('patient_id', $request->user()->id)
            ->with(['clinic', 'doctor'])
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('records.index', compact('documents'));
    }

    public function show(Request $request, CareDocument $careDocument): View
    {
        abort_unless($careDocument->canBeViewedBy($request->user()), 404);

        $careDocument->load(['clinic', 'doctor', 'patient', 'booking.serviceType', 'labOrder']);

        return view('records.show', ['document' => $careDocument]);
    }

    public function pdf(Request $request, CareDocument $careDocument): Response
    {
        abort_unless($careDocument->canBeViewedBy($request->user()), 404);

        $careDocument->load(['clinic', 'doctor', 'patient', 'booking.serviceType', 'labOrder']);

        return response()
            ->view('records.print', [
                'document' => $careDocument,
                'autoPrint' => true,
            ])
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Content-Disposition', 'inline; filename="'.$careDocument->downloadName().'"');
    }
}
