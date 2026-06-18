<?php

namespace App\Services;

use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantResolver
{
    public function resolve(Request $request): ?Campus
    {
        $host = $request->getHost();

        $customDomain = Campus::query()
            ->where('custom_domain', $host)
            ->where('status', 'active')
            ->first();

        if ($customDomain !== null) {
            return $customDomain;
        }

        $subdomain = Str::before($host, '.');

        if ($subdomain !== $host && ! in_array($subdomain, ['www', 'localhost'], true)) {
            $campus = Campus::query()
                ->where('subdomain', $subdomain)
                ->where('status', 'active')
                ->first();

            if ($campus !== null) {
                return $campus;
            }
        }

        if ($request->filled('kampus')) {
            return Campus::query()
                ->where('slug', $request->string('kampus'))
                ->where('status', 'active')
                ->first();
        }

        return null;
    }
}
