<x-layouts.public
    title="Kontak"
    :description="'Hubungi redaksi '.$site['site_directorate'].' melalui formulir kontak resmi.'"
>
    <section class="border-b border-ink-800 bg-ink-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-2 text-xs text-ink-400">
                <a href="{{ route('home') }}" class="hover:text-white">Beranda</a>
                <span aria-hidden="true">/</span>
                <span class="text-ink-200">Kontak</span>
            </nav>
            <p class="eyebrow mt-4 text-accent-300">Layanan Publik</p>
            <h1 class="mt-2 font-serif text-3xl font-bold tracking-tight sm:text-4xl">Hubungi Kami</h1>
            <p class="mt-3 max-w-2xl text-sm leading-relaxed text-ink-300">
                Sampaikan pertanyaan, masukan, atau permintaan informasi kepada {{ $site['site_short_name'] }}.
                Pesan yang masuk akan ditindaklanjuti oleh tim redaksi.
            </p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-12 lg:px-8">
        <div class="lg:col-span-7">
            <div id="form-kontak" class="rounded-lg border border-ink-100 bg-white p-6 sm:p-8">
                <h2 class="font-serif text-2xl font-bold text-ink-950">Formulir Pesan</h2>
                <p class="mt-2 text-sm leading-relaxed text-ink-500">
                    Kolom bertanda <span class="font-semibold text-ink-800">*</span> wajib diisi.
                    Sertakan informasi yang cukup agar kami dapat membantu.
                </p>

                @if (session('contact_status'))
                    <div role="status" class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        {{ session('contact_status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div role="alert" class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                        <p class="font-semibold">Pesan belum dapat dikirim.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('contact.store') }}" method="POST" class="mt-6 space-y-5">
                    @csrf

                    <div class="hidden" aria-hidden="true">
                        <label for="contact-website">Website</label>
                        <input id="contact-website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="contact-name" class="mb-1 block text-sm font-semibold text-ink-800">
                                Nama lengkap <span class="text-red-600" aria-hidden="true">*</span>
                            </label>
                            <input id="contact-name" type="text" name="name" value="{{ old('name') }}" required
                                   maxlength="120" autocomplete="name"
                                   @error('name') aria-invalid="true" aria-describedby="contact-name-error" @enderror
                                   class="w-full rounded border border-ink-200 bg-paper px-3 py-2 text-sm text-ink-900 focus:border-accent-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-300">
                            @error('name')
                                <p id="contact-name-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="contact-email" class="mb-1 block text-sm font-semibold text-ink-800">
                                Email <span class="text-red-600" aria-hidden="true">*</span>
                            </label>
                            <input id="contact-email" type="email" name="email" value="{{ old('email') }}" required
                                   maxlength="150" autocomplete="email"
                                   @error('email') aria-invalid="true" aria-describedby="contact-email-error" @enderror
                                   class="w-full rounded border border-ink-200 bg-paper px-3 py-2 text-sm text-ink-900 focus:border-accent-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-300">
                            @error('email')
                                <p id="contact-email-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="contact-phone" class="mb-1 block text-sm font-semibold text-ink-800">
                                Nomor telepon <span class="font-normal text-ink-500">(opsional)</span>
                            </label>
                            <input id="contact-phone" type="tel" name="phone" value="{{ old('phone') }}"
                                   maxlength="50" autocomplete="tel"
                                   @error('phone') aria-invalid="true" aria-describedby="contact-phone-error" @enderror
                                   class="w-full rounded border border-ink-200 bg-paper px-3 py-2 text-sm text-ink-900 focus:border-accent-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-300">
                            @error('phone')
                                <p id="contact-phone-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="contact-subject" class="mb-1 block text-sm font-semibold text-ink-800">
                                Subjek <span class="text-red-600" aria-hidden="true">*</span>
                            </label>
                            <input id="contact-subject" type="text" name="subject" value="{{ old('subject') }}" required
                                   maxlength="150"
                                   @error('subject') aria-invalid="true" aria-describedby="contact-subject-error" @enderror
                                   class="w-full rounded border border-ink-200 bg-paper px-3 py-2 text-sm text-ink-900 focus:border-accent-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-300">
                            @error('subject')
                                <p id="contact-subject-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="contact-message" class="mb-1 block text-sm font-semibold text-ink-800">
                            Isi pesan <span class="text-red-600" aria-hidden="true">*</span>
                        </label>
                        <textarea id="contact-message" name="message" rows="7" required maxlength="5000"
                                  @error('message') aria-invalid="true" aria-describedby="contact-message-error" @enderror
                                  class="w-full rounded border border-ink-200 bg-paper px-3 py-2 text-sm text-ink-900 focus:border-accent-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-300">{{ old('message') }}</textarea>
                        <p class="mt-1 text-xs text-ink-500">Minimal 20 karakter. Jangan sertakan data pribadi yang tidak diperlukan.</p>
                        @error('message')
                            <p id="contact-message-error" class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-start gap-3 text-sm text-ink-700">
                            <input type="checkbox" name="consent" value="1" @checked(old('consent')) required
                                   class="mt-0.5 h-4 w-4 rounded border-ink-300 text-accent-600 focus:ring-accent-400">
                            <span>
                                Saya setuju data yang saya kirimkan digunakan untuk menindaklanjuti pesan ini.
                            </span>
                        </label>
                        @error('consent')
                            <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t border-ink-100 pt-5">
                        <button type="submit"
                                class="rounded-full bg-ink-900 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-ink-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-accent-400">
                            Kirim Pesan
                        </button>
                        <p class="text-xs text-ink-500">
                            Pesan dikirim ke redaksi {{ $site['site_short_name'] }}. Kami tidak menampilkan pesan Anda di portal publik.
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <aside class="space-y-6 lg:col-span-5">
            <div class="rounded-lg border border-ink-100 bg-white p-6">
                <h2 class="eyebrow mb-4 text-ink-950">Informasi Kontak</h2>
                <address class="space-y-4 text-sm not-italic leading-relaxed text-ink-700">
                    @if ($site['contact_address'])
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">Alamat</p>
                            <p class="mt-1">{{ $site['contact_address'] }}</p>
                        </div>
                    @endif
                    @if ($site['contact_email'])
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">Email</p>
                            <p class="mt-1">
                                <a href="mailto:{{ $site['contact_email'] }}" class="font-semibold text-accent-700 hover:text-accent-800">
                                    {{ $site['contact_email'] }}
                                </a>
                            </p>
                        </div>
                    @endif
                    @if ($site['contact_phone'])
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-ink-400">Telepon</p>
                            <p class="mt-1">
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['contact_phone']) }}" class="font-semibold text-accent-700 hover:text-accent-800">
                                    {{ $site['contact_phone'] }}
                                </a>
                            </p>
                        </div>
                    @endif
                    @if (! $site['contact_address'] && ! $site['contact_email'] && ! $site['contact_phone'])
                        <p class="text-ink-500">
                            Informasi kontak resmi belum tersedia. Administrator dapat melengkapinya melalui
                            menu Pengaturan Website pada panel admin.
                        </p>
                    @endif
                </address>

                <div class="mt-6 border-t border-ink-100 pt-4">
                    <p class="text-xs text-ink-500">
                        {{ $site['site_directorate'] }}
                    </p>
                    @if (filled($site['site_parent_name']) || filled($site['site_parent_url']))
                        <p class="mt-2 text-xs text-ink-500">
                            Situs induk:
                            <a href="{{ $site['site_parent_url'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-accent-700 hover:text-accent-800">
                                {{ $site['site_parent_name'] ?: $site['site_parent_url'] }}
                            </a>
                        </p>
                    @endif
                </div>
            </div>

            <div class="rounded-lg bg-ink-950 p-6 text-ink-200">
                <h2 class="eyebrow text-accent-300">Sebelum Mengirim</h2>
                <ul class="mt-3 space-y-2 text-sm leading-relaxed">
                    <li>Periksa kembali alamat email agar balasan kami sampai.</li>
                    <li>Untuk permintaan informasi resmi, jelaskan instansi dan keperluannya.</li>
                    <li>Pesan yang mengandung ujaran kebencian atau spam tidak akan ditindaklanjuti.</li>
                </ul>
                <p class="mt-4 text-xs text-ink-400">
                    Untuk pertanyaan yang sudah terjawab di portal, lihat
                    <a href="{{ route('pages.profil') }}" class="font-semibold text-accent-300 hover:text-white">Profil Direktorat</a>
                    atau
                    <a href="{{ route('posts.index') }}" class="font-semibold text-accent-300 hover:text-white">Berita</a>.
                </p>
            </div>
        </aside>
    </div>
</x-layouts.public>
