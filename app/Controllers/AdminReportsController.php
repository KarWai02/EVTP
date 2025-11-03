<?php
class AdminReportsController {
  private function ensureAdmin(){ Auth::requireRole(['admin']); }

  private function parseRange(){
    $range = $_GET['range'] ?? '30d';
    $from = $_GET['from'] ?? '';
    $to   = $_GET['to']   ?? '';
    $course = $_GET['course'] ?? '';
    if($range==='custom' && $from && $to){
      $fromD = date('Y-m-d', strtotime($from));
      $toD   = date('Y-m-d', strtotime($to));
    } else {
      $days = 30;
      if($range==='7d') $days=7; elseif($range==='90d') $days=90;
      $toD = date('Y-m-d');
      $fromD = date('Y-m-d', strtotime('-'.($days-1).' days'));
    }
    return [$fromD, $toD, $range, $course];
  }

  public function index(){
    $this->ensureAdmin();
    $pdo = DB::conn();
    [$from,$to,$range,$courseId] = $this->parseRange();
    // courses list for filter
    $courses = $pdo->query("SELECT courseID, courseTitle FROM Course ORDER BY courseTitle ASC")->fetchAll();

    // KPIs
    $kpis = [
      'enrolls'=>0,'learners'=>0,'active_courses'=>0,'completion_rate'=>0.0,'dropped'=>0
    ];

    // Enrollments in range
    if($courseId){
      $q1 = $pdo->prepare("SELECT COUNT(*) c FROM Enroll WHERE enrollDate BETWEEN ? AND ? AND courseID=?");
      $q1->execute([$from,$to,$courseId]);
    } else {
      $q1 = $pdo->prepare("SELECT COUNT(*) c FROM Enroll WHERE enrollDate BETWEEN ? AND ?");
      $q1->execute([$from,$to]);
    }
    $kpis['enrolls'] = (int)$q1->fetchColumn();

    // New learners in range
    // If no createdDate, approximate by id ordering; otherwise use learnerCreateDate
    $hasCreate = false;
    try { $pdo->query("SELECT learnerCreateDate FROM Learners LIMIT 1"); $hasCreate=true; } catch(Throwable $e){ $hasCreate=false; }
    if($hasCreate && !$courseId){
      $q2 = $pdo->prepare("SELECT COUNT(*) c FROM Learners WHERE learnerCreateDate BETWEEN ? AND ?");
      $q2->execute([$from,$to]); $kpis['learners'] = (int)($q2->fetchColumn());
    } elseif($courseId){
      // learners who enrolled in the selected course within range
      $q2 = $pdo->prepare("SELECT COUNT(DISTINCT learnerID) c FROM Enroll WHERE enrollDate BETWEEN ? AND ? AND courseID=?");
      $q2->execute([$from,$to,$courseId]); $kpis['learners']=(int)$q2->fetchColumn();
    } else {
      $kpis['learners'] = (int)$pdo->query("SELECT COUNT(*) FROM Learners")->fetchColumn();
    }

    // Active courses in range (courses that received enrollments)
    if($courseId){
      $kpis['active_courses'] = 1; // filtering a specific course
    } else {
      $q3 = $pdo->prepare("SELECT COUNT(DISTINCT courseID) c FROM Enroll WHERE enrollDate BETWEEN ? AND ?");
      $q3->execute([$from,$to]); $kpis['active_courses'] = (int)$q3->fetchColumn();
    }

    // Completion rate in range
    if($courseId){
      $qr = $pdo->prepare("SELECT SUM(completionStatus='Completed') completed, COUNT(*) total FROM Enroll WHERE enrollDate BETWEEN ? AND ? AND courseID=?");
      $qr->execute([$from,$to,$courseId]);
    } else {
      $qr = $pdo->prepare("SELECT SUM(completionStatus='Completed') completed, COUNT(*) total FROM Enroll WHERE enrollDate BETWEEN ? AND ?");
      $qr->execute([$from,$to]);
    }
    $row = $qr->fetch();
    $kpis['completion_rate'] = ($row && (int)$row['total']>0) ? round(100.0*((int)$row['completed'])/((int)$row['total']),1) : 0.0;

    // Dropped count in range
    if($courseId){
      $qd = $pdo->prepare("SELECT SUM(completionStatus='Dropped') c FROM Enroll WHERE enrollDate BETWEEN ? AND ? AND courseID=?");
      $qd->execute([$from,$to,$courseId]);
    } else {
      $qd = $pdo->prepare("SELECT SUM(completionStatus='Dropped') c FROM Enroll WHERE enrollDate BETWEEN ? AND ?");
      $qd->execute([$from,$to]);
    }
    $kpis['dropped'] = (int)($qd->fetchColumn() ?: 0);

    // Time series per day for enrollments and new learners
    $days = (strtotime($to)-strtotime($from))/86400 + 1;
    $tsEnroll = array_fill(0,$days,0);
    $tsLearners = array_fill(0,$days,0);

    if($courseId){
      $qe = $pdo->prepare("SELECT enrollDate d, COUNT(*) c FROM Enroll WHERE enrollDate BETWEEN ? AND ? AND courseID=? GROUP BY d ORDER BY d");
      $qe->execute([$from,$to,$courseId]);
    } else {
      $qe = $pdo->prepare("SELECT enrollDate d, COUNT(*) c FROM Enroll WHERE enrollDate BETWEEN ? AND ? GROUP BY d ORDER BY d");
      $qe->execute([$from,$to]);
    }
    foreach($qe->fetchAll() as $r){ $i = (int)((strtotime($r['d'])-strtotime($from))/86400); if(isset($tsEnroll[$i])) $tsEnroll[$i]=(int)$r['c']; }

    if($hasCreate && !$courseId){
      $ql = $pdo->prepare("SELECT learnerCreateDate d, COUNT(*) c FROM Learners WHERE learnerCreateDate BETWEEN ? AND ? GROUP BY d ORDER BY d");
      $ql->execute([$from,$to]); foreach($ql->fetchAll() as $r){ $i = (int)((strtotime($r['d'])-strtotime($from))/86400); if(isset($tsLearners[$i])) $tsLearners[$i]=(int)$r['c']; }
    }

    // Top courses by enrollments (limit 5)
    $top = $pdo->prepare("SELECT c.courseID, c.courseTitle, COUNT(e.courseID) cnt
                           FROM Enroll e JOIN Course c ON c.courseID=e.courseID
                           WHERE e.enrollDate BETWEEN ? AND ?" . ($courseId?" AND e.courseID=?":"") . "
                           GROUP BY c.courseID, c.courseTitle
                           ORDER BY cnt DESC LIMIT 5");
    $top->execute($courseId?[$from,$to,$courseId]:[$from,$to]); $topCourses = $top->fetchAll();

    // Course performance (full table)
    $perf = $pdo->prepare("SELECT c.courseID, c.courseTitle,
                                  COUNT(e.courseID) AS enrollments,
                                  SUM(e.completionStatus='Completed') AS completed,
                                  SUM(e.completionStatus='In Progress') AS in_progress,
                                  SUM(e.completionStatus='Dropped') AS dropped,
                                  AVG(COALESCE(e.progress,0)) AS avg_progress
                           FROM Enroll e JOIN Course c ON c.courseID=e.courseID
                           WHERE e.enrollDate BETWEEN ? AND ?" . ($courseId?" AND e.courseID=?":"") . "
                           GROUP BY c.courseID, c.courseTitle
                           ORDER BY enrollments DESC");
    $perf->execute($courseId?[$from,$to,$courseId]:[$from,$to]); $performance = $perf->fetchAll();

    // Certificate summary (issued, pending, updated, deleted) in range
    $certSummary = ['issued'=>0,'pending'=>0,'updated'=>0,'deleted'=>0,'total'=>0];
    try{
      if($courseId){
        $qc = $pdo->prepare("SELECT certStatus st, COUNT(*) c FROM certificate WHERE dateIssued BETWEEN ? AND ? AND courseID=? GROUP BY st");
        $qc->execute([$from,$to,$courseId]);
      } else {
        $qc = $pdo->prepare("SELECT certStatus st, COUNT(*) c FROM certificate WHERE dateIssued BETWEEN ? AND ? GROUP BY st");
        $qc->execute([$from,$to]);
      }
      foreach(($qc->fetchAll()?:[]) as $r){ $st=strtolower((string)$r['st']); $n=(int)$r['c']; if(isset($certSummary[$st])) $certSummary[$st]+=$n; $certSummary['total']+=$n; }
    }catch(\Throwable $e){ /* certificate table optional */ }

    return render('admin/reports/index', [
      'from'=>$from,'to'=>$to,'range'=>$range,
      'courseId'=>$courseId,
      'courses'=>$courses,
      'kpis'=>$kpis,
      'tsEnroll'=>$tsEnroll,
      'tsLearners'=>$tsLearners,
      'topCourses'=>$topCourses,
      'performance'=>$performance,
      'certSummary'=>$certSummary,
    ]);
  }

  public function export(){
    $this->ensureAdmin(); csrf_verify();
    $pdo = DB::conn();
    [$from,$to,$range,$courseId] = $this->parseRange();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="top_courses_'.$from.'_to_'.$to.'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['courseID','courseTitle','enrollments']);
    $stmt = $pdo->prepare("SELECT c.courseID, c.courseTitle, COUNT(e.courseID) cnt
                            FROM Enroll e JOIN Course c ON c.courseID=e.courseID
                            WHERE e.enrollDate BETWEEN ? AND ?" . ($courseId?" AND e.courseID=?":"") . "
                            GROUP BY c.courseID, c.courseTitle
                            ORDER BY cnt DESC");
    $stmt->execute($courseId?[$from,$to,$courseId]:[$from,$to]);
    foreach($stmt->fetchAll() as $r){ fputcsv($out, [$r['courseID'],$r['courseTitle'],$r['cnt']]); }
    fclose($out); exit;
  }

  public function exportCertificates(){
    $this->ensureAdmin(); csrf_verify();
    $pdo = DB::conn();
    [$from,$to,$range,$courseId] = $this->parseRange();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="certificate_summary_'.$from.'_to_'.$to.'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['status','count']);
    try{
      if($courseId){
        $qc = $pdo->prepare("SELECT certStatus st, COUNT(*) c FROM certificate WHERE dateIssued BETWEEN ? AND ? AND courseID=? GROUP BY st");
        $qc->execute([$from,$to,$courseId]);
      } else {
        $qc = $pdo->prepare("SELECT certStatus st, COUNT(*) c FROM certificate WHERE dateIssued BETWEEN ? AND ? GROUP BY st");
        $qc->execute([$from,$to]);
      }
      foreach(($qc->fetchAll()?:[]) as $r){ fputcsv($out, [$r['st'], (int)$r['c']]); }
    }catch(\Throwable $e){ /* no data */ }
    fclose($out); exit;
  }

  public function exportPerformance(){
    $this->ensureAdmin(); csrf_verify();
    $pdo = DB::conn();
    [$from,$to,$range,$courseId] = $this->parseRange();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="course_performance_detailed_'.$from.'_to_'.$to.'.csv"');
    $out = fopen('php://output','w');
    fputcsv($out, ['courseID','courseTitle','enrollments','completed','completion_rate_%','avg_progress_%','in_progress','dropped']);
    $stmt = $pdo->prepare("SELECT c.courseID, c.courseTitle,
                                   COUNT(e.courseID) AS enrollments,
                                   SUM(e.completionStatus='Completed') AS completed,
                                   SUM(e.completionStatus='In Progress') AS in_progress,
                                   SUM(e.completionStatus='Dropped') AS dropped,
                                   AVG(COALESCE(e.progress,0)) AS avg_progress
                            FROM Enroll e JOIN Course c ON c.courseID=e.courseID
                            WHERE e.enrollDate BETWEEN ? AND ?" . ($courseId?" AND e.courseID=?":"") . "
                            GROUP BY c.courseID, c.courseTitle
                            ORDER BY enrollments DESC");
    $stmt->execute($courseId?[$from,$to,$courseId]:[$from,$to]);
    foreach($stmt->fetchAll() as $r){
      $en = (int)$r['enrollments']; $comp=(int)$r['completed'];
      $rate = $en>0 ? round(100.0*$comp/$en,1) : 0.0;
      $avgp = round((float)$r['avg_progress'],1);
      fputcsv($out, [$r['courseID'],$r['courseTitle'],$en,$comp,$rate,$avgp,(int)$r['in_progress'],(int)$r['dropped']]);
    }
    fclose($out); exit;
  }
}
