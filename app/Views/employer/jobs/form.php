<section class="container py-6">
  <?php Auth::requireRole(['employer']); ?>
  <?php $mode = $mode ?? 'create'; $j = $job ?? []; $errors=$errors??[]; $old=$old??[]; ?>
  <h1 style="margin:0 0 10px;"><?= $mode==='create' ? 'Post a Job' : 'Edit Job' ?></h1>

  <form method="post" action="<?= $mode==='create' ? app_url('employer/jobs') : app_url('employer/jobs/update') ?>" class="card" style="max-width:860px;padding:20px;">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <?php if($mode==='edit'): ?><input type="hidden" name="id" value="<?= e($j['jobID'] ?? '') ?>"><?php endif; ?>

    <div style="display:grid;gap:10px;grid-template-columns:1fr 1fr;">
      <label style="grid-column:1 / -1;">Job Title
        <input type="text" name="jobTitle" value="<?= e(($old['jobTitle'] ?? '') ?: ($j['jobTitle'] ?? '')) ?>" required>
        <?php if(!empty($errors['jobTitle'])): ?><small class="error"><?= e($errors['jobTitle']) ?></small><?php endif; ?>
      </label>
      <label>Job Type
        <?php $jt = ($old['jobType'] ?? '') ?: ($j['jobType'] ?? ''); ?>
        <select name="jobType">
          <?php foreach(['','Internship','Contract','Full-time','Part-time'] as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $jt===$opt?'selected':'' ?>><?= $opt!==''?$opt:'-- Select --' ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Location
        <input type="text" name="location" value="<?= e(($old['location'] ?? '') ?: ($j['location'] ?? '')) ?>">
      </label>
      <label>Salary Min
        <input type="number" step="0.01" name="salaryMin" value="<?= e(($old['salaryMin'] ?? '') !== '' ? $old['salaryMin'] : ($j['salaryMin'] ?? '')) ?>" placeholder="e.g. 3000.00">
      </label>
      <label>Salary Max
        <input type="number" step="0.01" name="salaryMax" value="<?= e(($old['salaryMax'] ?? '') !== '' ? $old['salaryMax'] : ($j['salaryMax'] ?? '')) ?>" placeholder="e.g. 5000.00">
      </label>
      <label>Application Deadline
        <span style="display:inline-flex;align-items:center;gap:6px;">
          <input id="emp-deadline" type="date" name="deadline" value="<?= e(($old['deadline'] ?? '') ?: ($j['deadline'] ?? '')) ?>">
          <button type="button" class="btn btn-outline btn-sm" onclick="(function(){var el=document.getElementById('emp-deadline'); if(el && el.showPicker){ el.showPicker(); } else if(el){ el.focus(); } })()" aria-label="Open calendar">📅</button>
        </span>
      </label>
      <label style="grid-column:1 / -1;">Description
        <textarea name="jobDesc" rows="7" placeholder="Role responsibilities, requirements, benefits..."><?= e(($old['jobDesc'] ?? '') ?: ($j['jobDesc'] ?? '')) ?></textarea>
      </label>
      <label style="grid-column:1 / -1;">Required Skills (comma separated)
        <input type="text" name="skills" value="<?= e(($old['skills'] ?? '') ?: ($j['skills'] ?? '')) ?>" placeholder="e.g. PHP, MySQL, JavaScript">
      </label>
      <label style="grid-column:1 / -1;">Education Requirements
        <input type="text" name="educationReq" value="<?= e(($old['educationReq'] ?? '') ?: ($j['educationReq'] ?? '')) ?>" placeholder="e.g. Diploma in CS or related">
      </label>
      <?php if($mode==='edit'): ?>
        <label style="grid-column:1 / -1;display:flex;align-items:center;gap:8px;">
          <input type="checkbox" name="close" value="1" <?= !empty($j['closedDate'])?'checked':'' ?>> Close job (set closed date to today)
        </label>
      <?php endif; ?>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px;">
      <button class="btn" type="submit">Save</button>
      <a class="btn btn-outline" href="<?= app_url('employer/jobs') ?>">Cancel</a>
    </div>
  </form>
</section>
