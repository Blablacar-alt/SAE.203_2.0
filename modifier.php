<?php
session_start();
require_once 'db.php'; // Connexion à la base de données

$error = '';
$id_salle_active = null;

// 1. SÉCURITÉ : L'UTILISATEUR DOIT ÊTRE CONNECTÉ
if (!isset($_SESSION['visiteur_id'])) {
    header("Location: connexion.php");
    exit;
}

$id_visiteur = $_SESSION['visiteur_id'];

// 2. VÉRIFICATION DE LA RÉSERVATION À MODIFIER
if (!isset($_GET['edit_id'])) {
    header("Location: gestion.php");
    exit;
}

$edit_id = intval($_GET['edit_id']);

// On récupère la réservation de l'utilisateur avec la salle associée
$stmt = $pdo->prepare("
    SELECT i.*, c.id_date, c.id_salle, s.numero as numero_salle, s.nom_thematique
    FROM inscription i
    JOIN creneau c ON i.id_creneau = c.id_creneau
    JOIN salle s ON c.id_salle = s.id_salle
    WHERE i.id_inscription = ? AND i.id_visiteur = ?
");
$stmt->execute([$edit_id, $id_visiteur]);
$reservation_actuelle = $stmt->fetch();

if (!$reservation_actuelle) {
    // Si la réservation n'existe pas ou n'appartient pas à ce visiteur
    header("Location: gestion.php");
    exit;
}

$id_salle_active = $reservation_actuelle['id_salle'];

// Récupération des détails de la salle pour l'en-tête
$stmtSalle = $pdo->prepare("SELECT * FROM salle WHERE id_salle = ?");
$stmtSalle->execute([$id_salle_active]);
$infos_salle = $stmtSalle->fetch();


// 3. TRAITEMENT DU FORMULAIRE DE MISE À JOUR (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_creneau_choisi = intval($_POST['id_creneau']);
    $nb_personnes = intval($_POST['nb_personnes']);
    $participe_buffet = isset($_POST['participe_buffet']) ? 1 : 0; // 1 si coché, 0 si décoché

    $id_creneau_ancien = $reservation_actuelle['id_creneau'];
    $ancien_nb_personnes = $reservation_actuelle['nb_personnes'];
    
    $pdo->beginTransaction();
    try {
        if ($id_creneau_choisi != $id_creneau_ancien) {
            // L'utilisateur change d'horaire ou de date : on vérifie les places sur le nouveau créneau
            $stmtCheck = $pdo->prepare("SELECT places_restante FROM creneau WHERE id_creneau = ?");
            $stmtCheck->execute([$id_creneau_choisi]);
            $creneau_destination = $stmtCheck->fetch();
            
            if ($creneau_destination['places_restante'] < $nb_personnes) {
                throw new Exception("Désolé, il n'y a plus assez de places libres sur ce créneau horaire.");
            }
            
            // Restituer les places à l'ancien créneau
            $stmtRestituer = $pdo->prepare("UPDATE creneau SET places_restante = places_restante + ? WHERE id_creneau = ?");
            $stmtRestituer->execute([$ancien_nb_personnes, $id_creneau_ancien]);
            
            // Prendre les places sur le nouveau créneau
            $stmtPrendre = $pdo->prepare("UPDATE creneau SET places_restante = places_restante - ? WHERE id_creneau = ?");
            $stmtPrendre->execute([$nb_personnes, $id_creneau_choisi]);
        } else {
            // Même créneau mais modification du nombre de personnes
            $difference = $nb_personnes - $ancien_nb_personnes;
            
            $stmtCheck = $pdo->prepare("SELECT places_restante FROM creneau WHERE id_creneau = ?");
            $stmtCheck->execute([$id_creneau_choisi]);
            $creneau_actuel = $stmtCheck->fetch();
            
            if ($difference > $creneau_actuel['places_restante']) {
                throw new Exception("Places insuffisantes pour ajouter autant de personnes.");
            }
            
            $stmtAjust = $pdo->prepare("UPDATE creneau SET places_restante = places_restante - ? WHERE id_creneau = ?");
            $stmtAjust->execute([$difference, $id_creneau_choisi]);
        }
        
        // Exécution de la mise à jour finale en base de données
        $stmtUpdate = $pdo->prepare("
            UPDATE inscription 
            SET id_creneau = ?, nb_personnes = ?, participe_buffet = ? 
            WHERE id_inscription = ?
        ");
        $stmtUpdate->execute([$id_creneau_choisi, $nb_personnes, $participe_buffet, $edit_id]);
        
        $pdo->commit();
        header("Location: gestion.php"); // Redirection vers l'espace compte
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// 4. RÉCUPÉRATION DES CRÉNEAUX ET DATES DE CETTE SALLE UNIQUEMENT
// Étape A : Dates disponibles pour cette salle
$dates_stmt = $pdo->prepare("
    SELECT DISTINCT d.* FROM date_expo d
    JOIN creneau c ON c.id_date = d.id_date
    WHERE c.id_salle = ?
    ORDER BY d.date ASC
");
$dates_stmt->execute([$id_salle_active]);
$liste_dates = $dates_stmt->fetchAll();

// Étape B : Créneaux horaires disponibles pour cette salle
$queryCreneaux = "
    SELECT c.id_creneau, c.id_date, c.heure_debut, c.heure_fin, c.places_restante
    FROM creneau c
    WHERE c.id_salle = ?
    ORDER BY c.heure_debut ASC
";
$creneaux_stmt = $pdo->prepare($queryCreneaux);
$creneaux_stmt->execute([$id_salle_active]);
$tous_les_creneaux = $creneaux_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-LLUSION — Modifier ma réservation</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="style.css"/>
    <style>
        .form-inscription-container { max-width: 750px; margin: 5rem auto; padding: 2.5rem; background: var(--white); border: 1px solid var(--cyan); clip-path: polygon(0 0, calc(100% - 16px) 0, 100% 16px, 100% 100%, 16px 100%, 0 calc(100% - 16px)); position: relative;}
        .form-inscription-container::before { content: ''; position: absolute; top: 0; left: 0; width: 28px; height: 28px; border-top: 2px solid var(--cyan); border-left: 2px solid var(--cyan); }
        
        h1 { font-family: 'Space Mono', monospace; text-transform: uppercase; margin-bottom: 0.2rem; font-size: 2rem; }
        h1 span { color: var(--cyan); }
        .salle-badge-title { font-family: 'Space Mono', monospace; background: var(--ink); color: var(--cyan); padding: 0.2rem 0.6rem; font-size: 0.85rem; font-weight: bold; display: inline-block; margin-bottom: 2rem; text-transform: uppercase;}
        
        /* En-têtes des Rubriques (Cohérence esthétique complète) */
        .rubrique-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; margin-top: 2rem; }
        .rubrique-num { background: var(--ink); color: var(--cyan); font-family: 'Space Mono', monospace; font-weight: bold; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; }
        .rubrique-title { font-family: 'Space Mono', monospace; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--ink); font-weight: bold; }

        /* Grilles de cartes d'options */
        .options-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .radio-card { position: relative; border: 1px solid #e0e0e0; padding: 1.2rem; background: #fff; cursor: pointer; transition: 0.2s ease; }
        .radio-card input[type="radio"] { position: absolute; top: 12px; right: 12px; accent-color: var(--cyan); cursor: pointer; }
        
        .radio-card:hover { border-color: var(--cyan); background: #fafafa; }
        .radio-card.active { border: 2px solid var(--cyan); background: var(--cyan-light); }
        
        .card-main-text { font-family: 'Space Mono', monospace; font-weight: bold; font-size: 1.1rem; color: var(--ink); display: block; margin-bottom: 0.3rem; }
        .card-sub-text { font-family: 'DM Sans', sans-serif; font-size: 0.85rem; color: #666; display: block; }
        .card-places-badge { display: inline-block; font-family: 'Space Mono', monospace; font-size: 0.75rem; background: var(--ink); color: #fff; padding: 0.1rem 0.4rem; margin-top: 0.6rem; }
        .radio-card.active .card-places-badge { background: var(--cyan); color: var(--ink); font-weight: bold; }

        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #ccc; font-family: 'DM Sans', sans-serif; font-size: 1rem; box-sizing: border-box; }

        /* Style de la Rubrique Buffet */
        .buffet-selection-box { border: 1px solid #e0e0e0; padding: 1.5rem; background: #fff; display: flex; align-items: flex-start; gap: 1rem; cursor: pointer; }
        .buffet-selection-box:hover { border-color: var(--cyan); }
        .buffet-selection-box input[type="checkbox"] { width: 22px; height: 22px; accent-color: var(--cyan); cursor: pointer; margin-top: 0.2rem; }
        .buffet-label { cursor: pointer; font-family: 'DM Sans', sans-serif; font-size: 1rem; color: var(--ink); line-height: 1.4; }
        .buffet-label strong { font-family: 'Space Mono', monospace; display: block; color: #d9534f; font-size: 0.8rem; margin-top: 0.4rem; text-transform: uppercase; }

        .btn-submit { background: var(--cyan); color: var(--ink); border: none; padding: 1.2rem; font-family: 'Space Mono', monospace; font-weight: bold; cursor: pointer; text-transform: uppercase; transition: 0.3s; width: 100%; font-size: 1rem; margin-top: 2rem; letter-spacing: 0.05em; }
        .btn-submit:hover { background: var(--ink); color: var(--cyan); }
        .alert-error { background: #fdf2f2; border-left: 4px solid #d9534f; padding: 1rem; font-family: 'Space Mono'; margin-bottom: 1.5rem; color: #d9534f; }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; color: #666; font-family: 'Space Mono'; font-size: 0.85rem; text-decoration: underline; }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="form-inscription-container">

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <h1>Modifier mes choix<span>.</span></h1>
    <div class="salle-badge-title">// Salle <?= $infos_salle['numero'] ?> : <?= htmlspecialchars($infos_salle['nom_thematique']) ?></div>

    <form method="POST" action="">
        
        <div class="rubrique-header">
            <div class="rubrique-num">01</div>
            <div class="rubrique-title">Choisir la date de visite</div>
        </div>
        <div class="options-grid">
            <?php foreach ($liste_dates as $d): ?>
                <?php 
                    $date_id_actuel = $reservation_actuelle['id_date'];
                    $is_checked = ($d['id_date'] == $date_id_actuel) ? 'checked' : '';
                    $is_active_class = ($d['id_date'] == $date_id_actuel) ? 'active' : '';
                ?>
                <label class="radio-card date-card <?= $is_active_class ?>" data-date-id="<?= $d['id_date'] ?>">
                    <input type="radio" name="select_date_radio" value="<?= $d['id_date'] ?>" <?= $is_checked ?>>
                    <span class="card-main-text"><?= date('d/m/Y', strtotime($d['date'])) ?></span>
                    <span class="card-sub-text">Exposition Multimédia</span>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="rubrique-header">
            <div class="rubrique-num">02</div>
            <div class="rubrique-title">Horaires disponibles (Salle <?= $infos_salle['numero'] ?>)</div>
        </div>
        <div class="options-grid" id="horaires-container">
            </div>

        <div class="rubrique-header">
            <div class="rubrique-num">03</div>
            <div class="rubrique-title">Nombre de visiteurs</div>
        </div>
        <div style="margin-bottom: 2rem;">
            <input type="number" id="nb_personnes" name="nb_personnes" class="form-control" min="1" max="10" 
                   value="<?= intval($reservation_actuelle['nb_personnes']) ?>" required>
        </div>

        <div class="rubrique-header">
            <div class="rubrique-num">04</div>
            <div class="rubrique-title">Option Buffet de fin de journée</div>
        </div>
        <label class="buffet-selection-box">
            <input type="checkbox" id="participe_buffet" name="participe_buffet" 
                   <?= ($reservation_actuelle['participe_buffet'] == 1) ? 'checked' : '' ?>>
            <div class="buffet-label">
                Je souhaite participer au buffet de fin de journée.
                <strong>💡 Pour enlever le buffet, décochez simplement cette case avant d'enregistrer.</strong>
            </div>
        </label>

        <button type="submit" class="btn-submit">Enregistrer mes modifications</button>
    </form>

    <a href="gestion.php" class="back-link">‹ Annuler et revenir à mon compte</a>
</div>

<script>
// On passe uniquement les créneaux de cette salle au script JS
const creneaux_data = <?= json_encode($tous_les_creneaux) ?>;
const id_creneau_actuel = <?= intval($reservation_actuelle['id_creneau']) ?>;

const horairesContainer = document.getElementById('horaires-container');
const dateCards = document.querySelectorAll('.date-card');

function filtrerEtAfficherHoraires(dateId) {
    horairesContainer.innerHTML = '';
    
    // Filtrer les créneaux par rapport à la date cliquée
    const creneauxFiltres = creneaux_data.filter(c => c.id_date == dateId);
    
    creneauxFiltres.forEach(c => {
        const h_deb = c.heure_debut.substring(0, 5);
        const h_fin = c.heure_fin.substring(0, 5);
        const isSelected = (c.id_creneau == id_creneau_actuel);
        
        const labelCard = document.createElement('label');
        labelCard.className = `radio-card horaire-card ${isSelected ? 'active' : ''}`;
        
        labelCard.innerHTML = `
            <input type="radio" name="id_creneau" value="${c.id_creneau}" ${isSelected ? 'checked' : ''} required>
            <span class="card-main-text">${h_deb} - ${h_fin}</span>
            <span class="card-sub-text">Créneau de visite</span>
            <span class="card-places-badge">${c.places_restante} places libres</span>
        `;
        
        labelCard.addEventListener('click', function() {
            document.querySelectorAll('.horaire-card').forEach(card => card.classList.remove('active'));
            labelCard.classList.add('active');
        });
        
        horairesContainer.appendChild(labelCard);
    });
}

// Clic sur les cartes de date
dateCards.forEach(card => {
    card.addEventListener('click', function() {
        dateCards.forEach(c => c.classList.remove('active'));
        card.classList.add('active');
        
        const radioInput = card.querySelector('input[type="radio"]');
        radioInput.checked = true;
        
        filtrerEtAfficherHoraires(radioInput.value);
    });
});

// Initialisation de la page sur la date sélectionnée par défaut
window.addEventListener('DOMContentLoaded', () => {
    const activeDateCard = document.querySelector('.date-card.active input[type="radio"]');
    if(activeDateCard) {
        filtrerEtAfficherHoraires(activeDateCard.value);
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>