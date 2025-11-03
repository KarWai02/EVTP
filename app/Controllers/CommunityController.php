<?php
class CommunityController {
  private function moderationPath(){ return __DIR__.'/../../storage/forum_moderation.json'; }
  private function loadModeration(){
    $path = $this->moderationPath();
    if(!file_exists($path)) return ['hidden'=>[],'hiddenMsg'=>[],'locked'=>[],'pinned'=>[],'reports'=>[]];
    $data = json_decode(file_get_contents($path), true); if(!is_array($data)) $data=[];
    $data += ['hidden'=>[],'hiddenMsg'=>[],'locked'=>[],'pinned'=>[],'reports'=>[]];
    return $data;
  }
  private function saveModeration($data){ file_put_contents($this->moderationPath(), json_encode($data)); }

  public function index(){
    $pdo = DB::conn();
    $q   = trim($_GET['q'] ?? '');
    $grp = trim($_GET['group'] ?? ''); // pseudo-group via title prefix like [General]
    $mod = $this->loadModeration(); $hidden = $mod['hidden'] ?? []; $pinned = $mod['pinned'] ?? [];
    $showAll = (Auth::check() && (Auth::user()['role']??'')==='admin' && (($_GET['show'] ?? '')==='all'));

    $sql = "SELECT p.postID, p.learnerID, p.name, p.email, p.forumTitle, p.postContent, p.timestamp,
                   (SELECT COUNT(*) FROM Message m WHERE m.postID=p.postID) AS replies
            FROM ForumPost p";
    $conds = []; $params = [];
    if($q!==''){ $conds[] = "(p.forumTitle LIKE ? OR p.postContent LIKE ?)"; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
    if($grp!==''){ $conds[] = "p.forumTitle LIKE ?"; $params[]='[%'.$grp.'%]%'; }
    if(!empty($conds)) $sql .= ' WHERE '.implode(' AND ',$conds);
    $sql .= ' ORDER BY p.timestamp DESC LIMIT 100';
    $rows = $pdo->prepare($sql); $rows->execute($params); $posts = $rows->fetchAll();

    // filter hidden unless admin requested show all
    if(!$showAll){
      $posts = array_values(array_filter($posts, function($r) use ($hidden){ return !in_array($r['postID'], $hidden, true); }));
    }
    // sort by pinned first
    usort($posts, function($a,$b) use($pinned){
      $ap = in_array($a['postID'],$pinned,true); $bp = in_array($b['postID'],$pinned,true);
      if($ap && !$bp) return -1; if(!$ap && $bp) return 1; return strcmp($b['timestamp'],$a['timestamp']);
    });

    return render('community/index', ['posts'=>$posts,'q'=>$q,'group'=>$grp,'pinned'=>$pinned,'hiddenIds'=>$hidden,'showAll'=>$showAll]);
  }

  public function thread(){
    $pdo = DB::conn(); $id = $_GET['id'] ?? '';
    if($id===''){ http_response_code(404); echo 'Thread not found'; return; }
    $mod = $this->loadModeration(); $hidden=$mod['hidden']??[]; $locked=$mod['locked']??[]; $pinned=$mod['pinned']??[];

    $s = $pdo->prepare("SELECT * FROM ForumPost WHERE postID=?"); $s->execute([$id]); $post = $s->fetch();
    if(!$post || in_array($id,$hidden,true)){
      $_SESSION['flash'] = 'Thread not available';
      $dest = app_url('community');
      header('Location: '.$dest); exit;
    }

    $m = $pdo->prepare("SELECT * FROM Message WHERE postID=? ORDER BY msgTimestamp ASC"); $m->execute([$id]); $msgs = $m->fetchAll();
    $hiddenMsg = $mod['hiddenMsg'] ?? [];
    $showAll = (Auth::check() && (Auth::user()['role']??'')==='admin' && (($_GET['show'] ?? '')==='all'));
    if(!$showAll){ $msgs = array_values(array_filter($msgs, function($x) use($hiddenMsg){ return !in_array($x['msgID'], $hiddenMsg, true); })); }
    return render('community/thread', ['post'=>$post,'msgs'=>$msgs,'locked'=>in_array($id,$locked,true),'pinned'=>in_array($id,$pinned,true),'showAll'=>$showAll,'hiddenMsgIds'=>$hiddenMsg]);
  }

  public function create(){
    if($_SERVER['REQUEST_METHOD']==='GET'){
      return render('community/create', ['errors'=>$_SESSION['errors']??[], 'old'=>$_SESSION['old']??[]]);
    }
    csrf_verify();
    $pdo = DB::conn();
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body'] ?? '');
    $group = trim($_POST['group'] ?? '');
    $guestName  = trim($_POST['name'] ?? '');
    $guestEmail = trim($_POST['email'] ?? '');

    if($group!==''){ $title = '['.$group.'] '.$title; }

    // attach image (optional)
    if(!empty($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])){
      $imgUrl = $this->saveImage($_FILES['image']); if($imgUrl){ $body .= "\n\n".$imgUrl; }
    }

    $errors=[]; if($title===''){ $errors['title']='Title is required'; } if($body===''){ $errors['body']='Content is required'; }
    if($errors){ $_SESSION['errors']=$errors; $_SESSION['old']=$_POST; return redirect('community/create'); }

    $id = gen_id($pdo, 'ForumPost', 'postID', 'POST');
    $learnerID = null; if(Auth::check()){ $user=Auth::user(); if(($user['role']??'')==='learner'){ $learnerID=$user['id']; } }
    $ins = $pdo->prepare("INSERT INTO ForumPost (postID, learnerID, name, email, forumTitle, postContent, timestamp, isPublic) VALUES (?,?,?,?,?,?,?,?)");
    $ins->execute([$id,$learnerID,$guestName?:($user['name']??'Anonymous'??'Anonymous'),$guestEmail,$title,$body,date('Y-m-d H:i:s'),1]);
    return redirect('community/thread?id='.$id);
  }

