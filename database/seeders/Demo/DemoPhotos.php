<?php

namespace Database\Seeders\Demo;

use App\Helpers\FileHelper;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Real photos for the demo seeder: downloaded once per key from picsum.photos (deterministic seeds),
 * stored the way the app stores uploads, and replaced by a locally drawn image when offline.
 */
class DemoPhotos
{
    public int $downloaded = 0;

    public int $generated = 0;

    /** @var list<array{disk: string, path: string}> files written during this run, for cleanup on failure */
    public array $written = [];

    /** @var array<string, string> key => local source file */
    private array $sources = [];

    /** @var array<string, string> folder|key => stored path */
    private array $stored = [];

    private string $tmpDir;

    public function __construct(private readonly ?\Closure $warn = null)
    {
        $this->tmpDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'scamproof-demo-'.Str::random(8);
        @mkdir($this->tmpDir, 0777, true);
    }

    public function __destruct()
    {
        foreach (glob($this->tmpDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tmpDir);
    }

    /**
     * Store a photo through FileHelper (public/uploads/<folder>, WebP, max 1200px) like a form upload.
     * The same key in the same folder is stored once and its path reused.
     */
    public function upload(string $key, string $folder, int $width, int $height, string $label): string
    {
        return $this->stored[$folder.'|'.$key] ??= $this->remember('my_public', FileHelper::uploadImage(
            new UploadedFile($this->source($key, $width, $height, $label), $key.'.jpg', 'image/jpeg', null, true),
            $folder,
        ));
    }

    /**
     * Store a photo on the public disk under posts/, like NewfeedPostController::store does.
     */
    public function newfeed(string $key, string $label): string
    {
        return $this->stored['newfeed|'.$key] ??= $this->remember('public', Storage::disk('public')->putFile(
            'posts',
            new File($this->source($key, 800, 600, $label)),
        ));
    }

    public function deleteWritten(): void
    {
        foreach ($this->written as $file) {
            Storage::disk($file['disk'])->delete($file['path']);
        }
        $this->written = [];
    }

    private function remember(string $disk, string $path): string
    {
        $this->written[] = ['disk' => $disk, 'path' => $path];

        return $path;
    }

    private function source(string $key, int $width, int $height, string $label): string
    {
        if (isset($this->sources[$key])) {
            return $this->sources[$key];
        }

        $file = $this->tmpDir.DIRECTORY_SEPARATOR.Str::slug($key).'.jpg';
        $bytes = $this->download("https://picsum.photos/seed/{$key}/{$width}/{$height}");

        if ($bytes !== null) {
            file_put_contents($file, $bytes);
            $this->downloaded++;
        } else {
            $this->drawFallback($file, $key, $width, $height, $label);
            $this->generated++;
            Log::warning("Demo seeder: could not download photo {$key}, drew a local placeholder.");
            if ($this->warn) {
                ($this->warn)("Không tải được ảnh {$key}, dùng ảnh tạo tại chỗ.");
            }
        }

        return $this->sources[$key] = $file;
    }

    /**
     * One request plus one retry; null when the response is not an image.
     */
    private function download(string $url): ?string
    {
        // Stay polite to the free image service; tests fake it and need no pauses.
        $politeMs = app()->runningUnitTests() ? 0 : 150;
        usleep($politeMs * 1000);

        try {
            $response = Http::timeout(20)->retry(2, $politeMs * 5, throw: false)->get($url);
        } catch (Throwable) {
            return null;
        }

        $isImage = str_starts_with((string) $response->header('Content-Type'), 'image/');

        return $response->successful() && $isImage && $response->body() !== '' ? $response->body() : null;
    }

    /**
     * Offline fallback: a two-colour gradient with the label, drawn small and scaled up.
     */
    private function drawFallback(string $file, string $key, int $width, int $height, string $label): void
    {
        $w = max(1, intdiv($width, 4));
        $h = max(1, intdiv($height, 4));
        $hash = crc32($key);
        $from = [($hash >> 16) & 0x7F, ($hash >> 8) & 0x7F, $hash & 0x7F];
        $to = [$from[1] + 110, $from[2] + 110, $from[0] + 110];

        $small = imagecreatetruecolor($w, $h);
        for ($y = 0; $y < $h; $y++) {
            $t = $h > 1 ? $y / ($h - 1) : 0;
            $color = imagecolorallocate($small, ...array_map(fn ($a, $b) => (int) round($a + ($b - $a) * $t), $from, $to));
            imageline($small, 0, $y, $w, $y, $color);
        }

        $text = Str::limit(Str::ascii($label), max(4, intdiv($w, 9) - 1), '');
        $x = max(2, intdiv($w - imagefontwidth(5) * strlen($text), 2));
        imagestring($small, 5, $x, intdiv($h - imagefontheight(5), 2), $text, imagecolorallocate($small, 255, 255, 255));

        $image = imagescale($small, $width, $height, IMG_BICUBIC);
        imagejpeg($image, $file, 85);
        imagedestroy($small);
        imagedestroy($image);
    }
}
