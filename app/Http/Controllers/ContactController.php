<?php

namespace App\Http\Controllers;

use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ContactController extends Controller
{
    public const SUCCESS_MESSAGE = 'Pesan Anda sudah dikirim. Tim redaksi akan menindaklanjuti melalui email yang Anda cantumkan.';

    public function index(): View
    {
        return view('contact.index');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            // Honeypot terisi: perlakukan sebagai spam tanpa memberi sinyal
            // kepada pengirim otomatis, tetapi tetap tampilkan pesan sukses.
            return $this->successResponse();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\s.-]{6,50}$/'],
            'subject' => ['required', 'string', 'min:3', 'max:150'],
            'message' => ['required', 'string', 'min:20', 'max:5000'],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'Centang persetujuan agar pesan dapat diproses.',
        ], [
            'name' => 'nama',
            'email' => 'email',
            'phone' => 'nomor telepon',
            'subject' => 'subjek',
            'message' => 'isi pesan',
            'consent' => 'persetujuan',
        ]);

        $contactMessage = ContactMessage::query()->create([
            'name' => trim($validated['name']),
            'email' => Str::lower(trim($validated['email'])),
            'phone' => filled($validated['phone'] ?? null) ? trim($validated['phone']) : null,
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'status' => 'baru',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);

        $this->notifyRedaksi($contactMessage);

        return $this->successResponse();
    }

    private function successResponse(): RedirectResponse
    {
        return redirect()
            ->route('contact.index')
            ->with('contact_status', self::SUCCESS_MESSAGE);
    }

    private function notifyRedaksi(ContactMessage $contactMessage): void
    {
        $recipient = Setting::get('contact_email') ?: config('portal.contact.notification_email');

        if (! is_string($recipient) || filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
            Log::warning('Pesan kontak tersimpan tanpa notifikasi email: alamat tujuan belum dikonfigurasi.', [
                'contact_message_id' => $contactMessage->getKey(),
            ]);

            return;
        }

        try {
            Mail::to($recipient)->send(new NewContactMessage($contactMessage));
        } catch (Throwable $exception) {
            // Kegagalan email tidak boleh membatalkan pesan yang sudah tersimpan.
            Log::error('Gagal mengirim notifikasi pesan kontak.', [
                'contact_message_id' => $contactMessage->getKey(),
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
