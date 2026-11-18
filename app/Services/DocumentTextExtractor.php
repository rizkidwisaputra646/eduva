<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use ZipArchive;

class DocumentTextExtractor
{
    public const SUPPORTED_EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
    public const MAX_TEXT_LENGTH = 25000;

    /**
     * Extract readable text from an uploaded or stored document.
     *
     * @return array{success: bool, text: string, error: ?string}
     */
    public function extract(UploadedFile|string $file, ?string $extension = null): array
    {
        if ($file instanceof UploadedFile) {
            $path = $file->getRealPath();
            $ext = strtolower($extension ?? $file->getClientOriginalExtension());
        } else {
            $path = $file;
            $ext = strtolower($extension ?? pathinfo($file, PATHINFO_EXTENSION));
        }

        if (!$path || !file_exists($path) || !is_readable($path)) {
            return $this->fail('Berkas dokumen tidak ditemukan atau tidak dapat dibaca.');
        }

        if (!in_array($ext, self::SUPPORTED_EXTENSIONS, true)) {
            return $this->fail('Format berkas tidak didukung. Gunakan PDF, DOC, DOCX, PPT, PPTX, atau TXT.');
        }

        if (filesize($path) === 0) {
            return $this->fail('Dokumen kosong (0 byte). Silakan unggah dokumen yang berisi materi pembelajaran.');
        }

        try {
            $rawText = match ($ext) {
                'txt' => $this->extractTxt($path),
                'docx' => $this->extractDocx($path),
                'pptx' => $this->extractPptx($path),
                'pdf' => $this->extractPdf($path),
                'doc' => $this->extractDoc($path),
                'ppt' => $this->extractPpt($path),
            };
        } catch (\Throwable $e) {
            return $this->fail('Gagal mengekstrak teks dari dokumen (' . strtoupper($ext) . '): ' . $e->getMessage());
        }

        $cleanText = $this->normalizeWhitespace($rawText);

        if (mb_strlen($cleanText) < 20) {
            return $this->fail(
                'Teks materi pada dokumen tidak cukup atau tidak dapat dibaca (dokumen kosong, terenkripsi, atau hanya berisi gambar/scan).'
            );
        }

        return [
            'success' => true,
            'text' => Str::limit($cleanText, self::MAX_TEXT_LENGTH, ''),
            'error' => null,
        ];
    }

    protected function extractTxt(string $path): string
    {
        $content = (string) file_get_contents($path);
        // Strip UTF-8 BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        }

        return $content;
    }

    protected function extractDocx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Arsip DOCX tidak valid atau rusak.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if ($documentXml === false) {
            $zip->close();
            throw new \RuntimeException('Konten utama (word/document.xml) tidak ditemukan pada berkas DOCX.');
        }

