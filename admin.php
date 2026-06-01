<?php
session_start();

// ── Connexion BDD ──────────────────────────────────────────────
$host = 'localhost';
$db   = 'sae203';
$user = 'root';
$pass = 'local';
$charset = 'utf8';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Erreur BDD : " . $e->getMessage());
}

// ── Identifiants admin (à sécuriser en prod) ────────────────────
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', password_hash('ellusion2026', PASSWORD_DEFAULT));

// ── Logout ──────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ── Login ───────────────────────────────────────────────────────
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');
    if ($u === ADMIN_USER && password_verify($p, ADMIN_PASS)) {
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Identifiants incorrects.';
    }
}

// ── Actions CRUD ─────────────────────────────────────────────────
$message = '';
if (isset($_SESSION['admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // DELETE
    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        // récupérer id_creneau et nb_personnes pour libérer les places
        $row = $pdo->prepare("SELECT id_creneau, nb_personnes FROM inscription WHERE id_inscription = ?");
        $row->execute([$id]);
        $ins = $row->fetch();
        if ($ins) {
            $pdo->prepare("UPDATE creneau SET places_restante = places_restante + ? WHERE id_creneau = ?")->execute([$ins['nb_personnes'], $ins['id_creneau']]);
            $pdo->prepare("DELETE FROM inscription WHERE id_inscription = ?")->execute([$id]);
            $message = 'success|Inscription supprimée et places libérées.';
        }
    }

    // UPDATE
    if (isset($_POST['update_id'])) {
        $id      = (int)$_POST['update_id'];
        $statut  = $_POST['statut'] ?? '';
        $nb      = (int)($_POST['nb_personnes'] ?? 1);
        $buffet  = isset($_POST['participe_buffet']) ? 1 : 0;

        $old = $pdo->prepare("SELECT nb_personnes, id_creneau FROM inscription WHERE id_inscription = ?");
        $old->execute([$id]);
        $prev = $old->fetch();
        if ($prev) {
            $diff = $prev['nb_personnes'] - $nb;
            $pdo->prepare("UPDATE creneau SET places_restante = places_restante + ? WHERE id_creneau = ?")->execute([$diff, $prev['id_creneau']]);
            $pdo->prepare("UPDATE inscription SET statut=?, nb_personnes=?, participe_buffet=? WHERE id_inscription=?")->execute([$statut, $nb, $buffet, $id]);
            $message = 'success|Inscription mise à jour.';
        }
    }
}

// ── Récupération des données ──────────────────────────────────────
$inscriptions = [];
$stats = ['total' => 0, 'buffet' => 0, 'salles' => []];
if (isset($_SESSION['admin'])) {
    $filter_salle = $_GET['salle'] ?? '';
    $filter_date  = $_GET['date']  ?? '';

    $sql = "SELECT i.id_inscription, v.nom, v.prenom, v.email, v.profil,
                   s.numero AS salle_numero, s.nom_thematique,
                   d.date AS date_expo,
                   c.heure_debut, c.heure_fin, c.places_restante,
                   i.nb_personnes, i.statut, i.participe_buffet, i.date_inscription
            FROM inscription i
            JOIN visiteur v ON i.id_visiteur = v.id_visiteur
            JOIN creneau c ON i.id_creneau = c.id_creneau
            JOIN salle s ON c.id_salle = s.id_salle
            JOIN date_expo d ON c.id_date = d.id_date
            WHERE 1=1";
    $params = [];
    if ($filter_salle) { $sql .= " AND s.numero = ?"; $params[] = $filter_salle; }
    if ($filter_date)  { $sql .= " AND d.date = ?";   $params[] = $filter_date; }
    $sql .= " ORDER BY d.date, c.heure_debut, s.numero";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $inscriptions = $stmt->fetchAll();

    $stats['total']  = count($inscriptions);
    $stats['buffet'] = count(array_filter($inscriptions, fn($r) => $r['participe_buffet']));

    $salles_stmt = $pdo->query("SELECT s.numero, s.nom_thematique, COUNT(i.id_inscription) as nb
        FROM salle s LEFT JOIN creneau c ON s.id_salle = c.id_salle
        LEFT JOIN inscription i ON c.id_creneau = i.id_creneau
        GROUP BY s.id_salle ORDER BY s.numero");
    $stats['salles'] = $salles_stmt->fetchAll();

    // Édition
    $edit_ins = null;
    if (isset($_GET['edit'])) {
        $es = $pdo->prepare("SELECT i.*, v.nom, v.prenom, v.email, v.profil,
            s.numero, c.heure_debut, c.heure_fin, d.date
            FROM inscription i
            JOIN visiteur v ON i.id_visiteur = v.id_visiteur
            JOIN creneau c ON i.id_creneau = c.id_creneau
            JOIN salle s ON c.id_salle = s.id_salle
            JOIN date_expo d ON c.id_date = d.id_date
            WHERE i.id_inscription = ?");
        $es->execute([(int)$_GET['edit']]);
        $edit_ins = $es->fetch();
    }
}

// Parse message
$msg_type = $msg_text = '';
if ($message) { [$msg_type, $msg_text] = explode('|', $message, 2); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-LLUSION — Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --mint: #b2f0e8;
      --mint-dark: #7de0d2;
      --black: #111111;
      --white: #ffffff;
      --grey: #f4f4f4;
      --text: #222222;
      --accent: #e0302a;
      --success: #d4f5ee;
      --success-border: #7de0d2;
      --error: #ffe5e5;
      --error-border: #e0302a;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'DM Sans',sans-serif; color:var(--text); background:var(--white); }

    /* ── NAV ── */
    nav {
      position:sticky; top:0; z-index:100;
      background:var(--mint);
      display:flex; align-items:center; justify-content:space-between;
      padding:0 2rem; height:56px;
    }
    .nav-logo { font-family:'Space Mono',monospace; font-size:1rem; font-weight:700; color:var(--black); text-decoration:none; }
    .nav-badge {
      font-family:'Space Mono',monospace; font-size:.7rem; font-weight:700;
      background:var(--black); color:var(--mint); padding:.2rem .5rem; border-radius:4px; margin-left:.5rem;
    }
    .nav-right { display:flex; align-items:center; gap:1.5rem; }
    .nav-right a { font-size:.85rem; font-weight:500; color:var(--black); text-decoration:none; }
    .nav-right a:hover { opacity:.6; }
    .btn-logout {
      background:var(--black); color:var(--mint); border:none; border-radius:999px;
      padding:.4rem 1rem; font-family:'DM Sans',sans-serif; font-size:.8rem; font-weight:600;
      cursor:pointer; transition:opacity .2s;
    }
    .btn-logout:hover { opacity:.75; }

    /* ── LOGIN ── */
    .login-wrap {
      min-height:100vh; display:flex; align-items:center; justify-content:center;
      background:var(--grey);
    }
    .login-card {
      background:var(--white); border:1px solid #ddd; border-radius:16px;
      padding:2.5rem; width:100%; max-width:400px;
    }
    .login-card h1 { font-family:'Space Mono',monospace; font-size:1.3rem; margin-bottom:.3rem; }
    .login-card p { font-size:.85rem; color:#666; margin-bottom:2rem; }
    .form-group { display:flex; flex-direction:column; gap:.4rem; margin-bottom:1rem; }
    .form-group label { font-size:.82rem; font-weight:600; }
    .form-group input {
      border:1.5px solid #ddd; border-radius:8px;
      padding:.65rem .9rem; font-family:'DM Sans',sans-serif; font-size:.9rem;
      outline:none; transition:border-color .2s;
    }
    .form-group input:focus { border-color:var(--mint-dark); }
    .btn-submit {
      width:100%; padding:.85rem; background:var(--mint-dark); border:none;
      border-radius:999px; font-family:'DM Sans',sans-serif;
      font-size:.95rem; font-weight:700; color:var(--black); cursor:pointer; transition:background .2s;
    }
    .btn-submit:hover { background:#5cd3c5; }
    .error-msg {
      background:var(--error); border:1px solid var(--error-border);
      border-radius:8px; padding:.75rem 1rem; font-size:.85rem;
      color:#b00000; margin-bottom:1rem;
    }

    /* ── DASHBOARD ── */
    .admin-main { max-width:1200px; margin:0 auto; padding:2rem; }

    .page-title { font-family:'Space Mono',monospace; font-size:1.4rem; font-weight:700; margin-bottom:.3rem; }
    .page-sub { font-size:.88rem; color:#666; margin-bottom:2rem; }

    /* Stats */
    .stats-grid {
      display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
      gap:1rem; margin-bottom:2rem;
    }
    .stat-card {
      background:var(--white); border:1px solid #e0e0e0; border-radius:12px;
      padding:1.2rem 1.5rem;
    }
    .stat-card.mint { background:var(--mint); border-color:transparent; }
    .stat-label { font-size:.75rem; font-weight:600; color:#666; text-transform:uppercase; letter-spacing:.08em; margin-bottom:.3rem; }
    .stat-value { font-family:'Space Mono',monospace; font-size:1.8rem; font-weight:700; color:var(--black); }

    /* Filters */
    .filters {
      display:flex; gap:1rem; flex-wrap:wrap; align-items:center;
      background:var(--grey); border-radius:12px; padding:1rem 1.2rem;
      margin-bottom:1.5rem;
    }
    .filters label { font-size:.82rem; font-weight:600; }
    .filters select {
      border:1.5px solid #ddd; border-radius:8px;
      padding:.45rem .8rem; font-family:'DM Sans',sans-serif;
      font-size:.85rem; background:var(--white); outline:none;
    }
    .filters select:focus { border-color:var(--mint-dark); }
    .btn-filter {
      background:var(--mint-dark); border:none; border-radius:999px;
      padding:.45rem 1.2rem; font-family:'DM Sans',sans-serif;
      font-size:.82rem; font-weight:600; cursor:pointer; transition:background .2s;
    }
    .btn-filter:hover { background:#5cd3c5; }
    .btn-reset {
      background:none; border:1.5px solid #ddd; border-radius:999px;
      padding:.45rem 1rem; font-family:'DM Sans',sans-serif;
      font-size:.82rem; font-weight:500; cursor:pointer; color:#555;
    }
    .btn-reset:hover { border-color:#999; }

    /* Alert */
    .alert {
      border-radius:10px; padding:.85rem 1.2rem;
      font-size:.88rem; font-weight:600; margin-bottom:1.5rem;
    }
    .alert.success { background:var(--success); border:1px solid var(--success-border); color:#1a7a6e; }
    .alert.error   { background:var(--error);   border:1px solid var(--error-border);   color:#b00000; }

    /* Table */
    .table-wrap { overflow-x:auto; border-radius:12px; border:1px solid #e0e0e0; }
    table { width:100%; border-collapse:collapse; font-size:.85rem; }
    thead tr { background:var(--black); color:var(--mint); }
    thead th {
      padding:.75rem 1rem; text-align:left;
      font-family:'Space Mono',monospace; font-size:.72rem;
      font-weight:700; letter-spacing:.06em; white-space:nowrap;
    }
    tbody tr { border-bottom:1px solid #f0f0f0; transition:background .15s; }
    tbody tr:hover { background:#f9fffe; }
    tbody tr:last-child { border-bottom:none; }
    td { padding:.75rem 1rem; vertical-align:middle; }

    .badge {
      display:inline-block; padding:.2rem .6rem; border-radius:999px;
      font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em;
    }
    .badge-confirmed { background:#d4f5ee; color:#1a7a6e; }
    .badge-modified  { background:#fff3cd; color:#856404; }
    .badge-pending   { background:#e8e8e8; color:#555; }
    .badge-buffet    { background:var(--mint); color:var(--black); }

    .btn-edit {
      background:none; border:1.5px solid var(--black); border-radius:6px;
      padding:.3rem .7rem; font-family:'DM Sans',sans-serif;
      font-size:.78rem; font-weight:600; cursor:pointer; transition:all .2s;
    }
    .btn-edit:hover { background:var(--black); color:var(--white); }
    .btn-del {
      background:none; border:1.5px solid var(--accent); color:var(--accent);
      border-radius:6px; padding:.3rem .7rem; font-family:'DM Sans',sans-serif;
      font-size:.78rem; font-weight:600; cursor:pointer; transition:all .2s;
    }
    .btn-del:hover { background:var(--accent); color:var(--white); }

    .actions { display:flex; gap:.5rem; }

    /* Modal edit */
    .modal-overlay {
      display:none; position:fixed; inset:0;
      background:rgba(0,0,0,.4); z-index:200;
      align-items:center; justify-content:center;
    }
    .modal-overlay.open { display:flex; }
    .modal {
      background:var(--white); border-radius:16px; padding:2rem;
      width:100%; max-width:500px; max-height:90vh; overflow-y:auto;
    }
    .modal h2 { font-family:'Space Mono',monospace; font-size:1rem; margin-bottom:1.5rem; }
    .modal-info {
      background:var(--grey); border-radius:8px; padding:.75rem 1rem;
      font-size:.82rem; line-height:1.8; color:#444; margin-bottom:1.5rem;
    }
    .modal-info strong { color:var(--black); }
    .modal-actions { display:flex; gap:.75rem; margin-top:1.5rem; }
    .btn-cancel {
      background:none; border:1.5px solid #ddd; border-radius:999px;
      padding:.6rem 1.2rem; font-family:'DM Sans',sans-serif;
      font-size:.88rem; cursor:pointer;
    }
    .btn-save {
      background:var(--mint-dark); border:none; border-radius:999px;
      padding:.6rem 1.5rem; font-family:'DM Sans',sans-serif;
      font-size:.88rem; font-weight:700; cursor:pointer; transition:background .2s;
    }
    .btn-save:hover { background:#5cd3c5; }

    /* Empty state */
    .empty {
      text-align:center; padding:4rem 2rem; color:#999;
    }
    .empty p { font-size:.9rem; margin-top:.5rem; }

    footer {
      background:var(--white); border-top:1px solid #eee;
      padding:1.5rem 2rem;
      display:flex; align-items:center; justify-content:space-between;
      font-size:.8rem; color:#777;
    }
  </style>
</head>
<body>

<?php if (!isset($_SESSION['admin'])): ?>
<!-- ═══ PAGE LOGIN ═══ -->
<div class="login-wrap">
  <div class="login-card">
    <h1>E–LLUSION<span style="color:var(--accent)">.</span></h1>
    <p>Accès réservé à l'administration</p>
    <?php if ($login_error): ?>
      <div class="error-msg">⚠️ <?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="login" value="1"/>
      <div class="form-group">
        <label>Identifiant</label>
        <input type="text" name="username" placeholder="admin" required autocomplete="username"/>
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password"/>
      </div>
      <button type="submit" class="btn-submit" style="margin-top:.5rem">Se connecter →</button>
    </form>
    <p style="text-align:center;margin-top:1.2rem;font-size:.78rem;color:#aaa;">
      Mot de passe par défaut : <code>ellusion2026</code>
    </p>
  </div>
</div>

<?php else: ?>
<!-- ═══ DASHBOARD ADMIN ═══ -->
<nav>
  <span>
    <a class="nav-logo" href="accueil.html">E-LLUSION</a>
    <span class="nav-badge">ADMIN</span>
  </span>
  <div class="nav-right">
    <a href="accueil.html">← Retour au site</a>
    <form method="GET" style="margin:0">
      <input type="hidden" name="logout" value="1"/>
      <button type="submit" class="btn-logout">Déconnexion</button>
    </form>
  </div>
</nav>

<main class="admin-main">

  <h1 class="page-title">Tableau de bord</h1>
  <p class="page-sub">Gestion des inscriptions — Exposition E-LLUSION · 18 & 19 juin 2026</p>

  <?php if ($msg_text): ?>
    <div class="alert <?= $msg_type ?>"><?= htmlspecialchars($msg_text) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card mint">
      <div class="stat-label">Total inscriptions</div>
      <div class="stat-value"><?= $stats['total'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Participent au buffet</div>
      <div class="stat-value"><?= $stats['buffet'] ?></div>
    </div>
    <?php foreach ($stats['salles'] as $s): ?>
    <div class="stat-card">
      <div class="stat-label">Salle <?= htmlspecialchars($s['numero']) ?></div>
      <div class="stat-value"><?= $s['nb'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Filtres -->
  <form method="GET" class="filters">
    <label>Salle :</label>
    <select name="salle">
      <option value="">Toutes</option>
      <option value="001" <?= ($filter_salle==='001')?'selected':'' ?>>001</option>
      <option value="002" <?= ($filter_salle==='002')?'selected':'' ?>>002</option>
      <option value="005" <?= ($filter_salle==='005')?'selected':'' ?>>005</option>
      <option value="021" <?= ($filter_salle==='021')?'selected':'' ?>>021</option>
    </select>
    <label>Date :</label>
    <select name="date">
      <option value="">Toutes</option>
      <option value="2026-06-18" <?= ($filter_date==='2026-06-18')?'selected':'' ?>>Jeudi 18 juin</option>
      <option value="2026-06-19" <?= ($filter_date==='2026-06-19')?'selected':'' ?>>Vendredi 19 juin</option>
    </select>
    <button type="submit" class="btn-filter">Filtrer</button>
    <a href="admin.php" class="btn-reset">Réinitialiser</a>
  </form>

  <!-- Table inscriptions -->
  <?php if (empty($inscriptions)): ?>
    <div class="empty">
      <strong>Aucune inscription trouvée</strong>
      <p>Modifiez les filtres ou attendez les premières inscriptions.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nom / Prénom</th>
          <th>Email</th>
          <th>Profil</th>
          <th>Salle</th>
          <th>Date</th>
          <th>Créneau</th>
          <th>Pers.</th>
          <th>Buffet</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($inscriptions as $ins): ?>
        <tr>
          <td style="color:#999;font-family:'Space Mono',monospace;font-size:.75rem"><?= $ins['id_inscription'] ?></td>
          <td><strong><?= htmlspecialchars($ins['nom'].' '.$ins['prenom']) ?></strong></td>
          <td style="font-size:.8rem;color:#555"><?= htmlspecialchars($ins['email']) ?></td>
          <td style="font-size:.8rem"><?= htmlspecialchars($ins['profil']) ?></td>
          <td>
            <span class="badge badge-confirmed"><?= htmlspecialchars($ins['salle_numero']) ?></span>
          </td>
          <td style="font-size:.8rem"><?= date('d/m/Y', strtotime($ins['date_expo'])) ?></td>
          <td style="font-family:'Space Mono',monospace;font-size:.78rem">
            <?= substr($ins['heure_debut'],0,5) ?>–<?= substr($ins['heure_fin'],0,5) ?>
          </td>
          <td style="text-align:center"><?= $ins['nb_personnes'] ?></td>
          <td style="text-align:center">
            <?php if ($ins['participe_buffet']): ?>
              <span class="badge badge-buffet">Oui</span>
            <?php else: ?>
              <span style="color:#ccc">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php
              $sc = 'badge-pending';
              if ($ins['statut'] === 'confirmé') $sc = 'badge-confirmed';
              if ($ins['statut'] === 'modifié')  $sc = 'badge-modified';
            ?>
            <span class="badge <?= $sc ?>"><?= htmlspecialchars($ins['statut'] ?: 'en attente') ?></span>
          </td>
          <td>
            <div class="actions">
              <button class="btn-edit" onclick="openEdit(<?= htmlspecialchars(json_encode($ins)) ?>)">Modifier</button>
              <form method="POST" onsubmit="return confirm('Supprimer cette inscription ?')">
                <input type="hidden" name="delete_id" value="<?= $ins['id_inscription'] ?>"/>
                <button type="submit" class="btn-del">Supprimer</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</main>

<!-- ── Modal édition ── -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <h2>✏️ Modifier l'inscription</h2>
    <div class="modal-info" id="modalInfo"></div>
    <form method="POST" id="editForm">
      <input type="hidden" name="update_id" id="edit_id"/>
      <div class="form-group">
        <label>Nombre de personnes</label>
        <input type="number" name="nb_personnes" id="edit_nb" min="1" max="12" required/>
      </div>
      <div class="form-group">
        <label>Statut</label>
        <select name="statut" id="edit_statut">
          <option value="confirmé">Confirmé</option>
          <option value="modifié">Modifié</option>
          <option value="annulé">Annulé</option>
          <option value="en attente">En attente</option>
        </select>
      </div>
      <div class="form-group" style="flex-direction:row;align-items:center;gap:.75rem">
        <input type="checkbox" name="participe_buffet" id="edit_buffet" style="width:16px;height:16px"/>
        <label for="edit_buffet" style="font-size:.88rem;font-weight:400;cursor:pointer">Participe au buffet du jeudi 18h30</label>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeEdit()">Annuler</button>
        <button type="submit" class="btn-save">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<footer>
  <span>© 2026 E-LLUSION — Interface Administration</span>
  <span style="font-family:'Space Mono',monospace;font-size:.72rem;color:#bbb">SAE 203</span>
</footer>

<script>
function openEdit(ins) {
  document.getElementById('edit_id').value    = ins.id_inscription;
  document.getElementById('edit_nb').value    = ins.nb_personnes;
  document.getElementById('edit_statut').value = ins.statut || 'en attente';
  document.getElementById('edit_buffet').checked = ins.participe_buffet == 1;
  document.getElementById('modalInfo').innerHTML =
    `<strong>${ins.nom} ${ins.prenom}</strong><br/>
     ${ins.email}<br/>
     Salle ${ins.salle_numero} · ${ins.heure_debut ? ins.heure_debut.slice(0,5) : ''} – ${ins.heure_fin ? ins.heure_fin.slice(0,5) : ''}`;
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() {
  document.getElementById('editModal').classList.remove('open');
}
document.getElementById('editModal').addEventListener('click', function(e) {
  if (e.target === this) closeEdit();
});
</script>

<?php endif; ?>
</body>
</html>
