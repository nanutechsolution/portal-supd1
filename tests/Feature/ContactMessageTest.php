<?php

namespace Tests\Feature;

use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Setting::flushCache();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Budi Santoso',
            'email' => 'Budi@Example.test',
            'phone' => '0812-3456-7890',
            'subject' => 'Permintaan informasi publikasi',
            'message' => 'Mohon informasi jadwal publikasi buletin edisi berikutnya.',
            'consent' => '1',
        ], $overrides);
    }

    public function test_contact_page_is_public_and_shows_configured_contact_details(): void
    {
        Setting::putMany([
            'contact_email' => 'humas@example.test',
            'contact_phone' => '021-1234567',
            'contact_address' => 'Jl. Contoh Raya No. 1, Jakarta',
        ]);

        $this->get(route('contact.index'))
            ->assertOk()
            ->assertSee('Formulir Pesan')
            ->assertSee('humas@example.test')
            ->assertSee('021-1234567')
            ->assertSee('Jl. Contoh Raya No. 1, Jakarta')
            ->assertSee('name="website"', false);
    }

    public function test_contact_page_handles_missing_contact_information(): void
    {
        $this->get(route('contact.index'))
            ->assertOk()
            ->assertSee('Informasi kontak resmi belum tersedia');
    }

    public function test_submission_is_stored_and_redaksi_is_notified(): void
    {
        Mail::fake();
        Setting::putMany(['contact_email' => 'redaksi@example.test']);

        $this->post(route('contact.store'), $this->payload())
            ->assertRedirect(route('contact.index'))
            ->assertSessionHas('contact_status');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'subject' => 'Permintaan informasi publikasi',
            'status' => 'baru',
        ]);

        $message = ContactMessage::query()->firstOrFail();

        $this->assertSame('0812-3456-7890', $message->phone);
        $this->assertNotNull($message->ip_address);

        Mail::assertSent(NewContactMessage::class, function (NewContactMessage $mail) use ($message): bool {
            return $mail->hasTo('redaksi@example.test')
                && $mail->contactMessage->is($message);
        });
    }

    public function test_message_is_stored_even_when_mail_delivery_is_not_configured(): void
    {
        Mail::fake();
        config(['portal.contact.notification_email' => null]);

        $this->post(route('contact.store'), $this->payload())->assertRedirect(route('contact.index'));

        $this->assertDatabaseCount('contact_messages', 1);
        Mail::assertNothingSent();
    }

    public function test_notification_falls_back_to_the_configured_admin_address(): void
    {
        Mail::fake();
        config(['portal.contact.notification_email' => 'fallback@example.test']);

        $this->post(route('contact.store'), $this->payload())->assertRedirect(route('contact.index'));

        Mail::assertSent(NewContactMessage::class, fn (NewContactMessage $mail): bool => $mail->hasTo('fallback@example.test'));
    }

    public function test_honeypot_submissions_are_silently_ignored(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), $this->payload(['website' => 'https://spam.example']))
            ->assertRedirect(route('contact.index'))
            ->assertSessionHas('contact_status');

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    public function test_invalid_submission_is_rejected_with_validation_errors(): void
    {
        Mail::fake();

        $this->post(route('contact.store'), [
            'name' => '',
            'email' => 'bukan-email',
            'phone' => 'abc',
            'subject' => '',
            'message' => 'terlalu pendek',
            'consent' => '',
        ])->assertSessionHasErrors(['name', 'email', 'phone', 'subject', 'message', 'consent']);

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }

    public function test_contact_form_is_rate_limited_per_ip(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contact.store'), $this->payload([
                'subject' => 'Pesan nomor '.($i + 1),
            ]))->assertRedirect(route('contact.index'));
        }

        $this->post(route('contact.store'), $this->payload(['subject' => 'Pesan keenam']))
            ->assertStatus(429);

        $this->assertDatabaseCount('contact_messages', 5);
    }

    public function test_admin_can_read_messages_and_status_changes_are_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $message = ContactMessage::factory()->create(['subject' => 'Pesan audit status']);

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->assertCanSeeTableRecords([$message])
            ->callTableAction('markRead', $message);

        $this->assertSame('dibaca', $message->refresh()->status->value);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'module' => 'contact_messages',
            'subject_id' => $message->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->callTableAction('markReplied', $message);

        $this->assertSame('dibalas', $message->refresh()->status->value);
    }

    public function test_admin_can_delete_a_message_and_deletion_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $message = ContactMessage::factory()->create(['subject' => 'Pesan untuk dihapus']);

        Livewire::actingAs($admin)
            ->test(ListContactMessages::class)
            ->callTableAction('delete', $message);

        $this->assertDatabaseMissing('contact_messages', ['id' => $message->id]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deleted',
            'module' => 'contact_messages',
            'subject_id' => $message->id,
        ]);
    }

    public function test_editor_cannot_access_contact_messages(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->get('/admin/contact-messages')
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_the_admin_login(): void
    {
        $this->get('/admin/contact-messages')
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_sitemap_lists_the_contact_page(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('contact.index'), false);
    }
}
