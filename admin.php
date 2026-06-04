<?php
session_start();


$host = '192.168.135.113';
$port = '3306';
$db   = 'rahimmoh';
$user = 'user';
$pass = 'rQUSxP2xUCxnzU45';
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) { die("Erreur BDD : " . $e->getMessage()); }

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); // password

if (isset($_GET['logout'])) { session_destroy(); header('Location: admin.php'); exit; }

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === ADMIN_USER && password_verify($_POST['password'], ADMIN_PASS)) {
        $_SESSION['admin'] = true; header('Location: admin.php'); exit;
    } else { $login_error = 'Identifiants incorrects.'; }
}

$message = $msg_type = '';
if (isset($_SESSION['admin']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        $id = (int)$_POST['delete_id'];
        $row = $pdo->prepare("SELECT id_creneau, nb_personnes FROM inscription WHERE id_inscription=?");
        $row->execute([$id]); $ins = $row->fetch();
        if ($ins) {
            $pdo->prepare("UPDATE creneau SET places_restante=places_restante+? WHERE id_creneau=?")->execute([$ins['nb_personnes'],$ins['id_creneau']]);
            $pdo->prepare("DELETE FROM inscription WHERE id_inscription=?")->execute([$id]);
            $message = 'Inscription supprimée.'; $msg_type = 'success';
        }
    }
    if (isset($_POST['update_id'])) {
        $id=(int)$_POST['update_id']; $nb=(int)($_POST['nb_personnes']??1); $buffet=isset($_POST['participe_buffet'])?1:0;
        $old=$pdo->prepare("SELECT nb_personnes,id_creneau FROM inscription WHERE id_inscription=?");
        $old->execute([$id]); $prev=$old->fetch();
        if ($prev) {
            $pdo->prepare("UPDATE creneau SET places_restante=places_restante+? WHERE id_creneau=?")->execute([$prev['nb_personnes']-$nb,$prev['id_creneau']]);
            $pdo->prepare("UPDATE inscription SET nb_personnes=?,participe_buffet=? WHERE id_inscription=?")->execute([$nb,$buffet,$id]);
            $message='Inscription mise à jour.'; $msg_type='success';
        }
    }
}

