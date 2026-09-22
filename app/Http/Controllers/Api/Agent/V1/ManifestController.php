<?php

namespace App\Http\Controllers\Api\Agent\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $base = url('/api/agent/v1');

        return response()->json([
            'name' => 'Hakeem Hermes Agent API',
            'version' => '1',
            'locale' => app()->getLocale(),
            'auth' => [
                'hermes' => [
                    'required' => true,
                    'header' => 'X-Hermes-Key',
                    'note' => 'Send the platform Hermes key on every request. This key identifies the agent, not a patient.',
                ],
                'customer' => [
                    'when' => 'profile, bookings, or updating an existing account',
                    'bearer' => 'Authorization: Bearer {customer_token}',
                    'or_credentials' => ['identifier or phone or email', 'password'],
                    'skip_when_authenticated' => true,
                ],
                'locale' => 'Pass locale=ar|en or X-Locale to receive Arabic or English copy.',
            ],
            'rules' => [
                'can' => [
                    'search_doctors_clinics_offers_labs',
                    'help_visitors',
                    'suggest_searches',
                    'register_customers',
                    'reset_passwords',
                    'open_support_tickets',
                    'read_or_update_a_customer_profile_after_auth',
                ],
                'cannot' => [
                    'admin',
                    'delete_records',
                    'add_or_edit_clinic_services',
                    'manage_clinics_or_staff',
                    'read_a_profile_without_customer_token_or_password',
                ],
            ],
            'endpoints' => [
                ['method' => 'GET', 'path' => $base, 'name' => 'manifest', 'customer' => false, 'purpose' => 'List tools, auth rules, and this collection.'],
                ['method' => 'GET', 'path' => $base.'/help', 'name' => 'help', 'customer' => false, 'purpose' => 'Visitor FAQ, how booking works, and support contacts.'],
                ['method' => 'GET', 'path' => $base.'/suggestions', 'name' => 'suggestions', 'customer' => false, 'purpose' => 'Search and intent suggestions. Query: q.'],
                ['method' => 'GET', 'path' => $base.'/search', 'name' => 'search', 'customer' => false, 'purpose' => 'Search doctors, clinics, services, offers, labs. Query: q, type, specialty, governorate, city, gender, min_experience, category, max_price, limit.'],
                ['method' => 'GET', 'path' => $base.'/doctors/{slug}', 'name' => 'doctors.show', 'customer' => false, 'purpose' => 'Public doctor profile and booking links.'],
                ['method' => 'GET', 'path' => $base.'/doctors/{slug}/slots', 'name' => 'doctors.slots', 'customer' => false, 'purpose' => 'Available slots. Query: clinic_address_id, service_type_id, date.'],
                ['method' => 'GET', 'path' => $base.'/clinics/{slug}', 'name' => 'clinics.show', 'customer' => false, 'purpose' => 'Public clinic profile.'],
                ['method' => 'GET', 'path' => $base.'/offers', 'name' => 'offers.index', 'customer' => false, 'purpose' => 'Running offers. Query: q, category, governorate, city, max_price.'],
                ['method' => 'GET', 'path' => $base.'/offers/{slug}', 'name' => 'offers.show', 'customer' => false, 'purpose' => 'Offer details and booking URL.'],
                ['method' => 'GET', 'path' => $base.'/campaigns', 'name' => 'campaigns', 'customer' => false, 'purpose' => 'Signup campaign plus featured live offers.'],
                ['method' => 'GET', 'path' => $base.'/reference/geography', 'name' => 'reference.geography', 'customer' => false, 'purpose' => 'Governorates and cities for filters.'],
                ['method' => 'GET', 'path' => $base.'/reference/specialties', 'name' => 'reference.specialties', 'customer' => false, 'purpose' => 'Active specialties.'],
                ['method' => 'GET', 'path' => $base.'/reference/service-types', 'name' => 'reference.service-types', 'customer' => false, 'purpose' => 'Bookable service types.'],
                ['method' => 'POST', 'path' => $base.'/customers', 'name' => 'customers.register', 'customer' => false, 'purpose' => 'Create a patient account. Body: name, identifier|phone|email, password, ref?, insurance_provider_id?'],
                ['method' => 'POST', 'path' => $base.'/customers/session', 'name' => 'customers.login', 'customer' => false, 'purpose' => 'Exchange phone/email + password for a customer token.'],
                ['method' => 'POST', 'path' => $base.'/customers/password/forgot', 'name' => 'customers.password.forgot', 'customer' => false, 'purpose' => 'Send a reset code or email. Does not reveal whether the account exists.'],
                ['method' => 'POST', 'path' => $base.'/customers/password/reset', 'name' => 'customers.password.reset', 'customer' => false, 'purpose' => 'Set a new password with the reset token/code.'],
                ['method' => 'GET', 'path' => $base.'/customers/me', 'name' => 'customers.me', 'customer' => true, 'purpose' => 'Read the profile when the visitor already has a customer bearer token. Do not ask again.'],
                ['method' => 'POST', 'path' => $base.'/customers/me', 'name' => 'customers.me.lookup', 'customer' => true, 'purpose' => 'Read the profile with phone/email + password when there is no token yet. Returns a token for later calls.'],
                ['method' => 'PUT', 'path' => $base.'/customers/me', 'name' => 'customers.update', 'customer' => true, 'purpose' => 'Update the profile. Bearer token or phone/email + password in the same body.'],
                ['method' => 'GET', 'path' => $base.'/customers/bookings', 'name' => 'customers.bookings', 'customer' => true, 'purpose' => 'List appointments for a signed-in customer. Read only.'],
                ['method' => 'POST', 'path' => $base.'/customers/bookings', 'name' => 'customers.bookings.lookup', 'customer' => true, 'purpose' => 'List appointments with phone/email + password when there is no token.'],
                ['method' => 'POST', 'path' => $base.'/support/tickets', 'name' => 'support.tickets.store', 'customer' => false, 'purpose' => 'Open a support ticket. Body: name, phone, subject, body, category?, channel?'],
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
