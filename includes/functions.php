<?php
/**
 * Fonctions utilitaires réutilisées par les différents modules.
 */

/** Formate un montant en devise (ex: 4 500 000 000 FCFA). */
function formatMoney($amount, string $devise = 'FCFA'): string
{
    if ($amount === null || $amount === '') {
        return '—';
    }
    return number_format((float)$amount, 0, ',', ' ') . ' ' . $devise;
}

/** Formate une date SQL (Y-m-d ou Y-m-d H:i:s) au format sénégalais jj/mm/aaaa. */
function formatDate(?string $date, string $fmt = 'd/m/Y'): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($fmt, $ts) : '—';
}

/**
 * Calcule les informations de pagination.
 * @return array{page:int, perPage:int, offset:int, totalPages:int, total:int}
 */
function paginate(int $total, ?int $page = null, int $perPage = ITEMS_PER_PAGE): array
{
    $page = $page ?? (int)($_GET['page'] ?? 1);
    $page = max(1, $page);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;

    return ['page' => $page, 'perPage' => $perPage, 'offset' => $offset, 'totalPages' => $totalPages, 'total' => $total];
}

/** Construit la barre de pagination Bootstrap, en conservant les paramètres GET existants. */
function renderPagination(array $pageInfo, string $baseFile): void
{
    if ($pageInfo['totalPages'] <= 1) {
        return;
    }
    $params = $_GET;
    echo '<nav aria-label="Pagination"><ul class="pagination">';
    for ($i = 1; $i <= $pageInfo['totalPages']; $i++) {
        $params['page'] = $i;
        $url = $baseFile . '?' . http_build_query($params);
        $active = $i === $pageInfo['page'] ? ' active' : '';
        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . e($url) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}

/** Badge Bootstrap pour le statut d'un projet M&A. */
function projectStatusBadge(string $statut): string
{
    $map = [
        'prospection'    => ['secondary', 'Prospection'],
        'nda_signe'      => ['info', 'NDA signé'],
        'due_diligence'  => ['warning', 'Due diligence'],
        'negociation'    => ['primary', 'Négociation'],
        'closing'        => ['success', 'Closing'],
        'abandonne'      => ['danger', 'Abandonné'],
    ];
    [$class, $label] = $map[$statut] ?? ['secondary', $statut];
    return '<span class="badge text-bg-' . $class . '">' . e($label) . '</span>';
}

/** Badge Bootstrap pour le statut d'un point de due diligence. */
function ddStatusBadge(string $statut): string
{
    $map = [
        'a_verifier' => ['secondary', 'À vérifier'],
        'en_cours'   => ['warning', 'En cours'],
        'valide'     => ['success', 'Validé'],
        'alerte'     => ['danger', 'Alerte'],
    ];
    [$class, $label] = $map[$statut] ?? ['secondary', $statut];
    return '<span class="badge text-bg-' . $class . '">' . e($label) . '</span>';
}

/** Pastille "Red Flag" si l'item de due diligence est signalé à risque. */
function redFlagBadge(bool $flag): string
{
    return $flag
        ? '<span class="badge text-bg-danger"><i class="bi bi-flag-fill"></i> Red flag</span>'
        : '';
}

/** Libellé lisible d'un rôle utilisateur. */
function roleLabel(string $role): string
{
    return ['admin' => 'Administrateur', 'conseiller' => 'Conseiller M&A', 'client' => 'Client / Investisseur'][$role] ?? $role;
}

/** Libellé lisible d'un domaine de due diligence. */
function domaineLabel(string $domaine): string
{
    return [
        'juridique'  => 'Juridique',
        'fiscal'     => 'Fiscal',
        'financier'  => 'Financier',
        'commercial' => 'Commercial',
        'rh'         => 'Ressources humaines',
        'it'         => 'Systèmes d\'information',
    ][$domaine] ?? $domaine;
}

/**
 * Envoi d'un email applicatif (notification). Utilise PHPMailer si la librairie
 * a été installée via composer (voir composer.json), sinon repli sur mail() natif.
 * Retourne true si l'envoi a été tenté sans erreur détectée.
 */
function sendAppMail(string $to, string $subject, string $htmlBody): bool
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = SMTP_HOST;
                $mail->Port       = SMTP_PORT;
                if (SMTP_USER !== '') {
                    $mail->SMTPAuth = true;
                    $mail->Username = SMTP_USER;
                    $mail->Password = SMTP_PASS;
                }
                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlBody;
                $mail->send();
                return true;
            } catch (Throwable $e) {
                error_log('Erreur envoi email (PHPMailer) : ' . $e->getMessage());
                return false;
            }
        }
    }

    // Repli : fonction mail() native de PHP (nécessite un serveur SMTP local configuré dans php.ini)
    $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: " . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";
    return @mail($to, $subject, $htmlBody, $headers);
}

