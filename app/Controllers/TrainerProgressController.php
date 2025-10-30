<?php
class TrainerProgressController {
  private function ensureTrainer(){ Auth::requireRole(['trainer']); }
  private function trainerId(){ return Auth::user()['id']; }

  private function trainerCourses(PDO $pdo, $tid){
    try{
      $s = $pdo->prepare("SELECT courseID, courseTitle FROM Course WHERE trainerID=? ORDER BY courseTitle");
      $s->execute([$tid]); return $s->fetchAll();
    }catch(Throwable $e){ return []; }
  }

  public function index(){
    $this->ensureTrainer();
    $pdo = DB::conn(); $tid = $this->trainerId();
    $courses = $this->trainerCourses($pdo, $tid);
    $courseID = $_GET['course'] ?? ($courses[0]['courseID'] ?? null);
    $status = $_GET['status'] ?? ''; // '', Completed, In Progress
    $sort   = $_GET['sort'] ?? 'name'; // name|progress|date

    $kpis = ['enrolled'=>0,'completed'=>0,'avg_progress'=>0.0,'in_progress'=>0];
    $rows = [];
    $trend = [];
    $hist = [0,0,0,0,0]; // 0-20,20-40,40-60,60-80,80-100
    if($courseID){
      // KPIs
      $q1 = $pdo->prepare("SELECT COUNT(*) FROM Enroll WHERE courseID=?");
      $q1->execute([$courseID]); $kpis['enrolled'] = (int)$q1->fetchColumn();
      $q2 = $pdo->prepare("SELECT COUNT(*) FROM Enroll WHERE courseID=? AND completionStatus='Completed'");
      $q2->execute([$courseID]); $kpis['completed'] = (int)$q2->fetchColumn();
      $q3 = $pdo->prepare("SELECT AVG(progress) FROM Enroll WHERE courseID=?");
      $q3->execute([$courseID]); $kpis['avg_progress'] = round((float)$q3->fetchColumn(),1);
      $q4 = $pdo->prepare("SELECT COUNT(*) FROM Enroll WHERE courseID=? AND (completionStatus IS NULL OR completionStatus='' OR completionStatus='In Progress')");
      $q4->execute([$courseID]); $kpis['in_progress'] = (int)$q4->fetchColumn();

      // Table
      $where = "WHERE e.courseID=?"; $params = [$courseID];
      if($status==='Completed'){ $where .= " AND e.completionStatus='Completed'"; }
      elseif($status==='In Progress'){ $where .= " AND (e.completionStatus IS NULL OR e.completionStatus='' OR e.completionStatus='In Progress')"; }
      $order = "l.learnerName ASC";
      if($sort==='progress') $order = "e.progress DESC, l.learnerName ASC";
      elseif($sort==='date') $order = "e.enrollDate DESC, l.learnerName ASC";
      $sql = "SELECT e.learnerID, l.learnerName, l.learnerEmail, e.progress, e.completionStatus, e.enrollDate
              FROM Enroll e JOIN Learners l ON l.learnerID=e.learnerID
              $where ORDER BY $order";
      $list = $pdo->prepare($sql); $list->execute($params); $rows = $list->fetchAll();

      // Trend: enrollments per day last 14 days
      try{
        $t = $pdo->prepare("SELECT DATE(enrollDate) d, COUNT(*) c FROM Enroll WHERE courseID=? AND enrollDate >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY DATE(enrollDate) ORDER BY d");
        $t->execute([$courseID]); $data = $t->fetchAll();
        $map=[]; foreach($data as $r){ $map[$r['d']] = (int)$r['c']; }
        for($i=13;$i>=0;$i--){ $day = date('Y-m-d', strtotime('-'.$i.' day')); $trend[] = ['d'=>$day,'c'=>($map[$day] ?? 0)]; }
      }catch(Throwable $e){ $trend=[]; }

      // Histogram: bucketize progress
      try{
        $h = $pdo->prepare("SELECT progress FROM Enroll WHERE courseID=?");
        $h->execute([$courseID]);
        foreach($h->fetchAll() as $r){
          $p = max(0,min(100,(float)$r['progress']));
          $idx = (int)floor($p/20.000001); if($idx>4) $idx=4; $hist[$idx]++;
        }
      }catch(Throwable $e){ $hist=[0,0,0,0,0]; }
    }

    return render('trainer/progress/index', compact('courses','courseID','kpis','rows','status','sort','trend','hist'));
  }

