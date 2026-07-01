<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

final class MatchdayNewsCrestCompositor
{
    private const int CREST_SIZE = 96;

    private const int CREST_GAP = 24;

    private const int BOTTOM_MARGIN = 48;

    /**
     * @param  list<string>  $crestPaths  Lokale Pfade zu Wappen-Bildern
     */
    public function overlayCrests(string $baseImagePath, array $crestPaths): string
    {
        if ($crestPaths === [] || ! extension_loaded('gd')) {
            return $baseImagePath;
        }

        try {
            $base = $this->loadImage($baseImagePath);

            if ($base === null) {
                return $baseImagePath;
            }

            $baseWidth = imagesx($base);
            $baseHeight = imagesy($base);
            $crestCount = count($crestPaths);
            $rowWidth = ($crestCount * self::CREST_SIZE) + (($crestCount - 1) * self::CREST_GAP);
            $startX = (int) max(0, ($baseWidth - $rowWidth) / 2);
            $y = (int) max(0, $baseHeight - self::CREST_SIZE - self::BOTTOM_MARGIN);

            foreach ($crestPaths as $index => $crestPath) {
                $crest = $this->loadImage($crestPath);

                if ($crest === null) {
                    continue;
                }

                $resized = imagecreatetruecolor(self::CREST_SIZE, self::CREST_SIZE);
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                imagefilledrectangle($resized, 0, 0, self::CREST_SIZE, self::CREST_SIZE, $transparent);

                imagecopyresampled(
                    $resized,
                    $crest,
                    0,
                    0,
                    0,
                    0,
                    self::CREST_SIZE,
                    self::CREST_SIZE,
                    imagesx($crest),
                    imagesy($crest),
                );

                $x = $startX + ($index * (self::CREST_SIZE + self::CREST_GAP));
                imagecopy($base, $resized, $x, $y, 0, 0, self::CREST_SIZE, self::CREST_SIZE);

                imagedestroy($crest);
                imagedestroy($resized);
            }

            $outputPath = tempnam(sys_get_temp_dir(), 'tippspiel-news-composite-').'.jpg';
            imagejpeg($base, $outputPath, 90);
            imagedestroy($base);

            return $outputPath;
        } catch (Throwable $e) {
            Log::warning('Tippspiel: Wappen konnten nicht auf das Titelbild gelegt werden.', [
                'error' => $e->getMessage(),
            ]);

            return $baseImagePath;
        }
    }

    /**
     * @return resource|null
     */
    private function loadImage(string $path)
    {
        $info = @getimagesize($path);

        if ($info === false) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }
}
