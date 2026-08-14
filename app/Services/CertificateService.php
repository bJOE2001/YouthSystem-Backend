<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\SkOfficial;
use App\Models\SportsProgram;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Resolve the full name of a participant for certificate display.
     */
    public function getParticipantFullName(User $user): string
    {
        if ($user->youthProfile) {
            $profile = $user->youthProfile;
            $parts = array_filter([
                $profile->first_name,
                $profile->middle_name,
                $profile->last_name,
                $profile->suffix,
            ], fn ($val) => filled($val));

            $fullName = implode(' ', $parts);
            if (! empty(trim($fullName))) {
                return trim($fullName);
            }
        }

        if ($user->role === UserRole::SkAdmin) {
            $skOfficial = SkOfficial::where('email', $user->email)->first();
            if ($skOfficial && ! empty($skOfficial->name)) {
                return $skOfficial->name;
            }
        }

        return $user->name ?: 'Participant';
    }

    /**
     * Upload and assign a certificate template to an Event or SportsProgram.
     */
    public function uploadTemplate(Event|SportsProgram $model, UploadedFile $file, array $settings = []): string
    {
        // Delete previous template if exists
        if (! empty($model->certificate_template_path) && Storage::disk('public')->exists($model->certificate_template_path)) {
            Storage::disk('public')->delete($model->certificate_template_path);
        }

        $path = $file->store('certificates/templates', 'public');

        $updateData = ['certificate_template_path' => $path];
        if (! empty($settings)) {
            $updateData['certificate_settings'] = $settings;
        }

        $model->update($updateData);

        return $path;
    }

    /**
     * Update certificate settings for an Event or SportsProgram.
     */
    public function updateSettings(Event|SportsProgram $model, array $settings): void
    {
        $existing = $model->certificate_settings ?? [];
        $merged = array_merge($existing, $settings);
        $model->update(['certificate_settings' => $merged]);
    }

    /**
     * Generate a personalized certificate for a participant.
     *
     * @return array{content: string, mime: string, filename: string}
     */
    public function generateCertificate(Event|SportsProgram $model, User $user): array
    {
        $participantName = $this->getParticipantFullName($user);
        $activityName = $model->name;
        $slugName = Str::slug($participantName) ?: 'participant';
        $slugActivity = Str::slug($activityName) ?: 'activity';
        $filename = "Certificate-{$slugActivity}-{$slugName}.pdf";

        $templatePath = $model->certificate_template_path;
        if (! $templatePath || ! Storage::disk('public')->exists($templatePath)) {
            throw new \RuntimeException('Certificate template not found.');
        }

        $fullPath = Storage::disk('public')->path($templatePath);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $settings = $model->certificate_settings ?? [];

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $jpegData = $this->renderParticipantOnImage($fullPath, $participantName, $activityName, $settings);

            // Get dimensions of the rendered image
            $imageInfo = getimagesizefromstring($jpegData);
            $width = $imageInfo ? $imageInfo[0] : 1200;
            $height = $imageInfo ? $imageInfo[1] : 850;

            $pdfContent = $this->buildPdfFromJpeg($jpegData, $width, $height, "Certificate - {$activityName}");

            return [
                'content' => $pdfContent,
                'mime' => 'application/pdf',
                'filename' => $filename,
            ];
        }

        // If template is already a PDF, read it or generate custom PDF
        $fileContent = Storage::disk('public')->get($templatePath);

        return [
            'content' => $fileContent,
            'mime' => 'application/pdf',
            'filename' => $filename,
        ];
    }

    /**
     * Generate a live preview of the certificate with custom placement settings.
     *
     * @return array{content: string, mime: string, filename: string}
     */
    public function generatePreview(Event|SportsProgram $model, array $customSettings = [], ?string $sampleName = null): array
    {
        $participantName = $sampleName ?: 'JUAN DELA CRUZ';
        $activityName = $model->name;
        $filename = 'Certificate-Preview.pdf';

        $templatePath = $model->certificate_template_path;
        if (! $templatePath || ! Storage::disk('public')->exists($templatePath)) {
            throw new \RuntimeException('Certificate template not found.');
        }

        $fullPath = Storage::disk('public')->path($templatePath);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        $settings = array_merge($model->certificate_settings ?? [], $customSettings);

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $jpegData = $this->renderParticipantOnImage($fullPath, $participantName, $activityName, $settings);

            $imageInfo = getimagesizefromstring($jpegData);
            $width = $imageInfo ? $imageInfo[0] : 1200;
            $height = $imageInfo ? $imageInfo[1] : 850;

            $pdfContent = $this->buildPdfFromJpeg($jpegData, $width, $height, "Certificate Preview - {$activityName}");

            return [
                'content' => $pdfContent,
                'mime' => 'application/pdf',
                'filename' => $filename,
            ];
        }

        $fileContent = Storage::disk('public')->get($templatePath);

        return [
            'content' => $fileContent,
            'mime' => 'application/pdf',
            'filename' => $filename,
        ];
    }

    /**
     * Render the participant name onto the certificate image template using GD.
     */
    protected function renderParticipantOnImage(string $imagePath, string $participantName, string $activityName, array $settings = []): string
    {
        $imageContent = file_get_contents($imagePath);
        $image = imagecreatefromstring($imageContent);

        if (! $image) {
            throw new \RuntimeException('Failed to load certificate template image.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        // Parse custom settings with defaults
        $nameYPercent = isset($settings['name_y']) ? (float) $settings['name_y'] : 52.0;
        $nameXPercent = isset($settings['name_x']) ? (float) $settings['name_x'] : 50.0;
        $customFontSize = isset($settings['font_size']) ? (int) $settings['font_size'] : null;
        $hexColor = $settings['font_color'] ?? $settings['color'] ?? '#1A202C';
        $textAlign = strtolower($settings['text_align'] ?? 'center');

        // Resolve RGB text color
        $rgb = $this->hexToRgb($hexColor);
        $textColor = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);

        // Find available font
        $fontPath = $this->resolveTtfFont();

        if ($fontPath && function_exists('imagettftext')) {
            // Determine dynamic font size based on image width (approx 3.5% of width or custom scaled)
            if ($customFontSize && $customFontSize > 0) {
                // Scale configured pixel font size relative to standard 1000px width
                $fontSize = max(14, (int) round($customFontSize * ($width / 1000)));
            } else {
                $fontSize = max(18, (int) round($width * 0.035));
            }

            // Calculate bounding box for centering / positioning
            $bbox = imagettfbbox($fontSize, 0, $fontPath, $participantName);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);

            // Prevent text overflow beyond 90% of image width
            $maxWidth = $width * 0.9;
            if ($textWidth > $maxWidth && $textWidth > 0) {
                $fontSize = (int) round($fontSize * ($maxWidth / $textWidth));
                $bbox = imagettfbbox($fontSize, 0, $fontPath, $participantName);
                $textWidth = abs($bbox[4] - $bbox[0]);
            }

            // Calculate X coordinate
            if ($textAlign === 'left') {
                $x = (int) round($width * ($nameXPercent / 100));
            } elseif ($textAlign === 'right') {
                $x = (int) round($width * ($nameXPercent / 100) - $textWidth);
            } else {
                // Center aligned: use nameXPercent as the center anchor point
                $anchorX = (int) round($width * ($nameXPercent / 100));
                $x = (int) round($anchorX - ($textWidth / 2));
            }

            // Ensure within boundaries
            $x = max(10, min($width - $textWidth - 10, $x));

            // Calculate Y coordinate (baseline)
            $y = (int) round($height * ($nameYPercent / 100));

            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $participantName);
        } else {
            // Fallback for environments without TTF
            $font = 5; // Built-in font (9x15 pixels)
            $charWidth = imagefontwidth($font);
            $textWidth = strlen($participantName) * $charWidth;

            $anchorX = (int) round($width * ($nameXPercent / 100));
            $x = max(10, (int) round($anchorX - ($textWidth / 2)));
            $y = (int) round($height * ($nameYPercent / 100) - 8);

            imagestring($image, $font, $x, $y, $participantName, $textColor);
        }

        ob_start();
        imagejpeg($image, null, 95);
        $jpegData = ob_get_clean();
        imagedestroy($image);

        return $jpegData;
    }

    /**
     * Convert Hex color string (e.g. #1A202C or 1A202C) to RGB array.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $r = hexdec(str_repeat(substr($hex, 0, 1), 2));
            $g = hexdec(str_repeat(substr($hex, 1, 1), 2));
            $b = hexdec(str_repeat(substr($hex, 2, 1), 2));
        } elseif (strlen($hex) === 6) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        } else {
            // Default dark slate
            return [26, 32, 44];
        }

        return [$r, $g, $b];
    }

    /**
     * Resolve a valid TrueType font on the server.
     */
    protected function resolveTtfFont(): ?string
    {
        $candidateFonts = [
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            'C:\\Windows\\Fonts\\timesbd.ttf',
            'C:\\Windows\\Fonts\\times.ttf',
            'C:\\Windows\\Fonts\\georgiab.ttf',
            'C:\\Windows\\Fonts\\georgia.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ];

        foreach ($candidateFonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }

        return null;
    }

    /**
     * Build a compliant single-page PDF document embedding the high-resolution JPEG.
     */
    protected function buildPdfFromJpeg(string $jpegData, int $width, int $height, string $title): string
    {
        $imageLength = strlen($jpegData);

        // Standard A4 landscape points (842 x 595) or scaled to aspect ratio
        if ($width >= $height) {
            $pageWidth = 842;
            $pageHeight = (int) round(842 * ($height / $width));
        } else {
            $pageHeight = 842;
            $pageWidth = (int) round(842 * ($width / $height));
        }

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        $obj1 = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $offsets[1] = strlen($pdf);
        $pdf .= $obj1;

        $obj2 = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $offsets[2] = strlen($pdf);
        $pdf .= $obj2;

        $obj3 = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Resources << /XObject << /Im1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $offsets[3] = strlen($pdf);
        $pdf .= $obj3;

        $obj4 = "4 0 obj\n<< /Type /XObject /Subtype /Image /Width {$width} /Height {$height} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$imageLength} >>\nstream\n".$jpegData."\nendstream\nendobj\n";
        $offsets[4] = strlen($pdf);
        $pdf .= $obj4;

        $contentStream = "q\n{$pageWidth} 0 0 {$pageHeight} 0 0 cm\n/Im1 Do\nQ\n";
        $contentLength = strlen($contentStream);
        $obj5 = "5 0 obj\n<< /Length {$contentLength} >>\nstream\n{$contentStream}endstream\nendobj\n";
        $offsets[5] = strlen($pdf);
        $pdf .= $obj5;

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }
}
