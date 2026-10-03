<?php

namespace App\Support;

/**
 * Whitelisted video providers. Admins paste a normal video URL; the site
 * never accepts iframe HTML. Unsupported URLs fail validation.
 *
 * Supported: YouTube (embedded via youtube-nocookie.com) and Vimeo.
 */
class VideoEmbed
{
    /**
     * Hosts allowed in Content-Security-Policy `frame-src`.
     */
    public const FRAME_HOSTS = ['https://www.youtube-nocookie.com', 'https://player.vimeo.com'];

    public static function isSupported(?string $url): bool
    {
        return self::embedUrl($url) !== null;
    }

    public static function embedUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $parts = parse_url(trim($url));

        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || ! isset($parts['host'])) {
            return null;
        }

        $host = strtolower(preg_replace('/^(www\.|m\.)/', '', $parts['host']));
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        $youtubeId = match (true) {
            $host === 'youtu.be' => ltrim($path, '/'),
            $host === 'youtube.com' && $path === '/watch' => $query['v'] ?? null,
            $host === 'youtube.com' && preg_match('#^/(embed|shorts|live)/([^/]+)#', $path, $m) === 1 => $m[2],
            default => null,
        };

        if (is_string($youtubeId) && preg_match('/^[A-Za-z0-9_-]{11}$/', $youtubeId) === 1) {
            return "https://www.youtube-nocookie.com/embed/{$youtubeId}";
        }

        if ($host === 'vimeo.com' && preg_match('#^/(\d+)$#', $path, $m) === 1) {
            return "https://player.vimeo.com/video/{$m[1]}";
        }

        return null;
    }
}