  public function reply(){
    csrf_verify(); $pdo = DB::conn(); $post = $_POST['post'] ?? '';
    if($post===''){ return redirect('community'); }
    // block if locked
    $mod=$this->loadModeration(); if(in_array($post, $mod['locked'] ?? [], true)){ $_SESSION['flash']='Thread is locked'; return redirect('community/thread?id='.$post); }

    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $body = trim($_POST['body'] ?? '');
    if(!empty($_FILES['image']) && is_uploaded_file($_FILES['image']['tmp_name'])){
      $imgUrl = $this->saveImage($_FILES['image']); if($imgUrl){ $body .= "\n\n".$imgUrl; }
    }
    if($body===''){ $_SESSION['errors']=['reply'=>'Message cannot be empty']; return redirect('community/thread?id='.$post); }

    try{
      $id = gen_id($pdo, 'Message', 'msgID', 'MSG');
      $ins = $pdo->prepare("INSERT INTO Message (msgID, postID, msgName, msgEmail, msgContent, msgTimestamp) VALUES (?,?,?,?,?,?)");
      $ins->execute([$id,$post,($name!==''?$name:'Anonymous'),$email,$body,date('Y-m-d H:i:s')]);
      $back = app_url('community/thread').'?id='.urlencode($post).'#msg-'.$id;
      header('Location: '.$back); exit;
    }catch(\Throwable $e){
      $_SESSION['flash'] = 'Failed to post reply';
      $back = app_url('community/thread').'?id='.urlencode($post);
      header('Location: '.$back); exit;
    }
  }

