<?php

namespace Tests\Feature;

use App\Enums\BillingCycle;
use App\Enums\InvoicePaymentMethod;
use App\Enums\InvoicePaymentStatus;
use App\Enums\RoleName;
use App\Enums\SubscriptionStatus;
use App\Models\Clinic;
use App\Models\ClinicSubscription;
use App\Models\InAppNotification;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_agent_cannot_open_billing(): void
    {
        $this->actingAsRole(RoleName::SupportAgent);

        $this->get('/admin/billing')->assertForbidden();
    }

    public function test_admin_updates_invoice_payment_status_and_plan(): void
    {
        $this->freezeTime();
        $this->actingAsRole(RoleName::PlatformAdmin);
        $catalog = $this->seedClinicCatalog();
        $paidPlan = SubscriptionPlan::query()->create([
            'name_ar' => 'النمو',
            'name_en' => 'Growth',
            'slug' => 'growth-test',
            'monthly_price' => 2500,
            'yearly_price' => 25000,
            'is_default_free' => false,
            'is_active' => true,
        ]);
        $clinic = Clinic::factory()->create([
            'subscription_plan_id' => $catalog['plan']->id,
            'email' => 'clinic-billing@hakeem.test',
        ]);
        $subscription = ClinicSubscription::factory()->create([
            'clinic_id' => $clinic->id,
            'subscription_plan_id' => $catalog['plan']->id,
            'amount' => 1500,
            'payment_status' => InvoicePaymentStatus::Pending,
        ]);

        $this->get('/admin/billing')->assertOk()->assertSee($subscription->invoice_number);
        $this->get('/admin/billing/'.$subscription->id)
            ->assertOk()
            ->assertSee(__('admin.billing.send_invoice'))
            ->assertSee(__('admin.billing.send_reminder'));

        $this->from('/admin/billing/'.$subscription->id)
            ->put('/admin/billing/'.$subscription->id, [
                'subscription_plan_id' => $paidPlan->id,
                'billing_cycle' => BillingCycle::Yearly->value,
                'status' => SubscriptionStatus::Active->value,
                'payment_status' => InvoicePaymentStatus::Paid->value,
                'amount' => '25000',
                'payment_method' => InvoicePaymentMethod::BankTransfer->value,
                'payment_reference' => 'TRX-9921',
                'notes' => 'تم التحويل يدوياً',
            ])
            ->assertRedirect('/admin/billing/'.$subscription->id);

        $subscription->refresh();

        $this->assertSame($paidPlan->id, $subscription->subscription_plan_id);
        $this->assertSame(BillingCycle::Yearly, $subscription->billing_cycle);
        $this->assertSame(InvoicePaymentStatus::Paid, $subscription->payment_status);
        $this->assertSame(InvoicePaymentMethod::BankTransfer, $subscription->payment_method);
        $this->assertSame('TRX-9921', $subscription->payment_reference);
        $this->assertSame('25000.00', $subscription->amount);
        $this->assertNotNull($subscription->paid_at);
        $this->assertSame($paidPlan->id, $clinic->fresh()->subscription_plan_id);
    }

    public function test_admin_sends_invoice_and_reminder_to_clinic_owner(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);
        $catalog = $this->seedClinicCatalog();
        $owner = User::factory()->create(['name' => 'مالك الفاتورة']);
        $clinic = Clinic::factory()->create([
            'owner_user_id' => $owner->id,
            'subscription_plan_id' => $catalog['plan']->id,
        ]);
        $subscription = ClinicSubscription::factory()->create([
            'clinic_id' => $clinic->id,
            'subscription_plan_id' => $catalog['plan']->id,
            'amount' => 900,
        ]);

        $this->from('/admin/billing/'.$subscription->id)
            ->post('/admin/billing/'.$subscription->id.'/invoice')
            ->assertRedirect()
            ->assertSessionHas('status', __('admin.billing.invoice_sent'));

        $this->assertNotNull($subscription->fresh()->invoice_sent_at);
        $this->assertTrue(
            InAppNotification::query()
                ->where('user_id', $owner->id)
                ->where('event', 'invoice_issued')
                ->exists()
        );

        $this->from('/admin/billing/'.$subscription->id)
            ->post('/admin/billing/'.$subscription->id.'/reminder')
            ->assertRedirect()
            ->assertSessionHas('status', __('admin.billing.reminder_sent'));

        $this->assertNotNull($subscription->fresh()->reminder_sent_at);
        $this->assertTrue(
            InAppNotification::query()
                ->where('user_id', $owner->id)
                ->where('event', 'invoice_reminder')
                ->exists()
        );
    }

    public function test_admin_rejects_invalid_payment_status(): void
    {
        $this->actingAsRole(RoleName::PlatformAdmin);
        $catalog = $this->seedClinicCatalog();
        $clinic = Clinic::factory()->create(['subscription_plan_id' => $catalog['plan']->id]);
        $subscription = ClinicSubscription::factory()->create([
            'clinic_id' => $clinic->id,
            'subscription_plan_id' => $catalog['plan']->id,
        ]);

        $this->from('/admin/billing/'.$subscription->id)
            ->put('/admin/billing/'.$subscription->id, [
                'subscription_plan_id' => $catalog['plan']->id,
                'billing_cycle' => BillingCycle::Monthly->value,
                'status' => SubscriptionStatus::Active->value,
                'payment_status' => 'not-a-status',
                'amount' => '100',
            ])
            ->assertRedirect('/admin/billing/'.$subscription->id)
            ->assertSessionHasErrors('payment_status');
    }
}
