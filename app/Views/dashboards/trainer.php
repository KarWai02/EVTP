<section class="mt-6">
  <?php Auth::requireRole(['trainer']); $pdo = DB::conn(); $tid = Auth::user()['id']; ?>
  <h2>Welcome, <?=e($user['name'] ?? 'Trainer')?> 👋</h2>

  <div class="card" style="padding:16px; margin:12px 0;">
    <div class="flex-between" style="margin-bottom:8px;">
      <h3 style="margin:0;">Quick actions</h3>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
      <a class="btn" href="<?= app_url('trainer/courses') ?>">Manage Materials</a>
      <a class="btn" href="<?= app_url('trainer/workshops') ?>">Workshops</a>
      <a class="btn btn-outline" href="<?= app_url('profile') ?>">Profile</a>
    </div>
  </div>

  <?php
    // Upcoming workshops within 7 days
    try{
      $stmt = $pdo->prepare("SELECT workshopID, workshopTitle, workshopTopic, dateTime, duration, platformLink FROM Workshop WHERE trainerID=? AND dateTime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) ORDER BY dateTime ASC LIMIT 10");
      $stmt->execute([$tid]); $upcoming = $stmt->fetchAll();
    }catch(Throwable $e){
      // duration column may not exist in your schema; fallback without it
      $stmt = $pdo->prepare("SELECT workshopID, workshopTitle, workshopTopic, dateTime, platformLink FROM Workshop WHERE trainerID=? AND dateTime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY) ORDER BY dateTime ASC LIMIT 10");
      $stmt->execute([$tid]); $tmp = $stmt->fetchAll();
      $upcoming = array_map(function($r){ $r['duration']=0; return $r; }, $tmp);
    }
    // Count soon (next 24h)
    $soon = 0; try{
      $c = $pdo->prepare("SELECT COUNT(*) FROM Workshop WHERE trainerID=? AND dateTime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)");
      $c->execute([$tid]); $soon = (int)$c->fetchColumn();
    }catch(Throwable $e){ $soon=0; }
  ?>

  <div class="card" style="padding:16px;">
    <div class="flex-between" style="margin-bottom:8px;">
      <h3 style="margin:0;">Upcoming sessions</h3>
      <a class="btn btn-outline btn-sm" href="<?= app_url('trainer/workshops') ?>">View all</a>
    </div>
    <?php if($soon>0): ?>
      <div class="alert" style="margin-bottom:10px;">
        You have <strong><?= $soon ?></strong> workshop(s) starting within 24 hours.
      </div>
    <?php endif; ?>
    <?php if(!empty($upcoming)): ?>
      <div class="card" style="padding:0; overflow:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Title</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">When</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Duration</th>
              <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Link</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($upcoming as $w): ?>
              <tr>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><?= e($w['workshopTitle']) ?></strong><br><small class="muted"><?= e($w['workshopTopic'] ?? '') ?></small></td>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($w['dateTime']) ?></td>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><?= (int)($w['duration'] ?? 0) ?>m</td>
                <td style="padding:10px;border-bottom:1px solid var(--border)"><a class="btn btn-outline btn-sm" href="<?= e($w['platformLink']) ?>" target="_blank" rel="noopener">Join</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="muted" style="margin:0;">No sessions scheduled in the next 7 days.</p>
    <?php endif; ?>
  </div>
</section>

