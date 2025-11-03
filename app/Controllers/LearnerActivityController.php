<?php
class LearnerActivityController {
  public function index(){
    Auth::requireRole(['learner']);
    $pdo = DB::conn(); $lid = Auth::user()['id'];

    // Tabs: saved | applied (default applied)
    $tab = $_GET['tab'] ?? 'applied';
    if(!in_array($tab, ['saved','applied'], true)) $tab = 'applied';

    // Filters for applied
    $status = $_GET['status'] ?? 'all';
    $from = trim($_GET['from'] ?? '');
    $to = trim($_GET['to'] ?? '');
    $applied = [];
    try{
      $sql = "SELECT a.appID, a.appStatus, a.applicationDate,
                      j.jobID, j.jobTitle, j.location AS locations,
                      e.companyName
               FROM Application a
               JOIN JobPosting j ON j.jobID=a.jobID
               LEFT JOIN Employers e ON e.employerID=j.employerID
               WHERE a.learnerID=?";
      $params = [$lid];
      if($status !== 'all'){ $sql .= " AND a.appStatus=?"; $params[] = $status; }
      if($from !== ''){ $sql .= " AND a.applicationDate >= ?"; $params[] = $from; }
      if($to !== ''){ $sql .= " AND a.applicationDate <= ?"; $params[] = $to; }
      $sql .= " ORDER BY a.applicationDate DESC LIMIT 200";
      $q = $pdo->prepare($sql); $q->execute($params); $applied = $q->fetchAll();
    }catch(Throwable $e){ $applied = []; }

    $saved = [];
    try{
      // Try if savedAt exists
      try{ $pdo->query("SELECT savedAt FROM SavedJobs LIMIT 1"); $hasSavedAt=true; }catch(\Throwable $e){ $hasSavedAt=false; }
      if($hasSavedAt){
        $s = $pdo->prepare("SELECT sj.jobID, j.jobTitle, j.location AS locations, e.companyName, sj.savedAt
                             FROM SavedJobs sj
                             JOIN JobPosting j ON j.jobID=sj.jobID
                             LEFT JOIN Employers e ON e.employerID=j.employerID
                             WHERE sj.learnerID=?
                             ORDER BY sj.savedAt DESC LIMIT 100");
      } else {
        $s = $pdo->prepare("SELECT sj.jobID, j.jobTitle, j.location AS locations, e.companyName
                             FROM SavedJobs sj
                             JOIN JobPosting j ON j.jobID=sj.jobID
                             LEFT JOIN Employers e ON e.employerID=j.employerID
                             WHERE sj.learnerID=?
                             ORDER BY j.jobTitle ASC LIMIT 100");
      }
      $s->execute([$lid]); $saved = $s->fetchAll();
    }catch(Throwable $e){ $saved = []; }

    return render('learner/activity', [
      'tab'=>$tab,
      'applied'=>$applied,
      'saved'=>$saved,
      'status'=>$status,
      'from'=>$from,
      'to'=>$to,
    ]);
  }
}
