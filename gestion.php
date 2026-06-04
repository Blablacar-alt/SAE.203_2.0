<?php
session_start();
require_once 'db.php';

// SÉCURITÉ : Redirection vers connexion.php si non connecté
if (!isset($_SESSION['visiteur_id'])) {
    header("Location: connexion.php");
    exit;
}

$id_visiteur = $_SESSION['visiteur_id'];
$error = '';
$success = '';

// FONCTION POUR ENVOYER UN EMAIL AUX ACCOMPAGNANTS
function envoyerEmailAccompagnant($to, $prenom, $nom, $nom_salle, $date_choisie, $heure_deb, $heure_fin) {
    $subject = "=?UTF-8?B?".base64_encode("Votre invitation pour l'exposition E-LLUSION")."?=";
    $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\nFrom: E-LLUSION <noreply@univ-savoie.fr>\r\n";
    $date_formattee = date("d/m/2026", strtotime($date_choisie));
    $heure_deb_f = substr($heure_deb, 0, 5);
    $heure_fin_f = substr($heure_fin, 0, 5);

    $message = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:Arial,sans-serif;background:#f4f7f6;padding:20px;"><div style="max-width:550px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.06);"><div style="background:linear-gradient(135deg,#111b21 0%,#00b8a0 100%);color:#fff;padding:30px;text-align:center;"><h2>E-LLUSION</h2><p>EXPOSITION IMMERSIVE</p></div><div style="padding:25px;line-height:1.6;"><p>Bonjour '.htmlspecialchars($prenom).' '.htmlspecialchars($nom).',</p><p>Une place vous a été réservée pour l\'exposition <strong>E-LLUSION</strong>.</p><div style="background:#e6f8f6;border-left:4px solid #00b8a0;padding:15px;margin:15px 0;">🏛️ <strong>Salle '.htmlspecialchars($nom_salle).'</strong><br>📅 Le '.$date_formattee.'<br>⏱️ Créneau : '.$heure_deb_f.' à '.$heure_fin_f.'</div></div></div></body></html>';
    @mail($to, $subject, $message, $headers);
}

// 1. ACTION : SUPPRESSION INDIVIDUELLE D'UN ACCOMPAGNANT
if (isset($_GET['action']) && $_GET['action'] === 'annuler_accompagnant' && isset($_GET['id_acc']) && isset($_GET['id_creneau'])) {
    $id_acc_inscription = intval($_GET['id_acc']);
    $id_creneau = intval($_GET['id_creneau']);

    $pdo->beginTransaction();
    try {
        $stmtDel = $pdo->prepare("DELETE FROM inscription WHERE id_inscription = ?");
        $stmtDel->execute([$id_acc_inscription]);

        $stmtRestituer = $pdo->prepare("UPDATE creneau SET places_restante = places_restante + 1 WHERE id_creneau = ?");
        $stmtRestituer->execute([$id_creneau]);

        $pdo->commit();
        header("Location: gestion.php?msg=accompagnant_supprime");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Impossible de retirer cet accompagnant.";
    }
}

// 2. ACTION : ANNULATION COMPLÈTE D'UNE RÉSERVATION
if (isset($_GET['action']) && $_GET['action'] === 'annuler' && isset($_GET['id'])) {
    $id_ins = intval($_GET['id']);
    $stmtBillet = $pdo->prepare("SELECT id_creneau, token_modification, nb_personnes FROM inscription WHERE id_inscription = ? AND id_visiteur = ?");
    $stmtBillet->execute([$id_ins, $id_visiteur]);
    $billet = $stmtBillet->fetch();
    
    if ($billet) {
        $pdo->beginTransaction();
        try {
            if (!empty($billet['token_modification'])) {
                $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM inscription WHERE token_modification = ? AND id_creneau = ?");
                $stmtCount->execute([$billet['token_modification'], $billet['id_creneau']]);
                $total_places = $stmtCount->fetchColumn() ?: 1;

                $stmtRestituer = $pdo->prepare("UPDATE creneau SET places_restante = places_restante + ? WHERE id_creneau = ?");
                $stmtRestituer->execute([$total_places, $billet['id_creneau']]);
                
                $stmtDelete = $pdo->prepare("DELETE FROM inscription WHERE token_modification = ? AND id_creneau = ?");
                $stmtDelete->execute([$billet['token_modification'], $billet['id_creneau']]);
            } else {
                $stmtRestituer = $pdo->prepare("UPDATE creneau SET places_restante = places_restante + ? WHERE id_creneau = ?");
                $stmtRestituer->execute([$billet['nb_personnes'], $billet['id_creneau']]);
                $stmtDelete = $pdo->prepare("DELETE FROM inscription WHERE id_inscription = ?");
                $stmtDelete->execute([$id_ins]);
            }
            $pdo->commit();
            header("Location: gestion.php?msg=annule");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Impossible d'annuler la réservation.";
        }
    }
}

