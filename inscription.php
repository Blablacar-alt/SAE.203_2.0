<?php
session_start();

// ── Connexion BDD ──────────────────────────────────────────────
$host    = 'localhost';
$db      = 'sae203';
$user    = 'root';
$pass    = '';
$charset = 'utf8';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Erreur BDD : " . $e->getMessage());
}

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

    // Validation
    if (!$nom || !$prenom || !$email || !$profil || !$id_creneau || $nb < 1) {
        $error = 'Merci de remplir tous les champs obligatoires.';
    } elseif ($nb > 12) {
        $error = 'Le nombre de personnes ne peut pas dépasser 12.';
    } else {
        // Vérifier places restantes
        $check = $pdo->prepare("SELECT places_restante FROM creneau WHERE id_creneau = ?");
        $check->execute([$id_creneau]);
        $creneau = $check->fetch();

        if (!$creneau) {
            $error = 'Créneau introuvable.';
        } elseif ($creneau['places_restante'] < $nb) {
            $error = "Plus assez de places disponibles (reste {$creneau['places_restante']} place(s)).";
        } else {
            // Insérer ou récupérer le visiteur
            $vcheck = $pdo->prepare("SELECT id_visiteur FROM visiteur WHERE email = ?");
            $vcheck->execute([$email]);
            $visiteur = $vcheck->fetch();

            if ($visiteur) {
                $id_visiteur = $visiteur['id_visiteur'];
                // Mettre à jour les infos
                $pdo->prepare("UPDATE visiteur SET nom=?, prenom=?, profil=? WHERE id_visiteur=?")
                    ->execute([$nom, $prenom, $profil, $id_visiteur]);
            } else {
                $pdo->prepare("INSERT INTO visiteur (nom, prenom, email, profil) VALUES (?,?,?,?)")
                    ->execute([$nom, $prenom, $email, $profil]);
                $id_visiteur = $pdo->lastInsertId();
            }

            // Générer token de modification
            $token = bin2hex(random_bytes(16));

            // Insérer inscription
            $pdo->prepare("INSERT INTO inscription (id_creneau, id_visiteur, nb_personnes, date_inscription, statut, token_modification, participe_buffet)
                           VALUES (?, ?, ?, NOW(), 'confirmé', ?, ?)")
                ->execute([$id_creneau, $id_visiteur, $nb, $token, $buffet]);

            // Mettre à jour places restantes
            $pdo->prepare("UPDATE creneau SET places_restante = places_restante - ? WHERE id_creneau = ?")
                ->execute([$nb, $id_creneau]);

            $success = $token;
        }
    }
}