  public function report(){
    // Accept report from guests; do not enforce CSRF strictly to avoid 403 for public users
    $postType = ($_POST['type'] ?? $_GET['type'] ?? 'post');
    $id = ($_POST['id'] ?? $_GET['id'] ?? '');
    $reason = trim((string)($_POST['reason'] ?? $_GET['reason'] ?? '')) ?: 'inappropriate';
    $mod = $this->loadModeration();
    $reporter = Auth::check()? (Auth::user()['id'].'/'.(Auth::user()['role']??'')) : 'guest';
    $mod['reports'][] = ['rid'=>bin2hex(random_bytes(6)),'type'=>$postType,'id'=>$id,'reason'=>$reason,'at'=>date('Y-m-d H:i:s'),'by'=>$reporter,'status'=>'open'];
    // Auto-hide after 3 distinct reporters
    $distinct = [];
    foreach(($mod['reports'] ?? []) as $r){ if(($r['type']??'')===$postType && ($r['id']??'')===$id){ $distinct[$r['by'] ?? 'guest']=true; }}
    if(count($distinct) >= 3){
      if($postType==='thread'){
        if(!in_array($id, $mod['hidden'] ?? [], true)) $mod['hidden'][] = $id;
      } else { // message
        if(!in_array($id, $mod['hiddenMsg'] ?? [], true)) $mod['hiddenMsg'][] = $id;
      }
    }
    // Resolve context for better notification message
    $threadTitle = '';
    $replySnippet = '';
    try{
      if($postType==='thread'){
        $q=$pdo->prepare("SELECT forumTitle FROM ForumPost WHERE postID=?"); $q->execute([$id]); $threadTitle = (string)($q->fetch()['forumTitle'] ?? '');
      } else { // message
        $q=$pdo->prepare("SELECT m.msgContent, p.forumTitle FROM Message m JOIN ForumPost p ON p.postID=m.postID WHERE m.msgID=?"); $q->execute([$id]); $row=$q->fetch();
        $threadTitle = (string)($row['forumTitle'] ?? '');
        $replySnippet = mb_strimwidth(strip_tags((string)($row['msgContent'] ?? '')), 0, 80, '…');
      }
    }catch(\Throwable $e){}

    // Notify all admins
    try{
      $pdo = DB::conn();
      $admins=[]; try{ $rs=$pdo->query("SELECT adminID FROM Admin"); $admins = $rs ? $rs->fetchAll() : []; }catch(\Throwable $e){ $admins=[]; }
      if(!empty($admins)){
        $title = ($postType==='thread'?'[Community] Thread reported':'[Community] Reply reported');
        $link  = app_url('community/reports');
        if($postType==='thread'){
          $body = 'Thread: '.($threadTitle ?: $id).' · Reason: '.$reason;
        } else {
          $body = 'Reply on "'.($threadTitle ?: '').'": "'.$replySnippet.'" · Reason: '.$reason;
        }
        $ins = $pdo->prepare("INSERT INTO notifications (role, user_id, title, body, url, created_at) VALUES (?,?,?,?,?,NOW())");
        foreach($admins as $a){ $aid = $a['adminID'] ?? null; if($aid){ $ins->execute(['admin',$aid,$title,$body,$link]); } }
      }
    }catch(\Throwable $e){ /* notifications table or admin list may not exist; ignore */ }

    // File-based fallback notifications
    try{
      $title = ($postType==='thread'?'[Community] Thread reported':'[Community] Reply reported');
      $link  = app_url('community/reports');
      if($postType==='thread'){
        $body = 'Thread: '.($threadTitle ?: $id).' · Reason: '.$reason;
      } else {
        $body = 'Reply on "'.($threadTitle ?: '').'": "'.$replySnippet.'" · Reason: '.$reason;
      }
      $path = __DIR__.'/../../storage/notifications.json';
      $list = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
      if(!is_array($list)) $list = [];
      // If we couldn't read admins from DB, still notify a generic admin pool (user_id null)
      $targets = [];
      try{
        $pdo = DB::conn();
        $admins=[]; try{ $rs=$pdo->query("SELECT adminID FROM Admin"); $admins = $rs ? $rs->fetchAll() : []; }catch(\Throwable $e){ $admins=[]; }
        foreach($admins as $a){ $aid = $a['adminID'] ?? null; if($aid) $targets[] = $aid; }
        if(empty($targets)) $targets[] = null; // generic
      }catch(\Throwable $e){ $targets[] = null; }
      foreach($targets as $aid){
        $list[] = [
          'id' => 'file:'.bin2hex(random_bytes(6)),
          'role' => 'admin',
          'user_id' => $aid,
          'title' => $title,
          'body' => $body,
          'url' => $link,
          'created_at' => date('Y-m-d H:i:s'),
          'read_at' => null,
        ];
      }
      file_put_contents($path, json_encode($list));
    }catch(\Throwable $e){ /* ignore */ }

    $this->saveModeration($mod); $_SESSION['flash']='Reported';
    $back = $_SERVER['HTTP_REFERER'] ?? app_url('community');
    if(stripos($back, 'http') !== 0){ $back = app_url(ltrim($back,'/')); }
    header('Location: '.$back); exit;
  }

