<section class="container py-6">
  <?php Auth::requireRole(['admin','trainer']); ?>
  <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin:0 0 10px;">
    <h1 style="margin:0;">Modules · <?= e($course['courseTitle']) ?></h1>
    <a class="btn btn-outline" target="_blank" rel="noopener" href="<?= app_url('courses/view') ?>?id=<?= e($course['courseID']) ?>">View Course</a>
  </div>
  <?php if(!empty($_SESSION['flash'])): ?><div class="alert success" style="margin-bottom:10px;"><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></div><?php endif; ?>
  <?php if(!empty($errors)): ?><div class="alert error" style="margin-bottom:10px;">Please fix the errors below.</div><?php endif; ?>
  <p class="muted" style="margin:0 0 12px;">Add modules and specify minutes and content counts. You can list video/task titles (one per line) to help learners.</p>

  <?php
    // Compute total minutes like course page: prefer videoList minute sums when available; else use estimatedDuration
    $__totalMins = 0;
    foreach(($modules ?? []) as $__m){
      if(!empty($__m['videoList'])){
        foreach((array)$__m['videoList'] as $__vv){ $__totalMins += (int)($__vv['mins'] ?? 0); }
      } else {
        $__totalMins += (int)($__m['estimatedDuration'] ?? 0);
      }
    }
    $__hours = (int)floor($__totalMins/60); $__mins = (int)($__totalMins%60);
  ?>
  <div class="card" style="padding:8px 12px; margin-bottom:12px; display:flex; gap:8px; align-items:center;">
    <strong>Current total length:</strong> <span><?= (int)$__hours ?>h <?= (int)$__mins ?>m</span>
  </div>

  <div class="card" style="padding:12px; margin-bottom:14px;">
    <form method="post" action="<?= app_url('admin/courses/modules/add') ?>" style="display:grid; grid-template-columns:repeat(6,1fr); gap:10px; align-items:end;">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="courseID" value="<?= e($course['courseID']) ?>">

      <label style="grid-column: span 2;">Module title
        <input type="text" name="content" value="<?= e($old['content'] ?? '') ?>" required>
        <?php if(!empty(($errors['content'] ?? ''))): ?><small class="error"><?= e($errors['content']) ?></small><?php endif; ?>
      </label>
      <label>Minutes
        <input type="number" name="estimatedDuration" min="0" value="<?= (int)($old['estimatedDuration'] ?? 0) ?>">
      </label>
      <label>Videos
        <input type="number" name="videoCount" min="0" value="<?= (int)($old['videoCount'] ?? 0) ?>">
      </label>
      <label>Tasks
        <input type="number" name="taskCount" min="0" value="<?= (int)($old['taskCount'] ?? 0) ?>">
      </label>
      <label>Quiz
        <input type="number" name="quizCount" min="0" value="<?= (int)($old['quizCount'] ?? 0) ?>">
      </label>
      <label style="grid-column: 1 / -1;">Description
        <textarea name="description" rows="3" placeholder="Optional module description"><?= e($old['description'] ?? '') ?></textarea>
      </label>
      <label style="grid-column: 1 / 4;">Video titles (one per line)
        <textarea name="videoTitles" rows="4" placeholder="e.g. Lesson 1: Intro&#10;Lesson 2: Deep Dive"></textarea>
      </label>
      <label style="grid-column: 4 / -1;">Task titles (one per line)
        <textarea name="taskTitles" rows="4" placeholder="e.g. Case Study&#10;Worksheet"></textarea>
      </label>
      <label style="grid-column: 1 / 4;">Video URLs (one per line)
        <textarea name="videoUrls" rows="3" placeholder="https://...\nhttps://..." pattern="https?://.*"></textarea>
        <small class="muted">Order should match video titles one-by-one. Use full https URLs.</small>
      </label>
      <label style="grid-column: 4 / -1;">Quiz URL
        <input type="url" name="quizUrl" placeholder="https://forms..." pattern="https?://.*">
      </label>
      <div style="grid-column: 1 / -1; display:flex; gap:8px;">
        <button class="btn" type="submit">Add module</button>
        <a class="btn btn-outline" href="<?= app_url('admin/courses') ?>">Back to courses</a>
      </div>
    </form>
  </div>

  <?php if(!empty($modules)): ?>
    <div class="card" style="padding:0; overflow:auto;">
      <table id="modules-table" style="width:100%; border-collapse:collapse;">
        <thead>
          <tr>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Order</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Title</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Minutes</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Videos</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Tasks</th>
            <th style="text-align:left;padding:10px;border-bottom:1px solid var(--border)">Quiz</th>
            <th style="text-align:right;padding:10px;border-bottom:1px solid var(--border)">Actions</th>
          </tr>
        </thead>
        <tbody id="modules-body">
          <?php foreach($modules as $m): ?>
            <tr draggable="true" data-id="<?= e($m['moduleID']) ?>">
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= e($m['moduleOrder'] ?? '') ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><strong><?= e($m['content']) ?></strong><div class="muted" style="max-width:560px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= e(mb_strimwidth($m['description'] ?? '', 0, 120, '…')) ?></div></td>
              <?php
                $___mins = 0;
                if(!empty($m['videoList'])){ foreach((array)$m['videoList'] as $vv){ $___mins += (int)($vv['mins'] ?? 0); } }
                else { $___mins = (int)($m['estimatedDuration'] ?? 0); }
              ?>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= (int)$___mins ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= (int)($m['videoCount'] ?? 0) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= (int)($m['taskCount'] ?? 0) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border)"><?= (int)($m['quizCount'] ?? 0) ?></td>
              <td style="padding:10px;border-bottom:1px solid var(--border); text-align:right;">
                <details>
                  <summary class="btn btn-outline btn-sm">Edit</summary>
                  <form method="post" action="<?= app_url('admin/courses/modules/update') ?>" style="margin-top:8px; display:grid; grid-template-columns:repeat(6,1fr); gap:8px; align-items:end;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="moduleID" value="<?= e($m['moduleID']) ?>">
                    <input type="hidden" name="courseID" value="<?= e($course['courseID']) ?>">
                    <label style="grid-column: span 2;">Title
                      <input type="text" name="content" value="<?= e($m['content']) ?>" required>
                    </label>
                    <label>Minutes
                      <input type="number" name="estimatedDuration" min="0" value="<?= (int)($m['estimatedDuration'] ?? 0) ?>">
                    </label>
                    <label>Videos
                      <input type="number" name="videoCount" min="0" value="<?= (int)($m['videoCount'] ?? 0) ?>">
                    </label>
                    <label>Tasks
                      <input type="number" name="taskCount" min="0" value="<?= (int)($m['taskCount'] ?? 0) ?>">
                    </label>
                    <label>Quiz
                      <input type="number" name="quizCount" min="0" value="<?= (int)($m['quizCount'] ?? 0) ?>">
                    </label>
                    <label>Order
                      <input type="number" name="moduleOrder" value="<?= (int)($m['moduleOrder'] ?? 0) ?>">
                    </label>
                    <label style="grid-column: 1 / -1;">Description
                      <textarea name="description" rows="3"><?= e($m['description'] ?? '') ?></textarea>
                    </label>
                    <label style="grid-column: 1 / 4;">Video titles (one per line)
                      <textarea name="videoTitles" rows="3"></textarea>
                    </label>
                    <label style="grid-column: 4 / -1;">Task titles (one per line)
                      <textarea name="taskTitles" rows="3"></textarea>
                    </label>
                    <label style="grid-column: 1 / 4;">Video URLs (one per line)
                      <textarea name="videoUrls" rows="3" placeholder="https://...\nhttps://..." pattern="https?://.*"></textarea>
                    </label>
                    <label style="grid-column: 4 / -1;">Quiz URL
                      <input type="url" name="quizUrl" placeholder="https://forms..." pattern="https?://.*">
                    </label>
                    <div style="grid-column: 1 / -1; display:flex; gap:8px;">
                      <button class="btn btn-sm" type="submit">Save</button>
                      <form method="post" action="<?= app_url('admin/courses/modules/delete') ?>" onsubmit="return confirm('Delete this module?');" style="display:inline;">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="moduleID" value="<?= e($m['moduleID']) ?>">
                        <input type="hidden" name="courseID" value="<?= e($course['courseID']) ?>">
                        <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                      </form>
                    </div>
                  </form>
                </details>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <script>
      (function(){
        const tbody = document.getElementById('modules-body');
        if(!tbody) return;
        let dragEl = null;
        tbody.addEventListener('dragstart', e=>{ const tr=e.target.closest('tr'); if(tr){ dragEl=tr; e.dataTransfer.effectAllowed='move'; tr.classList.add('dragging'); }});
        tbody.addEventListener('dragend', e=>{ const tr=e.target.closest('tr'); if(tr){ tr.classList.remove('dragging'); }});
        tbody.addEventListener('dragover', e=>{ e.preventDefault(); const tr=e.target.closest('tr'); if(!tr||tr===dragEl) return; const rect=tr.getBoundingClientRect(); const before=e.clientY < rect.top + rect.height/2; tbody.insertBefore(dragEl, before? tr : tr.nextSibling); });
        tbody.addEventListener('drop', ()=>{
          const ids=[...tbody.querySelectorAll('tr')].map(tr=>tr.dataset.id);
          fetch('<?= app_url('admin/courses/modules/reorder') ?>',{
            method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:new URLSearchParams({csrf:'<?= csrf_token() ?>', courseID:'<?= e($course['courseID']) ?>', ...ids.reduce((a,id,i)=>{a['order['+i+']']=id;return a;}, {})})
          }).then(()=>{ /* no-op */ });
        });
      })();
    </script>
  <?php else: ?>
    <p class="muted">No modules yet.</p>
  <?php endif; ?>
</section>
