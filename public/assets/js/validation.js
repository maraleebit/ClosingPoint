/**
 * Validation côté client (complément obligatoire à la validation côté serveur PHP).
 * S'applique à tous les formulaires de la plateforme ClosingPoint.
 */
(function () {
  'use strict';

  // Active la validation Bootstrap standard (champs "required", "type=email", etc.)
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (form.checkValidity && !form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });

  // Formulaire de login : feedback immédiat si le mot de passe est trop court
  var loginForm = document.getElementById('loginForm');
  if (loginForm) {
    var pwd = loginForm.querySelector('input[name="password"]');
    if (pwd) {
      pwd.addEventListener('input', function () {
        pwd.setCustomValidity(pwd.value.length > 0 && pwd.value.length < 6 ? 'Le mot de passe doit contenir au moins 6 caractères.' : '');
      });
    }
  }

  // Formulaire de dépôt de document (data room) : contrôle de la taille du fichier avant envoi
  var uploadForm = document.getElementById('uploadForm');
  if (uploadForm) {
    var MAX_SIZE = 20 * 1024 * 1024; // doit rester cohérent avec MAX_UPLOAD_SIZE (config/config.php)
    var fileInput = uploadForm.querySelector('input[type="file"]');
    uploadForm.addEventListener('submit', function (event) {
      if (fileInput && fileInput.files.length > 0 && fileInput.files[0].size > MAX_SIZE) {
        event.preventDefault();
        alert('Le fichier sélectionné dépasse la taille maximale autorisée (20 Mo).');
      }
    });
  }

  // Formulaire DCF : vérifie que g < WACC avant envoi (règle de convergence de Gordon-Shapiro)
  var dcfForm = document.getElementById('dcfForm');
  if (dcfForm) {
    dcfForm.addEventListener('submit', function (event) {
      var wacc = parseFloat(dcfForm.querySelector('[name="wacc"]').value);
      var g = parseFloat(dcfForm.querySelector('[name="g_terminal"]').value);
      if (!isNaN(wacc) && !isNaN(g) && g >= wacc) {
        event.preventDefault();
        alert('Le taux de croissance à l\'infini (g) doit être strictement inférieur au WACC.');
      }
    });
  }

  // Confirmation systématique avant toute action de suppression sans confirm() inline dédié
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });
})();
