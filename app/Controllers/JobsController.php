<?php
class JobsController {
  public function index(){
    $pdo = DB::conn();
    // Filters
    $q = trim($_GET['q'] ?? '');
    $loc = trim($_GET['loc'] ?? '');
    $posted = trim($_GET['posted'] ?? 'any'); // any|24h|7d|30d
    $selected = isset($_GET['id']) ? trim($_GET['id']) : '';
    $savedOnly = isset($_GET['saved']) && $_GET['saved'] === '1';

    // Build query
    $sql = "SELECT j.jobID, j.jobTitle, j.postDate,
                   e.companyName,
                   SUBSTRING(COALESCE(j.jobDesc,''),1,160) AS snippet,
                   j.location AS locations, j.salaryMin, j.salaryMax, j.salary,
                   j.jobType AS jobType, j.deadline AS deadline
            FROM JobPosting j LEFT JOIN Employers e ON e.employerID=j.employerID
            WHERE (j.closedDate IS NULL OR j.closedDate='')";
    $params = [];
    if($q!==''){ $sql .= " AND (j.jobTitle LIKE ? OR e.companyName LIKE ?)"; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
    // Posted filter
    if($posted==='24h'){ $sql .= " AND j.postDate >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)"; }
    elseif($posted==='7d'){ $sql .= " AND j.postDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"; }
    elseif($posted==='30d'){ $sql .= " AND j.postDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"; }

    $sql .= " ORDER BY j.postDate DESC LIMIT 200";
    $stmt = $pdo->prepare($sql); $stmt->execute($params); $jobs = $stmt->fetchAll();

    // Location post-filter tolerantly
    if($loc!==''){
      $needle = mb_strtolower($loc);
      $jobs = array_values(array_filter($jobs, function($r) use($needle){
        foreach($r as $k=>$v){ if(!is_string($k)) continue; $lk = mb_strtolower($k);
          if(strpos($lk,'city')!==false || strpos($lk,'location')!==false || strpos($lk,'address')!==false || strpos($lk,'state')!==false || strpos($lk,'country')!==false){
            if(is_string($v) && strpos(mb_strtolower($v), $needle)!==false) return true; }
        }
        return false;
      }));
    }

    // Saved-only filter will be applied after we load savedIds below

    // Selected job details (default to first job if none selected)
    $detail = null;
    if($selected===''){
      $first = $jobs[0]['jobID'] ?? null;
      if($first!==null){ $selected = (string)$first; }
    }
    if($selected !== ''){
      try{
        $d = $pdo->prepare("SELECT j.*, e.companyName, e.companyIndustry FROM JobPosting j LEFT JOIN Employers e ON e.employerID=j.employerID WHERE j.jobID=?");
        $d->execute([$selected]); $detail = $d->fetch();
        // optional company logo (if column exists) — fetch softly
        try{
          if($detail && isset($detail['employerID'])){
            $lg = $pdo->prepare("SELECT companyLogo FROM Employers WHERE employerID=?");
            $lg->execute([$detail['employerID']]);
            $logoRow = $lg->fetch(); if($logoRow && isset($logoRow['companyLogo'])){ $detail['companyLogo'] = $logoRow['companyLogo']; }
          }
        }catch(\Throwable $e){ /* logo optional */ }
      }catch(Throwable $e){ $detail = null; }
    }

    // Applied jobs and Saved jobs for the current learner (to show badges and disable/apply/save)
    $appliedIds = []; $savedIds = [];
    if(Auth::check() && (Auth::user()['role'] ?? '')==='learner'){
      try{
        $a = $pdo->prepare("SELECT jobID FROM Application WHERE learnerID=?");
        $a->execute([Auth::user()['id']]);
        $appliedIds = array_map('strval', array_column($a->fetchAll(), 'jobID'));
      }catch(Throwable $e){ $appliedIds = []; }
      try{
        $s = $pdo->prepare("SELECT jobID FROM SavedJobs WHERE learnerID=?");
        $s->execute([Auth::user()['id']]);
        $savedIds = array_map('strval', array_column($s->fetchAll(), 'jobID'));
      }catch(Throwable $e){ $savedIds = []; }
    }

    // If saved-only was requested, filter jobs now that we have savedIds
    if($savedOnly){
      $jobs = array_values(array_filter($jobs, function($r) use($savedIds){ return in_array((string)$r['jobID'], $savedIds, true); }));
      // If current selection is not in saved list, reset to first saved job
      if($selected!=='' && !in_array((string)$selected, $savedIds, true)){
        $selected = (string)($jobs[0]['jobID'] ?? $selected);
      }
    }

    return render('jobs/index', [
      'q'=>$q,
      'loc'=>$loc,
      'posted'=>$posted,
      'saved'=>$savedOnly ? '1' : '0',
      'jobs'=>$jobs,
      'detail'=>$detail,
      'selected'=>$selected,
      'appliedIds'=>$appliedIds,
      'savedIds'=>$savedIds,
    ]);
  }

  public function save(){
    Auth::requireRole(['learner']); csrf_verify();
    $pdo = DB::conn(); $uid = Auth::user()['id'];
    $job = trim($_POST['job'] ?? '');
    if($job===''){ $_SESSION['flash'] = 'Invalid job.'; return redirect('jobs'); }
    // toggle save
    try{
      $chk = $pdo->prepare("SELECT 1 FROM SavedJobs WHERE learnerID=? AND jobID=? LIMIT 1");
      $chk->execute([$uid,$job]);
      if($chk->fetch()){
        $del = $pdo->prepare("DELETE FROM SavedJobs WHERE learnerID=? AND jobID=?");
        $del->execute([$uid,$job]);
        $_SESSION['flash'] = 'Removed from saved.';
      } else {
        // Try with savedAt column if exists, otherwise insert without it
        try{
          $pdo->query("SELECT savedAt FROM SavedJobs LIMIT 1");
          $ins = $pdo->prepare("INSERT INTO SavedJobs (learnerID, jobID, savedAt) VALUES (?,?, NOW())");
          $ins->execute([$uid,$job]);
        }catch(\Throwable $e){
          $ins = $pdo->prepare("INSERT INTO SavedJobs (learnerID, jobID) VALUES (?,?)");
          $ins->execute([$uid,$job]);
        }
        $_SESSION['flash'] = 'Saved job.';
      }
    }catch(Throwable $e){ $_SESSION['flash'] = 'Unable to update saved jobs.'; }
    return redirect('jobs?id='.$job.'#detail');
  }

  public function apply(){
    Auth::requireRole(['learner']); csrf_verify();
    $pdo = DB::conn(); $uid = Auth::user()['id'];
    $job = trim($_POST['job'] ?? '');
    if($job===''){ $_SESSION['flash'] = 'Invalid job.'; return redirect('jobs'); }

    // Require resume upload
    if(empty($_FILES['resume']) || !is_uploaded_file($_FILES['resume']['tmp_name'])){
      $_SESSION['flash'] = 'Please upload your resume to apply.';
      return redirect('jobs?id='.$job.'#detail');
    }
    $ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
    if(!in_array($ext,['pdf','doc','docx'])){
      $_SESSION['flash']='Resume must be PDF, DOC, or DOCX.'; return redirect('jobs?id='.$job.'#detail');
    }
    if(($_FILES['resume']['size'] ?? 0) > 5*1024*1024){ // 5MB
      $_SESSION['flash']='Resume file too large (max 5MB).'; return redirect('jobs?id='.$job.'#detail');
    }

    // Prevent duplicate
    $chk = $pdo->prepare("SELECT appID FROM Application WHERE jobID=? AND learnerID=? LIMIT 1");
    $chk->execute([$job,$uid]); if($chk->fetch()){ $_SESSION['flash']='You already applied.'; return redirect('jobs?id='.$job.'#detail'); }

    // Insert application (handle tables without AUTO_INCREMENT primary key)
    $appId = '';
    try{
      $mx = $pdo->query("SELECT MAX(CAST(SUBSTRING(appID,4) AS UNSIGNED)) mx FROM Application")->fetch();
      $next = (int)($mx['mx'] ?? 0) + 1;
      $appId = 'APP'.str_pad((string)$next, 5, '0', STR_PAD_LEFT);
    }catch(Throwable $e){
      try{ $appId = 'APP'.bin2hex(random_bytes(3)); }catch(Throwable $e2){ $appId = 'APP'.time(); }
    }

    // Try inserting with explicit appID first
    try{
      $ins = $pdo->prepare("INSERT INTO Application (appID, jobID, learnerID, appStatus, applicationDate) VALUES (?,?,?, 'Under Review', CURDATE())");
      $ins->execute([$appId,$job,$uid]);
    }catch(Throwable $e){
      // Fallback: table might be AUTO_INCREMENT, insert without appID then fetch lastInsertId
      $ins = $pdo->prepare("INSERT INTO Application (jobID, learnerID, appStatus, applicationDate) VALUES (?,?, 'Under Review', CURDATE())");
      $ins->execute([$job,$uid]);
      $appId = (string)$pdo->lastInsertId();
    }

    // Resume upload (validated above)
    $dir = dirname(__DIR__,2).'/storage/applications/'.$appId;
    if(!is_dir($dir)) @mkdir($dir,0777,true);
    $dest = $dir.'/resume.'.$ext;
    @move_uploaded_file($_FILES['resume']['tmp_name'], $dest);
    // Optional: update DB if resumePath exists
    try{ $pdo->query("SELECT resumePath FROM Application LIMIT 1"); $u=$pdo->prepare("UPDATE Application SET resumePath=? WHERE appID=?"); $u->execute([$dest,$appId]); }catch(Throwable $e){}

    $_SESSION['flash']='Application submitted.';
    return redirect('jobs?id='.$job.'#detail');
  }
}