// 3. ACTION : MODIFICATION DES INFORMATIONS (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_modifier'])) {
    $id_main_inscription = intval($_POST['id_inscription']);
    $participe_buffet = isset($_POST['participe_buffet']) ? 1 : 0;
    $modif_accompagnants = $_POST['modif_accompagnants'] ?? [];

    $pdo->beginTransaction();
    try {
        // Mettre à jour le buffet du visiteur principal
        $stmtUpMain = $pdo->prepare("UPDATE inscription SET participe_buffet = ? WHERE id_inscription = ? AND id_visiteur = ?");
        $stmtUpMain->execute([$participe_buffet, $id_main_inscription, $id_visiteur]);

        // Mettre à jour chaque accompagnant
        foreach ($modif_accompagnants as $id_acc_visiteur => $infos) {
            $nom = trim($infos['nom'] ?? '');
            $prenom = trim($infos['prenom'] ?? '');
            $email = trim($infos['email'] ?? '');

            if (!empty($nom) && !empty($prenom)) {
                $stmtUpAcc = $pdo->prepare("UPDATE visiteur SET nom = ?, prenom = ?, email = ? WHERE id_visiteur = ?");
                $stmtUpAcc->execute([$nom, $prenom, !empty($email) ? $email : null, $id_acc_visiteur]);
            }
        }

        $pdo->commit();
        header("Location: gestion.php?msg=modifie");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la mise à jour des informations.";
    }
}