  // Admin moderation
  private function ensureAdmin(){ Auth::requireRole(['admin']); }
  public function mod(){
    $this->ensureAdmin();
    $act = $_REQUEST['action'] ?? ($_REQUEST['act'] ?? '');
    $id = $_REQUEST['id'] ?? ($_REQUEST['i'] ?? '');
    $type = $_REQUEST['type'] ?? ($_REQUEST['t'] ?? 'thread');
    // Map short codes to actions to avoid blocked keywords in URLs
    $map = ['p'=>'pin','up'=>'unpin','l'=>'lock','ul'=>'unlock','h'=>'hide','uh'=>'unhide','d'=>'delete','del'=>'delete'];
    if(isset($map[$act])) $act = $map[$act];
    $mod = $this->loadModeration(); foreach(['hidden','hiddenMsg','locked','pinned'] as $k){ if(!isset($mod[$k])||!is_array($mod[$k])) $mod[$k]=[]; }
    $toggle = function(&$arr,$id,$on){ $arr = array_values($on ? array_unique(array_merge($arr,[$id])) : array_filter($arr, fn($x)=>$x!==$id)); };
    if($type==='thread'){
      if($act==='hide'){ $toggle($mod['hidden'],$id,true); }
      elseif($act==='unhide'){ $toggle($mod['hidden'],$id,false); }
      elseif($act==='lock'){ $toggle($mod['locked'],$id,true); }
      elseif($act==='unlock'){ $toggle($mod['locked'],$id,false); }
      elseif($act==='pin'){ $toggle($mod['pinned'],$id,true); }
      elseif($act==='unpin'){ $toggle($mod['pinned'],$id,false); }
      elseif($act==='delete'){
        $pdo = DB::conn(); $d1=$pdo->prepare("DELETE FROM Message WHERE postID=?"); $d1->execute([$id]); $d2=$pdo->prepare("DELETE FROM ForumPost WHERE postID=?"); $d2->execute([$id]);
      }
    } else { // message
      if($act==='hide'){ $toggle($mod['hiddenMsg'],$id,true); }
      elseif($act==='unhide'){ $toggle($mod['hiddenMsg'],$id,false); }
      elseif($act==='delete'){ $pdo=DB::conn(); $d=$pdo->prepare("DELETE FROM Message WHERE msgID=?"); $d->execute([$id]); }
    }
    $this->saveModeration($mod); $_SESSION['flash']='Updated';
    $back = $_SERVER['HTTP_REFERER'] ?? app_url('community');
    if(stripos($back, 'http') !== 0){ $back = app_url(ltrim($back,'/')); }
    header('Location: '.$back); exit;
  }

  private function saveImage($file){
    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if(!in_array($ext,['png','jpg','jpeg'])) return null;
    if(($file['size'] ?? 0) > 2*1024*1024) return null;
    $dir = dirname(__DIR__,2).'/public/uploads/forum'; if(!is_dir($dir)) @mkdir($dir,0777,true);
    $name = 'img_'.date('Ymd_His').'_'.substr(md5(uniqid('',true)),0,6).'.'.$ext;
    $dest = $dir.'/'.$name; if(@move_uploaded_file($file['tmp_name'],$dest)) return app_url('uploads/forum/'.$name);
    return null;
  }

