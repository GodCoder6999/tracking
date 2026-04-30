<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class UserImportService
{
    public static function parse(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        $raw = match ($ext) {
            'csv'  => static::parseCsv($file->getRealPath()),
            'json' => static::parseJson($file->getRealPath()),
            default => throw new \InvalidArgumentException("Unsupported file type: {$ext}. Use CSV or JSON."),
        };

        if (count($raw) < 2) return [];

        $headers = array_map(fn($h) => strtolower(trim($h)), $raw[0]);
        $hCount  = count($headers);
        $rows    = [];

        foreach (array_slice($raw, 1) as $line) {
            $line = array_map('trim', $line);
            // Pad short rows, slice long rows — both must equal header count
            $line = array_slice(array_pad($line, $hCount, ''), 0, $hCount);
            if (array_filter($line, fn($v) => $v !== '')) {
                $rows[] = array_combine($headers, $line);
            }
        }

        return $rows;
    }

    // ── CSV ──────────────────────────────────────────────────────────────────

    private static function parseCsv(string $path): array
    {
        $rows = [];
        $fh   = fopen($path, 'r');
        while (($row = fgetcsv($fh)) !== false) {
            $rows[] = $row;
        }
        fclose($fh);
        return $rows;
    }

    // ── JSON ─────────────────────────────────────────────────────────────────

    private static function parseJson(string $path): array
    {
        $data = json_decode(file_get_contents($path), true);
        if (! is_array($data) || empty($data)) return [];

        if (is_array($data[0])) {
            return $data;
        }

        $headers = array_keys($data[0]);
        $rows    = [$headers];
        foreach ($data as $obj) {
            $rows[] = array_values($obj);
        }
        return $rows;
    }
}
