<?php

namespace App\Http\Controllers;

use App\Models\CareDocument;
use Illuminate\View\View;

class DocumentVerifyController extends Controller
{
    public function __invoke(string $code): View
    {
        $document = CareDocument::query()
            ->where('verification_code', strtoupper($code))
            ->with(['clinic', 'doctor', 'patient'])
            ->firstOrFail();

        return view('records.verify', ['document' => $document]);
    }
}