// 4. ACTION : CRÉATION D'UNE NOUVELLE RÉSERVATION (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_ajout'])) {
    $id_creneau_choisi = intval($_POST['id_creneau']);
    $nb_personnes = intval($_POST['nb_personnes']);
    $participe_buffet = isset($_POST['participe_buffet']) ? 1 : 0;
    $accompagnants = $_POST['accompagnants'] ?? [];

    if (!empty($id_creneau_choisi)) {
        $pdo->beginTransaction();
        try {
            $stmtCheck = $pdo->prepare("SELECT c.*, s.nom_thematique, d.date FROM creneau c JOIN salle s ON c.id_salle=s.id_salle JOIN date_expo d ON c.id_date=d.id_date WHERE c.id_creneau = ?");
            $stmtCheck->execute([$id_creneau_choisi]);
            $creneau = $stmtCheck->fetch();

            if (!$creneau || $creneau['places_restante'] < $nb_personnes) {
                throw new Exception("Places insuffisantes sur ce créneau.");
            }

            $token_groupe = md5(uniqid());

            $stmtInscription = $pdo->prepare("INSERT INTO inscription (id_visiteur, id_creneau, nb_personnes, date_inscription, statut, token_modification, participe_buffet) VALUES (?, ?, 1, NOW(), 'confirmé', ?, ?)");
            $stmtInscription->execute([$id_visiteur, $id_creneau_choisi, $token_groupe, $participe_buffet]);

            foreach ($accompagnants as $acc) {
                $acc_nom = trim($acc['nom']);
                $acc_prenom = trim($acc['prenom']);
                $acc_email = trim($acc['email']);

                if (!empty($acc_nom) && !empty($acc_prenom)) {
                    $stmtAccVisiteur = $pdo->prepare("INSERT INTO visiteur (nom, prenom, email, profil) VALUES (?, ?, ?, 'Accompagnant') ON DUPLICATE KEY UPDATE id_visiteur=LAST_INSERT_ID(id_visiteur)");
                    $stmtAccVisiteur->execute([$acc_nom, $acc_prenom, !empty($acc_email) ? $acc_email : null]);
                    $id_acc_visiteur = $pdo->lastInsertId();

                    $stmtAccInsc = $pdo->prepare("INSERT INTO inscription (id_visiteur, id_creneau, nb_personnes, date_inscription, statut, token_modification, participe_buffet) VALUES (?, ?, 1, NOW(), 'confirmé', ?, 0)");
                    $stmtAccInsc->execute([$id_acc_visiteur, $id_creneau_choisi, $token_groupe]);

                    if (!empty($acc_email)) {
                        envoyerEmailAccompagnant($acc_email, $acc_prenom, $acc_nom, $creneau['nom_thematique'], $creneau['date'], $creneau['heure_debut'], $creneau['heure_fin']);
                    }
                }
            }

            $stmtUpdatePlaces = $pdo->prepare("UPDATE creneau SET places_restante = places_restante - ? WHERE id_creneau = ?");
            $stmtUpdatePlaces->execute([$nb_personnes, $id_creneau_choisi]);

            $pdo->commit();
            header("Location: gestion.php?msg=ajoute");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// RÉCUPÉRATION DES RÉSERVATIONS ACTIVES
$query = "SELECT i.id_inscription, i.id_creneau, i.token_modification, i.nb_personnes, i.participe_buffet, c.heure_debut, c.heure_fin, s.numero as numero_salle, s.nom_thematique, d.date FROM inscription i JOIN creneau c ON i.id_creneau = c.id_creneau JOIN salle s ON c.id_salle = s.id_salle JOIN date_expo d ON c.id_date = d.id_date WHERE i.id_visiteur = ? ORDER BY d.date ASC, c.heure_debut ASC";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_visiteur]);
$mes_reservations = $stmt->fetchAll();

// CONFIG FORMULAIRE AJOUT
$salles = $pdo->query("SELECT * FROM salle ORDER BY numero ASC")->fetchAll();
$all_creneaux = $pdo->query("SELECT c.id_creneau, s.numero as numero_salle, d.date as date_texte, c.heure_debut, c.heure_fin, c.places_restante FROM creneau c JOIN salle s ON c.id_salle = s.id_salle JOIN date_expo d ON c.id_date = d.id_date ORDER BY c.heure_debut ASC")->fetchAll();
$creneaux_json_data = [];
foreach ($all_creneaux as $c) {
    $creneaux_json_data[$c['numero_salle']][$c['date_texte']][] = ['id_creneau' => $c['id_creneau'], 'heure_debut' => $c['heure_debut'], 'heure_fin' => $c['heure_fin'], 'places_restante' => $c['places_restante']];
}
$creneaux_json = json_encode($creneaux_json_data);

