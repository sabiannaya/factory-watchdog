<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LanguageController extends Controller
{
    public function switch(Request $request): void
    {
        $language = $request->input('language', 'en');

        if (! in_array($language, ['en', 'id'])) {
            $language = 'en';
        }

        $request->session()->put('locale', $language);
        app()->setLocale($language);
    }
}
