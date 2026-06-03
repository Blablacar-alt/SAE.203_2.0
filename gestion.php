<?php
session_start();
require_once 'db.php';

// Sécurité : Si pas connecté, retour à la page de connexion
if (!isset($_SESSION['visiteur_id'])) {
    header("Location: connexion.php");
    exit;
}

$id_visiteur = $_SESSION['visiteur_id'];

// TRAITEMENT DE L'ANNULATION (DELETE)
if (isset($_GET['action']) && $_GET['action'] === 'annuler' && isset($_GET['id'])) {
    $id_ins = intval($_GET['id']);
    
    // Récupérer le créneau et le nombre de personnes avant de supprimer
    $stmtBillet = $pdo->prepare("SELECT id_creneau, nb_personnes FROM inscription WHERE id_inscription = ? AND id_visiteur = ?");
    $stmtBillet->execute([$id_ins, $id_visiteur]);
    $billet = $stmtBillet->fetch();
    
    if ($billet) {
        $pdo->beginTransaction();
        try {
            // 1. Rendre les places au créneau
            $stmtRestituer = $pdo->prepare("UPDATE creneau SET places_restante = places_restante + ? WHERE id_creneau = ?");
            $stmtRestituer->execute([$billet['nb_personnes'], $billet['id_creneau']]);
            
            // 2. Supprimer la réservation
            $stmtDelete = $pdo->prepare("DELETE FROM inscription WHERE id_inscription = ?");
            $stmtDelete->execute([$id_ins]);
            
            $pdo->commit();
            header("Location: gestion.php?msg=annule");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
}

// RÉCUPÉRATION DES RÉSERVATIONS DU VISITEUR
$query = "
    SELECT i.id_inscription, i.nb_personnes, i.participe_buffet, 
           c.heure_debut, c.heure_fin, s.numero as numero_salle, s.nom_thematique, d.date
    FROM inscription i
    JOIN creneau c ON i.id_creneau = c.id_creneau
    JOIN salle s ON c.id_salle = s.id_salle
    JOIN date_expo d ON c.id_date = d.id_date
    WHERE i.id_visiteur = ?
    ORDER BY d.date ASC, c.heure_debut ASC
";
$stmt = $pdo->prepare($query);
$stmt->execute([$id_visiteur]);
$mes_reservations = $stmt->fetchAll();
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
        .profile-card { background: #fafafa; border-left: 4px solid var(--cyan); padding: 1.5rem; margin-bottom: 3rem; font-family: 'Space Mono'; }
        .ticket-card { border: 1px solid #e0e0e0; background: #fff; padding: 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; }
        .ticket-details h3 { margin: 0 0 0.5rem 0; font-family: 'Space Mono'; font-size: 1.2rem; }
        .ticket-meta { font-family: 'DM Sans'; color: #666; font-size: 0.95rem; }
        .buffet-badge { display: inline-block; background: #e0faf7; color: var(--cyan-dark); padding: 0.2rem 0.6rem; font-family: 'Space Mono'; font-size: 0.75rem; font-weight: bold; margin-top: 0.5rem; }
        .buffet-badge.no { background: #fdf2f2; color: #d9534f; }
        .ticket-actions { display: flex; gap: 1rem; }
        .btn-action { font-family: 'Space Mono'; font-weight: bold; padding: 0.6rem 1.2rem; text-decoration: none; font-size: 0.85rem; text-transform: uppercase; text-align: center;}
        .btn-edit { border: 1px solid var(--cyan); color: var(--ink); background: #fff; }
        .btn-edit:hover { background: var(--cyan); }
        .btn-cancel { background: #d9534f; color: #fff; border: none; }
        .btn-cancel:hover { background: #c9302c; }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="account-container">
    <h1 style="font-family: 'Space Mono'; text-transform: uppercase;">Mon Espace Compte<span>.</span></h1>
    
    <div class="profile-card">
        <strong>Visiteur :</strong> <?= htmlspecialchars($_SESSION['visiteur_prenom'] . " " . $_SESSION['visiteur_nom']) ?><br>
        <span style="font-size:0.85rem; color:#666;">Statut : Connecté via accès sécurisé</span>
        <a href="logout.php" style="float:right; color:#d9534f; text-decoration:none; font-size:0.85rem;">[ Se déconnecter ]</a>
    </div>

    <h2 style="font-family: 'Space Mono'; text-transform: uppercase; font-size: 1.3rem; margin-bottom: 1.5rem;">Mes réservations actives</h2>
    
    <?php if (count($mes_reservations) === 0): ?>
        <p style="font-family: 'DM Sans'; color: #666;">Vous n'avez pas encore de réservation de salle.</p>
    <?php else: ?>
        <?php foreach ($mes_reservations as $res): ?>
            <div class="ticket-card">
                <div class="ticket-details">
                    <h3>Salle <?= $res['numero_salle'] ?> : <?= htmlspecialchars($res['nom_thematique']) ?></h3>
                    <div class="ticket-meta">
                        📅 Le <strong><?= date('d/m/Y', strtotime($res['date'])) ?></strong> 
                        🕐 Créneau de <strong><?= substr($res['heure_debut'], 0, 5) ?> à <?= substr($res['heure_fin'], 0, 5) ?></strong><br>
                        👥 Nombre de personnes : <strong><?= $res['nb_personnes'] ?></strong>
                    </div>
                    <?php if ($res['participe_buffet'] == 1): ?>
                        <span class="buffet-badge">✓ Buffet inclus ce soir</span>
                    <?php else: ?>
                        <span class="buffet-badge no">𐄂 Sans buffet</span>
                    <?php endif; ?>
                </div>
                
                <div class="ticket-actions">
                    <a href="inscription.php?edit_id=<?= $res['id_inscription'] ?>" class="btn-action btn-edit">Modifier</a>
                    <a href="gestion.php?action=annuler&id=<?= $res['id_inscription'] ?>" class="btn-action btn-cancel" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?');">Annuler</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>