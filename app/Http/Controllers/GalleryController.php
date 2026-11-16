<?php

namespace App\Http\Controllers;

use App\Models\GalleryItem;
use Illuminate\Contracts\View\View;

class GalleryController extends Controller
{
    public function index(): View
    {
        $items = GalleryItem::query()
            ->latestFirst()
            ->paginate(config('portal.pagination.gallery'));

        return view('galleries.index', [
            'items' => $items,
        ]);
    }
}
