<x-layouts.public title="Halaman tidak ditemukan" noindex>
    <div class="mx-auto max-w-3xl px-4 py-24 text-center sm:px-6">
        <p class="font-serif text-7xl font-bold leading-none text-ink-200">404</p>
        <h1 class="mt-4 font-serif text-3xl font-bold text-ink-950">Halaman tidak ditemukan</h1>
        <p class="mt-3 text-sm leading-relaxed text-ink-600">
            Halaman yang Anda cari mungkin sudah dipindahkan, dihapus, atau belum diterbitkan.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('home') }}" class="rounded bg-ink-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-ink-800">
                Kembali ke beranda
            </a>
            <a href="{{ route('posts.index') }}" class="rounded border border-ink-200 px-4 py-2.5 text-sm font-semibold text-ink-700 hover:border-ink-400">
                Lihat berita
            </a>
        </div>
    </div>
</x-layouts.public>
