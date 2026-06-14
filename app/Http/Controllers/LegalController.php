<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Renders the currently-published versions of our legal documents from
 * resources/legal/. The active version for each doc is set in
 * config('booking.legal') — never edit a published file; publish a new
 * dated one and bump the version.
 */
class LegalController extends Controller
{
    public function privacy()
    {
        $version = config('booking.legal.privacy_policy_version');

        return view('legal.document', [
            'title' => 'Privacy Policy',
            'version' => $version,
            'html' => $this->render("privacy-policy-{$version}.md"),
        ]);
    }

    public function collectionNotice()
    {
        $version = config('booking.legal.collection_notice_version');

        return view('legal.document', [
            'title' => 'Collection Notice',
            'version' => $version,
            'html' => $this->render("collection-notice-{$version}.md"),
        ]);
    }

    private function render(string $filename): string
    {
        $path = resource_path('legal/'.$filename);
        abort_unless(File::exists($path), 404);

        // Markdown source is repo-controlled — no untrusted input.
        return Str::markdown(File::get($path));
    }
}
