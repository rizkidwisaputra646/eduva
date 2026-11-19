<?php
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{User,Course,Quiz,Question};
$teacher=User::firstOrCreate(['email'=>'monitor-teacher@example.test'],['name'=>'Monitoring Teacher','identity_number'=>'monitor-teacher','password'=>bcrypt('test-password'),'role'=>'guru','must_change_password'=>false]);
$student=User::firstOrCreate(['email'=>'monitor-student@example.test'],['name'=>'Monitoring Student','identity_number'=>'monitor-student','password'=>bcrypt('test-password'),'role'=>'siswa','must_change_password'=>false]);
$course=Course::firstOrCreate(['title'=>'Browser monitoring test'],['description'=>'Isolated browser test fixture','teacher_id'=>$teacher->id,'invitation_code'=>'BROWSER']);$course->students()->syncWithoutDetaching([$student->id]);
$ids=[];foreach(['standard','strict','auto'] as $mode){$q=Quiz::create(['course_id'=>$course->id,'title'=>'Browser exam '.$mode,'type'=>'quiz','duration'=>20,'is_visible'=>true,'detect_tab_switch'=>true]);$p=App\Services\ExamMonitoringService::policy($q);$p['fullscreen']=$mode==='strict';$p['auto_submit']=$mode==='auto';$p['threshold']=$mode==='auto'?1:3;$q->forceFill(['monitoring_policy'=>$p])->save();Question::create(['quiz_id'=>$q->id,'question_text'=>'Explain your answer','type'=>'essay','points'=>100]);$ids[$mode]=$q->id;}
echo json_encode($ids);
