<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleName;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private SupportTicket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agent = $this->actingAsRole(RoleName::SupportAgent);

        $this->ticket = SupportTicket::create([
            'opened_by_user_id' => User::factory()->create()->id,
            'channel' => 'whatsapp',
            'subject' => 'لم يصلني تأكيد الحجز',
            'category' => 'booking',
        ]);
    }

    public function test_a_reference_is_generated_automatically(): void
    {
        $this->assertMatchesRegularExpression('/^HK-[A-Z0-9]{8}$/', $this->ticket->reference);
    }

    public function test_replying_starts_the_sla_clock_and_claims_the_ticket(): void
    {
        $this->post('/admin/support/'.$this->ticket->id.'/reply', [
            'body' => 'تم تأكيد حجزك، وسنعيد إرسال الرسالة الآن.',
        ])->assertRedirect();

        $this->ticket->refresh();

        $this->assertSame('in_progress', $this->ticket->status);
        $this->assertNotNull($this->ticket->first_response_at);
        $this->assertSame($this->agent->id, $this->ticket->assigned_agent_id);
    }

    /**
     * Internal notes are for the team, so they must not count as a customer response.
     */
    public function test_an_internal_note_does_not_start_the_sla_clock(): void
    {
        $this->post('/admin/support/'.$this->ticket->id.'/reply', [
            'body' => 'راجعت سجل الإرسال، الرسالة فشلت على واتساب.',
            'is_internal_note' => '1',
        ])->assertRedirect();

        $this->ticket->refresh();

        $this->assertSame('open', $this->ticket->status);
        $this->assertNull($this->ticket->first_response_at);
        $this->assertTrue($this->ticket->messages->first()->is_internal_note);
    }

    public function test_resolving_a_ticket_stamps_resolved_at_and_reopening_clears_it(): void
    {
        $this->put('/admin/support/'.$this->ticket->id, [
            'status' => 'resolved',
            'priority' => 'normal',
            'category' => 'booking',
        ])->assertRedirect();

        $this->assertNotNull($this->ticket->fresh()->resolved_at);
        $this->assertFalse($this->ticket->fresh()->isOpen());

        $this->put('/admin/support/'.$this->ticket->id, [
            'status' => 'open',
            'priority' => 'high',
            'category' => 'booking',
        ])->assertRedirect();

        $this->assertNull($this->ticket->fresh()->resolved_at);
    }

    public function test_an_empty_reply_is_rejected(): void
    {
        $this->post('/admin/support/'.$this->ticket->id.'/reply', ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertSame(0, $this->ticket->messages()->count());
    }
}
