<?php

if (!function_exists('fileUrl')) {
    function fileUrl($path)
    {
        if (!$path) return null;
        return asset('storage/' . $path);
    }
}