$inscriptions=[]; $stats=['total'=>0,'personnes'=>0,'buffet'=>0,'salles'=>[]];
if (isset($_SESSION['admin'])) {
    $fs=$_GET['salle']??''; $fd=$_GET['date']??'';
    
    $sql = "SELECT i.id_inscription, v.nom, v.prenom, v.email, v.profil, s.numero, s.nom_thematique, d.date AS date_expo, c.heure_debut, c.heure_fin, c.places_restante, i.nb_personnes, i.participe_buffet, i.date_inscription 
            FROM visiteur v
            LEFT JOIN inscription i ON v.id_visiteur = i.id_visiteur
            LEFT JOIN creneau c ON i.id_creneau = c.id_creneau
            LEFT JOIN salle s ON c.id_salle = s.id_salle
            LEFT JOIN date_expo d ON c.id_date = d.id_date
            WHERE 1=1";
            
    $params=[];
    if($fs){$sql.=" AND s.numero=?";$params[]=$fs;}
    if($fd){$sql.=" AND d.date=?";$params[]=$fd;}
    
    $sql.=" ORDER BY v.email ASC, d.date ASC, c.heure_debut ASC";
    
    $stmt=$pdo->prepare($sql); 
    $stmt->execute($params); 
    $inscriptions=$stmt->fetchAll();
    
    $real_reservations = array_filter($inscriptions, fn($r) => !is_null($r['id_inscription']));
    $stats['total'] = count($real_reservations);
    $stats['personnes'] = array_sum(array_column($real_reservations, 'nb_personnes'));
    $stats['buffet'] = count(array_filter($real_reservations, fn($r) => $r['participe_buffet']));
    
    $sq=$pdo->query("SELECT s.numero, COUNT(i.id_inscription) as nb, COALESCE(SUM(i.nb_personnes),0) as pers FROM salle s LEFT JOIN creneau c ON s.id_salle=c.id_salle LEFT JOIN inscription i ON c.id_creneau=i.id_creneau GROUP BY s.id_salle ORDER BY s.numero");
    $stats['salles']=$sq->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>E-LLUSION — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet"/>
<style>
:root{--mint:#b2f0e8;--mint-dark:#7de0d2;--black:#111;--white:#fff;--grey:#f8f9fa;--text:#222;--accent:#e0302a;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;color:var(--text);background:#fafdfc;min-height:100vh;}

/* NAV */
nav{position:sticky;top:0;z-index:100;background:var(--black);display:flex;align-items:center;justify-content:space-between;padding:0 2rem;height:56px;}
.nav-logo{font-family:'Space Mono',monospace;font-size:1rem;font-weight:700;color:var(--mint);text-decoration:none;}
.nav-badge{font-family:'Space Mono',monospace;font-size:.65rem;background:var(--accent);color:#fff;padding:.15rem .5rem;border-radius:4px;margin-left:.5rem;vertical-align:middle;}
.nav-right{display:flex;align-items:center;gap:1.5rem;}
.nav-right a{font-size:.85rem;color:#aaa;text-decoration:none;}
.nav-right a:hover{color:var(--mint);}
.btn-logout{background:transparent;color:#aaa;border:1px solid #444;border-radius:999px;padding:.35rem .9rem;font-size:.78rem;cursor:pointer;}
.btn-logout:hover{border-color:var(--mint);color:var(--mint);}

/* LOGIN */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f7fffe 0%,var(--mint) 100%);}
.login-card{background:var(--white);border-radius:20px;padding:3rem;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.1);}
.login-logo{font-family:'Space Mono',monospace;font-size:1.8rem;font-weight:700;margin-bottom:2rem;}
.login-logo span{color:var(--accent);}
.form-group{display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem;}
.form-group label{font-size:.82rem;font-weight:600;color:#444;}
.form-group input{border:1.5px solid #e0e0e0;border-radius:10px;padding:.7rem 1rem;font-size:.9rem;outline:none;}
.form-group input:focus{border-color:var(--mint-dark);}
.btn-submit{width:100%;padding:.9rem;background:var(--black);border:none;border-radius:999px;font-size:.95rem;font-weight:700;color:var(--white);cursor:pointer;}

/* DASHBOARD */
.admin-wrap{max-width:1300px;margin:0 auto;padding:2rem;}
.page-title{font-family:'Space Mono',monospace;font-size:1.4rem;font-weight:700;margin-bottom:1.5rem;text-transform:uppercase;}

/* Stats */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:2rem;}
.stat-card{background:var(--white);border:1px solid #eee;border-radius:12px;padding:1.2rem;box-shadow: 0 2px 4px rgba(0,0,0,.02);}
.stat-card.accent { background: var(--black); border-color: var(--black); }
.stat-card.accent .stat-label { color: #888; }
.stat-card.accent .stat-value { color: var(--mint); }
.stat-label{font-size:.68rem;font-weight:700;text-transform:uppercase;color:#999;margin-bottom:.2rem;}
.stat-value{font-family:'Space Mono',monospace;font-size:1.8rem;font-weight:700;}

/* Alert */
.alert{border-radius:10px;padding:.8rem 1.2rem;font-size:.85rem;margin-bottom:1.5rem;}
.alert.success{background:#d4f5ee;border:1px solid var(--mint-dark);color:#1a7a6e;}

/* Filters */
.filters-bar{background:var(--white);border:1px solid #eee;border-radius:12px;padding:.8rem 1.2rem;display:flex;gap:1rem;align-items:center;margin-bottom:1.5rem;font-size:.82rem;}
.filters-bar select{border:1.5px solid #e0e0e0;border-radius:6px;padding:.3rem .6rem;background:var(--white);outline:none;}
.btn-filter{background:var(--black);color:var(--white);border:none;border-radius:999px;padding:.4rem 1.2rem;font-weight:600;cursor:pointer;}
.btn-reset{color:#666;text-decoration:none;font-size:.8rem;}
.results-count{margin-left:auto;color:#aaa;font-family:'Space Mono',monospace;}

/* Table */
.table-wrap{background:var(--white);border:1px solid #eee;border-radius:12px;overflow:hidden;}
table{width:100%;border-collapse:collapse;font-size:.83rem;}
thead tr{background:var(--grey);}
thead th{padding:.75rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;color:#888;border-bottom:1px solid #eee;}
tbody tr{border-bottom:1px solid #f8f9fa;}
tbody tr:hover{background:#fdfdfd;}
td{padding:.75rem 1rem;vertical-align:middle;}

.avatar{width:30px;height:30px;border-radius:50%;background:var(--mint);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.72rem;color:var(--black);}
.td-name{display:flex;align-items:center;gap:.6rem;}
.td-name strong{display:block;font-weight:600;}
.td-name span{font-size:.75rem;color:#888;}

.badge{display:inline-flex;padding:.15rem .5rem;border-radius:4px;font-size:.7rem;font-weight:700;text-transform:uppercase;}
.badge-salle{background:var(--mint);color:var(--black);}
.badge-buffet{background:#e8f4fd;color:#1565c0;}
.badge-none{background:#fafafa;color:#bbb;border:1px dashed #ddd;}

.actions{display:flex;gap:.3rem;}
.btn-edit{background:none;border:1px solid #ddd;border-radius:6px;padding:.25rem .5rem;font-size:.75rem;cursor:pointer;}
.btn-edit:hover{background:var(--black);color:var(--white);border-color:var(--black);}
.btn-del{background:none;border:1px solid #ffd0d0;border-radius:6px;padding:.25rem .5rem;font-size:.75rem;cursor:pointer;color:var(--accent);}
.btn-del:hover{background:var(--accent);color:var(--white);}

/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:300;align-items:center;justify-content:center;backdrop-filter:blur(2px);}
.modal-overlay.open{display:flex;}
.modal{background:var(--white);border-radius:16px;padding:2rem;width:100%;max-width:440px;}
.modal-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;}
.modal-top h2{font-family:'Space Mono',monospace;font-size:.95rem;}
.modal-close{background:none;border:none;font-size:1.2rem;cursor:pointer;color:#aaa;}
.modal-info{background:var(--grey);border-radius:8px;padding:.8rem 1rem;font-size:.8rem;line-height:1.6;color:#555;margin-bottom:1.2rem;}
.modal-actions{display:flex;gap:.5rem;margin-top:1.2rem;}
.btn-cancel{flex:1;background:none;border:1px solid #ddd;border-radius:999px;padding:.5rem;cursor:pointer;font-size:.85rem;}
.btn-save{flex:2;background:var(--black);border:none;border-radius:999px;color:var(--white);font-weight:700;cursor:pointer;font-size:.85rem;}

footer{padding:1.5rem 2rem;display:flex;justify-content:between;font-size:.75rem;color:#bbb;border-top:1px solid #eee;margin-top:3rem;}
</style>
</head>
<body>

<?php if (!isset($_SESSION['admin'])): ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">E–LLUSION<span>.</span></div>
    <?php if ($login_error): ?>
      <div class="error-msg">⚠️ <?= htmlspecialchars($login_error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="login" value="1"/>
      <div class="form-group">
        <label>Identifiant</label>
        <input type="text" name="username" required autocomplete="username"/>
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="password" required/>
      </div>
      <button type="submit" class="btn-submit">Connexion →</button>
    </form>
  </div>
</div>

<?php else: ?>
<nav>
  <a class="nav-logo" href="index.php">E-LLUSION <span class="nav-badge">ADMIN</span></a>
  <div class="nav-right">
    <a href="index.php">Voir le site</a>
    <a href="gestion.php">Espace Compte</a>
    <form method="GET" style="margin:0">
      <input type="hidden" name="logout" value="1"/>
      <button type="submit" class="btn-logout">Déconnexion</button>
    </form>
  </div>
</nav>

<div class="admin-wrap">
  <h1 class="page-title">Dashboard</h1>

  <?php if ($message): ?>
    <div class="alert <?= $msg_type ?>">✅ <?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <div class="stats-grid">
    <div class="stat-card accent">
      <div class="stat-label">Inscriptions</div>
      <div class="stat-value"><?= $stats['total'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Total Visiteurs</div>
      <div class="stat-value"><?= $stats['personnes'] ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Buffet Jeudi</div>
      <div class="stat-value"><?= $stats['buffet'] ?></div>
    </div>
    <?php foreach ($stats['salles'] as $s): ?>
    <div class="stat-card">
      <div class="stat-label">Salle <?= $s['numero'] ?></div>
      <div class="stat-value"><?= $s['nb'] ?> <span style="font-size:1rem;color:#888;">(<?= $s['pers'] ?>p)</span></div>
    </div>
    <?php endforeach; ?>
  </div>

  <form method="GET" class="filters-bar">
    <label>Salle</label>
    <select name="salle">
      <option value="">Toutes</option>
      <?php foreach(['001','002','005','021'] as $n): ?>
        <option value="<?=$n?>" <?=($fs===$n)?'selected':''?>><?=$n?></option>
      <?php endforeach; ?>
    </select>
    
    <label>Jour</label>
    <select name="date">
      <option value="">Tous</option>
      <option value="2026-06-18" <?=($fd==='2026-06-18')?'selected':''?>>Jeudi 18</option>
      <option value="2026-06-19" <?=($fd==='2026-06-19')?'selected':''?>>Vendredi 19</option>
    </select>
    
    <button type="submit" class="btn-filter">Filtrer</button>
    <a href="admin.php" class="btn-reset">Actualiser</a>
    <span class="results-count"><?= count($inscriptions) ?> lignes</span>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Visiteur</th>
          <th>Profil</th>
          <th>Salle</th>
          <th>Date & Horaire</th>
          <th>Places</th>
          <th>Buffet</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($inscriptions as $ins):
          $initials = strtoupper(substr($ins['nom'] ?? 'V', 0, 1) . substr($ins['prenom'] ?? 'X', 0, 1));
          $date_fr = !empty($ins['date_expo']) ? ((new DateTime($ins['date_expo']))->format('d/m') === '18/06/2026' ? 'Jeu 18 juin' : 'Ven 19 juin') : null;
        ?>
        <tr>
          <td style="color:#aaa; font-family: 'Space Mono'; font-size:.75rem;">
             <?= $ins['id_inscription'] ?? '—' ?>
          </td>
          <td>
            <div class="td-name">
              <div class="avatar"><?= $initials ?></div>
              <div>
                <strong><?= htmlspecialchars(($ins['nom'] ?? '') . ' ' . ($ins['prenom'] ?? '')) ?></strong>
                <span><?= htmlspecialchars($ins['email']) ?></span>
              </div>
            </div>
          </td>
          <td style="color:#555;"><?= htmlspecialchars($ins['profil'] ?? '—') ?></td>
          <td>
             <?= $ins['numero'] ? '<span class="badge badge-salle">'.$ins['numero'].'</span>' : '<span style="color:#ccc">—</span>' ?>
          </td>
          <td>
            <?php if ($date_fr): ?>
              <strong><?= $date_fr ?></strong>
              <div style="font-size:.75rem;color:#777;font-family:'Space Mono'"><?= substr($ins['heure_debut'],0,5) ?>–<?= substr($ins['heure_fin'],0,5) ?></div>
            <?php else: ?>
              <span class="badge badge-none">Aucune réservation</span>
            <?php endif; ?>
          </td>
          <td style="font-weight:700; text-align:center;"><?= $ins['nb_personnes'] ?? 0 ?></td>
          <td>
            <?php if ($ins['id_inscription'] && $ins['participe_buffet']): ?>
              <span class="badge badge-buffet">✓ Oui</span>
            <?php else: ?>
              <span style="color:#ccc">—</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="actions">
              <?php if ($ins['id_inscription']): ?>
                <button class="btn-edit" onclick="openEdit(<?= htmlspecialchars(json_encode($ins)) ?>)">✏️</button>
                <form method="POST" onsubmit="return confirm('Supprimer l\'inscription ?')">
                  <input type="hidden" name="delete_id" value="<?= $ins['id_inscription'] ?>"/>
                  <button type="submit" class="btn-del">🗑️</button>
                </form>
              <?php else: ?>
                <span style="color:#ccc; font-size:.75rem;">Aucune</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-top">
      <h2>Modifier l'inscription</h2>
      <button class="modal-close" onclick="closeEdit()">×</button>
    </div>
    <div class="modal-info" id="modalInfo"></div>
    <form method="POST">
      <input type="hidden" name="update_id" id="edit_id"/>
      <div class="form-group">
        <label>Nombre de places</label>
        <input type="number" name="nb_personnes" id="edit_nb" min="1" max="12" required/>
      </div>
      <div class="form-group" style="flex-direction:row;align-items:center;gap:.5rem; margin-top:1rem;">
        <input type="checkbox" name="participe_buffet" id="edit_buffet" style="width:16px;height:16px"/>
        <label for="edit_buffet" style="cursor:pointer">Participe au buffet</label>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeEdit()">Annuler</button>
        <button type="submit" class="btn-save">Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<footer>
  <span>© 2026 E-LLUSION — Administration</span>
</footer>

<script>
function openEdit(ins) {
  document.getElementById('edit_id').value = ins.id_inscription;
  document.getElementById('edit_nb').value = ins.nb_personnes;
  document.getElementById('edit_buffet').checked = ins.participe_buffet == 1;
  document.getElementById('modalInfo').innerHTML = `<strong>${ins.nom} ${ins.prenom}</strong><br>Salle ${ins.numero} — ${(ins.heure_debut||'').slice(0,5)}`;
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
document.getElementById('editModal').addEventListener('click', e => { if(e.target===document.getElementById('editModal')) closeEdit(); });
</script>
<?php endif; ?>
</body>
</html>