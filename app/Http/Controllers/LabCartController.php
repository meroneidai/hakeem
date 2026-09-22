<?php

namespace App\Http\Controllers;

use App\Services\LabCart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabCartController extends Controller
{
    public function show(Request $request, LabCart $cart): View|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json($cart->toPayload());
        }

        return view('labs.cart', ['cart' => $cart]);
    }

    public function store(Request $request, LabCart $cart): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:test,package'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $cart->add($data['type'], (int) $data['id']);

        return $this->cartResponse($request, $cart, __('labs.cart.added'));
    }

    public function update(Request $request, LabCart $cart): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:test,package'],
            'id' => ['required', 'integer', 'min:1'],
            'qty' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $cart->setQty($data['type'], (int) $data['id'], (int) $data['qty']);

        return $this->cartResponse($request, $cart, __('labs.cart.updated'));
    }

    public function destroy(Request $request, LabCart $cart): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:test,package'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $cart->remove($data['type'], (int) $data['id']);

        return $this->cartResponse($request, $cart, __('labs.cart.removed'));
    }

    private function cartResponse(Request $request, LabCart $cart, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json($cart->toPayload() + ['message' => $message]);
        }

        return back()->with('status', $message);
    }
}
