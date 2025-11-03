<?php
class EmployerCandidatesController {
  private function ensureEmployer(){ Auth::requireRole(['employer']); }
  private function eid(){ return Auth::user()['id']; }

  public function index(){
    $this->ensureEmployer();
    $pdo = DB::conn();
    $eid = $this->eid();

    // Filters
    if(isset($_GET['clear']) && $_GET['clear']==='1'){ unset($_SESSION['cand_filters']); }
    $f = $_SESSION['cand_filters'] ?? [];
    $job    = trim($_GET['job'] ?? ($f['job'] ?? ''));
    $status = trim($_GET['status'] ?? ($f['status'] ?? ''));
    $q      = trim($_GET['q'] ?? ($f['q'] ?? ''));
    $from   = trim($_GET['from'] ?? ($f['from'] ?? ''));
    $to     = trim($_GET['to']   ?? ($f['to']   ?? ''));
    $loc    = trim($_GET['location'] ?? ($f['location'] ?? ''));
    $short  = trim($_GET['shortlist'] ?? ($f['shortlist'] ?? ''));
    $sort   = trim($_GET['sort'] ?? ($f['sort'] ?? 'applied_newest'));
    $page   = max(1, (int)($_GET['p'] ?? ($f['p'] ?? 1)));
    $per    = min(50, max(10, (int)($_GET['per'] ?? ($f['per'] ?? 20))));
    // persist (use explicit keys to avoid undefined var names)
    $_SESSION['cand_filters'] = [
      'job'=>$job,
      'status'=>$status,
      'q'=>$q,
      'from'=>$from,
      'to'=>$to,
      'location'=>$loc,
      'shortlist'=>$short,
      'sort'=>$sort,
      'p'=>$page,
      'per'=>$per,
    ];

    // Employer jobs for dropdown
    $jobs = [];
    try{
      $st = $pdo->prepare("SELECT jobID, jobTitle FROM JobPosting WHERE employerID=? ORDER BY createdDate DESC, jobTitle ASC");
      $st->execute([$eid]);
      $jobs = $st->fetchAll();
    }catch(Throwable $e){ $jobs = []; }

    // Base query: all applications for employer
    $sql = "SELECT a.appID, a.appStatus, a.applicationDate,
                   j.jobID, j.jobTitle,
                   l.*
            FROM Application a
            JOIN JobPosting j ON j.jobID=a.jobID
            JOIN Learners l   ON l.learnerID=a.learnerID
            WHERE j.employerID=?";
    $params = [$eid];
    if($job!==''){ $sql .= " AND j.jobID=?"; $params[] = $job; }
    if($from!==''){ $sql .= " AND a.applicationDate >= ?"; $params[] = date('Y-m-d', strtotime($from)); }
    if($to  !==''){ $sql .= " AND a.applicationDate <= ?"; $params[] = date('Y-m-d', strtotime($to)); }
    if($status!==''){ $sql .= " AND a.appStatus=?"; $params[] = $status; }
    if($q!==''){ $sql .= " AND (l.learnerName LIKE ? OR l.learnerEmail LIKE ?)"; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
    // Do not limit in SQL to allow accurate post-filters; cap to 1000 for safety
    $sql .= " ORDER BY a.applicationDate DESC LIMIT 1000";
    $stm = $pdo->prepare($sql); $stm->execute($params); $rows = $stm->fetchAll();

    // Shortlist filter using JSON store
    $shortlist = $this->readShortlist($eid);
    if($short==='yes'){
      $rows = array_values(array_filter($rows, function($r) use($shortlist){ return !empty($shortlist[$r['learnerID']]); }));
    }    if($short==='no'){
      $rows = array_values(array_filter($rows, function($r) use($shortlist){ return empty($shortlist[$r['learnerID']]); }));
    }

    // Location post-filter (tolerant to column names)
    if($loc!== ''){
      $needle = mb_strtolower($loc);
      $rows = array_values(array_filter($rows, function($r) use($needle){
        foreach($r as $k=>$v){
          if(!is_string($k)) continue;
          $lk = mb_strtolower($k);
          if(strpos($lk,'city')!==false || strpos($lk,'location')!==false || strpos($lk,'address')!==false || strpos($lk,'state')!==false || strpos($lk,'country')!==false){
            if(is_string($v) && strpos(mb_strtolower($v), $needle)!==false) return true;
          }
        }
        return false;
      }));
    }

    // Add a computed location text for display
    foreach($rows as &$rr){
      $locTxt = '';
      foreach($rr as $k=>$v){
        if(!is_string($k)) continue; $lk = strtolower($k);
        if(strpos($lk,'city')!==false || strpos($lk,'location')!==false || strpos($lk,'address')!==false || strpos($lk,'state')!==false || strpos($lk,'country')!==false){
          if(is_string($v) && trim($v)!==''){ $locTxt = $v; break; }
        }
      }
      $rr['__location'] = $locTxt;
    }
    unset($rr);

    // Sorting
    usort($rows, function($a,$b) use($sort){
      switch($sort){
        case 'applied_oldest': return strcmp($a['applicationDate'],$b['applicationDate']);
        case 'name_az': return strcasecmp($a['learnerName'],$b['learnerName']);
        case 'name_za': return strcasecmp($b['learnerName'],$a['learnerName']);
        case 'status': return strcasecmp($a['appStatus'],$b['appStatus']);
        case 'applied_newest': default: return strcmp($b['applicationDate'],$a['applicationDate']);
      }
    });

    // Pagination
    $total = count($rows);
    $pages = max(1, (int)ceil($total / $per));
    if($page > $pages) $page = $pages;
    $offset = ($page-1)*$per;
    $rows = array_slice($rows, $offset, $per);

    // Status counts for tabs (based on same base filters except status)
    $counts = ['Under Review'=>0,'Interview'=>0,'Hired'=>0,'Rejected'=>0];
    try{
      $baseSql = "SELECT a.appStatus, COUNT(*) c
                  FROM Application a JOIN JobPosting j ON j.jobID=a.jobID JOIN Learners l ON l.learnerID=a.learnerID
                  WHERE j.employerID=?";
      $bp = [$eid];
      if($job!==''){ $baseSql .= " AND j.jobID=?"; $bp[]=$job; }
      if($from!==''){ $baseSql .= " AND a.applicationDate >= ?"; $bp[] = date('Y-m-d', strtotime($from)); }
      if($to  !==''){ $baseSql .= " AND a.applicationDate <= ?"; $bp[] = date('Y-m-d', strtotime($to)); }
      if($q   !==''){ $baseSql .= " AND (l.learnerName LIKE ? OR l.learnerEmail LIKE ?)"; $bp[]='%'.$q.'%'; $bp[]='%'.$q.'%'; }
      $baseSql .= " GROUP BY a.appStatus";
      $stc = $pdo->prepare($baseSql); $stc->execute($bp);
      foreach($stc->fetchAll() as $r){ $counts[$r['appStatus']] = (int)$r['c']; }
    }catch(Throwable $e){}

    return render('employer/candidates/index', [
      'jobs'=>$jobs,
      'rows'=>$rows,
      'total'=>$total,
      'page'=>$page,
      'per'=>$per,
      'pages'=>$pages,
      'counts'=>$counts,
      'q'=>$q,
      'job'=>$job,
      'status'=>$status,
      'from'=>$from,
      'to'=>$to,
      'location'=>$loc,
      'shortlistSel'=>$short,
      'sort'=>$sort,
      'shortlist'=>$shortlist,
    ]);
  }

  public function bulkShortlist(){
    $this->ensureEmployer(); csrf_verify();
    $eid = $this->eid();
    $ids = $_POST['ids'] ?? [];
    if(!is_array($ids)) $ids = [];
    $action = $_POST['action'] ?? 'add';
    $list = $this->readShortlist($eid);
    foreach($ids as $lid){ $lid = trim($lid); if($lid==='') continue; if($action==='remove'){ unset($list[$lid]); } else { $list[$lid]=true; } }
    $path = __DIR__.'/../../storage/shortlist_'.$eid.'.json';
    @file_put_contents($path, json_encode($list));
    $_SESSION['flash'] = 'Updated shortlist for '.count($ids).' candidates';
    return redirect('employer/candidates');
  }

  private function readShortlist($eid){
    $path = __DIR__.'/../../storage/shortlist_'.$eid.'.json';
    if(file_exists($path)){
      $data = json_decode(file_get_contents($path), true);
      return is_array($data)? $data : [];
    }
    return [];
  }
}
