<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function profil(): View
    {
        $pages = Page::query()
            ->whereIn('slug', ['tentang-supd1', 'tugas-dan-fungsi'])
            ->get()
            ->keyBy('slug');

        return view('pages.profil', [
            'about' => $pages->get('tentang-supd1'),
            'duties' => $pages->get('tugas-dan-fungsi'),
        ]);
    }
}
