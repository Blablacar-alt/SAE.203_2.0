<?php
session_start();

// ── Connexion BDD ──────────────────────────────────────────────
$host    = 'localhost';
$db      = 'sae203';
$user    = 'root';
$pass    = 'local';
$charset = 'utf8';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]); 
} catch (PDOException $e) {
    die("Erreur BDD : " . $e->getMessage());
}

// ── Salles définies en dur ──────────────────────────────────────
$salles = [
    ['id_salle' => 1, 'numero' => '001', 'nom_thematique' => 'Les Miroirs du Réel'],
    ['id_salle' => 2, 'numero' => '002', 'nom_thematique' => "L'Envers du Décor"],
    ['id_salle' => 3, 'numero' => '005', 'nom_thematique' => 'Données & Fantômes'],
    ['id_salle' => 4, 'numero' => '021', 'nom_thematique' => 'Le Théâtre des Algorithmes'],
];

$success = '';
$error   = '';

// ── Traitement du formulaire ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom        = trim($_POST['nom']        ?? '');
    $prenom     = trim($_POST['prenom']     ?? '');
    $email      = trim($_POST['email']      ?? '');
    $profil     = trim($_POST['profil']     ?? '');
    $id_creneau = (int)($_POST['id_creneau'] ?? 0);
    $nb         = (int)($_POST['nb_personnes'] ?? 1);
    $buffet     = isset($_POST['participe_buffet']) ? 1 : 0;

    // Fallback : chercher créneau depuis salle + date si pas de id_creneau
    if (!$id_creneau && !empty($_POST['salle']) && !empty($_POST['date'])) {
        if (preg_match('/(\d+)/', $_POST['salle'], $m)) {
            $cstmt = $pdo->prepare(
                "SELECT c.id_creneau FROM creneau c
                 JOIN salle s ON c.id_salle = s.id_salle
                 JOIN date_expo d ON c.id_date = d.id_date
                 WHERE s.numero = ? AND d.date = ? AND c.places_restante >= ?
                 ORDER BY c.heure_debut LIMIT 1"
            );
            $cstmt->execute([$m[1], $_POST['date'], $nb]);
            $found = $cstmt->fetch();
            if ($found) $id_creneau = (int)$found['id_creneau'];
        }
    }

    // Validation
    if (!$nom || !$prenom || !$email || !$profil || !$id_creneau || $nb < 1) {
        $error = 'Merci de remplir tous les champs obligatoires et de sélectionner un créneau.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif ($nb > 12) {
        $error = 'Le nombre de personnes ne peut pas dépasser 12.';
    } else {
        $check = $pdo->prepare("SELECT places_restante FROM creneau WHERE id_creneau = ?");
        $check->execute([$id_creneau]);
        $creneau = $check->fetch();

        if (!$creneau) {
            $error = 'Créneau introuvable.';
        } elseif ($creneau['places_restante'] < $nb) {
            $error = "Plus assez de places disponibles (reste {$creneau['places_restante']} place(s)).";
        } else {
            // Visiteur : insérer ou mettre à jour
            $vcheck = $pdo->prepare("SELECT id_visiteur FROM visiteur WHERE email = ?");
            $vcheck->execute([$email]);
            $visiteur = $vcheck->fetch();

            if ($visiteur) {
                $id_visiteur = $visiteur['id_visiteur'];
                $pdo->prepare("UPDATE visiteur SET nom=?, prenom=?, profil=? WHERE id_visiteur=?")
                    ->execute([$nom, $prenom, $profil, $id_visiteur]);
            } else {
                $pdo->prepare("INSERT INTO visiteur (nom, prenom, email, profil) VALUES (?,?,?,?)")
                    ->execute([$nom, $prenom, $email, $profil]);
                $id_visiteur = $pdo->lastInsertId();
            }

            $token = bin2hex(random_bytes(16));

            $pdo->prepare(
                "INSERT INTO inscription (id_creneau, id_visiteur, nb_personnes, date_inscription, statut, token_modification, participe_buffet)
                 VALUES (?, ?, ?, NOW(), 'confirmé', ?, ?)"
            )->execute([$id_creneau, $id_visiteur, $nb, $token, $buffet]);

            $pdo->prepare("UPDATE creneau SET places_restante = places_restante - ? WHERE id_creneau = ?")
                ->execute([$nb, $id_creneau]);

            $success = $token;
        }
    }
}

// ── Créneaux groupés par numéro de salle et date ─────────────────
$creneaux_data = [];
foreach ($salles as $s) {
    $creneaux_data[$s['numero']] = [];
}
try {
    $stmt = $pdo->query(
        "SELECT c.id_creneau, c.heure_debut, c.heure_fin, c.places_restante,
                s.numero, d.date
         FROM creneau c
         JOIN salle s ON c.id_salle = s.id_salle
         JOIN date_expo d ON c.id_date = d.id_date
         ORDER BY s.numero, d.date, c.heure_debut"
    );
    foreach ($stmt->fetchAll() as $row) {
        $creneaux_data[$row['numero']][$row['date']][] = $row;
    }
} catch (Exception $e) { /* pas de créneaux encore */ }

$prefill_salle   = $_GET['salle']   ?? '';
$prefill_creneau = isset($_GET['creneau']) ? (int)$_GET['creneau'] : 0;
$creneaux_json   = json_encode($creneaux_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT);
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