<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('exam:maintain {--prune : Delete monitoring evidence older than retention period}', function () {
    \App\Models\ExamSession::whereNull('submitted_at')->whereNotNull('expires_at')->where('expires_at','<',now())->chunkById(100,function($sessions){foreach($sessions as $session) app(\App\Services\ExamSubmissionService::class)->finish($session,'timeout');});
    if($this->option('prune')) {
        $days=max(1,config('exam.retention_days'));
        $count=\App\Models\ExamLog::where('created_at','<',now()->subDays($days))->delete();
        \Illuminate\Support\Facades\DB::table('exam_client_instances')->where('last_seen_at','<',now()->subDays($days))->delete();
        $this->info("Evidence removed: {$count}");
    }
    $this->info('Exam maintenance completed.');
});
\Illuminate\Support\Facades\Schedule::command('exam:maintain')->everyMinute()->withoutOverlapping();
\Illuminate\Support\Facades\Schedule::command('exam:maintain --prune')->daily()->withoutOverlapping();
