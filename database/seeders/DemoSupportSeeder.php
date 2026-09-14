<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local-only sample tickets so the support inbox is inspectable before the
 * chat/WhatsApp channels go live in Phase 6.
 */
class DemoSupportSeeder extends Seeder
{
    public function run(): void
    {
        $patient = User::updateOrCreate(
            ['phone' => User::normalizePhone('01011112222')],
            [
                'name' => 'منى إبراهيم',
                'password' => 'password',
                'preferred_language' => 'ar',
                'is_active' => true,
            ],
        );

        $patient->assignRole(RoleName::Patient);

        $tickets = [
            [
                'subject' => 'لم يصلني تأكيد الحجز على واتساب',
                'category' => 'booking',
                'channel' => 'whatsapp',
                'priority' => 'high',
                'status' => 'open',
                'message' => 'حجزت موعد أمس ولم تصلني أي رسالة تأكيد على واتساب، هل الحجز مؤكد؟',
            ],
            [
                'subject' => 'مشكلة في الدفع الإلكتروني',
                'category' => 'payment',
                'channel' => 'chat',
                'priority' => 'urgent',
                'status' => 'open',
                'message' => 'حاولت الدفع بالبطاقة وخُصم المبلغ لكن الحجز ظهر غير مدفوع.',
            ],
            [
                'subject' => 'طلب توضيح بخصوص مشاركة السجل الطبي',
                'category' => 'record_access',
                'channel' => 'chat',
                'priority' => 'normal',
                'status' => 'in_progress',
                'message' => 'وصلني طلب من عيادة لم أزرها للوصول لتحاليلي، هل هذا طبيعي؟',
            ],
        ];

        foreach ($tickets as $data) {
            $ticket = SupportTicket::firstOrCreate(
                ['subject' => $data['subject']],
                [
                    'opened_by_user_id' => $patient->id,
                    'channel' => $data['channel'],
                    'category' => $data['category'],
                    'priority' => $data['priority'],
                    'status' => $data['status'],
                ],
            );

            if ($ticket->messages()->doesntExist()) {
                $ticket->messages()->create([
                    'sender_user_id' => $patient->id,
                    'body' => $data['message'],
                    'sent_at' => now()->subHours(random_int(1, 48)),
                ]);
            }
        }
    }
}
