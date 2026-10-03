<?php

namespace Tests\Unit;

use App\Support\VideoEmbed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VideoEmbedTest extends TestCase
{
    #[DataProvider('supported')]
    public function test_supported_urls_become_privacy_friendly_embeds(string $url, string $expected): void
    {
        $this->assertSame($expected, VideoEmbed::embedUrl($url));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function supported(): array
    {
        $yt = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ';

        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10', $yt],
            'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', $yt],
            'short link' => ['https://youtu.be/dQw4w9WgXcQ', $yt],
            'shorts' => ['https://youtube.com/shorts/dQw4w9WgXcQ', $yt],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', $yt],
            'vimeo' => ['https://vimeo.com/123456789', 'https://player.vimeo.com/video/123456789'],
        ];
    }

    #[DataProvider('unsupported')]
    public function test_other_urls_are_rejected(?string $url): void
    {
        $this->assertNull(VideoEmbed::embedUrl($url));
        $this->assertFalse(VideoEmbed::isSupported($url));
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function unsupported(): array
    {
        return [
            'null' => [null],
            'http' => ['http://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'other host' => ['https://evil.example.com/watch?v=dQw4w9WgXcQ'],
            'lookalike host' => ['https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ'],
            'iframe html' => ['<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>'],
            'bad id' => ['https://www.youtube.com/watch?v=short'],
            'javascript' => ['javascript:alert(1)'],
        ];
    }
}
