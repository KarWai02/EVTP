<section class="container py-6">
  <?php Auth::requireRole(['trainer']); $mode=$mode??'create'; $a=$assessment??[]; $errors=$errors??[]; $old=$old??[]; ?>
  <h1 style="margin:0 0 10px;"><?= $mode==='create' ? 'Add Assessment' : 'Edit Assessment' ?></h1>

  <form method="post" action="<?= $mode==='create' ? app_url('trainer/assessments') : app_url('trainer/assessments/update') ?>" class="card" style="max-width:720px;padding:16px;">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <?php if($mode==='edit'): ?><input type="hidden" name="id" value="<?= e($a['assessmentID'] ?? '') ?>"><?php endif; ?>
    <?php if($mode==='create'): ?><input type="hidden" name="moduleID" value="<?= e($moduleID ?? '') ?>"><?php endif; ?>

    <div style="display:grid;gap:10px;grid-template-columns:1fr 1fr;">
      <label>Type
        <select name="assessType">
          <?php $sel = ($old['assessType'] ?? ($a['assessType'] ?? 'quiz')); foreach(['quiz','assignment'] as $t): ?>
            <option value="<?= $t ?>" <?= $sel===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Max score
        <input type="number" name="maxScore" min="1" step="1" value="<?= e(($old['maxScore'] ?? '') !== '' ? $old['maxScore'] : ($a['maxScore'] ?? 100)) ?>" required>
        <?php if(!empty($errors['maxScore'])): ?><small class="error"><?= e($errors['maxScore']) ?></small><?php endif; ?>
      </label>

      <label>Pass score
        <input type="number" name="passScore" min="0" step="1" value="<?= e(($old['passScore'] ?? '') !== '' ? $old['passScore'] : ($a['passScore'] ?? 0)) ?>" required>
        <?php if(!empty($errors['passScore'])): ?><small class="error"><?= e($errors['passScore']) ?></small><?php endif; ?>
      </label>

      <label>Duration (minutes)
        <input type="number" name="durationLimit" min="0" step="5" value="<?= e(($old['durationLimit'] ?? '') !== '' ? $old['durationLimit'] : ($a['durationLimit'] ?? 0)) ?>">
      </label>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px;">
      <button class="btn" type="submit">Save</button>
      <a class="btn btn-outline" href="<?= app_url('trainer/assessments') ?>?module=<?= e($moduleID ?? ($a['moduleID'] ?? '')) ?>">Cancel</a>
    </div>
  </form>
</section>
