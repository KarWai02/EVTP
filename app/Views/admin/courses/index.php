<section class="container py-6">
  <?php Auth::requireRole(['admin']); ?>
  <h1 style="margin:0 0 10px;">Courses</h1>

  <form method="get" action="<?= app_url('admin/courses') ?>" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;align-items:center;">
    <input type="text" name="q" placeholder="Search title or category" value="<?= e($_GET['q'] ?? '') ?>" style="flex:1;min-width:260px;">
    <label class="muted">Per page
      <select name="pp">
        <?php $ppSel = (int)($perPage ?? 10); foreach([10,25,50] as $pp): ?>
          <option value="<?= $pp ?>" <?= $pp===$ppSel?'selected':'' ?>><?= $pp ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <a class="btn" href="<?= app_url('admin/courses/create') ?>">New Course</a>
    <button class="btn btn-outline" type="submit">Search</button>
  </form>

  <?php if(!empty($rows)): ?>
    <div class="card" style="padding:0; overflow:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Title</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Category</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Created</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($rows as $c): ?>
          <tr>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($c['courseTitle']) ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($c['category'] ?: '—') ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($c['createdDate'] ?: '—') ?></td>
            <td style="padding:10px;border-bottom:1px solid var(--border);text-align:right;">
              <a class="btn btn-outline btn-sm" href="<?= app_url('admin/courses/edit') ?>?id=<?= e($c['courseID']) ?>">Edit</a>
              <form method="post" action="<?= app_url('admin/courses/delete') ?>" style="display:inline;" onsubmit="return confirm('Delete this course?');">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= e($c['courseID']) ?>">
                <button class="btn btn-outline btn-sm" type="submit">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if(($pages ?? 1) > 1): ?>
      <?php
        $current = (int)($page ?? 1); $last = (int)($pages ?? 1);
        $qs = ['q'=>($_GET['q'] ?? ''),'pp'=>(int)($perPage ?? 10)];
        $link = function($p) use ($qs){ $qs['page']=$p; return app_url('admin/courses').'?'.http_build_query($qs); };
      ?>
      <nav class="pagination" aria-label="Pagination" style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
        <span class="muted">Page <?= $current ?> of <?= $last ?> (<?= (int)($total ?? 0) ?>)</span>
        <a class="btn btn-outline btn-sm" href="<?= $link(1) ?>" <?= $current<=1?'aria-disabled="true" tabindex="-1"':'' ?>>« First</a>
        <a class="btn btn-outline btn-sm" href="<?= $link(max(1,$current-1)) ?>" <?= $current<=1?'aria-disabled="true" tabindex="-1"':'' ?>>‹ Prev</a>
        <?php
          $start = max(1, $current - 2); $end = min($last, $current + 2);
          if ($start > 1) echo '<span class="muted">…</span>';
          for($p=$start; $p<=$end; $p++){
            $cls = $p===$current ? 'btn btn-sm' : 'btn btn-outline btn-sm';
            echo '<a class="'.$cls.'" href="'.e($link($p)).'">'.$p.'</a>';
          }
          if ($end < $last) echo '<span class="muted">…</span>';
        ?>
        <a class="btn btn-outline btn-sm" href="<?= $link(min($last,$current+1)) ?>" <?= $current>=$last?'aria-disabled="true" tabindex="-1"':'' ?>>Next ›</a>
        <a class="btn btn-outline btn-sm" href="<?= $link($last) ?>" <?= $current>=$last?'aria-disabled="true" tabindex="-1"':'' ?>>Last »</a>
      </nav>
    <?php endif; ?>
  <?php else: ?>
    <p class="muted">No courses found.</p>
  <?php endif; ?>
</section>
