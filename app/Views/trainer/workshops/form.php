<section class="container py-6">
  <?php Auth::requireRole(['trainer']); $mode=$mode??'create'; $w=$workshop??[]; $errors=$errors??[]; $old=$old??[]; ?>
  <h1 style="margin:0 0 10px;"><?= $mode==='create' ? 'Schedule Workshop' : 'Edit Workshop' ?></h1>

  <form method="post" action="<?= $mode==='create' ? app_url('trainer/workshops') : app_url('trainer/workshops/update') ?>" class="card" style="max-width:820px;padding:16px;">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <?php if($mode==='edit'): ?><input type="hidden" name="id" value="<?= e($w['workshopID'] ?? '') ?>"><?php endif; ?>

    <div style="display:grid;gap:10px;grid-template-columns:1fr 1fr;">
      <label style="grid-column:1 / -1;">Title
        <input type="text" name="workshopTitle" value="<?= e(($old['workshopTitle'] ?? '') ?: ($w['workshopTitle'] ?? '')) ?>" required>
        <?php if(!empty($errors['workshopTitle'])): ?><small class="error"><?= e($errors['workshopTitle']) ?></small><?php endif; ?>
      </label>

      <label>Topic
        <input type="text" name="workshopTopic" value="<?= e(($old['workshopTopic'] ?? '') ?: ($w['workshopTopic'] ?? '')) ?>">
      </label>

      <label>Course (optional)
        <select name="courseID">
          <option value="">-- None --</option>
          <?php foreach(($courses ?? []) as $c): $sel = (($old['courseID'] ?? ($w['courseID'] ?? ''))===$c['courseID']); ?>
            <option value="<?= e($c['courseID']) ?>" <?= $sel ? 'selected' : '' ?>><?= e($c['courseTitle']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <?php
        $__existingDT = ($old['dateTime'] ?? '') ?: ($w['dateTime'] ?? '');
        $__datePart = $__existingDT ? date('Y-m-d', strtotime($__existingDT)) : date('Y-m-d');
        $__timePart = $__existingDT ? date('H:i', strtotime($__existingDT)) : date('H:i');
        $__minDate  = date('Y-m-d');
      ?>
      <label style="grid-column:1 / -1;">Date & Time
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;align-items:end;">
          <span style="display:inline-flex;align-items:center;gap:6px;">
            <input id="ws-date" type="date" name="date" value="<?= e($__datePart) ?>" min="<?= e($__minDate) ?>" required>
            <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('ws-date'); if(el && el.showPicker){ el.showPicker(); } else if(el){ el.focus(); } })()" aria-label="Open calendar">📅</button>
          </span>
          <span style="display:inline-flex;align-items:center;gap:6px;">
            <input id="ws-time" type="time" name="time" value="<?= e($__timePart) ?>" step="300" required>
            <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('ws-time'); if(el && el.showPicker){ el.showPicker(); } else if(el){ el.focus(); } })()" aria-label="Open time picker">⏰</button>
          </span>
          <input type="hidden" id="ws-datetime" name="dateTime" value="<?= e($__existingDT ? date('Y-m-d H:i', strtotime($__existingDT)) : (date('Y-m-d').' '.date('H:i'))) ?>">
        </div>
        <?php if(!empty($errors['dateTime'])): ?><small class="error"><?= e($errors['dateTime']) ?></small><?php endif; ?>
      </label>

      <label>Duration (minutes)
        <input type="number" min="15" step="5" name="duration" value="<?= e(($old['duration'] ?? '') !== '' ? $old['duration'] : ($w['duration'] ?? 60)) ?>">
      </label>

      <label style="grid-column:1 / -1;">Meeting link
        <input type="url" name="platformLink" placeholder="https://meet.google.com/... or https://zoom.us/j/.." value="<?= e(($old['platformLink'] ?? '') ?: ($w['platformLink'] ?? '')) ?>" required>
        <?php if(!empty($errors['platformLink'])): ?><small class="error"><?= e($errors['platformLink']) ?></small><?php endif; ?>
      </label>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px;">
      <button class="btn" type="submit">Save</button>
      <a class="btn btn-outline" href="<?= app_url('trainer/workshops') ?>">Cancel</a>
    </div>
  </form>
</section>
<script>
  (function(){
    var form = document.currentScript && document.currentScript.previousElementSibling && document.currentScript.previousElementSibling.tagName.toLowerCase()==='section'
      ? document.currentScript.previousElementSibling.querySelector('form')
      : document.querySelector('form[action$="trainer/workshops"]') || document.querySelector('form[action$="trainer/workshops/update"]');
    if(!form) return;
    var d = form.querySelector('#ws-date');
    var t = form.querySelector('#ws-time');
    var h = form.querySelector('#ws-datetime');
    function sync(){
      if(!d || !t || !h) return;
      var dv = (d.value || '').trim();
      var tv = (t.value || '').trim();
      if(dv && tv){ h.value = dv + ' ' + tv; }
    }
    if(d) d.addEventListener('change', sync);
    if(t) t.addEventListener('change', sync);
    if(form) form.addEventListener('submit', function(){ sync(); });
  })();
</script>
