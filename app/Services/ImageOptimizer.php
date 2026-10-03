<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Every uploaded image goes through here: it is decoded from raster data
 * (so disguised files fail), resized to a maximum width and re-encoded as
 * WebP. Re-encoding also strips metadata such as EXIF location.
 *
 * SVG is never accepted. Form Requests must also validate MIME, extension
 * and size before calling this service.
 */
class ImageOptimizer
{
    public const MAX_WIDTH = 1280;

    public const QUALITY = 80;

    public function storeAsWebp(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $contents = (string) file_get_contents($file->getRealPath());
        $info = @getimagesizefromstring($contents);

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new InvalidArgumentException('الملف ليس صورة صالحة.');
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            throw new InvalidArgumentException('تعذّرت قراءة الصورة.');
        }

        if (imagesx($image) > self::MAX_WIDTH) {
            $image = imagescale($image, self::MAX_WIDTH);
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, self::QUALITY);
        $webp = (string) ob_get_clean();
        imagedestroy($image);

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        Storage::disk($disk)->put($path, $webp);

        return $path;
    }

    public function delete(?string $path, string $disk = 'public'): void
    {
        if ($path) {
            Storage::disk($disk)->delete($path);
        }
    }
}
