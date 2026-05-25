<?php

if (!function_exists('storageUrl')) {
    function storageUrl(?string $path): ?string {
        if (!$path) return null;
        $encoded = implode('/', array_map('rawurlencode', explode('/', $path)));
        return url('/api/file/' . $encoded);
    }
}
