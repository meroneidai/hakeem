<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BillingCycle;
use App\Enums\BookingStatus;
use App\Enums\InvoicePaymentMethod;
use App\Enums\InvoicePaymentStatus;
use App\Enums\Permission;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\ClinicSubscription;
use App\Models\DiscountCode;
use App\Models\SubscriptionPlan;
use App\Services\MarketplaceInsights;
use App\Services\NotificationDispatcher;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BillingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['can:'.Permission::ManageSubscriptionPlans->value];
    }

    public function index(MarketplaceInsights $insights): View
    {
        $since = now()->subDays(30);

        $subscriptions = ClinicSubscription::query()
            ->with(['clinic', 'plan', 'discountCode'])
            ->latest('current_period_start')
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.billing.index', [
            'subscriptions' => $subscriptions,
            'totals' => [
                'subscription_amount' => (float) ClinicSubscription::query()->sum('amount'),
                'period_amount' => (float) ClinicSubscription::query()
                    ->where('current_period_start', '>=', $since)
                    ->sum('amount'),
                'paid_bookings' => Booking::query()
                    ->where('created_at', '>=', $since)
                    ->where('payment_status', 'paid')
                    ->count(),
                'completed_bookings' => Booking::query()
                    ->where('created_at', '>=', $since)
                    ->where('status', BookingStatus::Completed)
                    ->count(),
            ],
            'insights' => $insights->snapshot($since),
        ]);
    }

    public function show(ClinicSubscription $subscription): View
    {
        $subscription->load(['clinic.owner', 'plan', 'discountCode']);

        return view('admin.billing.show', [
            'subscription' => $subscription,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, ClinicSubscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'billing_cycle' => ['required', Rule::enum(BillingCycle::class)],
            'status' => ['required', Rule::enum(SubscriptionStatus::class)],
            'payment_status' => ['required', Rule::enum(InvoicePaymentStatus::class)],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::enum(InvoicePaymentMethod::class)],
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'current_period_start' => ['nullable', 'date'],
            'current_period_end' => ['nullable', 'date'],
            'discount_code_id' => ['nullable', 'integer', 'exists:discount_codes,id'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $data['payment_method'] = filled($data['payment_method'] ?? null)
            ? InvoicePaymentMethod::from($data['payment_method'])
            : null;
        $data['discount_code_id'] = filled($data['discount_code_id'] ?? null)
            ? (int) $data['discount_code_id']
            : null;
        $data['paid_at'] = $this->parseLocalDateTime($data['paid_at'] ?? null);
        $data['due_at'] = $this->parseLocalDateTime($data['due_at'] ?? null);
        $data['current_period_start'] = $this->parseLocalDateTime($data['current_period_start'] ?? null);
        $data['current_period_end'] = $this->parseLocalDateTime($data['current_period_end'] ?? null);

        $paymentStatus = InvoicePaymentStatus::from($data['payment_status']);
        $subscriptionStatus = SubscriptionStatus::from($data['status']);

        if ($paymentStatus === InvoicePaymentStatus::Paid && $data['paid_at'] === null) {
            $data['paid_at'] = now();
        }

        $data['cancelled_at'] = $subscriptionStatus === SubscriptionStatus::Cancelled
            ? ($subscription->cancelled_at ?? now())
            : null;

        $before = $subscription->getOriginal();
        $subscription->update($data);

        $clinic = $subscription->clinic;

        if ($clinic && (int) $clinic->subscription_plan_id !== (int) $subscription->subscription_plan_id) {
            $clinic->update(['subscription_plan_id' => $subscription->subscription_plan_id]);
        }

        Audit::updated($subscription, $before);

        return redirect()
            ->route('admin.billing.show', $subscription)
            ->with('status', __('common.updated_successfully'));
    }

    public function sendInvoice(ClinicSubscription $subscription, NotificationDispatcher $dispatcher): RedirectResponse
    {
        $this->notify($subscription, $dispatcher, 'invoice_issued');
        $subscription->update(['invoice_sent_at' => now()]);
        Audit::log('clinic_subscription.invoice_sent', $subscription);

        return back()->with('status', __('admin.billing.invoice_sent'));
    }

    public function sendReminder(ClinicSubscription $subscription, NotificationDispatcher $dispatcher): RedirectResponse
    {
        $this->notify($subscription, $dispatcher, 'invoice_reminder');
        $subscription->update(['reminder_sent_at' => now()]);
        Audit::log('clinic_subscription.reminder_sent', $subscription);

        return back()->with('status', __('admin.billing.reminder_sent'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'plans' => SubscriptionPlan::query()->ordered()->get()
                ->mapWithKeys(fn (SubscriptionPlan $plan) => [$plan->id => $plan->name])
                ->all(),
            'cycles' => collect(BillingCycle::cases())
                ->mapWithKeys(fn (BillingCycle $cycle) => [$cycle->value => $cycle->label()])
                ->all(),
            'statuses' => collect(SubscriptionStatus::cases())
                ->mapWithKeys(fn (SubscriptionStatus $status) => [$status->value => $status->label()])
                ->all(),
            'paymentStatuses' => collect(InvoicePaymentStatus::cases())
                ->mapWithKeys(fn (InvoicePaymentStatus $status) => [$status->value => $status->label()])
                ->all(),
            'paymentMethods' => collect(InvoicePaymentMethod::cases())
                ->mapWithKeys(fn (InvoicePaymentMethod $method) => [$method->value => $method->label()])
                ->all(),
            'discountCodes' => DiscountCode::query()->orderBy('code')->get()
                ->mapWithKeys(fn (DiscountCode $code) => [$code->id => $code->code])
                ->all(),
        ];
    }

    private function notify(ClinicSubscription $subscription, NotificationDispatcher $dispatcher, string $event): void
    {
        $subscription->loadMissing(['clinic.owner', 'plan']);
        $owner = $subscription->clinic?->owner;
        $due = $subscription->due_at?->copy()->timezone(config('hakeem.display_timezone'))->format('Y-m-d');

        $dispatcher->send($event, [
            'clinic_id' => $subscription->clinic_id,
            'subscription_id' => $subscription->id,
            'user_id' => $owner?->id,
            'name' => $owner?->name ?? $subscription->clinic?->name,
            'clinic' => $subscription->clinic?->name,
            'invoice' => $subscription->invoice_number,
            'amount' => number_format((float) $subscription->amount).' '.__('common.currency'),
            'date' => $due,
            'plan' => $subscription->plan?->name,
        ]);
    }

    private function parseLocalDateTime(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value, config('hakeem.display_timezone'))->utc();
    }
}