$stmtP = $pdo->prepare("SELECT profil FROM visiteur WHERE id_visiteur = ?");
$stmtP->execute([$id_visiteur]);
$profil_visiteur = $stmtP->fetchColumn() ?: '';
$buffet_autorise = in_array($profil_visiteur, ["Enseignant·e", "Personnel de l'université", "Visiteur·euse extérieur·e", "Professionnels/partenaires"]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>E-LLUSION — Mon Compte</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="style.css"/>
    <style>
        .account-container { max-width: 900px; margin: 4rem auto; padding: 2rem; }
        .profile-card { background: #fafafa; border-left: 4px solid #00b8a0; padding: 1.5rem; margin-bottom: 3rem; font-family: 'Space Mono'; }
        .ticket-card { border: 1px solid #e0e0e0; background: #fff; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
        .ticket-main-row { display: flex; justify-content: space-between; align-items: center; }
        .ticket-details h3 { margin: 0 0 0.5rem 0; font-family: 'Space Mono'; font-size: 1.2rem; text-transform: uppercase; }
        .ticket-meta { font-family: 'DM Sans'; color: #666; font-size: 0.95rem; line-height: 1.6; }
        .buffet-badge { display: inline-block; background: #e0faf7; color: #009682; padding: 0.2rem 0.6rem; font-family: 'Space Mono'; font-size: 0.75rem; font-weight: bold; margin-top: 0.5rem; border-radius: 4px; }
        .buffet-badge.no { background: #fdf2f2; color: #d9534f; }
        .ticket-actions { display: flex; gap: 0.75rem; }
        .btn-action { font-family: 'Space Mono'; font-weight: bold; padding: 0.6rem 1.2rem; text-decoration: none; font-size: 0.85rem; text-transform: uppercase; cursor: pointer; border-radius: 6px; border: none; }
        .btn-edit { border: 1px solid #00b8a0; color: #222; background: #fff; transition: 0.2s; }
        .btn-edit:hover { background: #00b8a0; color: #fff; }
        .btn-cancel { background: #d9534f; color: #fff; }
        .btn-cancel:hover { background: #c9302c; }
        
        .companions-section { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #eee; font-family: 'DM Sans'; }
        .companion-chip { display: inline-flex; align-items: center; gap: 0.5rem; background: #f0fdfa; border: 1px solid #a7f3d0; color: #047857; padding: 0.3rem 0.7rem; border-radius: 20px; font-size: 0.85rem; margin-right: 0.5rem; margin-top: 0.5rem; font-weight: 500; }
        .companion-remove { color: #ef4444; text-decoration: none; font-weight: bold; font-size: 1rem; padding-left: 2px; }
        .companion-remove:hover { color: #b91c1c; }

        /* Bloc d'édition intégré */
        .edit-inline-panel { background: #f9fbfb; border: 1px solid #00b8a0; padding: 1.5rem; margin-top: 1.5rem; border-radius: 8px; display: none; }
        .edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; }
        
        /* Section Ajout (Cachée par défaut) */
        .add-reservation-section { background: #fff; border: 1px dashed #bbb; border-radius: 12px; padding: 2rem; margin-top: 3rem; display: none; }
        .form-group { margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: .5rem; font-family: 'DM Sans'; }
        .form-group label { font-weight: 500; font-size: .95rem; }
        .form-group select, .form-group input { padding: .6rem; border: 1px solid #ccc; border-radius: 6px; font-family: 'DM Sans'; }
        .day-grid { display: flex; gap: .75rem; }
        .day-btn { border: 1.5px solid #ddd; border-radius: 8px; padding: .6rem 1rem; background: #fff; cursor: pointer; font-family: 'DM Sans'; font-size: .85rem; }
        .day-btn.selected { border-color: #00b8a0; background: #b2f0e8; font-weight: 600; }
        .creneau-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: .5rem; }
        .creneau-btn { border: 1.5px solid #ddd; border-radius: 8px; padding: .5rem; text-align: center; font-family: 'DM Sans'; font-size: .8rem; cursor: pointer; background: #fff; }
        .creneau-btn.selected { border-color: #00b8a0; background: #b2f0e8; font-weight: 600; }
        .creneau-btn:disabled { opacity: .4; background: #f5f5f5; }
        .btn-submit-add { background: #00b8a0; color: #fff; border: none; font-family: 'Space Mono'; font-weight: bold; padding: .8rem 1.5rem; text-transform: uppercase; cursor: pointer; border-radius: 6px; }
        .success-banner { background: #d4f5ee; border: 1px solid #7de0d2; border-radius: 8px; padding: .8rem; color: #005c52; font-family: 'DM Sans'; margin-bottom: 1.5rem; }
        .alert-msg { background: #ffe5e5; border: 1px solid #e0302a; border-radius: 8px; padding: .8rem; color: #b00000; font-family: 'DM Sans'; }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="account-container">
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'annule'): ?><div class="success-banner">✅ Réservation annulée avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'modifie'): ?><div class="success-banner">✅ Modifications enregistrées avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'accompagnant_supprime'): ?><div class="success-banner">✅ Accompagnant supprimé et place libérée.</div><?php endif; ?>
    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ajoute'): ?><div class="success-banner">🎉 Nouvelle réservation ajoutée !</div><?php endif; ?>
    <?php if ($error): ?><div class="alert-msg">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <h1 style="font-family: 'Space Mono'; text-transform: uppercase;">Mon Espace Compte<span>.</span></h1>
    
    <div class="profile-card">
        <strong>Visiteur principal :</strong> <?= htmlspecialchars($_SESSION['visiteur_prenom'] . " " . $_SESSION['visiteur_nom']) ?>
        <a href="logout.php" style="float:right; color:#d9534f; text-decoration:none; font-size:0.85rem;">[ Se déconnecter ]</a>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 style="font-family: 'Space Mono'; text-transform: uppercase; font-size: 1.3rem; margin: 0;">Mes réservations</h2>
        <button class="btn-action btn-edit" onclick="toggleAddSection()">[ + Ajouter une autre réservation ]</button>
    </div>
    
    <?php if (count($mes_reservations) === 0): ?>
        <p style="font-family: 'DM Sans'; color: #666;">Aucune réservation active.</p>
    <?php else: ?>
        <?php foreach ($mes_reservations as $res): 
            $accompagnants_liste = [];
            if (!empty($res['token_modification'])) {
                $stmtAcc = $pdo->prepare("SELECT i.id_inscription, v.id_visiteur, v.nom, v.prenom, v.email FROM inscription i JOIN visiteur v ON i.id_visiteur = v.id_visiteur WHERE i.token_modification = ? AND i.id_creneau = ? AND i.id_visiteur != ?");
                $stmtAcc->execute([$res['token_modification'], $res['id_creneau'], $id_visiteur]);
                $accompagnants_liste = $stmtAcc->fetchAll();
            }
            $total_personnes = count($accompagnants_liste) + 1;
        ?>
            <div class="ticket-card">
                <div class="ticket-main-row">
                    <div class="ticket-details">
                        <h3>Salle <?= $res['numero_salle'] ?> : <?= htmlspecialchars($res['nom_thematique']) ?></h3>
                        <div class="ticket-meta">
                            📅 Le <strong><?= date('d/m/Y', strtotime($res['date'])) ?></strong> à <strong><?= substr($res['heure_debut'],0,5) ?></strong><br>
                            👥 Places : <strong><?= $total_personnes ?></strong>
                        </div>
                        <span class="buffet-badge <?= $res['participe_buffet'] ? '' : 'no' ?>"><?= $res['participe_buffet'] ? '✓ Buffet inclus' : '𐄂 Sans buffet' ?></span>
                    </div>
                    <div class="ticket-actions">
                        <button class="btn-action btn-edit" onclick="toggleEditPanel(<?= $res['id_inscription'] ?>)">Modifier</button>
                        <a href="gestion.php?action=annuler&id=<?= $res['id_inscription'] ?>" class="btn-action btn-cancel" onclick="return confirm('Annuler toute la réservation ?');">Annuler tout</a>
                    </div>
                </div>

                <?php if (!empty($accompagnants_liste)): ?>
                    <div class="companions-section">
                        <strong>Accompagnants :</strong>
                        <?php foreach ($accompagnants_liste as $acc): ?>
                            <span class="companion-chip">
                                <?= htmlspecialchars($acc['prenom'] . ' ' . $acc['nom']) ?>
                                <a href="gestion.php?action=annuler_accompagnant&id_acc=<?= $acc['id_inscription'] ?>&id_creneau=<?= $res['id_creneau'] ?>" class="companion-remove" onclick="return confirm('Supprimer cet accompagnant ?');" title="Supprimer">✕</a>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="edit-inline-panel" id="edit-panel-<?= $res['id_inscription'] ?>">
                    <form method="POST">
                        <input type="hidden" name="action_modifier" value="1">
                        <input type="hidden" name="id_inscription" value="<?= $res['id_inscription'] ?>">
                        
                        <h4 style="margin-top:0; font-family:'Space Mono'; text-transform:uppercase;">Mettre à jour la réservation</h4>
                        
                        <?php if ($buffet_autorise): ?>
                            <div class="form-group" style="flex-direction:row; align-items:center; margin-bottom:1.5rem;">
                                <input type="checkbox" name="participe_buffet" id="buf-<?= $res['id_inscription'] ?>" value="1" <?= $res['participe_buffet'] ? 'checked' : '' ?>>
                                <label for="buf-<?= $res['id_inscription'] ?>" style="cursor:pointer;">Je participe au buffet du jeudi soir</label>
                            </div>
                        <?php endif; ?>

                        <?php if(!empty($accompagnants_liste)): ?>
                            <p style="font-weight:bold; font-size:0.9rem; margin-bottom:0.5rem;">Modifier l'identité des accompagnants :</p>
                            <?php foreach ($accompagnants_liste as $index => $acc): ?>
                                <div style="border-bottom:1px dashed #ddd; padding-bottom:1rem; margin-bottom:1rem;">
                                    <span style="font-size:0.8rem; color:#666; font-weight:bold;">ACCOMPAGNANT N°<?= $index+1 ?></span>
                                    <div class="edit-grid">
                                        <div class="form-group">
                                            <label>Nom</label>
                                            <input type="text" name="modif_accompagnants[<?= $acc['id_visiteur'] ?>][nom]" value="<?= htmlspecialchars($acc['nom']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Prénom</label>
                                            <input type="text" name="modif_accompagnants[<?= $acc['id_visiteur'] ?>][prenom]" value="<?= htmlspecialchars($acc['prenom']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Email (Optionnel)</label>
                                        <input type="email" name="modif_accompagnants[<?= $acc['id_visiteur'] ?>][email]" value="<?= htmlspecialchars($acc['email']) ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                            <button type="button" class="btn-action" style="background:#ccc;" onclick="toggleEditPanel(<?= $res['id_inscription'] ?>)">Annuler</button>
                            <button type="submit" class="btn-action" style="background:#00b8a0; color:#white; color:white;">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div> 
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="add-reservation-section" id="add-section">
        <h2 style="font-family: 'Space Mono'; text-transform: uppercase; font-size: 1.2rem; margin-top: 0; color: #222;">// Réserver un autre créneau</h2>
        <form method="POST" onsubmit="return validateForm()">
            <input type="hidden" name="action_ajout" value="1">
            <input type="hidden" name="id_creneau" id="id_creneau_hidden" value="">
            <input type="hidden" name="date" id="date_hidden" value="2026-06-18">

            <div class="form-group">
                <label>Sélectionner une salle *</label>
                <select id="salle" onchange="onSalleChange()" required>
                    <option value="">— Choisir la salle —</option>
                    <?php foreach ($salles as $s): ?>
                        <option value="<?= $s['numero'] ?>">Salle <?= $s['numero'] ?> — <?= htmlspecialchars($s['nom_thematique']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Sélectionner le Jour *</label>
                <div class="day-grid">
                    <button type="button" class="day-btn selected" id="day1" onclick="selectDay('2026-06-18')">Jeudi 18 juin 2026</button>
                    <button type="button" class="day-btn" id="day2" onclick="selectDay('2026-06-19')">Vendredi 19 juin 2026</button>
                </div>
            </div>

            <div class="form-group">
                <label>Nombre de personnes *</label>
                <input type="number" name="nb_personnes" id="nbPersonnes" min="1" max="12" value="1" oninput="gererChampsAccompagnants()" required>
            </div>

            <div id="accompagnantsWrapper"></div>

            <?php if ($buffet_autorise): ?>
                <div class="form-group" style="flex-direction: row; align-items: center; gap: .5rem; margin-top: 1.5rem;">
                    <input type="checkbox" name="participe_buffet" id="participe_buffet_add">
                    <label for="participe_buffet_add" style="cursor:pointer; font-size: .9rem;">Participer également au buffet</label>
                </div>
            <?php endif; ?>

            <div class="form-group" id="creneauxWrapper" style="display:none; margin-top: 1.5rem;">
                <label style="font-weight: bold; color: #00b8a0;">Séances disponibles *</label>
                <div id="creneauxContainer"></div>
            </div>

            <button type="submit" class="btn-submit-add">Confirmer la réservation</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const CRENEAUX_DATA = <?= $creneaux_json ?>;
let selectedDay = '2026-06-18';
let selectedSalle = null;

function toggleAddSection() {
    const section = document.getElementById('add-section');
    section.style.display = (section.style.display === 'block') ? 'none' : 'block';
}

function toggleEditPanel(id) {
    const panel = document.getElementById('edit-panel-' + id);
    panel.style.display = (panel.style.display === 'block') ? 'none' : 'block';
}

function gererChampsAccompagnants() {
    const nbTotal = parseInt(document.getElementById('nbPersonnes').value) || 1;
    const wrapper = document.getElementById('accompagnantsWrapper');
    wrapper.innerHTML = '';
    
    if (nbTotal > 1) {
        let html = '<p style="font-family:\'Space Mono\'; font-size:.9rem; margin-top:1.5rem; color:#00b8a0; font-weight:bold;">// Informations des accompagnants</p>';
        for (let i = 1; i < nbTotal; i++) {
            html += `
                <div class="acc-card">
                    <div style="font-weight:bold; font-size:.85rem; margin-bottom:.5rem;">Accompagnant n°${i}</div>
                    <div style="display:flex; gap:1rem; margin-bottom:0.5rem;">
                        <div class="form-group" style="flex:1;"><label>Nom *</label><input type="text" name="accompagnants[${i}][nom]" required></div>
                        <div class="form-group" style="flex:1;"><label>Prénom *</label><input type="text" name="accompagnants[${i}][prenom]" required></div>
                    </div>
                    <div class="form-group"><label>Adresse mail (Optionnel)</label><input type="email" name="accompagnants[${i}][email]"></div>
                </div>`;
        }
        wrapper.innerHTML = html;
    }
    renderCreneaux();
}

function selectDay(dateStr) {
    selectedDay = dateStr;
    document.getElementById('day1').classList.toggle('selected', dateStr === '2026-06-18');
    document.getElementById('day2').classList.toggle('selected', dateStr === '2026-06-19');
    document.getElementById('date_hidden').value = dateStr;
    renderCreneaux();
}

function onSalleChange() {
    selectedSalle = document.getElementById('salle').value || null;
    document.getElementById('id_creneau_hidden').value = '';
    renderCreneaux();
}

function renderCreneaux() {
    const wrapper = document.getElementById('creneauxWrapper');
    const container = document.getElementById('creneauxContainer');
    const nb = parseInt(document.getElementById('nbPersonnes').value) || 1;

    if (!selectedSalle || !selectedDay) { wrapper.style.display = 'none'; return; }
    const creneaux = (CRENEAUX_DATA[selectedSalle] || {})[selectedDay] || [];
    wrapper.style.display = 'block';

    if (creneaux.length === 0) {
        container.innerHTML = '<p style="color:#888;font-size:.85rem">Aucun créneau disponible.</p>';
        return;
    }

    let html = '<div class="creneau-grid">';
    creneaux.forEach(function(c) {
        const disabled = c.places_restante < nb;
        html += `<button type="button" class="creneau-btn" id="cbtn-${c.id_creneau}" ${disabled ? 'disabled' : ''} onclick="selectCreneau(${c.id_creneau})">
                    ${c.heure_debut.slice(0,5)}–${c.heure_fin.slice(0,5)}<br><small>${c.places_restante} pl.</small>
                 </button>`;
    });
    container.innerHTML = html + '</div>';
}

function selectCreneau(id) {
    document.querySelectorAll('.creneau-btn').forEach(b => b.classList.remove('selected'));
    if(document.getElementById('cbtn-' + id)) document.getElementById('cbtn-' + id).classList.add('selected');
    document.getElementById('id_creneau_hidden').value = id;
}

function validateForm() {
    if (!document.getElementById('id_creneau_hidden').value) { alert('Merci de choisir une séance.'); return false; }
    return true;
}

window.addEventListener('DOMContentLoaded', () => {
    onSalleChange();
    selectDay(selectedDay);
});
</script>
</body>
</html>