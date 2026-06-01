<?php
session_start();

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

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$success = '';
$error   = '';

if (!$token) {
    die("Token manquant.");
}

// Charger l'inscription
$stmt = $pdo->prepare("
    SELECT i.*, v.nom, v.prenom, v.email, v.profil,
           s.numero, s.nom_thematique,
           c.heure_debut, c.heure_fin, c.places_restante, c.id_salle,
           d.date
    FROM inscription i
    JOIN visiteur v ON i.id_visiteur = v.id_visiteur
    JOIN creneau c ON i.id_creneau = c.id_creneau
    JOIN salle s ON c.id_salle = s.id_salle
    JOIN date_expo d ON c.id_date = d.id_date
    WHERE i.token_modification = ?
");
$stmt->execute([$token]);
$ins = $stmt->fetch();

if (!$ins) {
    die("Réservation introuvable ou lien invalide.");
}

// Supprimer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $pdo->prepare("UPDATE creneau SET places_restante = places_restante + ? WHERE id_creneau = ?")
        ->execute([$ins['nb_personnes'], $ins['id_creneau']]);
    $pdo->prepare("DELETE FROM inscription WHERE token_modification = ?")
        ->execute([$token]);
    $success = 'deleted';
}

// Modifier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $nb     = (int)($_POST['nb_personnes'] ?? 1);
    $buffet = isset($_POST['participe_buffet']) ? 1 : 0;

    $check = $pdo->prepare("SELECT places_restante FROM creneau WHERE id_creneau = ?");
    $check->execute([$ins['id_creneau']]);
    $cr = $check->fetch();
    $available = $cr['places_restante'] + $ins['nb_personnes'];

    if ($nb < 1 || $nb > 12) {
        $error = 'Nombre de personnes invalide (1–12).';
    } elseif ($nb > $available) {
        $error = "Seulement $available place(s) disponible(s) sur ce créneau.";
    } else {
        $diff = $ins['nb_personnes'] - $nb;
        $pdo->prepare("UPDATE creneau SET places_restante = places_restante + ? WHERE id_creneau = ?")
            ->execute([$diff, $ins['id_creneau']]);
        $pdo->prepare("UPDATE inscription SET nb_personnes=?, participe_buffet=?, statut='modifié' WHERE token_modification=?")
            ->execute([$nb, $buffet, $token]);
        $success = 'updated';
        // Recharger
        $stmt->execute([$token]);
        $ins = $stmt->fetch();
    }
}

$buffet_profils = ['Enseignant·e', 'Personnel de l\'université', 'Visiteur·euse extérieur·e', 'Professionnels/partenaires'];
$can_buffet = in_array($ins['profil'] ?? '', $buffet_profils);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-LLUSION — Ma réservation</title>
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<main>
  <div class="page-header">
    <h1>MA RÉSERVATION</h1>
    <p>Modifier ou annuler votre inscription</p>
  </div>

  <div class="form-wrapper">
    <div class="form-card">

      <?php if ($success === 'deleted'): ?>
        <div style="text-align:center;padding:2rem 0">
          <div style="font-size:2.5rem;margin-bottom:1rem">🗑️</div>
          <h2 style="font-size:1.2rem;margin-bottom:.5rem">Réservation supprimée</h2>
          <p style="font-size:.9rem;color:#555;margin-bottom:1.5rem">Votre inscription a bien été annulée.</p>
          <a href="inscription.php" class="btn-primary" style="text-decoration:none;padding:.75rem 1.8rem;border-radius:999px;background:var(--mint-dark);font-weight:600;font-size:.95rem">Nouvelle inscription</a>
        </div>

      <?php elseif ($success === 'updated'): ?>
        <div style="background:var(--success,#d4f5ee);border:1px solid var(--mint-dark);border-radius:10px;padding:1rem 1.4rem;font-size:.9rem;font-weight:600;color:#1a7a6e;margin-bottom:1.5rem;text-align:center">
          ✅ Réservation mise à jour avec succès !
        </div>

      <?php endif; ?>

      <?php if ($error): ?>
        <div style="background:#ffe5e5;border:1px solid #e0302a;border-radius:10px;padding:.85rem 1.2rem;font-size:.88rem;color:#b00000;margin-bottom:1rem">
          ⚠️ <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success !== 'deleted'): ?>

        <!-- Récap -->
        <div style="background:var(--grey,#f4f4f4);border-radius:12px;padding:1.2rem 1.5rem;margin-bottom:1.5rem;font-size:.88rem;line-height:2">
          <strong style="display:block;margin-bottom:.3rem;font-size:.95rem"><?= htmlspecialchars($ins['nom'].' '.$ins['prenom']) ?></strong>
          <?= htmlspecialchars($ins['email']) ?><br/>
          Profil : <?= htmlspecialchars($ins['profil']) ?><br/>
          Salle <?= htmlspecialchars($ins['numero']) ?> — <?= htmlspecialchars($ins['nom_thematique']) ?><br/>
          <?= (new DateTime($ins['date']))->format('d/m/Y') ?> · <?= substr($ins['heure_debut'],0,5) ?>–<?= substr($ins['heure_fin'],0,5) ?><br/>
          Statut : <strong><?= htmlspecialchars($ins['statut']) ?></strong>
        </div>

        <!-- Formulaire modification -->
        <form method="POST">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"/>

          <p class="form-section-title">Modifier ma réservation</p>

          <div class="form-group">
            <label>Nombre de personnes (1–12)</label>
            <input type="number" name="nb_personnes" min="1" max="12" value="<?= $ins['nb_personnes'] ?>" required/>
          </div>

          <?php if ($can_buffet): ?>
          <div class="form-group" style="flex-direction:row;align-items:center;gap:.75rem">
            <input type="checkbox" name="participe_buffet" id="buffet" style="width:16px;height:16px" <?= $ins['participe_buffet'] ? 'checked' : '' ?>/>
            <label for="buffet" style="font-size:.88rem;font-weight:400;cursor:pointer">
              Je participe au buffet du <strong>jeudi 18 juin à 18h30</strong>
            </label>
          </div>
          <?php endif; ?>

          <div style="display:flex;gap:.75rem;margin-top:1.5rem">
            <button type="submit" name="update" class="btn-submit" style="flex:1">Enregistrer les modifications</button>
          </div>
        </form>

        <hr class="form-divider" style="margin:1.5rem 0"/>

        <!-- Suppression -->
        <form method="POST" onsubmit="return confirm('Êtes-vous sûr·e de vouloir annuler votre réservation ?')">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>"/>
          <button type="submit" name="delete" style="width:100%;padding:.85rem;background:none;border:1.5px solid #e0302a;border-radius:999px;color:#e0302a;font-family:'DM Sans',sans-serif;font-size:.95rem;font-weight:600;cursor:pointer;transition:all .2s"
            onmouseover="this.style.background='#e0302a';this.style.color='white'"
            onmouseout="this.style.background='none';this.style.color='#e0302a'">
            Annuler ma réservation
          </button>
        </form>

      <?php endif; ?>

    </div>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>
