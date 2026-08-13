<?php
/**
 * Partiel de formulaire réutilisé par add.php et edit.php.
 * Variables attendues : $data (valeurs actuelles), $errors (messages de validation)
 */
$statuts = ['prospection' => 'Prospection', 'nda_signe' => 'NDA signé', 'due_diligence' => 'Due diligence', 'negociation' => 'Négociation', 'closing' => 'Closing', 'abandonne' => 'Abandonné'];
?>
<?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" class="row g-3 needs-validation" novalidate id="projectForm">
  <?php csrf_field(); ?>

  <div class="col-md-4">
    <label class="form-label">Code projet *</label>
    <input type="text" name="code_projet" class="form-control" required maxlength="20" value="<?= e($data['code_projet'] ?? '') ?>">
  </div>
  <div class="col-md-8">
    <label class="form-label">Nom du projet (nom de code) *</label>
    <input type="text" name="nom_projet" class="form-control" required maxlength="180" value="<?= e($data['nom_projet'] ?? '') ?>">
  </div>

  <div class="col-md-6">
    <label class="form-label">Société cible *</label>
    <input type="text" name="societe_cible" class="form-control" required maxlength="180" value="<?= e($data['societe_cible'] ?? '') ?>">
  </div>
  <div class="col-md-6">
    <label class="form-label">Société acquéreur *</label>
    <input type="text" name="societe_acquereur" class="form-control" required maxlength="180" value="<?= e($data['societe_acquereur'] ?? '') ?>">
  </div>

  <div class="col-md-4">
    <label class="form-label">Secteur d'activité</label>
    <input type="text" name="secteur" class="form-control" maxlength="100" value="<?= e($data['secteur'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">Statut *</label>
    <select name="statut" class="form-select" required>
      <?php foreach ($statuts as $val => $label): ?>
        <option value="<?= e($val) ?>" <?= ($data['statut'] ?? 'prospection') === $val ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4">
    <label class="form-label">Devise</label>
    <select name="devise" class="form-select">
      <?php foreach (['FCFA','EUR','USD'] as $dev): ?>
        <option value="<?= e($dev) ?>" <?= ($data['devise'] ?? 'FCFA') === $dev ? 'selected' : '' ?>><?= e($dev) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-md-4">
    <label class="form-label">Valeur estimée</label>
    <input type="number" step="0.01" min="0" name="valeur_estimee" class="form-control" value="<?= e($data['valeur_estimee'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">Date de début</label>
    <input type="date" name="date_debut" class="form-control" value="<?= e($data['date_debut'] ?? '') ?>">
  </div>
  <div class="col-md-4">
    <label class="form-label">Date cible de closing</label>
    <input type="date" name="date_cible_closing" class="form-control" value="<?= e($data['date_cible_closing'] ?? '') ?>">
  </div>

  <div class="col-12">
    <label class="form-label">Description / contexte de l'opération</label>
    <textarea name="description" class="form-control" rows="4"><?= e($data['description'] ?? '') ?></textarea>
  </div>

  <div class="col-12">
    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
    <a href="list.php" class="btn btn-outline-secondary">Annuler</a>
  </div>
</form>
