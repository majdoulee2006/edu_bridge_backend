<?php

if (!function_exists('storageUrl')) {
    function storageUrl(?string $path): ?string {
        if (!$path) return null;
        $cleanPath = ltrim($path, '/');
        // إزالة بادئة storage/ إذا كانت موجودة لمنع التكرار
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }
        $encoded = implode('/', array_map('rawurlencode', explode('/', $cleanPath)));
        
        if (app()->bound('request') && request()->getHttpHost()) {
            return request()->getSchemeAndHttpHost() . '/api/file/' . $encoded;
        }
        return url('/api/file/' . $encoded);
    }
}
