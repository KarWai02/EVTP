<?php
class NotificationsController {
  private function ensureAuth(){ if(!Auth::check()) redirect('login'); }

  private function userKey(){ $u=Auth::user(); return [$u['role'] ?? '', $u['id'] ?? null]; }

  public function index(){
    $this->ensureAuth(); [$role,$uid] = $this->userKey();
    $rows=[]; try{
      $pdo = DB::conn();
      $st = $pdo->prepare("SELECT id, title, body, url, created_at, read_at FROM notifications WHERE role=? AND user_id=? ORDER BY created_at DESC");
      $st->execute([$role, $uid]); $rows = $st->fetchAll();
    }catch(Throwable $e){ $rows=[]; }
    // merge file-based fallback for admins
    if(($role ?? '')==='admin'){
      try{
        $path = __DIR__.'/../../storage/notifications.json';
        $list = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        if(is_array($list)){
          foreach(array_reverse($list) as $n){
            if(($n['role'] ?? '')!=='admin') continue;
            $target = $n['user_id'] ?? null;
            if($target!==null && $target!=$uid) continue; // specific admin only
            $rows[] = [
              'id' => $n['id'],
              'title' => $n['title'] ?? '(no title)',
              'body' => $n['body'] ?? '',
              'url' => $n['url'] ?? '',
              'created_at' => $n['created_at'] ?? '',
              'read_at' => $n['read_at'] ?? null,
            ];
          }
        }
      }catch(Throwable $e){ }
    }
    // Enrich legacy community report bodies with thread title/snippet if possible
    try{
      $pdo = DB::conn();
      foreach($rows as &$n){
        if(stripos($n['title'] ?? '', 'report') !== false && stripos($n['body'] ?? '', 'ID=') !== false){
          if(preg_match('/ID=([A-Z]+\d+)/', (string)$n['body'], $m)){
            $id = $m[1];
            // try message first
            $pretty = '';
            try{
              $q=$pdo->prepare("SELECT m.msgContent, p.forumTitle FROM Message m JOIN ForumPost p ON p.postID=m.postID WHERE m.msgID=?");
              $q->execute([$id]); $row=$q->fetch();
              if($row){ $pretty = 'Reply on "'.($row['forumTitle'] ?? '').'": "'.mb_strimwidth(strip_tags((string)($row['msgContent'] ?? '')),0,80,'…').'"'; }
            }catch(\Throwable $e){}
            if($pretty===''){
              try{ $q=$pdo->prepare("SELECT forumTitle FROM ForumPost WHERE postID=?"); $q->execute([$id]); $t=$q->fetch()['forumTitle'] ?? null; if($t){ $pretty = 'Thread: '.$t; } }catch(\Throwable $e){}
            }
            if($pretty!==''){ $n['body'] = $pretty; }
            if(empty($n['title'])){ $n['title'] = '[Community] Report'; }
          }
        }
        // Normalize created_at to ensure proper sorting
        if(empty($n['created_at'])){ $n['created_at'] = date('Y-m-d H:i:s'); }
      }
      unset($n);
    }catch(Throwable $e){}

    // Sort by created_at DESC after merge/enrichment
    usort($rows, function($a,$b){
      $ta = strtotime($a['created_at'] ?? '') ?: 0;
      $tb = strtotime($b['created_at'] ?? '') ?: 0;
      return $tb <=> $ta;
    });

    return render('notifications/index', ['rows'=>$rows]);
  }

  public function read(){
    $this->ensureAuth(); [$role,$uid] = $this->userKey();
    $id = trim($_POST['id'] ?? '');
    try{
      $pdo = DB::conn();
      $st = $pdo->prepare("UPDATE notifications SET read_at=NOW() WHERE id=? AND role=? AND user_id=?");
      $st->execute([$id, $role, $uid]);
    }catch(Throwable $e){}
    // also mark file-based fallback as read if matches
    if(str_starts_with($id, 'file:')){
      try{
        $path = __DIR__.'/../../storage/notifications.json';
        $list = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        if(is_array($list)){
          $changed=false; foreach($list as &$n){ if(($n['id'] ?? '')===$id){ $n['read_at']=date('Y-m-d H:i:s'); $changed=true; break; } } unset($n);
          if($changed) file_put_contents($path, json_encode($list));
        }
      }catch(Throwable $e){ }
    }
    return redirect('notifications');
  }

  public function readAll(){
    $this->ensureAuth(); [$role,$uid] = $this->userKey();
    try{
      $pdo = DB::conn();
      $st = $pdo->prepare("UPDATE notifications SET read_at=NOW() WHERE role=? AND user_id=? AND read_at IS NULL");
      $st->execute([$role, $uid]);
    }catch(Throwable $e){}
    // file-based
    if(($role ?? '')==='admin'){
      try{
        $path = __DIR__.'/../../storage/notifications.json';
        $list = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        if(is_array($list)){
          $changed=false; foreach($list as &$n){ if(($n['role'] ?? '')!=='admin') continue; $target=$n['user_id'] ?? null; if($target!==null && $target!=$uid) continue; if(empty($n['read_at'])){ $n['read_at']=date('Y-m-d H:i:s'); $changed=true; } } unset($n);
          if($changed) file_put_contents($path, json_encode($list));
        }
      }catch(Throwable $e){}
    }
    return redirect('notifications');
  }
}
