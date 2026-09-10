<?php

namespace App\Traits;

use Illuminate\Http\Request;

trait NormalizesAccountCredentialsTrait
{
    /**
     * The admin account forms only collect the local part of the email/username and
     * display the fixed domain suffix in the UI (e.g. "issakurdi" + "@gmail.com").
     * Only append the fixed domain when the submitted value has no "@" of its own —
     * otherwise an already-complete address (e.g. a legacy account on a different
     * domain, or the unchanged value from the edit form) would get silently
     * rewritten to "<prefix>@gmail.com" on every save.
     */
    protected function normalizeAccountCredentials(Request $request): void
    {
        if ($request->filled('email') && !str_contains($request->email, '@')) {
            $request->merge(['email' => trim($request->email) . '@gmail.com']);
        }

        if ($request->filled('username') && !str_contains($request->username, '@')) {
            $request->merge(['username' => trim($request->username) . '@edu-bridge.com']);
        }
    }
}
