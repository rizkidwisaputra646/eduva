<?php

namespace App\Services;

use ZipArchive;
use SimpleXMLElement;

class ExcelService
{
    /**
     * Parse an uploaded file (either .xlsx or .csv) and return array of rows
     */
    public function parseFile(string $filePath, ?string $originalName = null): array
    {
        $ext = strtolower(pathinfo($originalName ?? $filePath, PATHINFO_EXTENSION));

        if ($ext === 'xlsx') {
            return $this->readXlsx($filePath);
        }

        return $this->readCsv($filePath);
    }

    /**
     * Read an .xlsx file using native ZipArchive and SimpleXML
     */
    public function readXlsx(string $filePath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception('Berkas Excel (.xlsx) tidak dapat dibuka atau rusak.');
        }

        // 1. Baca sharedStrings.xml jika tersedia
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = @simplexml_load_string($sharedStringsXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Baca sheet1.xml
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            // Coba cari sheet lain jika nama bukan sheet1.xml
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                if (str_starts_with($filename, 'xl/worksheets/sheet') && str_ends_with($filename, '.xml')) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        if ($sheetXml === false) {
            $zip->close();
            throw new \Exception('Lembar kerja (sheet) tidak ditemukan dalam berkas Excel.');
        }

        $xml = @simplexml_load_string($sheetXml);
        $zip->close();

        if (!$xml || !isset($xml->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $currentCol = 0;

            foreach ($row->c as $c) {
                $cellRef = (string) $c['r']; // e.g. "A1", "B1"
                preg_match('/([A-Z]+)/', $cellRef, $matches);
                $colLetters = $matches[1] ?? 'A';
                $colIndex = $this->columnLetterToIndex($colLetters);

                // Isi kolom kosong di antara jika ada sel yang di-skip
                while ($currentCol < $colIndex) {
                    $rowData[] = '';
                    $currentCol++;
                }

                $type = (string) $c['t'];
                $val = '';

                if ($type === 's') {
                    $idx = (int) $c->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr' && isset($c->is->t)) {
                    $val = (string) $c->is->t;
                } elseif (isset($c->v)) {
                    $val = (string) $c->v;
                }

                $rowData[] = trim($val);
                $currentCol++;
            }

            if (!empty(array_filter($rowData, fn($v) => $v !== ''))) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    /**
     * Read a CSV file with automatic delimiter detection and UTF-8 handling
     */
    public function readCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception('Gagal membaca berkas CSV.');
        }

        $firstLine = file_get_contents($filePath, false, null, 0, 1024);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        // Strip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rows = [];
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_map('trim', $data);
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Create an .xlsx template file with headers and sample rows
     */
    public function generateTemplateXlsx(string $outputPath): bool
    {
        $headers = ['nama', 'nomor_identitas', 'peran', 'email'];
        $sampleData = [
            ['Ahmad Dahlan', '202401010', 'siswa', 'ahmad@sekolah.sch.id'],
            ['Siti Aminah', '202401011', 'siswa', 'siti@sekolah.sch.id'],
            ['Dr. Budi Utomo, M.Kom', '198205122010011002', 'guru', 'budi.utomo@sekolah.sch.id'],
            ['Dewi Sartika, S.Pd', '198708202014022003', 'guru', 'dewi.sartika@sekolah.sch.id'],
        ];

        return $this->createXlsx($headers, $sampleData, $outputPath);
    }

    /**
     * Generate .xlsx package natively via ZipArchive
     */
    public function createXlsx(array $headers, array $rows, string $outputPath): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        // Shared strings dictionary
        $sharedStrings = [];
        $stringIndices = [];

        $getStringIndex = function ($str) use (&$sharedStrings, &$stringIndices) {
            $str = (string) $str;
            if (!isset($stringIndices[$str])) {
                $idx = count($sharedStrings);
                $sharedStrings[] = $str;
                $stringIndices[$str] = $idx;
            }
            return $stringIndices[$str];
        };

        // 1. Generate Sheet1 XML
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . "\n";
        $sheetXml .= '<sheetData>' . "\n";

        // Baris Header (Row 1)
        $sheetXml .= '<row r="1">' . "\n";
        foreach ($headers as $colIdx => $h) {
            $colLetter = $this->indexToColumnLetter($colIdx);
            $strIdx = $getStringIndex($h);
            $sheetXml .= "<c r=\"{$colLetter}1\" t=\"s\"><v>{$strIdx}</v></c>\n";
        }
        $sheetXml .= '</row>' . "\n";

        // Baris Data
        foreach ($rows as $rowIdx => $rowValues) {
            $rowNum = $rowIdx + 2;
            $sheetXml .= "<row r=\"{$rowNum}\">\n";
            foreach ($rowValues as $colIdx => $val) {
                $colLetter = $this->indexToColumnLetter($colIdx);
                $strIdx = $getStringIndex($val);
                $sheetXml .= "<c r=\"{$colLetter}{$rowNum}\" t=\"s\"><v>{$strIdx}</v></c>\n";
            }
            $sheetXml .= "</row>\n";
        }

        $sheetXml .= '</sheetData>' . "\n";
        $sheetXml .= '</worksheet>';

        // 2. Generate sharedStrings XML
        $sharedStringsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
        $sharedStringsXml .= '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">' . "\n";
        foreach ($sharedStrings as $str) {
            $escaped = htmlspecialchars($str, ENT_XML1, 'UTF-8');
            $sharedStringsXml .= "<si><t>{$escaped}</t></si>\n";
        }
        $sharedStringsXml .= '</sst>';

        // 3. Static OpenXML manifests
        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStringTable+xml"/>
</Types>';

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';

        $workbookRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>
</Relationships>';

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Data Pengguna" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';

        $zip->addFromString('[Content_Types].xml', $contentTypesXml);
        $zip->addFromString('_rels/.rels', $relsXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRelsXml);
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();
        return true;
    }

    private function columnLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $len = strlen($letters);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }

    private function indexToColumnLetter(int $index): string
    {
        $letter = '';
        $index = $index + 1;
        while ($index > 0) {
            $modulo = ($index - 1) % 26;
            $letter = chr(65 + $modulo) . $letter;
            $index = (int) (($index - $modulo) / 26);
        }
        return $letter;
    }
}