  // --- Admin: Reports Review ---
  public function reports(){
    $this->ensureAdmin();
    $mod = $this->loadModeration();
    // backfill missing rids/status for older entries
    $changed=false; foreach($mod['reports'] as &$r){ if(empty($r['rid'])){ $r['rid']=bin2hex(random_bytes(6)); $changed=true; } if(empty($r['status'])){ $r['status']='open'; $changed=true; } }
    unset($r); if($changed) $this->saveModeration($mod);
    // enrich with titles, links, and friendly reporter names when possible
    $pdo = DB::conn();
    $list = [];
    foreach(array_reverse($mod['reports']) as $r){ // newest first
      $title=''; $snippet=''; if(($r['type'] ?? '')==='thread'){
        try{ $q=$pdo->prepare("SELECT forumTitle FROM ForumPost WHERE postID=?"); $q->execute([$r['id']??'']); $title=(string)($q->fetch()['forumTitle'] ?? ''); }catch(\Throwable $e){}
        $r['thread_link'] = app_url('community/thread').'?id='.urlencode($r['id'] ?? '');
      } else if(($r['type'] ?? '')==='message'){
        try{ $q=$pdo->prepare("SELECT m.msgContent, m.postID, p.forumTitle FROM Message m JOIN ForumPost p ON p.postID=m.postID WHERE m.msgID=?"); $q->execute([$r['id']??'']); $row=$q->fetch(); $snippet = mb_strimwidth((string)($row['msgContent'] ?? ''),0,80,'…'); $title = ($row['forumTitle'] ?? ''); $r['thread_link'] = app_url('community/thread').'?id='.urlencode($row['postID'] ?? ''); }catch(\Throwable $e){}
      }
      $r['snippet']=$snippet;
      // friendly reporter name
      $by = trim((string)($r['by'] ?? 'guest'));
      $prettyBy = 'guest';
      if($by !== 'guest' && strpos($by,'/') !== false){
        [$rid,$role] = explode('/', $by, 2);
        try{
          if($role==='learner'){ $q=$pdo->prepare("SELECT learnerName n FROM Learners WHERE learnerID=?"); $q->execute([$rid]); $nm=$q->fetch()['n'] ?? null; if($nm) $prettyBy = $nm.' (Learner)'; }
          elseif($role==='admin'){ $q=$pdo->prepare("SELECT adminName n FROM Admin WHERE adminID=?"); $q->execute([$rid]); $nm=$q->fetch()['n'] ?? null; if($nm) $prettyBy = $nm.' (Admin)'; }
          elseif($role==='employer'){ $q=$pdo->prepare("SELECT contactPerson n FROM Employers WHERE employerID=?"); $q->execute([$rid]); $nm=$q->fetch()['n'] ?? null; if($nm) $prettyBy = $nm.' (Employer)'; }
          elseif($role==='trainer'){ $q=$pdo->prepare("SELECT trainerName n FROM Trainers WHERE trainerID=?"); $q->execute([$rid]); $nm=$q->fetch()['n'] ?? null; if($nm) $prettyBy = $nm.' (Trainer)'; }
          else { $prettyBy = $by; }
        }catch(\Throwable $e){ $prettyBy = $by; }
      }
      $r['prettyBy']=$prettyBy;
      $r['title']=$title; $list[]=$r;
    }
    return render('community/reports', ['items'=>$list]);
  }

  public function resolveReport(){
    $this->ensureAdmin(); csrf_verify();
    $rid = $_POST['rid'] ?? ''; $action = $_POST['action'] ?? 'reviewed';
    $mod = $this->loadModeration();
    foreach($mod['reports'] as &$r){ if(($r['rid'] ?? '')===$rid){ $r['status'] = ($action==='dismiss'?'dismissed':'reviewed'); $r['resolved_at']=date('Y-m-d H:i:s'); break; }} unset($r);
    $this->saveModeration($mod); $_SESSION['flash']='Report updated';
    return redirect('community/reports');
  }

  // Nightly auto-clean (can be triggered manually): remove reports whose targets no longer exist
  public function cleanReports(){
    $this->ensureAdmin();
    $pdo = DB::conn(); $mod = $this->loadModeration();
    $reports = $mod['reports'] ?? []; $kept = [];
    $hidden = $mod['hidden'] ?? []; $hiddenMsg = $mod['hiddenMsg'] ?? [];
    $locked = $mod['locked'] ?? []; $pinned = $mod['pinned'] ?? [];

    foreach($reports as $r){
      $type = $r['type'] ?? ''; $id = $r['id'] ?? '';
      $exists = false;
      try{
        if($type==='thread'){
          $q=$pdo->prepare("SELECT 1 FROM ForumPost WHERE postID=?"); $q->execute([$id]); $exists = (bool)$q->fetchColumn();
          if(!$exists){
            // prune thread ids from lists
            $hidden = array_values(array_filter($hidden, fn($x)=>$x!==$id));
            $locked = array_values(array_filter($locked, fn($x)=>$x!==$id));
            $pinned = array_values(array_filter($pinned, fn($x)=>$x!==$id));
          }
        } else if($type==='message'){
          $q=$pdo->prepare("SELECT 1 FROM Message WHERE msgID=?"); $q->execute([$id]); $exists = (bool)$q->fetchColumn();
          if(!$exists){ $hiddenMsg = array_values(array_filter($hiddenMsg, fn($x)=>$x!==$id)); }
        }
      }catch(\Throwable $e){ $exists=false; }
      if($exists){ $kept[] = $r; }
    }

    $mod['reports'] = $kept;
    $mod['hidden'] = $hidden; $mod['hiddenMsg'] = $hiddenMsg; $mod['locked'] = $locked; $mod['pinned'] = $pinned; $mod['lastCleanup']=date('Y-m-d H:i:s');
    $this->saveModeration($mod); $_SESSION['flash']='Cleanup done';
    return redirect('community/reports');
  }
}