/** Crée (si besoin) et retourne le dossier physique de stockage pour un projet. */
function ensureUploadDirForProject(int $projectId): string
{
    $dir = UPLOAD_DIR . 'projet_' . $projectId . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

/** Vérifie qu'une extension de fichier fait partie de la liste blanche autorisée. */
function isAllowedExtension(string $filename): bool
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, ALLOWED_EXTENSIONS, true);
}

/** Formate une taille de fichier en octets vers une unité lisible (Ko/Mo). */
function formatFileSize(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . ' Mo';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 1) . ' Ko';
    }
    return $bytes . ' o';
}

/**
 * Applique un filigrane dynamique (nom + date/heure) sur un PDF avant téléchargement,
 * à des fins de traçabilité anti-fuite de la data room (watermarking dynamique).
 * Nécessite les librairies setasign/fpdi + tecnickcom/tcpdf (voir composer.json).
 * Retourne le chemin d'un fichier temporaire filigrané, ou null si indisponible/échec
 * (dans ce cas, download.php se replie sur le fichier original tout en conservant
 * la traçabilité via document_access_log).
 */
function generatePdfWatermarkCopy(string $sourcePath, string $watermarkText): ?string
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        return null;
    }
    require_once $autoload;
    if (!class_exists('setasign\\Fpdi\\Tcpdf\\Fpdi')) {
        return null;
    }

    try {
        $pdf = new setasign\Fpdi\Tcpdf\Fpdi();
        $pageCount = $pdf->setSourceFile($sourcePath);

        for ($i = 1; $i <= $pageCount; $i++) {
            $tplId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($tplId);
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetTextColor(200, 0, 0);
            $pdf->SetAlpha(0.35);
            $pdf->StartTransform();
            $pdf->Rotate(45, $size['width'] / 2, $size['height'] / 2);
            $pdf->Text($size['width'] / 4, $size['height'] / 2, $watermarkText);
            $pdf->StopTransform();
            $pdf->SetAlpha(1);
        }

        $tmpPath = sys_get_temp_dir() . '/wm_' . bin2hex(random_bytes(8)) . '.pdf';
        $pdf->Output($tmpPath, 'F');
        return $tmpPath;
    } catch (Throwable $e) {
        error_log('Watermark PDF impossible : ' . $e->getMessage());
        return null;
    }
}

/**
 * Calcule une valorisation DCF simplifiée (méthode Gordon-Shapiro sur la valeur terminale).
 * @param float $fcfAn1 Flux de trésorerie disponible prévu pour l'année 1
 * @param float $croissance Taux de croissance annuel des FCF sur l'horizon explicite
 * @param float $wacc Coût moyen pondéré du capital
 * @param float $gTerminal Taux de croissance à l'infini (< WACC)
 * @param int $horizon Nombre d'années de projection explicite
 * @return array{flux:array,valeurTerminale:float,valeurActualisee:float}
 */
function computeDCF(float $fcfAn1, float $croissance, float $wacc, float $gTerminal, int $horizon): array
{
    $flux = [];
    $fcf = $fcfAn1;
    $valeurActualisee = 0.0;

    for ($annee = 1; $annee <= $horizon; $annee++) {
        if ($annee > 1) {
            $fcf *= (1 + $croissance);
        }
        $facteurActualisation = 1 / ((1 + $wacc) ** $annee);
        $fcfActualise = $fcf * $facteurActualisation;
        $valeurActualisee += $fcfActualise;
        $flux[] = ['annee' => $annee, 'fcf' => $fcf, 'fcf_actualise' => $fcfActualise];
    }

    // Valeur terminale (Gordon-Shapiro) actualisée à l'année de l'horizon
    $valeurTerminale = ($wacc > $gTerminal) ? ($fcf * (1 + $gTerminal)) / ($wacc - $gTerminal) : 0.0;
    $valeurTerminaleActualisee = $valeurTerminale / ((1 + $wacc) ** $horizon);

    return [
        'flux'                       => $flux,
        'valeur_terminale'           => $valeurTerminale,
        'valeur_terminale_actualisee' => $valeurTerminaleActualisee,
        'valeur_entreprise'          => $valeurActualisee + $valeurTerminaleActualisee,
    ];
}