        $zip->close();

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($documentXml);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $lines = [];
        $paragraphs = $xpath->query('//w:p');
        if ($paragraphs) {
            foreach ($paragraphs as $paragraph) {
                $texts = $xpath->query('.//w:t', $paragraph);
                $line = '';
                if ($texts) {
                    foreach ($texts as $node) {
                        $line .= $node->nodeValue;
                    }
                }
                $line = trim($line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        return implode("\n", $lines);
    }

    protected function extractPptx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Arsip PPTX tidak valid atau rusak.');
        }

        $slideEntries = [];
        $noteEntries = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false) {
                continue;
            }
            if (preg_match('#^ppt/slides/slide(\d+)\.xml$#', $name, $m)) {
                $slideEntries[(int) $m[1]] = $name;
            } elseif (preg_match('#^ppt/notesSlides/notesSlide(\d+)\.xml$#', $name, $m)) {
                $noteEntries[(int) $m[1]] = $name;
            }
        }

        if (empty($slideEntries)) {
            $zip->close();
            throw new \RuntimeException('Tidak ada slide yang ditemukan pada berkas PPTX.');
        }

        ksort($slideEntries);
        $output = [];

        foreach ($slideEntries as $slideNum => $entryName) {
            $xml = $zip->getFromName($entryName);
            if ($xml === false) {
                continue;
            }

            $slideText = $this->extractDrawingMlParagraphs($xml);

            if (isset($noteEntries[$slideNum])) {
                $noteXml = $zip->getFromName($noteEntries[$slideNum]);
                if ($noteXml !== false) {
                    $noteText = $this->extractDrawingMlParagraphs($noteXml);
                    if ($noteText !== '') {
                        $slideText .= ($slideText !== '' ? "\n" : '') . 'Catatan: ' . $noteText;
                    }
                }
            }

            if (trim($slideText) !== '') {
                $output[] = "[Slide {$slideNum}]\n{$slideText}";
            }
        }

        $zip->close();

        return implode("\n\n", $output);
    }

    protected function extractDrawingMlParagraphs(string $xml): string
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadXML($xml);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

        $lines = [];
        $paragraphs = $xpath->query('//a:p');
        if ($paragraphs) {
            foreach ($paragraphs as $paragraph) {
                $runs = $xpath->query('.//a:t', $paragraph);
                $line = '';
                if ($runs) {
                    foreach ($runs as $run) {
                        $line .= $run->nodeValue;
                    }
                }
                $line = trim($line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        return implode("\n", $lines);
    }

    protected function extractPdf(string $path): string
    {
        if (class_exists(PdfParser::class)) {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($path);
            $text = (string) $pdf->getText();
            if (trim($text) !== '') {
                return $text;
            }
        }

        // Fallback sederhana untuk PDF dengan teks stream tidak terkompresi
        $raw = (string) file_get_contents($path);
        $chunks = [];
        if (preg_match_all('/\(([^()]{2,})\)\s*Tj/s', $raw, $matches)) {
            foreach ($matches[1] as $m) {
                $chunks[] = stripcslashes($m);
            }
        }

        return implode(' ', $chunks);
    }

    protected function extractDoc(string $path): string
    {
        $raw = (string) file_get_contents($path);

        // Tangani berkas yang sebenarnya DOCX, RTF, atau teks biasa berekstensi .doc
        if (str_starts_with($raw, "PK\x03\x04")) {
            return $this->extractDocx($path);
        }
        if (str_starts_with(ltrim($raw), '{\rtf')) {
            return $this->stripRtf($raw);
        }

        // Ekstrak run teks UTF-16LE dari dokumen Word biner (.doc)
        $utf16Lines = [];
        if (preg_match_all('/(?:[\x20-\x7E\x0A\x0D\x09]\x00){4,}/', $raw, $matches)) {
            foreach ($matches[0] as $segment) {
                $decoded = trim((string) mb_convert_encoding($segment, 'UTF-8', 'UTF-16LE'));
                if ($decoded !== '' && !$this->isOleMetadataNoise($decoded)) {
                    $utf16Lines[] = $decoded;
                }
            }
        }

        if (mb_strlen(implode(' ', $utf16Lines)) >= 30) {
            return implode("\n", $utf16Lines);
        }

        // Fallback ke ASCII printable runs jika teks disimpan sebagai single-byte
        $asciiLines = [];
        if (preg_match_all('/[\x20-\x7E\r\n\t]{12,}/', $raw, $matches)) {
            foreach ($matches[0] as $segment) {
                $clean = trim($segment);
                if ($clean !== '' && !$this->isOleMetadataNoise($clean)) {
                    $asciiLines[] = $clean;
                }
            }
        }

        return implode("\n", $asciiLines);
    }

    protected function extractPpt(string $path): string
    {
        $raw = (string) file_get_contents($path);

        // Tangani berkas PPTX yang berekstensi .ppt
        if (str_starts_with($raw, "PK\x03\x04")) {
            return $this->extractPptx($path);
        }

        $segments = [];
        $len = strlen($raw);

        // Pindai PowerPoint binary record headers:
        // TextCharsAtom (recType 0x0FA0 = \xA0\x0F -> UTF-16LE)
        // TextBytesAtom (recType 0x0FA8 = \xA8\x0F -> Latin1/Windows-1252)
        for ($i = 0; $i + 8 <= $len; $i++) {
            $recTypeByte1 = ord($raw[$i + 2]);
            $recTypeByte2 = ord($raw[$i + 3]);
            $recType = $recTypeByte1 | ($recTypeByte2 << 8);

            if ($recType !== 0x0FA0 && $recType !== 0x0FA8) {
                continue;
            }

            $recLen = unpack('V', substr($raw, $i + 4, 4))[1] ?? 0;
            if ($recLen <= 0 || $recLen > 200000 || ($i + 8 + $recLen) > $len) {
                continue;
            }

            $payload = substr($raw, $i + 8, $recLen);
            if ($recType === 0x0FA0) {
                $text = (string) mb_convert_encoding($payload, 'UTF-8', 'UTF-16LE');
            } else {
                $text = (string) mb_convert_encoding($payload, 'UTF-8', 'Windows-1252');
            }

            $text = trim(str_replace(["\r", "\x0B"], "\n", $text));
            if ($text !== '' && !$this->isOleMetadataNoise($text)) {
                $segments[] = $text;
            }

            $i += 7 + $recLen;
        }

        if (mb_strlen(implode(' ', $segments)) >= 20) {
            return implode("\n\n", $segments);
        }

        // Fallback ke ekstraksi teks UTF-16LE / ASCII umum
        return $this->extractDoc($path);
    }

    protected function stripRtf(string $rtf): string
    {
        $text = preg_replace('/\{\\\\(?:fonttbl|colortbl|stylesheet|info|pict|object).*?\}/s', ' ', $rtf);
        $text = preg_replace('/\\\\(?:par|line)\b/', "\n", (string) $text);
        $text = preg_replace('/\\\\[a-z]+\-?\d*\s?/i', '', (string) $text);
        return str_replace(['{', '}'], '', (string) $text);
    }

    protected function isOleMetadataNoise(string $line): bool
    {
        return (bool) preg_match(
            '/^(Root Entry|WordDocument|1Table|0Table|Data|PowerPoint Document|Current User|Pictures|SummaryInformation|DocumentSummaryInformation|Microsoft (Office )?(Word|PowerPoint)|Normal\.dotm|Times New Roman|Calibri|Arial|Cambria)/i',
            trim($line)
        );
    }

    protected function normalizeWhitespace(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\x0B", "\x0C"], "\n", $text);
        $text = preg_replace('/[^\P{C}\n\t]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    protected function fail(string $message): array
    {
        return [
            'success' => false,
            'text' => '',
            'error' => $message,
        ];
    }
}

