<?php

namespace App\Services;

use Illuminate\Http\Request;

class ExamLockdownService
{
    public function verify(Request $request, array $policy): void
    {
        if (! ($policy['require_seb'] ?? false)) {
            return;
        }
        $provided = strtolower((string) $request->header('X-SafeExamBrowser-ConfigKeyHash'));
        $valid = false;
        foreach (config('exam.seb_config_keys', []) as $key) {
            if (preg_match('/^[a-f0-9]{64}$/i', trim($key))) {
                $valid = $valid || hash_equals(hash('sha256', $request->fullUrl().strtolower(trim($key))), $provided);
            }
        }
        abort_unless($valid, 403, 'Ujian ini memerlukan Safe Exam Browser dengan konfigurasi sekolah yang disetujui.');
    }
}
