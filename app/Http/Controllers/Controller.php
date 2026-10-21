<?php

namespace App\Http\Controllers;

abstract class Controller
{
    // Semicolon + decimal comma so Excel with Indonesian locale opens it directly.
    protected function csv(string $name, array $header, iterable $rows)
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            // Participant-typed text may start with =,+,-,@: neutralise spreadsheet formulas.
            $cell = fn ($v) => is_float($v) ? number_format($v, 2, ',', '') : (is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v);
            fputcsv($out, $header, ';');
            foreach ($rows as $row) {
                fputcsv($out, array_map($cell, $row), ';');
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