// ── Charger les créneaux groupés par salle et date ─────────────
$creneaux_data = [];
$stmt = $pdo->query("
    SELECT c.id_creneau, c.heure_debut, c.heure_fin, c.places_restante,
           s.id_salle, s.numero, s.nom_thematique,
           d.id_date, d.date
    FROM creneau c
    JOIN salle s ON c.id_salle = s.id_salle
    JOIN date_expo d ON c.id_date = d.id_date
    ORDER BY s.numero, d.date, c.heure_debut
");
foreach ($stmt->fetchAll() as $row) {
    $creneaux_data[$row['numero']][$row['date']][] = $row;
}

// Profils pouvant participer au buffet
$buffet_profils = ['Enseignant·e', 'Personnel de l\'université', 'Visiteur·euse extérieur·e', 'Professionnels/partenaires'];

$prefill_salle = $_GET['salle'] ?? '';
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
    .creneau-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); gap:.5rem; margin-top:.5rem; }
    .creneau-btn {
      border:1.5px solid #ddd; border-radius:8px; padding:.5rem .4rem;
      text-align:center; font-family:'DM Sans',sans-serif; font-size:.8rem;
      cursor:pointer; background:var(--white); transition:all .2s; line-height:1.3;
    }
    .creneau-btn:hover:not(:disabled) { border-color:var(--mint-dark); }
    .creneau-btn.selected { border-color:var(--mint-dark); background:var(--mint); font-weight:600; }
    .creneau-btn:disabled { opacity:.4; cursor:not-allowed; background:#f5f5f5; }
    .creneau-btn small { display:block; font-size:.7rem; color:#888; }
    .salle-tabs { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem; }
    .salle-tab {
      border:1.5px solid #ddd; border-radius:999px; padding:.4rem 1rem;
      font-family:'DM Sans',sans-serif; font-size:.82rem; font-weight:500;
      cursor:pointer; background:var(--white); transition:all .2s;
    }
    .salle-tab.active { background:var(--black); color:var(--white); border-color:var(--black); }
    .date-section { margin-bottom:1.2rem; }
    .date-label { font-size:.82rem; font-weight:600; color:#666; margin-bottom:.5rem; }
    .buffet-group { display:none; }
    .buffet-group.visible { display:flex; }
    .error-msg { background:#ffe5e5; border:1px solid #e0302a; border-radius:10px; padding:.85rem 1.2rem; font-size:.88rem; color:#b00000; margin-bottom:1rem; }
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
        <!-- Confirmation -->
        <div style="text-align:center;padding:2rem 0">
          <div style="font-size:2.5rem;margin-bottom:1rem">✅</div>
          <h2 style="font-size:1.2rem;margin-bottom:.5rem">Inscription confirmée !</h2>
          <p style="font-size:.9rem;color:#555;margin-bottom:1.5rem">
            Conservez ce lien pour modifier ou annuler votre réservation :
          </p>
          <div style="background:var(--grey);border-radius:10px;padding:1rem;font-family:'Space Mono',monospace;font-size:.78rem;word-break:break-all;margin-bottom:1.5rem">
            <?= htmlspecialchars("http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/modifier.php?token=$success") ?>
          </div>
          <a href="inscription.php" class="btn-primary" style="text-decoration:none;padding:.75rem 1.8rem;border-radius:999px;background:var(--mint-dark);font-weight:600;font-size:.95rem">Nouvelle inscription</a>
        </div>

      <?php else: ?>

        <?php if ($error): ?>
          <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="inscriptionForm">
          <input type="hidden" name="id_creneau" id="id_creneau_hidden"/>

          <p class="form-section-title">Informations personnelles</p>

          <div class="form-row">
            <div class="form-group">
              <label>Nom *</label>
              <input type="text" name="nom" placeholder="Votre nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required/>
            </div>
            <div class="form-group">
              <label>Prénom *</label>
              <input type="text" name="prenom" placeholder="Votre prénom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required/>
            </div>
          </div>

          <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" placeholder="votre.email@exemple.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required/>
          </div>

          <div class="form-group">
            <label>Qui êtes-vous ? *</label>
            <select name="profil" id="profil" onchange="toggleBuffet()" required>
              <option value="">— Sélectionnez —</option>
              <option <?= (($_POST['profil']??'')==='Étudiant·e')?'selected':'' ?>>Étudiant·e</option>
              <option <?= (($_POST['profil']??'')==='Enseignant·e')?'selected':'' ?>>Enseignant·e</option>
              <option <?= (($_POST['profil']??'')==='Personnel de l\'université')?'selected':'' ?>>Personnel de l'université</option>
              <option <?= (($_POST['profil']??'')==='Visiteur·euse extérieur·e')?'selected':'' ?>>Visiteur·euse extérieur·e</option>
              <option <?= (($_POST['profil']??'')==='Professionnels/partenaires')?'selected':'' ?>>Professionnels/partenaires</option>
            </select>
          </div>

          <hr class="form-divider"/>
          <p class="form-section-title">Réservation</p>

          <!-- Sélection salle -->
          <div class="form-group">
            <label>Salle *</label>
            <div class="salle-tabs" id="salleTabs">
              <?php foreach (array_keys($creneaux_data) as $i => $numero): ?>
                <button type="button" class="salle-tab <?= ($i===0||$prefill_salle==="Salle $numero")?'active':'' ?>"
                  onclick="selectSalle('<?= $numero ?>')">Salle <?= $numero ?></button>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Créneaux par salle -->
          <?php foreach ($creneaux_data as $numero => $dates): ?>
          <div class="form-group salle-creneaux" id="creneaux_<?= $numero ?>" style="display:<?= (array_key_first($creneaux_data)===$numero||$prefill_salle==="Salle $numero")?'block':'none' ?>">
            <?php foreach ($dates as $date => $slots): ?>
            <div class="date-section">
              <div class="date-label">
                <?= (new DateTime($date))->format('l d F Y') === 'Thursday 18 June 2026' ? 'Jeudi 18 juin 2026' : (strpos($date,'2026-06-18')!==false ? 'Jeudi 18 juin 2026' : 'Vendredi 19 juin 2026') ?>
              </div>
              <div class="creneau-grid">
                <?php foreach ($slots as $slot): ?>
                  <button type="button"
                    class="creneau-btn"
                    <?= $slot['places_restante'] <= 0 ? 'disabled' : '' ?>
                    onclick="selectCreneau(<?= $slot['id_creneau'] ?>, this)"
                    data-salle="<?= $numero ?>">
                    <?= substr($slot['heure_debut'],0,5) ?>
                    <small><?= $slot['places_restante'] ?> pl.</small>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>

          <!-- Nombre de personnes -->
          <div class="form-group" style="margin-top:1rem">
            <label>Nombre de personnes * (maximum 12)</label>
            <input type="number" name="nb_personnes" id="nbPersonnes" min="1" max="12" placeholder="Ex : 3" value="<?= htmlspecialchars($_POST['nb_personnes'] ?? '') ?>" required/>
            <small style="color:#888;font-size:.8rem">Indiquez le nombre total de participants (vous inclus·e)</small>
          </div>

          <!-- Buffet (conditionnel) -->
          <div class="form-group buffet-group" id="buffetGroup" style="flex-direction:row;align-items:center;gap:.75rem">
            <input type="checkbox" name="participe_buffet" id="participe_buffet" style="width:16px;height:16px" <?= isset($_POST['participe_buffet'])?'checked':'' ?>/>
            <label for="participe_buffet" style="font-size:.88rem;font-weight:400;cursor:pointer">
              Je participe au buffet du <strong>jeudi 18 juin à 18h30</strong>
            </label>
          </div>

          <div class="info-box">
            <strong>ℹ️ Informations importantes :</strong>
            Jauge limitée à 12 personnes par créneau et par salle<br/>
            Vous recevrez un lien pour modifier ou annuler votre réservation<br/>
            En cas de problème, contactez le référent de votre salle
          </div>

          <button type="submit" class="btn-submit" onclick="return validateForm()">Confirmer mon inscription</button>

          <p class="contact-hint">
            Pour toute question, contactez
            <a href="mailto:contact.ellusion@univ-smb.fr">contact.ellusion@univ-smb.fr</a>
          </p>
        </form>

      <?php endif; ?>

    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
let currentSalle = '<?= array_key_first($creneaux_data) ?? '' ?>';

<?php if ($prefill_salle): ?>
const prefill = '<?= preg_replace('/[^0-9]/', '', $prefill_salle) ?>';
if (prefill) { selectSalle(prefill); }
<?php endif; ?>

function selectSalle(numero) {
  currentSalle = numero;
  document.querySelectorAll('.salle-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.salle-tab').forEach(t => {
    if (t.textContent.trim() === 'Salle ' + numero) t.classList.add('active');
  });
  document.querySelectorAll('.salle-creneaux').forEach(d => d.style.display = 'none');
  const el = document.getElementById('creneaux_' + numero);
  if (el) el.style.display = 'block';
  // Reset créneau sélectionné
  document.getElementById('id_creneau_hidden').value = '';
  document.querySelectorAll('.creneau-btn.selected').forEach(b => b.classList.remove('selected'));
}

function selectCreneau(id, btn) {
  document.querySelectorAll('.creneau-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  document.getElementById('id_creneau_hidden').value = id;
}

const buffetProfils = ['Enseignant·e', 'Personnel de l\'université', 'Visiteur·euse extérieur·e', 'Professionnels/partenaires'];
function toggleBuffet() {
  const profil = document.getElementById('profil').value;
  const group  = document.getElementById('buffetGroup');
  if (buffetProfils.includes(profil)) {
    group.style.display = 'flex';
  } else {
    group.style.display = 'none';
    document.getElementById('participe_buffet').checked = false;
  }
}

function validateForm() {
  const id = document.getElementById('id_creneau_hidden').value;
  if (!id) { alert('Merci de sélectionner un créneau horaire.'); return false; }
  return true;
}

// Init buffet au chargement si profil déjà sélectionné
toggleBuffet();
</script>

</body>
</html>