  // Per-learner detail within a course
  public function learner(){
    $this->ensureTrainer();
    $pdo = DB::conn(); $tid = $this->trainerId();
    $courseID = $_GET['course'] ?? '';
    $learnerID = $_GET['learner'] ?? '';
    if($courseID==='' || $learnerID===''){ http_response_code(400); echo 'course and learner required'; return; }
    // authorize course ownership
    $chk = $pdo->prepare("SELECT 1 FROM Course WHERE courseID=? AND trainerID=?");
    $chk->execute([$courseID,$tid]); if(!$chk->fetch()){ http_response_code(403); echo 'Forbidden'; return; }

    // Enrollment row
    $q = $pdo->prepare("SELECT e.*, l.learnerName, l.learnerEmail FROM Enroll e JOIN Learners l ON l.learnerID=e.learnerID WHERE e.courseID=? AND e.learnerID=?");
    $q->execute([$courseID,$learnerID]); $enroll = $q->fetch(); if(!$enroll){ http_response_code(404); echo 'Not found'; return; }

    // Other course history for this learner
    $histRows = [];
    try{
      $h = $pdo->prepare("SELECT c.courseTitle, e.courseID, e.progress, e.completionStatus, e.enrollDate FROM Enroll e JOIN Course c ON c.courseID=e.courseID WHERE e.learnerID=? ORDER BY e.enrollDate DESC");
      $h->execute([$learnerID]); $histRows = $h->fetchAll();
    }catch(Throwable $e){ $histRows=[]; }

    // Last activity approximation: latest enrollDate among enrollments
    $lastActivity = null; try{ $lastActivity = $histRows[0]['enrollDate'] ?? $enroll['enrollDate'] ?? null; }catch(Throwable $e){ $lastActivity = $enroll['enrollDate'] ?? null; }

    return render('trainer/progress/learner', [
      'courseID'=>$courseID,
      'learnerID'=>$learnerID,
      'enroll'=>$enroll,
      'history'=>$histRows,
      'lastActivity'=>$lastActivity,
    ]);
  }

  public function export(){
    $this->ensureTrainer();
    $pdo = DB::conn(); $tid = $this->trainerId();
    $courseID = $_POST['course'] ?? '';
    if($courseID===''){ $_SESSION['flash'] = 'Select a course to export'; return redirect('trainer/progress'); }
    // verify course belongs to trainer
    $chk = $pdo->prepare("SELECT 1 FROM Course WHERE courseID=? AND trainerID=?");
    $chk->execute([$courseID,$tid]); if(!$chk->fetch()){ http_response_code(403); echo 'Forbidden'; return; }

    $list = $pdo->prepare("SELECT l.learnerName, l.learnerEmail, e.progress, e.completionStatus, e.enrollDate
                            FROM Enroll e JOIN Learners l ON l.learnerID=e.learnerID
                            WHERE e.courseID=? ORDER BY l.learnerName ASC");
    $list->execute([$courseID]); $rows = $list->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="progress_'.preg_replace('/[^A-Za-z0-9_-]/','',$courseID).'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['Learner Name','Email','Progress %','Status','Enroll Date']);
    foreach($rows as $r){ fputcsv($out, [$r['learnerName'],$r['learnerEmail'],(float)$r['progress'],$r['completionStatus'] ?: 'In Progress',$r['enrollDate']]); }
    fclose($out); exit;
  }
}
