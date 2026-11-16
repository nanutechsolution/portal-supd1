<?php

namespace App\Http\Controllers;

use App\Models\Bulletin;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulletinController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $bulletins = Bulletin::query()
            ->published()
            ->when($validated['tahun'] ?? null, fn ($query, $year) => $query->where('year', $year))
            ->orderByDesc('year')
            ->orderByDesc('published_at')
            ->paginate(config('portal.pagination.bulletins'))
            ->withQueryString();

        $years = Bulletin::query()
            ->published()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        return view('bulletins.index', [
            'bulletins' => $bulletins,
            'years' => $years,
        ]);
    }

    public function show(Bulletin $bulletin): View
    {
        abort_unless($bulletin->isPublished(), 404);

        $others = Bulletin::query()
            ->published()
            ->whereKeyNot($bulletin->getKey())
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return view('bulletins.show', [
            'bulletin' => $bulletin,
            'others' => $others,
        ]);
    }

    public function preview(Bulletin $bulletin): BinaryFileResponse
    {
        abort_unless($bulletin->isPublished() && $bulletin->hasFile(), 404);

        return response()->file(
            Storage::disk('public')->path($bulletin->file_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.Str::slug($bulletin->title).'.pdf"',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    public function download(Bulletin $bulletin): StreamedResponse
    {
        abort_unless($bulletin->isPublished() && $bulletin->hasFile(), 404);

        $bulletin->increment('downloads');

        return Storage::disk('public')->download(
            $bulletin->file_path,
            Str::slug($bulletin->title).'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
