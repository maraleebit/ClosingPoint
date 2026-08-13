<?php
/** Partiel de formulaire réutilisé par add.php et edit.php (due diligence). */
?>
<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="row g-3" novalidate id="ddForm">
  <?php csrf_field(); ?>
  <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">

  <div class="col-md-4">
    <label class="form-label">Domaine *</label>
    <select name="domaine" class="form-select" required>
      <?php foreach (['juridique','fiscal','financier','commercial','rh','it'] as $d): ?>
        <option value="<?= $d ?>" <?= ($data['domaine'] ?? '') === $d ? 'selected' : '' ?>><?= e(domaineLabel($d)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-8">
    <label class="form-label">Libellé *</label>
    <input type="text" name="libelle" class="form-control" required maxlength="200" value="<?= e($data['libelle'] ?? '') ?>">
  </div>

  <div class="col-12">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="3"><?= e($data['description'] ?? '') ?></textarea>
  </div>

  <div class="col-md-3">
    <label class="form-label">Statut *</label>
    <select name="statut" class="form-select" required>
      <?php foreach (['a_verifier'=>'À vérifier','en_cours'=>'En cours','valide'=>'Validé','alerte'=>'Alerte'] as $val=>$label): ?>
        <option value="<?= $val ?>" <?= ($data['statut'] ?? 'a_verifier') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3 d-flex align-items-end">
    <div class="form-check mb-2">
      <input type="checkbox" name="red_flag" class="form-check-input" id="rf" <?= !empty($data['red_flag']) ? 'checked' : '' ?>>
      <label class="form-check-label" for="rf">Signaler comme <strong>red flag</strong></label>
    </div>
  </div>
  <div class="col-md-3">
    <label class="form-label">Impact estimé (FCFA)</label>
    <input type="number" step="0.01" min="0" name="impact_estime" class="form-control" value="<?= e($data['impact_estime'] ?? '') ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Échéance</label>
    <input type="date" name="date_limite" class="form-control" value="<?= e($data['date_limite'] ?? '') ?>">
  </div>

  <div class="col-md-6">
    <label class="form-label">Responsable</label>
    <select name="responsable_id" class="form-select">
      <option value="">— Aucun —</option>
      <?php foreach ($teamMembers as $m): ?>
        <option value="<?= (int)$m['id'] ?>" <?= (int)($data['responsable_id'] ?? 0) === (int)$m['id'] ? 'selected' : '' ?>><?= e($m['full_name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-12">
    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
    <a href="list.php?project_id=<?= (int)$projectId ?>" class="btn btn-outline-secondary">Annuler</a>
  </div>
</form>
