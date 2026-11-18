<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\Style\Table as TableStyle;
use Illuminate\Http\UploadedFile;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class WordQuestionService
{
    /**
     * Generate and return temporary path for the Word template.
     */
    public function generateTemplate(string $type = 'multiple_choice'): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'marginLeft' => 1000,
            'marginRight' => 1000,
            'orientation' => ($type === 'multiple_choice') ? 'landscape' : 'portrait',
        ]);

        if ($type === 'multiple_choice') {
            $this->buildMcqTemplate($section);
            $filename = 'Template_Soal_Pilihan_Ganda_Eduva.docx';
        } else {
            $this->buildEssayTemplate($section);
            $filename = 'Template_Soal_Essay_Eduva.docx';
        }

        $tempPath = storage_path('app/' . $filename);
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return $tempPath;
    }

    /**
     * Build Multiple Choice template section
     */
    protected function buildMcqTemplate($section): void
    {
        // Header Title
        $section->addTitle('TEMPLATE IMPORT SOAL PILIHAN GANDA (LMS EDUVA)', 1);
        $section->addText('Petunjuk Pengisian:', ['bold' => true, 'color' => '1E3A8A']);
        $section->addListItem('Isi soal pada tabel di bawah ini. Anda dapat menambah baris baru sesuai kebutuhan.', 0);
        $section->addListItem('Kolom "Kunci": Wajib diisi huruf kapital (A, B, C, D, atau E).', 0);
        $section->addListItem('Kolom "Pembahasan": Opsional, dapat diisi penjelasan untuk siswa.', 0);
        $section->addListItem('Kolom "Poin": Angka bobot nilai (contoh: 10).', 0);
        $section->addTextBreak(1);

        // Table Style
        $tableStyle = [
            'borderColor' => 'CBD5E1',
            'borderSize' => 6,
            'cellMargin' => 80,
            'alignment' => JcTable::CENTER,
        ];
        $headerCellStyle = ['bgColor' => '1E40AF'];
        $headerFont = ['bold' => true, 'color' => 'FFFFFF', 'size' => 10];

        $table = $section->addTable($tableStyle);
        $table->addRow(500);
        $table->addCell(600, $headerCellStyle)->addText('No', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(3500, $headerCellStyle)->addText('Pertanyaan', $headerFont);
        $table->addCell(1800, $headerCellStyle)->addText('Pilihan A', $headerFont);
        $table->addCell(1800, $headerCellStyle)->addText('Pilihan B', $headerFont);
        $table->addCell(1800, $headerCellStyle)->addText('Pilihan C', $headerFont);
        $table->addCell(1800, $headerCellStyle)->addText('Pilihan D', $headerFont);
        $table->addCell(1800, $headerCellStyle)->addText('Pilihan E', $headerFont);
        $table->addCell(900, $headerCellStyle)->addText('Kunci', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(2500, $headerCellStyle)->addText('Pembahasan (Opsional)', $headerFont);
        $table->addCell(800, $headerCellStyle)->addText('Poin', $headerFont, ['alignment' => Jc::CENTER]);

        // Sample Row 1
        $table->addRow();
        $table->addCell(600)->addText('1', [], ['alignment' => Jc::CENTER]);
        $table->addCell(3500)->addText('Manakah dari berikut ini yang merupakan protokol transfer halaman web yang aman?');
        $table->addCell(1800)->addText('HTTP');
        $table->addCell(1800)->addText('HTTPS');
        $table->addCell(1800)->addText('FTP');
        $table->addCell(1800)->addText('SMTP');
        $table->addCell(1800)->addText('SSH');
        $table->addCell(900)->addText('B', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(2500)->addText('HTTPS menggunakan sertifikat TLS/SSL untuk enkripsi.');
        $table->addCell(800)->addText('10', [], ['alignment' => Jc::CENTER]);

        // Sample Row 2
        $table->addRow();
        $table->addCell(600)->addText('2', [], ['alignment' => Jc::CENTER]);
        $table->addCell(3500)->addText('Bahasa pemrograman yang berjalan secara native di browser client-side adalah?');
        $table->addCell(1800)->addText('PHP');
        $table->addCell(1800)->addText('Python');
        $table->addCell(1800)->addText('JavaScript');
        $table->addCell(1800)->addText('Java');
        $table->addCell(1800)->addText('C++');
        $table->addCell(900)->addText('C', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(2500)->addText('JavaScript adalah bahasa pemrograman standar peramban web.');
        $table->addCell(800)->addText('10', [], ['alignment' => Jc::CENTER]);
    }

    /**
     * Build Essay template section
     */
    protected function buildEssayTemplate($section): void
    {
        // Header Title
        $section->addTitle('TEMPLATE IMPORT SOAL ESSAY / URAIAN (LMS EDUVA)', 1);
        $section->addText('Petunjuk Pengisian:', ['bold' => true, 'color' => '1E3A8A']);
        $section->addListItem('Isi soal pada tabel di bawah ini. Anda dapat menambah baris baru sesuai kebutuhan.', 0);
        $section->addListItem('Kolom "Pedoman / Kunci Jawaban": Opsional, dapat diisi poin penting penilaian.', 0);
        $section->addListItem('Kolom "Bobot Nilai": Wajib diisi angka poin maksimal (contoh: 20).', 0);
        $section->addTextBreak(1);

        // Table Style
        $tableStyle = [
            'borderColor' => 'CBD5E1',
            'borderSize' => 6,
            'cellMargin' => 100,
            'alignment' => JcTable::CENTER,
        ];
        $headerCellStyle = ['bgColor' => '1E40AF'];
        $headerFont = ['bold' => true, 'color' => 'FFFFFF', 'size' => 10];

        $table = $section->addTable($tableStyle);
        $table->addRow(500);
        $table->addCell(800, $headerCellStyle)->addText('No', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(4500, $headerCellStyle)->addText('Pertanyaan Essay', $headerFont);
        $table->addCell(3500, $headerCellStyle)->addText('Pedoman / Kunci Jawaban (Opsional)', $headerFont);
        $table->addCell(1200, $headerCellStyle)->addText('Bobot Nilai', $headerFont, ['alignment' => Jc::CENTER]);

        // Sample Row 1
        $table->addRow();
        $table->addCell(800)->addText('1', [], ['alignment' => Jc::CENTER]);
        $table->addCell(4500)->addText('Jelaskan perbedaan mendasar antara metode HTTP GET dan POST dalam pengiriman data web form!');
        $table->addCell(3500)->addText('Kriteria penilaian: GET mengirim parameter via URL/query string (tidak cocok data sensitif), POST via request body (lebih aman, kapasitas data lebih besar).');
        $table->addCell(1200)->addText('25', [], ['alignment' => Jc::CENTER]);

        // Sample Row 2
        $table->addRow();
        $table->addCell(800)->addText('2', [], ['alignment' => Jc::CENTER]);
        $table->addCell(4500)->addText('Sebutkan dan jelaskan 3 pilar utama dalam keamanan informasi (CIA Triad)!');
        $table->addCell(3500)->addText('1. Confidentiality (Kerahasiaan), 2. Integrity (Integritas data), 3. Availability (Ketersediaan layanan).');
        $table->addCell(1200)->addText('25', [], ['alignment' => Jc::CENTER]);
    }

    /**
     * Parse uploaded Word (.docx) file.
     *
     * @param  UploadedFile|string  $file
     * @param  string  $type  ('multiple_choice' or 'essay')
     * @return array
     */
    public function parseDocx($file, string $type = 'multiple_choice'): array
    {
        $filePath = is_string($file) ? $file : $file->getRealPath();

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return [
                'success' => false,
                'type' => $type,
                'message' => 'Gagal membuka berkas dokumen Word. Pastikan berkas berformat .docx yang valid.',
                'questions' => [],
                'errors' => ['Berkas bukan arsip Word (.docx) yang valid.'],
            ];
        }

        $xmlIndex = $zip->locateName('word/document.xml');
        if ($xmlIndex === false) {
            $zip->close();
            return [
                'success' => false,
                'type' => $type,
                'message' => 'Format internal berkas Word tidak valid (word/document.xml tidak ditemukan).',
                'questions' => [],
                'errors' => ['Struktur dokumen .docx rusak.'],
            ];
        }

        $xmlContent = $zip->getFromIndex($xmlIndex);
        $zip->close();

        $dom = new DOMDocument();
        // Suppress warnings for custom Word XML entities
        libxml_use_internal_errors(true);
        $dom->loadXML($xmlContent);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Check for tables in document
        $tables = $xpath->query('//w:tbl');
        if ($tables->length > 0) {
            return $this->parseFromTables($xpath, $tables, $type);
        }

        // Fallback: parse from paragraphs
        return $this->parseFromParagraphs($xpath, $type);
    }

    /**
     * Extract questions from tables in .docx
     */
    protected function parseFromTables(DOMXPath $xpath, $tables, string $type): array
    {
        $questions = [];
        $errors = [];
        $questionIndex = 1;

        foreach ($tables as $tbl) {
            $rows = $xpath->query('.//w:tr', $tbl);
            if ($rows->length < 2) {
                continue; // Skip single row table or empty header
            }

            // Read header row to determine column mapping
            $headerRow = $rows->item(0);
            $headerCells = $xpath->query('.//w:tc', $headerRow);
            $colMap = [];

            for ($i = 0; $i < $headerCells->length; $i++) {
                $text = strtolower(trim($this->extractCellText($xpath, $headerCells->item($i))));
                if (str_contains($text, 'no')) {
                    $colMap['no'] = $i;
                } elseif (str_contains($text, 'pertanyaan') || str_contains($text, 'soal')) {
                    $colMap['question'] = $i;
                } elseif (str_contains($text, 'pilihan a') || $text === 'a') {
                    $colMap['opt_a'] = $i;
                } elseif (str_contains($text, 'pilihan b') || $text === 'b') {
                    $colMap['opt_b'] = $i;
                } elseif (str_contains($text, 'pilihan c') || $text === 'c') {
                    $colMap['opt_c'] = $i;
                } elseif (str_contains($text, 'pilihan d') || $text === 'd') {
                    $colMap['opt_d'] = $i;
                } elseif (str_contains($text, 'pilihan e') || $text === 'e') {
                    $colMap['opt_e'] = $i;
                } elseif (str_contains($text, 'kunci')) {
                    $colMap['key'] = $i;
                } elseif (str_contains($text, 'pembahasan') || str_contains($text, 'pedoman')) {
                    $colMap['explanation'] = $i;
                } elseif (str_contains($text, 'poin') || str_contains($text, 'bobot')) {
                    $colMap['points'] = $i;
                }
            }

            // Iterate over data rows
            for ($r = 1; $r < $rows->length; $r++) {
                $row = $rows->item($r);
                $cells = $xpath->query('.//w:tc', $row);
                if ($cells->length < 2) continue;

                $rowValues = [];
                for ($c = 0; $c < $cells->length; $c++) {
                    $rowValues[$c] = trim($this->extractCellText($xpath, $cells->item($c)));
                }

                if ($type === 'multiple_choice') {
                    $qText = $this->getVal($rowValues, $colMap['question'] ?? 1);
                    if (empty($qText)) continue; // ignore blank row

                    $optA = $this->getVal($rowValues, $colMap['opt_a'] ?? 2);
                    $optB = $this->getVal($rowValues, $colMap['opt_b'] ?? 3);
                    $optC = $this->getVal($rowValues, $colMap['opt_c'] ?? 4);
                    $optD = $this->getVal($rowValues, $colMap['opt_d'] ?? 5);
                    $optE = $this->getVal($rowValues, $colMap['opt_e'] ?? 6);
                    $keyRaw = strtoupper($this->getVal($rowValues, $colMap['key'] ?? 7));
                    $expl = $this->getVal($rowValues, $colMap['explanation'] ?? 8);
                    $pointsRaw = $this->getVal($rowValues, $colMap['points'] ?? 9);
                    $points = is_numeric($pointsRaw) ? (int)$pointsRaw : 10;

                    // Clean key
                    preg_match('/[A-E]/', $keyRaw, $mKey);
                    $correctAnswer = $mKey[0] ?? '';

                    $rowErrors = [];
                    if (empty($optA) || empty($optB)) {
                        $rowErrors[] = "Soal #{$questionIndex}: Pilihan jawaban minimal A dan B harus diisi.";
                    }
                    if (empty($correctAnswer)) {
                        $rowErrors[] = "Soal #{$questionIndex}: Kunci jawaban tidak valid (harus salah satu dari A, B, C, D, atau E).";
                    }

                    $options = ['A' => $optA, 'B' => $optB];
                    if (!empty($optC)) $options['C'] = $optC;
                    if (!empty($optD)) $options['D'] = $optD;
                    if (!empty($optE)) $options['E'] = $optE;

                    // Check if key exists in options
                    if (!empty($correctAnswer) && !isset($options[$correctAnswer])) {
                        $rowErrors[] = "Soal #{$questionIndex}: Kunci jawaban {$correctAnswer} tidak memiliki opsi pilihan yang sesuai.";
                    }

                    $questions[] = [
                        'id' => 'q_' . $questionIndex,
                        'number' => $questionIndex,
                        'type' => 'multiple_choice',
                        'question_text' => $qText,
                        'options' => $options,
                        'correct_answer' => $correctAnswer,
                        'explanation' => $expl,
                        'points' => $points > 0 ? $points : 10,
                        'has_error' => !empty($rowErrors),
                        'error_message' => implode(' ', $rowErrors),
                    ];

                    if (!empty($rowErrors)) {
                        $errors = array_merge($errors, $rowErrors);
                    }
                    $questionIndex++;
                } else {
                    // Essay
                    $qText = $this->getVal($rowValues, $colMap['question'] ?? 1);
                    if (empty($qText)) continue;

                    $pedoman = $this->getVal($rowValues, $colMap['explanation'] ?? 2);
                    $pointsRaw = $this->getVal($rowValues, $colMap['points'] ?? 3);
                    $points = is_numeric($pointsRaw) ? (int)$pointsRaw : 20;

                    $rowErrors = [];
                    if ($points <= 0) {
                        $rowErrors[] = "Soal #{$questionIndex}: Bobot nilai harus lebih dari 0.";
                    }

                    $questions[] = [
                        'id' => 'q_' . $questionIndex,
                        'number' => $questionIndex,
                        'type' => 'essay',
                        'question_text' => $qText,
                        'explanation' => $pedoman,
                        'points' => $points > 0 ? $points : 20,
                        'has_error' => !empty($rowErrors),
                        'error_message' => implode(' ', $rowErrors),
                    ];

                    if (!empty($rowErrors)) {
                        $errors = array_merge($errors, $rowErrors);
                    }
                    $questionIndex++;
                }
            }
        }

        if (empty($questions)) {
            return [
                'success' => false,
                'type' => $type,
                'message' => 'Tidak ditemukan baris soal pada dokumen. Pastikan Anda mengisi soal sesuai format template tabel.',
                'questions' => [],
                'errors' => ['Tabel dokumen tidak memiliki baris data soal yang terbaca.'],
            ];
        }

        return [
            'success' => true,
            'type' => $type,
            'message' => 'Dokumen berhasil diproses. Ditemukan ' . count($questions) . ' soal.',
            'questions' => $questions,
            'errors' => $errors,
        ];
    }

    /**
     * Fallback paragraph-based parser if no tables found
     */
    protected function parseFromParagraphs(DOMXPath $xpath, string $type): array
    {
        $paragraphs = $xpath->query('//w:p');
        $lines = [];

        foreach ($paragraphs as $p) {
            $text = trim($this->extractCellText($xpath, $p));
            if ($text !== '') {
                $lines[] = $text;
            }
        }

        if (empty($lines)) {
            return [
                'success' => false,
                'type' => $type,
                'message' => 'Dokumen Word kosong atau tidak dapat dibaca.',
                'questions' => [],
                'errors' => ['Dokumen kosong.'],
            ];
        }

        $questions = [];
        $errors = [];
        $currQuestion = null;
        $qIndex = 1;

        if ($type === 'multiple_choice') {
            foreach ($lines as $line) {
                // Check if starts with a number, e.g. "1. " or "1) "
                if (preg_match('/^(\d+)[\.\)]\s*(.+)$/i', $line, $m)) {
                    if ($currQuestion && !empty($currQuestion['question_text'])) {
                        $questions[] = $this->finalizeParsedMcq($currQuestion, $qIndex++, $errors);
                    }
                    $currQuestion = [
                        'question_text' => trim($m[2]),
                        'options' => [],
                        'correct_answer' => '',
                        'explanation' => '',
                        'points' => 10,
                    ];
                    continue;
                }

                if (!$currQuestion) continue;

                // Check option: e.g. "A. option" or "A) option"
                if (preg_match('/^([A-E])[\.\)]\s*(.+)$/i', $line, $mOpt)) {
                    $optKey = strtoupper($mOpt[1]);
                    $currQuestion['options'][$optKey] = trim($mOpt[2]);
                    continue;
                }

                // Check key: e.g. "KUNCI: A" or "Jawaban: B"
                if (preg_match('/(?:kunci|jawaban|answer)\s*:\s*([A-E])/i', $line, $mKey)) {
                    $currQuestion['correct_answer'] = strtoupper($mKey[1]);
                    continue;
                }

                // Check explanation: e.g. "PEMBAHASAN: ..."
                if (preg_match('/(?:pembahasan|penjelasan|explanation)\s*:\s*(.+)$/i', $line, $mExp)) {
                    $currQuestion['explanation'] = trim($mExp[1]);
                    continue;
                }

                // Check points: e.g. "POIN: 10"
                if (preg_match('/(?:poin|bobot|points)\s*:\s*(\d+)/i', $line, $mPts)) {
                    $currQuestion['points'] = (int)$mPts[1];
                    continue;
                }

                // Continuation of question text if no options yet
                if (empty($currQuestion['options'])) {
                    $currQuestion['question_text'] .= "\n" . $line;
                }
            }

            if ($currQuestion && !empty($currQuestion['question_text'])) {
                $questions[] = $this->finalizeParsedMcq($currQuestion, $qIndex++, $errors);
            }
        } else {
            // Essay
            foreach ($lines as $line) {
                if (preg_match('/^(\d+)[\.\)]\s*(.+)$/i', $line, $m)) {
                    if ($currQuestion && !empty($currQuestion['question_text'])) {
                        $questions[] = $this->finalizeParsedEssay($currQuestion, $qIndex++, $errors);
                    }
                    $currQuestion = [
                        'question_text' => trim($m[2]),
                        'explanation' => '',
                        'points' => 20,
                    ];
                    continue;
                }

                if (!$currQuestion) continue;

                if (preg_match('/(?:pedoman|kunci|jawaban)\s*:\s*(.+)$/i', $line, $mPed)) {
                    $currQuestion['explanation'] = trim($mPed[1]);
                    continue;
                }

                if (preg_match('/(?:poin|bobot|nilai)\s*:\s*(\d+)/i', $line, $mPts)) {
                    $currQuestion['points'] = (int)$mPts[1];
                    continue;
                }

                $currQuestion['question_text'] .= "\n" . $line;
            }

            if ($currQuestion && !empty($currQuestion['question_text'])) {
                $questions[] = $this->finalizeParsedEssay($currQuestion, $qIndex++, $errors);
            }
        }

        return [
            'success' => !empty($questions),
            'type' => $type,
            'message' => !empty($questions) ? 'Dokumen berhasil diproses (' . count($questions) . ' soal).' : 'Format dokumen teks tidak cocok.',
            'questions' => $questions,
            'errors' => $errors,
        ];
    }

    protected function finalizeParsedMcq(array $q, int $number, array &$errors): array
    {
        $rowErrors = [];
        if (count($q['options']) < 2) {
            $rowErrors[] = "Soal #{$number}: Pilihan jawaban kurang dari 2.";
        }
        if (empty($q['correct_answer']) || !isset($q['options'][$q['correct_answer']])) {
            $rowErrors[] = "Soal #{$number}: Kunci jawaban tidak valid.";
        }

        if (!empty($rowErrors)) {
            $errors = array_merge($errors, $rowErrors);
        }

        return [
            'id' => 'q_' . $number,
            'number' => $number,
            'type' => 'multiple_choice',
            'question_text' => trim($q['question_text']),
            'options' => $q['options'],
            'correct_answer' => $q['correct_answer'],
            'explanation' => trim($q['explanation'] ?? ''),
            'points' => ($q['points'] ?? 0) > 0 ? (int)$q['points'] : 10,
            'has_error' => !empty($rowErrors),
            'error_message' => implode(' ', $rowErrors),
        ];
    }

    protected function finalizeParsedEssay(array $q, int $number, array &$errors): array
    {
        $rowErrors = [];
        if (empty($q['question_text'])) {
            $rowErrors[] = "Soal #{$number}: Pertanyaan essay kosong.";
        }

        if (!empty($rowErrors)) {
            $errors = array_merge($errors, $rowErrors);
        }

        return [
            'id' => 'q_' . $number,
            'number' => $number,
            'type' => 'essay',
            'question_text' => trim($q['question_text']),
            'explanation' => trim($q['explanation'] ?? ''),
            'points' => ($q['points'] ?? 0) > 0 ? (int)$q['points'] : 20,
            'has_error' => !empty($rowErrors),
            'error_message' => implode(' ', $rowErrors),
        ];
    }

    /**
     * Helper to extract all text runs inside a node
     */
    protected function extractCellText(DOMXPath $xpath, $node): string
    {
        $textNodes = $xpath->query('.//w:t', $node);
        $result = '';
        foreach ($textNodes as $t) {
            $result .= $t->nodeValue;
        }
        return trim($result);
    }

    protected function getVal(array $row, int $index, string $default = ''): string
    {
        return isset($row[$index]) ? trim($row[$index]) : $default;
    }
}

