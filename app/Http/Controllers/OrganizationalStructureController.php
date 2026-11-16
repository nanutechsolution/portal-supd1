<?php

namespace App\Http\Controllers;

use App\Models\OrganizationalUnit;
use Illuminate\Contracts\View\View;

class OrganizationalStructureController extends Controller
{
    public function __invoke(): View
    {
        return view('structure.index', [
            'units' => OrganizationalUnit::publicTree(),
        ]);
    }
}
