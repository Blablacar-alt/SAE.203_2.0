<?php
session_start();
require_once 'db.php'; // Connexion à la base de données

$error = '';
$id_salle_active = null;

// 1. SÉCURITÉ : L'UTILISATEUR DOIT ÊTRE CONNECTÉ POUR ACCÉDER À CETTE PAGE
if (!isset($_SESSION['visiteur_id'])) {
    // S'il n'est pas connecté, on le renvoie vers la page de connexion
    header("Location: connexion.php");
    exit;
}

$id_visiteur = $_SESSION['visiteur_id'];

// 2. VÉRIFICATION : MODE MODIFICATION (via edit_id) OU CRÉATION (via salle) ?
$is_editing = false;
$edit_id = null;
$reservation_actuelle = null;

if (isset($_GET['edit_id'])) {
    $is_editing = true;
    $edit_id = intval($_GET['edit_id']);
    
    // On récupère la réservation pour trouver la salle associée
    $stmt = $pdo->prepare("
        SELECT i.*, c.id_date, c.id_salle, s.numero as numero_salle, s.nom_thematique
        FROM inscription i
        JOIN creneau c ON i.id_creneau = c.id_creneau
        JOIN salle s ON c.id_salle = s.id_salle
        WHERE i.id_inscription = ? AND i.id_visiteur = ?
    ");
    $stmt->execute([$edit_id, $id_visiteur]);
    $reservation_actuelle = $stmt->fetch();
    
    if ($reservation_actuelle) {
        $id_salle_active = $reservation_actuelle['id_salle'];
    } else {
        // Si la réservation n'existe pas ou ne lui appartient pas
        header("Location: gestion.php");
        exit;
    }
} elseif (isset($_GET['salle'])) {
    // Mode inscription classique depuis salles.php (Ex: ?salle=Salle%20005)
    $salle_cible = htmlspecialchars($_GET['salle']); 
    $numero_salle_propre = trim(str_replace('Salle', '', $salle_cible));

    $stmtSalle = $pdo->prepare("SELECT id_salle FROM salle WHERE numero = ?");
    $stmtSalle->execute([$numero_salle_propre]);
    $salle_trouvee = $stmtSalle->fetch();
    
    if ($salle_trouvee) {
        $id_salle_active = $salle_trouvee['id_salle'];
    }
}

// Sécurité au cas où aucune salle n'est détectée
if (!$id_salle_active) {
    header("Location: gestion.php");
    exit;
}

// Récupération des informations de la salle pour l'affichage du titre
$stmtSalleActive = $pdo->prepare("SELECT * FROM salle WHERE id_salle = ?");
$stmtSalleActive->execute([$id_salle_active]);
$infos_salle = $stmtSalleActive->fetch();


// 3. TRAITEMENT DU FORMULAIRE EN CAS DE SOUMISSION (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_creneau_choisi = intval($_POST['id_creneau']);
    $nb_personnes = intval($_POST['nb_personnes']);
    $participe_buffet = isset($_POST['participe_buffet']) ? 1 : 0; // 1 si coché, 0 si décoché

    if ($is_editing) {
        // --- LOGIQUE DE MODIFICATION (UPDATE) ---
        $id_creneau_ancien = $reservation_actuelle['id_creneau'];
        $ancien_nb_personnes = $reservation_actuelle['nb_personnes'];
        
        $pdo->beginTransaction();
        try {
            if ($id_creneau_choisi != $id_creneau_ancien) {
                // Changement de créneau : Vérifier places dispo
                $stmtCheck = $pdo->prepare("SELECT places_restante FROM creneau WHERE id_creneau = ?");
                $stmtCheck->execute([$id_creneau_choisi]);
                $creneau_destination = $stmtCheck->fetch();
                
                if ($creneau_destination['places_restante'] < $nb_personnes) {
                    throw new Exception("Plus assez de places disponibles sur ce créneau.");
                }
                
                // Restituer les anciennes places et prendre les nouvelles
                $stmtRestituer = $pdo->prepare("UPDATE creneau SET places_restante = places_restante + ? WHERE id_creneau = ?");
                $stmtRestituer->execute([$ancien_nb_personnes, $id_creneau_ancien]);
                
                $stmtPrendre = $pdo->prepare("UPDATE creneau SET places_restante = places_restante - ? WHERE id_creneau = ?");
                $stmtPrendre->execute([$nb_personnes, $id_creneau_choisi]);
            } else {
                // Même créneau, ajustement du nombre de personnes
                $difference = $nb_personnes - $ancien_nb_personnes;
                $stmtCheck = $pdo->prepare("SELECT places_restante FROM creneau WHERE id_creneau = ?");
                $stmtCheck->execute([$id_creneau_choisi]);
                $creneau_actuel = $stmtCheck->fetch();
                
                if ($difference > $creneau_actuel['places_restante']) {
                    throw new Exception("Places insuffisantes pour augmenter votre groupe.");
                }
                
                $stmtAjust = $pdo->prepare("UPDATE creneau SET places_restante = places_restante - ? WHERE id_creneau = ?");
                $stmtAjust->execute([$difference, $id_creneau_choisi]);
            }
            
            // Appliquer les changements
            $stmtUpdate = $pdo->prepare("UPDATE inscription SET id_creneau = ?, nb_personnes = ?, participe_buffet = ? WHERE id_inscription = ?");
            $stmtUpdate->execute([$id_creneau_choisi, $nb_personnes, $participe_buffet, $edit_id]);
            
            $pdo->commit();
            header("Location: gestion.php"); // Retour à l'espace compte
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    } else {
        // --- LOGIQUE D'INSCRIPTION CLASSIQUE (INSERT) ---
        // (Mettez ici votre code d'insertion INSERT INTO inscription habituel si besoin)
    }
}

// 4. RÉCUPÉRATION DES DATES ET DES CRÉNEAUX DE CETTE SALLE UNIQUEMENT
// Étape A : Dates disponibles pour cette salle
$dates_stmt = $pdo->prepare("
    SELECT DISTINCT d.* FROM date_expo d
    JOIN creneau c ON c.id_date = d.id_date
    WHERE c.id_salle = ?
    ORDER BY d.date ASC
");
$dates_stmt->execute([$id_salle_active]);
$liste_dates = $dates_stmt->fetchAll();

// Étape B : Créneaux horaires de cette salle
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
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-LLUSION — Inscription</title>
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css"/>
  <style>
    .creneau-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:.5rem; margin-top:.5rem; }
    .creneau-btn {
      border:1.5px solid #ddd; border-radius:8px; padding:.5rem .4rem;
      text-align:center; font-family:'DM Sans',sans-serif; font-size:.8rem;
      cursor:pointer; background:var(--white); transition:all .2s; line-height:1.4;
    }
    .creneau-btn:hover:not(:disabled) { border-color:var(--mint-dark); }
    .creneau-btn.selected { border-color:var(--mint-dark); background:var(--mint); font-weight:600; }
    .creneau-btn:disabled { opacity:.4; cursor:not-allowed; background:#f5f5f5; }
    .creneau-btn small { display:block; font-size:.7rem; color:#888; }
    .buffet-group { display:none; align-items:center; gap:.75rem; margin-top:.5rem; }
    .error-msg { background:#ffe5e5; border:1px solid #e0302a; border-radius:10px; padding:.85rem 1.2rem; font-size:.88rem; color:#b00000; margin-bottom:1rem; }
    .success-msg { background:#d4f5ee; border:1px solid #7de0d2; border-radius:10px; padding:1rem 1.2rem; font-size:.9rem; color:#005c52; margin-bottom:1rem; }
    .day-grid { display:flex; gap:.75rem; flex-wrap:wrap; }
    .day-btn { border:1.5px solid #ddd; border-radius:8px; padding:.6rem 1rem; background:var(--white); cursor:pointer; font-family:'DM Sans',sans-serif; font-size:.85rem; font-weight:500; transition:all .2s; }
    .day-btn.selected { border-color:var(--mint-dark); background:var(--mint); font-weight:600; }
  </style>
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<main>
  <div class="page-header">
    <h1>INSCRIPTION</h1>
    <p>Réservez votre créneau pour l'exposition E-LLUSION</p>
  </div>
  

  <div class="form-wrapper">
    <div class="form-card">

      <?php if ($success): ?>
        <div class="success-msg">
          ✅ <strong>Inscription confirmée !</strong><br/>
          Votre token de modification : <code style="background:#b2f0e8;padding:.1rem .4rem;border-radius:4px"><?= htmlspecialchars($success) ?></code><br/>
          <small>Conservez-le pour modifier ou annuler votre réservation.</small>
        </div>
      <?php else: ?>

        <?php if ($error): ?>
          <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="inscriptionForm" onsubmit="return validateForm()">
          <input type="hidden" name="id_creneau" id="id_creneau_hidden" value="<?= $prefill_creneau ?>"/>
          <input type="hidden" name="date"  id="date_hidden"  value=""/>
          <input type="hidden" name="salle" id="salle_hidden" value="<?= htmlspecialchars($prefill_salle) ?>"/>

          <p class="form-section-title">Informations personnelles</p>

          <div class="form-row">
            <div class="form-group">
              <label>Nom *</label>
              <input type="text" name="nom" placeholder="Votre nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label>Prénom *</label>
              <input type="text" name="prenom" placeholder="Votre prénom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" placeholder="votre.email@exemple.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>

          <div class="form-group">
            <label>Qui êtes-vous ? *</label>
            <select name="profil" id="profil" onchange="toggleBuffet()" required>
              <option value="">— Sélectionnez —</option>
              <?php
              $profils = ['Étudiant·e', 'Enseignant·e', "Personnel de l'université", 'Visiteur·euse extérieur·e', 'Professionnels/partenaires'];
              foreach ($profils as $p):
              ?>
              <option <?= (($_POST['profil'] ?? '') === $p) ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="buffet-group" id="buffetGroup">
            <input type="checkbox" name="participe_buffet" id="participe_buffet"
              <?= isset($_POST['participe_buffet']) ? 'checked' : '' ?>
              style="width:16px;height:16px"/>
            <label for="participe_buffet" style="font-size:.88rem;cursor:pointer">
              Participer au buffet du jeudi 18 juin à 18h30
            </label>
          </div>

          <hr class="form-divider">
          <p class="form-section-title">Réservation</p>

          <div class="form-group">
            <label>Salle *</label>
            <select id="salle" onchange="onSalleChange()">
              <option value="">— Choisissez une salle —</option>
              <?php foreach ($salles as $s): ?>
                <option value="<?= $s['numero'] ?>"
                  <?= ($prefill_salle && strpos($prefill_salle, $s['numero']) !== false) ? 'selected' : '' ?>>
                  Salle <?= $s['numero'] ?> — <?= htmlspecialchars($s['nom_thematique']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Jour *</label>
            <div class="day-grid">
              <button type="button" class="day-btn" id="day1" onclick="selectDay('2026-06-18')">Jeudi 18 juin 2026</button>
              <button type="button" class="day-btn" id="day2" onclick="selectDay('2026-06-19')">Vendredi 19 juin 2026</button>
            </div>
          </div>

          <div class="form-group">
            <label>Nombre de personnes * <span style="color:#888;font-size:.8rem">(max. 12)</span></label>
            <input type="number" name="nb_personnes" id="nbPersonnes" min="1" max="12"
              placeholder="Ex : 3" value="<?= htmlspecialchars($_POST['nb_personnes'] ?? '') ?>"
              onchange="renderCreneaux()" required>
            <small style="color:#888;font-size:.8rem">Nombre total de participants, vous inclus·e</small>
          </div>

          <!-- Créneaux -->
          <div class="form-group" id="creneauxWrapper" style="display:none">
            <label>Créneau horaire *</label>
            <div id="creneauxContainer"></div>
          </div>

          <div class="info-box">
            <strong>ℹ️ Informations importantes :</strong><br/>
            Jauge limitée à 12 personnes par créneau et par salle<br/>
            Conservez votre token pour modifier ou annuler votre réservation
          </div>

          <button type="submit" class="btn-submit">Confirmer mon inscription</button>

          <p class="contact-hint">
            Pour toute question :
            <a href="mailto:contact.ellusion@univ-smb.fr">contact.ellusion@univ-smb.fr</a>
          </p>

        </form>

      <?php endif; ?>
    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const CRENEAUX_DATA = <?= $creneaux_json ?>;
let selectedDay   = null;
let selectedSalle = null;

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
  document.getElementById('salle_hidden').value = sel.value ? sel.options[sel.selectedIndex].text : '';
  document.getElementById('id_creneau_hidden').value = '';
  renderCreneaux();
}

function renderCreneaux() {
  const wrapper   = document.getElementById('creneauxWrapper');
  const container = document.getElementById('creneauxContainer');
  const nb        = parseInt(document.getElementById('nbPersonnes').value) || 1;

  if (!selectedSalle || !selectedDay) {
    wrapper.style.display = 'none';
    return;
  }

  const creneaux = (CRENEAUX_DATA[selectedSalle] || {})[selectedDay] || [];
  wrapper.style.display = 'block';

  if (creneaux.length === 0) {
    container.innerHTML = '<p style="color:#888;font-size:.85rem;padding:.5rem 0">Aucun créneau disponible pour cette salle / ce jour.</p>';
    return;
  }

  let html = '<div class="creneau-grid">';
  creneaux.forEach(function(c) {
    const disabled = c.places_restante < nb;
    const label    = c.heure_debut.slice(0,5) + '–' + c.heure_fin.slice(0,5);
    const badge    = disabled ? '<small>Complet</small>' : '<small>' + c.places_restante + ' place(s)</small>';
    html += '<button type="button" class="creneau-btn"'
          + (disabled ? ' disabled' : '')
          + ' onclick="selectCreneau(' + c.id_creneau + ', this)">'
          + label + badge + '</button>';
  });
  html += '</div>';
  container.innerHTML = html;
}

function selectCreneau(id, btn) {
  document.querySelectorAll('.creneau-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  document.getElementById('id_creneau_hidden').value = id;
}

const BUFFET_PROFILS = ["Enseignant·e", "Personnel de l'université", "Visiteur·euse extérieur·e", "Professionnels/partenaires"];
function toggleBuffet() {
  const profil = document.getElementById('profil').value;
  const group  = document.getElementById('buffetGroup');
  group.style.display = BUFFET_PROFILS.includes(profil) ? 'flex' : 'none';
  if (!BUFFET_PROFILS.includes(profil)) document.getElementById('participe_buffet').checked = false;
}

function validateForm() {
  const id = document.getElementById('id_creneau_hidden').value;
  if (!id) { alert('Merci de sélectionner un créneau horaire.'); return false; }
  return true;
}

// Pré-remplissage depuis URL
(function() {
  const params = new URLSearchParams(window.location.search);
  const s = params.get('salle');
  if (s) {
    const match = s.match(/(\d+)/);
    if (match) {
      const sel = document.getElementById('salle');
      for (let opt of sel.options) {
        if (opt.value === match[1] || opt.value.replace(/^0+/,'') === match[1].replace(/^0+/,'')) {
          sel.value = opt.value;
          selectedSalle = opt.value;
          document.getElementById('salle_hidden').value = opt.text;
          break;
        }
      }
    }
  }
  const c = params.get('creneau');
  if (c) document.getElementById('id_creneau_hidden').value = c;

  toggleBuffet();
  renderCreneaux();
})();
</script>

</body>
</html>