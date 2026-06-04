<?php
session_start();
require_once 'db.php';

// Sécurité : Si le visiteur n'est pas connecté, retour à la page de connexion
if (!isset($_SESSION['visiteur_id'])) {
    header("Location: connexion.php");
    exit;
}

$id_visiteur = $_SESSION['visiteur_id'];
$error = '';
$success = '';

// FONCTION POUR ENVOYER UN EMAIL EN HTML AUX ACCOMPAGNANTS AJOUTÉS
function envoyerEmailAccompagnant($to, $prenom, $nom, $nom_salle, $date_choisie, $heure_deb, $heure_fin) {
    $subject = "=?UTF-8?B?".base64_encode("Votre invitation pour l'exposition E-LLUSION")."?=";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: E-LLUSION <noreply@univ-savoie.fr>" . "\r\n";
    
    $date_formattee = date("d/m/2026", strtotime($date_choisie));
    $heure_deb_f = substr($heure_deb, 0, 5);
    $heure_fin_f = substr($heure_fin, 0, 5);

    $message = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f7f6; color: #222; margin: 0; padding: 20px; }
            .card { max-width: 550px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
            .header { background: linear-gradient(135deg, #111b21 0%, #00b8a0 100%); color: white; padding: 30px; text-align: center; }
            .header h2 { margin: 0; font-size: 24px; letter-spacing: 2px; }
            .content { padding: 25px; line-height: 1.6; }
            .details-box { background: #e6f8f6; border-left: 4px solid #00b8a0; padding: 15px; margin: 20px 0; border-radius: 0 8px 8px 0; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="header">
                <h2>E-LLUSION</h2>
                <p style="margin:5px 0 0 0; opacity:0.8; font-size:12px; text-transform:uppercase;">Exposition Immersive</p>
            </div>
            <div class="content">
                <p>Bonjour <strong>'.htmlspecialchars($prenom).' '.htmlspecialchars($nom).'</strong>,</p>
                <p>Une place vous a été réservée pour l\'exposition événement <strong>E-LLUSION</strong> à l\'IUT de Chambéry.</p>
                
                <div class="details-box">
                    🏛️ <strong>Salle :</strong> '.htmlspecialchars($nom_salle).'<br>
                    📅 <strong>Date :</strong> '.$date_formattee.'<br>
                    ⏱️ <strong>Créneau :</strong> de '.$heure_deb_f.' à '.$heure_fin_f.'
                </div>
                
                <p style="font-size: 13px; color: #666;">Nous vous prions de vous présenter 5 minutes avant le début de votre séance.</p>
            </div>
        </div>
    </body>
    </html>';

    @mail($to, $subject, $message, $headers);
}

// TRAITEMENT DU FORMULAIRE D'INSCRIPTION (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_creneau_choisi = isset($_POST['id_creneau']) ? intval($_POST['id_creneau']) : 0;
    $nb_personnes = isset($_POST['nb_personnes']) ? intval($_POST['nb_personnes']) : 1;
    $participe_buffet = isset($_POST['participe_buffet']) ? 1 : 0;
    $accompagnants = isset($_POST['accompagnants']) ? $_POST['accompagnants'] : [];

    if (empty($id_creneau_choisi)) {
        $error = "Veuillez sélectionner un créneau horaire pour valider votre inscription.";
    } else {
        $pdo->beginTransaction();
        try {
            // Vérification de la disponibilité des places
            $stmtCheck = $pdo->prepare("
                SELECT c.*, s.nom_thematique, d.date 
                FROM creneau c 
                JOIN salle s ON c.id_salle = s.id_salle 
                JOIN date_expo d ON c.id_date = d.id_date 
                WHERE c.id_creneau = ?
            ");
            $stmtCheck->execute([$id_creneau_choisi]);
            $creneau = $stmtCheck->fetch();

            if (!$creneau) {
                throw new Exception("Le créneau sélectionné n'existe pas.");
            }

            if ($creneau['places_restante'] < $nb_personnes) {
                throw new Exception("Désolé, il ne reste plus que " . $creneau['places_restante'] . " place(s) sur ce créneau. Votre demande de " . $nb_personnes . " places ne peut aboutir.");
            }

            // Un jeton (token) unique est généré pour lier le groupe ensemble
            $token_groupe = md5(uniqid(rand(), true));

            // 1. Enregistrement de l'utilisateur principal
            $stmtMain = $pdo->prepare("
                INSERT INTO inscription (id_visiteur, id_creneau, nb_personnes, date_inscription, statut, token_modification, participe_buffet) 
                VALUES (?, ?, 1, NOW(), 'confirmé', ?, ?)
            ");
            $stmtMain->execute([$id_visiteur, $id_creneau_choisi, $token_groupe, $participe_buffet]);

            // 2. Enregistrement de chaque accompagnant
            foreach ($accompagnants as $acc) {
                $acc_nom = trim($acc['nom'] ?? '');
                $acc_prenom = trim($acc['prenom'] ?? '');
                $acc_email = trim($acc['email'] ?? '');

                if (!empty($acc_nom) && !empty($acc_prenom)) {
                    // Création ou récupération du compte visiteur de l'accompagnant
                    $stmtAccVisiteur = $pdo->prepare("
                        INSERT INTO visiteur (nom, prenom, email, profil) 
                        VALUES (?, ?, ?, 'Accompagnant') 
                        ON DUPLICATE KEY UPDATE id_visiteur=LAST_INSERT_ID(id_visiteur)
                    ");
                    $stmtAccVisiteur->execute([$acc_nom, $acc_prenom, !empty($acc_email) ? $acc_email : null]);
                    $id_acc_visiteur = $pdo->lastInsertId();

                    // Liaison de l'accompagnant à la réservation
                    $stmtAccInsc = $pdo->prepare("
                        INSERT INTO inscription (id_visiteur, id_creneau, nb_personnes, date_inscription, statut, token_modification, participe_buffet) 
                        VALUES (?, ?, 1, NOW(), 'confirmé', ?, 0)
                    ");
                    $stmtAccInsc->execute([$id_acc_visiteur, $id_creneau_choisi, $token_groupe]);

                    // Envoi d'un e-mail si une adresse valide est transmise
                    if (!empty($acc_email)) {
                        envoyerEmailAccompagnant($acc_email, $acc_prenom, $acc_nom, $creneau['nom_thematique'], $creneau['date'], $creneau['heure_debut'], $creneau['heure_fin']);
                    }
                }
            }

            // 3. Déduction du nombre de places globales réservées
            $stmtUpdatePlaces = $pdo->prepare("UPDATE creneau SET places_restante = places_restante - ? WHERE id_creneau = ?");
            $stmtUpdatePlaces->execute([$nb_personnes, $id_creneau_choisi]);

            $pdo->commit();
            $success = "Votre inscription a bien été enregistrée ! Vous pouvez consulter vos billets depuis votre espace compte.";
            
            // Redirection vers la page de gestion après 2 secondes
            header("Refresh: 2; url=gestion.php");

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// CHARGEMENT DES DONNÉES DEPUIS LA BDD POUR L'INTERFACE DYNAMIQUE
$salles = $pdo->query("SELECT * FROM salle ORDER BY numero ASC")->fetchAll();

$all_creneaux = $pdo->query("
    SELECT c.id_creneau, s.numero as numero_salle, d.date as date_texte, c.heure_debut, c.heure_fin, c.places_restante 
    FROM creneau c 
    JOIN salle s ON c.id_salle = s.id_salle 
    JOIN date_expo d ON c.id_date = d.id_date 
    ORDER BY c.heure_debut ASC
")->fetchAll();

// Structuration des créneaux en tableau associatif pour injection au JavaScript
$creneaux_json_data = [];
foreach ($all_creneaux as $c) {
    $creneaux_json_data[$c['numero_salle']][$c['date_texte']][] = [
        'id_creneau' => $c['id_creneau'],
        'heure_debut' => $c['heure_debut'],
        'heure_fin' => $c['heure_fin'],
        'places_restante' => $c['places_restante']
    ];
}
$creneaux_json = json_encode($creneaux_json_data);

// Récupération du profil utilisateur connecté pour vérifier l'accès au buffet
$stmtP = $pdo->prepare("SELECT profil FROM visiteur WHERE id_visiteur = ?");
$stmtP->execute([$id_visiteur]);
$profil_visiteur = $stmtP->fetchColumn() ?: '';
$buffet_autorise = in_array($profil_visiteur, ["Enseignant·e", "Personnel de l'université", "Visiteur·euse extérieur·e", "Professionnels/partenaires"]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>E-LLUSION — Formulaire de réservation</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="style.css"/>
    <style>
        .booking-container { max-width: 700px; margin: 4rem auto; padding: 2rem; background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; }
        .form-group { margin-bottom: 2rem; display: flex; flex-direction: column; gap: .5rem; font-family: 'DM Sans'; }
        .form-group label { font-weight: 500; font-size: .95rem; color: #111; }
        .form-group select, .form-group input[type="text"], .form-group input[type="email"] { padding: .75rem; border: 1px solid #ccc; border-radius: 6px; font-family: 'DM Sans'; font-size: 0.95rem; background: #fff; }
        .form-group select:focus, .form-group input:focus { border-color: #00b8a0; outline: none; box-shadow: 0 0 0 3px rgba(0, 184, 160, 0.1); }
        
        /* Grille des boutons de dates */
        .day-grid { display: flex; gap: .75rem; flex-wrap: wrap; }
        .day-btn { border: 1.5px solid #ddd; border-radius: 8px; padding: .6rem 1.2rem; background: #fff; cursor: pointer; font-family: 'DM Sans'; font-size: .9rem; font-weight: 500; transition: all .2s; }
        .day-btn.selected { border-color: #00b8a0; background: #e0faf7; color: #007a6a; font-weight: 600; }
        
        /* Contrôle d'incrémentation du nombre de personnes */
        .number-stepper { display: flex; align-items: center; gap: .5rem; }
        .btn-step { background: #fff; border: 1.5px solid #ddd; color: #222; font-family: 'Space Mono'; font-weight: bold; font-size: 1.2rem; width: 42px; height: 42px; border-radius: 6px; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: 0.2s; }
        .btn-step:hover { border-color: #00b8a0; color: #00b8a0; }
        .number-stepper input { width: 60px; text-align: center; font-family: 'Space Mono'; font-size: 1rem; font-weight: bold; padding: .65rem; border: 1.5px solid #ddd; border-radius: 6px; background: #fdfdfd; }
        
        /* Fiche d'un accompagnant */
        .acc-card { border-left: 4px solid #00b8a0; background: #f9fbfb; padding: 1.25rem; margin-top: 1rem; border-radius: 0 8px 8px 0; border-top: 1px solid #edf2f2; border-right: 1px solid #edf2f2; border-bottom: 1px solid #edf2f2; }
        
        /* Grille des créneaux horaires */
        .creneau-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .75rem; margin-top: .5rem; }
        .creneau-btn { border: 1.5px solid #ddd; border-radius: 8px; padding: .75rem .5rem; text-align: center; font-family: 'DM Sans'; font-size: .85rem; cursor: pointer; background: #fff; transition: all .2s; line-height: 1.4; }
        .creneau-btn small { display: block; font-size: 0.75rem; color: #666; margin-top: 2px; }
        .creneau-btn.selected { border-color: #00b8a0; background: #e0faf7; color: #007a6a; font-weight: 600; }
        .creneau-btn:disabled { opacity: .4; cursor: not-allowed; background: #f5f5f5; border-color: #e0e0e0; color: #999; }
        .creneau-btn:disabled small { color: #999; }
        
        /* Notifications */
        .alert-msg { background: #ffe5e5; border: 1px solid #e0302a; border-radius: 8px; padding: 1rem; color: #b00000; font-family: 'DM Sans'; margin-bottom: 1.5rem; font-size: 0.95rem; }
        .success-banner { background: #d4f5ee; border: 1px solid #7de0d2; border-radius: 8px; padding: 1rem; color: #005c52; font-family: 'DM Sans'; margin-bottom: 1.5rem; font-size: 0.95rem; text-align: center; font-weight: 500; }
        
        .btn-submit-booking { display: block; width: 100%; background: #00b8a0; color: #fff; border: none; font-family: 'Space Mono'; font-weight: bold; padding: 1rem; text-transform: uppercase; cursor: pointer; margin-top: 2rem; border-radius: 6px; font-size: 1rem; transition: background 0.2s; letter-spacing: 1px; }
        .btn-submit-booking:hover { background: #009682; }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="booking-container">
    <h1 style="font-family: 'Space Mono'; text-transform: uppercase; margin-top:0; font-size:1.8rem; margin-bottom:2rem;">Nouvelle réservation<span>.</span></h1>

    <?php if ($success): ?>
        <div class="success-banner">🎉 <?= $success ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-msg">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateForm()">
        <input type="hidden" name="id_creneau" id="id_creneau_hidden" value="">
        <input type="hidden" name="date" id="date_hidden" value="2026-06-18">

        <div class="form-group">
            <label for="salle">Sélectionner une salle *</label>
            <select id="salle" onchange="onSalleChange()" required>
                <option value="">— Sélectionner le thème de l'exposition —</option>
                <?php foreach ($salles as $s): ?>
                    <option value="<?= $s['numero'] ?>">Salle <?= $s['numero'] ?> — <?= htmlspecialchars($s['nom_thematique']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Sélectionner la date de visite *</label>
            <div class="day-grid">
                <button type="button" class="day-btn selected" id="day1" onclick="selectDay('2026-06-18')">Jeudi 18 juin 2026</button>
                <button type="button" class="day-btn" id="day2" onclick="selectDay('2026-06-19')">Vendredi 19 juin 2026</button>
            </div>
        </div>

        <div class="form-group">
            <label>Nombre total de places requises * <span style="color:#666; font-size:.8rem; font-weight:normal;">(Vous inclus, max. 12 personnes)</span></label>
            <div class="number-stepper">
                <button type="button" class="btn-step" onclick="changeQuantity(-1)">-</button>
                <input type="number" name="nb_personnes" id="nbPersonnes" min="1" max="12" value="1" readonly required>
                <button type="button" class="btn-step" onclick="changeQuantity(1)">+</button>
            </div>
        </div>

        <div id="accompagnantsWrapper"></div>

        <?php if ($buffet_autorise): ?>
            <div class="form-group" style="flex-direction: row; align-items: center; gap: .6rem; margin-top: 2rem; background: #fcfdfd; border: 1px solid #e2eceb; padding: 1rem; border-radius: 8px;">
                <input type="checkbox" name="participe_buffet" id="participe_buffet" style="width: 18px; height: 18px; margin:0; cursor:pointer;">
                <label for="participe_buffet" style="cursor:pointer; font-size: .9rem; font-weight: 500; margin:0;">
                    Je souhaite participer au buffet de vernissage <span style="color:#00b8a0; font-weight:bold;">(Jeudi soir à partir de 18h30)</span>
                </label>
            </div>
        <?php endif; ?>

        <div class="form-group" id="creneauxWrapper" style="display:none; margin-top: 2rem;">
            <label style="font-weight: bold; color: #007a6a; text-transform: uppercase; font-family: 'Space Mono'; font-size:0.85rem; letter-spacing:0.5px;">// Sélectionner un horaire disponible *</label>
            <div id="creneauxContainer"></div>
        </div>

        <button type="submit" class="btn-submit-booking">Confirmer et réserver les places</button>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// Injection des données de créneaux préparées par PHP
const CRENEAUX_DATA = <?= $creneaux_json ?>;
let selectedDay = '2026-06-18';
let selectedSalle = null;

function changeQuantity(amount) {
    const input = document.getElementById('nbPersonnes');
    let val = parseInt(input.value) || 1;
    val += amount;
    if (val < 1) val = 1;
    if (val > 12) val = 12;
    input.value = val;
    gererChampsAccompagnants();
}

function gererChampsAccompagnants() {
    const nbTotal = parseInt(document.getElementById('nbPersonnes').value) || 1;
    const wrapper = document.getElementById('accompagnantsWrapper');
    wrapper.innerHTML = '';
    
    if (nbTotal > 1) {
        let html = '<p style="font-family:\'Space Mono\'; font-size:.9rem; margin-top:2rem; margin-bottom:0.5rem; color:#007a6a; font-weight: bold;">// Identité des accompagnants</p>';
        for (let i = 1; i < nbTotal; i++) {
            html += `
                <div class="acc-card">
                    <div style="font-weight:700; font-family:'Space Mono'; font-size:.8rem; text-transform:uppercase; color:#555; margin-bottom:.75rem;">Accompagnant n°${i}</div>
                    <div style="display:flex; gap:1rem; margin-bottom:0.75rem;">
                        <div class="form-group" style="flex:1; margin-bottom:0;">
                            <label style="font-size:0.85rem;">Nom *</label>
                            <input type="text" name="accompagnants[${i}][nom]" required>
                        </div>
                        <div class="form-group" style="flex:1; margin-bottom:0;">
                            <label style="font-size:0.85rem;">Prénom *</label>
                            <input type="text" name="accompagnants[${i}][prenom]" required>
                        </div>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label style="font-size:0.85rem;">Adresse e-mail <span style="color:#777; font-weight:normal;">(Optionnel — pour envoi du billet)</span></label>
                        <input type="email" name="accompagnants[${i}][email]">
                    </div>
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
    const sel = document.getElementById('salle');
    selectedSalle = sel.value || null;
    document.getElementById('id_creneau_hidden').value = '';
    renderCreneaux();
}

function renderCreneaux() {
    const wrapper = document.getElementById('creneauxWrapper');
    const container = document.getElementById('creneauxContainer');
    const nb = parseInt(document.getElementById('nbPersonnes').value) || 1;

    if (!selectedSalle || !selectedDay) {
        wrapper.style.display = 'none';
        return;
    }

    const creneaux = (CRENEAUX_DATA[selectedSalle] || {})[selectedDay] || [];
    wrapper.style.display = 'block';

    if (creneaux.length === 0) {
        container.innerHTML = '<p style="color:#888; font-size:.85rem; padding:.5rem 0; font-family:\'DM Sans\';">Aucun créneau horaire programmé pour cette configuration.</p>';
        return;
    }

    let html = '<div class="creneau-grid">';
    creneaux.forEach(function(c) {
        const disabled = c.places_restante < nb;
        const label = c.heure_debut.slice(0,5) + ' – ' + c.heure_fin.slice(0,5);
        const badge = disabled ? 'Complet' : c.places_restante + ' pl. dispos';
        
        html += `<button type="button" class="creneau-btn" id="cbtn-${c.id_creneau}" ${disabled ? 'disabled' : ''} onclick="selectCreneau(${c.id_creneau})">
                    <strong>${label}</strong>
                    <small>${badge}</small>
                 </button>`;
    });
    html += '</div>';
    container.innerHTML = html;
}

function selectCreneau(id) {
    document.querySelectorAll('.creneau-btn').forEach(b => b.classList.remove('selected'));
    const targetBtn = document.getElementById('cbtn-' + id);
    if (targetBtn) targetBtn.classList.add('selected');
    document.getElementById('id_creneau_hidden').value = id;
}

function validateForm() {
    const id = document.getElementById('id_creneau_hidden').value;
    if (!id) { 
        alert('Veuillez sélectionner un horaire de visite en cliquant sur l\'une des cases.'); 
        return false; 
    }
    return true;
}

// Initialisation au chargement de la page
window.addEventListener('DOMContentLoaded', () => {
    onSalleChange();
    selectDay(selectedDay);
});
</script>
</body>
</html>