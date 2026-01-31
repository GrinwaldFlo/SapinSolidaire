<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Try to get locale from session
        $locale = session('locale');

        // If not in session, try to detect from Accept-Language header
        if (!$locale) {
            $locale = $this->getLocaleFromHeader($request);
        }

        // Fallback to config default if no locale detected
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }

        // Ensure locale is one of supported locales (en, fr)
        if (!in_array($locale, ['en', 'fr'])) {
            $locale = config('app.locale', 'en');
        }

        // Set the application locale
        app()->setLocale($locale);

        // Store in session for consistency
        session(['locale' => $locale]);

        return $next($request);
    }

    /**
     * Get locale from Accept-Language header
     */
    private function getLocaleFromHeader(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $lang = trim($lang);
            $quality = 1.0;

            if (strpos($lang, ';') !== false) {
                [$lang, $q] = explode(';', $lang);
                $lang = trim($lang);
                if (preg_match('/q=([0-9.]+)/', $q, $matches)) {
                    $quality = (float) $matches[1];
                }
            }

            // Extract base language (e.g., 'fr' from 'fr-FR')
            $baseLang = explode('-', $lang)[0];
            $languages[$baseLang] = $quality;
        }

        // Sort by quality (highest first)
        arsort($languages);

        // Return first supported language
        foreach (array_keys($languages) as $lang) {
            if (in_array($lang, ['en', 'fr'])) {
                return $lang;
            }
        }

        return null;
    }
}
