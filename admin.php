<?php
session_start();

$host = 'localhost'; $db = 'sae203'; $user = 'root'; $pass = 'local';
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
            $message = 'Inscription supprimée et places libérées.'; $msg_type = 'success';
        }
    }
    if (isset($_POST['update_id'])) {
        $id=(int)$_POST['update_id']; $statut=$_POST['statut']??''; $nb=(int)($_POST['nb_personnes']??1); $buffet=isset($_POST['participe_buffet'])?1:0;
        $old=$pdo->prepare("SELECT nb_personnes,id_creneau FROM inscription WHERE id_inscription=?");
        $old->execute([$id]); $prev=$old->fetch();
        if ($prev) {
            $pdo->prepare("UPDATE creneau SET places_restante=places_restante+? WHERE id_creneau=?")->execute([$prev['nb_personnes']-$nb,$prev['id_creneau']]);
            $pdo->prepare("UPDATE inscription SET statut=?,nb_personnes=?,participe_buffet=? WHERE id_inscription=?")->execute([$statut,$nb,$buffet,$id]);
            $message='Inscription mise à jour.'; $msg_type='success';
        }
    }
}

$inscriptions=[]; $stats=['total'=>0,'personnes'=>0,'buffet'=>0,'salles'=>[]];
if (isset($_SESSION['admin'])) {
    $fs=$_GET['salle']??''; $fd=$_GET['date']??''; $fstat=$_GET['statut']??'';
    $sql="SELECT i.id_inscription,v.nom,v.prenom,v.email,v.profil,s.numero,s.nom_thematique,d.date AS date_expo,c.heure_debut,c.heure_fin,c.places_restante,i.nb_personnes,i.statut,i.participe_buffet,i.date_inscription FROM inscription i JOIN visiteur v ON i.id_visiteur=v.id_visiteur JOIN creneau c ON i.id_creneau=c.id_creneau JOIN salle s ON c.id_salle=s.id_salle JOIN date_expo d ON c.id_date=d.id_date WHERE 1=1";
    $params=[];
    if($fs){$sql.=" AND s.numero=?";$params[]=$fs;}
    if($fd){$sql.=" AND d.date=?";$params[]=$fd;}
    if($fstat){$sql.=" AND i.statut=?";$params[]=$fstat;}
    $sql.=" ORDER BY d.date,c.heure_debut,s.numero";
    $stmt=$pdo->prepare($sql); $stmt->execute($params); $inscriptions=$stmt->fetchAll();
    $stats['total']=count($inscriptions);
    $stats['personnes']=array_sum(array_column($inscriptions,'nb_personnes'));
    $stats['buffet']=count(array_filter($inscriptions,fn($r)=>$r['participe_buffet']));
    $sq=$pdo->query("SELECT s.numero,s.nom_thematique,COUNT(i.id_inscription) as nb,COALESCE(SUM(i.nb_personnes),0) as pers FROM salle s LEFT JOIN creneau c ON s.id_salle=c.id_salle LEFT JOIN inscription i ON c.id_creneau=i.id_creneau GROUP BY s.id_salle ORDER BY s.numero");
    $stats['salles']=$sq->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>E-LLUSION — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<style>
:root{--mint:#b2f0e8;--mint-dark:#7de0d2;--black:#111;--white:#fff;--grey:#f4f4f4;--text:#222;--accent:#e0302a;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;color:var(--text);background:#f7fffe;min-height:100vh;}

/* NAV */
nav{position:sticky;top:0;z-index:100;background:var(--black);display:flex;align-items:center;justify-content:space-between;padding:0 2rem;height:56px;}
.nav-logo{font-family:'Space Mono',monospace;font-size:1rem;font-weight:700;color:var(--mint);text-decoration:none;}
.nav-badge{font-family:'Space Mono',monospace;font-size:.65rem;background:var(--accent);color:#fff;padding:.15rem .5rem;border-radius:4px;margin-left:.5rem;vertical-align:middle;}
.nav-right{display:flex;align-items:center;gap:1.5rem;}
.nav-right a{font-size:.85rem;color:#aaa;text-decoration:none;transition:color .2s;}
.nav-right a:hover{color:var(--mint);}
.btn-logout{background:transparent;color:#aaa;border:1px solid #444;border-radius:999px;padding:.35rem .9rem;font-family:'DM Sans',sans-serif;font-size:.78rem;cursor:pointer;transition:all .2s;}
.btn-logout:hover{border-color:var(--mint);color:var(--mint);}

/* LOGIN */
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#f7fffe 0%,var(--mint) 100%);}
.login-card{background:var(--white);border-radius:20px;padding:3rem;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.1);}
.login-logo{font-family:'Space Mono',monospace;font-size:1.8rem;font-weight:700;color:var(--black);margin-bottom:.2rem;}
.login-logo span{color:var(--accent);}
.login-sub{font-size:.85rem;color:#888;margin-bottom:2rem;}
.form-group{display:flex;flex-direction:column;gap:.4rem;margin-bottom:1rem;}
.form-group label{font-size:.82rem;font-weight:600;color:#444;}
.form-group input{border:1.5px solid #e0e0e0;border-radius:10px;padding:.7rem 1rem;font-family:'DM Sans',sans-serif;font-size:.9rem;outline:none;transition:border-color .2s;}
.form-group input:focus{border-color:var(--mint-dark);}
.btn-submit{width:100%;padding:.9rem;background:var(--black);border:none;border-radius:999px;font-family:'DM Sans',sans-serif;font-size:.95rem;font-weight:700;color:var(--white);cursor:pointer;transition:opacity .2s;margin-top:.5rem;}
.btn-submit:hover{opacity:.8;}
.error-msg{background:#ffe5e5;border:1px solid #e0302a;border-radius:10px;padding:.8rem 1rem;font-size:.85rem;color:#b00000;margin-bottom:1rem;}
.login-hint{text-align:center;margin-top:1.2rem;font-size:.75rem;color:#bbb;}

/* DASHBOARD */
.admin-wrap{max-width:1300px;margin:0 auto;padding:2rem;}
.page-title{font-family:'Space Mono',monospace;font-size:1.3rem;font-weight:700;margin-bottom:.2rem;}
.page-sub{font-size:.85rem;color:#777;margin-bottom:2rem;}

/* Stats */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:2rem;}
.stat-card{background:var(--white);border:1px solid #e8f8f5;border-radius:16px;padding:1.2rem 1.5rem;transition:box-shadow .2s;}
.stat-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.07);}
.stat-card.accent{background:var(--black);border-color:var(--black);}
.stat-card.mint{background:var(--mint);border-color:transparent;}
.stat-label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#999;margin-bottom:.4rem;}
.stat-card.accent .stat-label{color:#888;}
.stat-card.mint .stat-label{color:#1a7a6e;}
.stat-value{font-family:'Space Mono',monospace;font-size:2rem;font-weight:700;color:var(--black);}
.stat-card.accent .stat-value{color:var(--mint);}
.stat-card.mint .stat-value{color:var(--black);}
.stat-sub{font-size:.75rem;color:#bbb;margin-top:.2rem;}
.stat-card.mint .stat-sub{color:#2bb5a0;}

/* Alert */
.alert{border-radius:12px;padding:.9rem 1.2rem;font-size:.88rem;font-weight:600;margin-bottom:1.5rem;display:flex;align-items:center;gap:.6rem;}
.alert.success{background:#d4f5ee;border:1px solid var(--mint-dark);color:#1a7a6e;}
.alert.error{background:#ffe5e5;border:1px solid #e0302a;color:#b00000;}

/* Filters */
.filters-bar{background:var(--white);border:1px solid #eee;border-radius:14px;padding:1rem 1.5rem;display:flex;gap:1rem;flex-wrap:wrap;align-items:center;margin-bottom:1.5rem;}
.filters-bar label{font-size:.78rem;font-weight:700;color:#666;white-space:nowrap;}
.filters-bar select{border:1.5px solid #e0e0e0;border-radius:8px;padding:.4rem .8rem;font-family:'DM Sans',sans-serif;font-size:.82rem;background:var(--white);outline:none;cursor:pointer;}
.filters-bar select:focus{border-color:var(--mint-dark);}
.btn-filter{background:var(--black);color:var(--white);border:none;border-radius:999px;padding:.45rem 1.2rem;font-family:'DM Sans',sans-serif;font-size:.82rem;font-weight:600;cursor:pointer;transition:opacity .2s;}
.btn-filter:hover{opacity:.75;}
.btn-reset{background:none;border:1.5px solid #ddd;color:#666;border-radius:999px;padding:.45rem 1rem;font-family:'DM Sans',sans-serif;font-size:.82rem;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;}
.btn-reset:hover{border-color:#999;}
.results-count{margin-left:auto;font-size:.8rem;color:#aaa;font-family:'Space Mono',monospace;}

/* Table */
.table-wrap{background:var(--white);border:1px solid #eee;border-radius:16px;overflow:hidden;}
.table-header{padding:1rem 1.5rem;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;}
.table-title{font-size:.9rem;font-weight:700;color:var(--black);}
table{width:100%;border-collapse:collapse;font-size:.83rem;}
thead tr{background:var(--grey);}
thead th{padding:.75rem 1rem;text-align:left;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#888;white-space:nowrap;border-bottom:2px solid #eee;}
tbody tr{border-bottom:1px solid #f5f5f5;transition:background .15s;}
tbody tr:hover{background:#f7fffe;}
tbody tr:last-child{border-bottom:none;}
td{padding:.8rem 1rem;vertical-align:middle;}

.avatar{width:32px;height:32px;border-radius:50%;background:var(--mint);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;color:var(--black);flex-shrink:0;}
.td-name{display:flex;align-items:center;gap:.7rem;}
.td-name strong{display:block;font-weight:600;}
.td-name span{font-size:.75rem;color:#999;}

.badge{display:inline-flex;align-items:center;padding:.2rem .65rem;border-radius:999px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;gap:.3rem;}
.badge-confirmed{background:#d4f5ee;color:#1a7a6e;}
.badge-modified{background:#fff3cd;color:#856404;}
.badge-pending{background:#f0f0f0;color:#666;}
.badge-cancelled{background:#ffe5e5;color:#b00000;}
.badge-salle{background:var(--mint);color:var(--black);}
.badge-buffet{background:#e8f4fd;color:#1565c0;}

.actions{display:flex;gap:.4rem;}
.btn-edit{background:none;border:1.5px solid #ddd;border-radius:8px;padding:.3rem .7rem;font-family:'DM Sans',sans-serif;font-size:.75rem;font-weight:600;cursor:pointer;transition:all .2s;color:var(--black);}
.btn-edit:hover{background:var(--black);color:var(--white);border-color:var(--black);}
.btn-del{background:none;border:1.5px solid #ffd0d0;border-radius:8px;padding:.3rem .7rem;font-family:'DM Sans',sans-serif;font-size:.75rem;font-weight:600;cursor:pointer;color:var(--accent);transition:all .2s;}
.btn-del:hover{background:var(--accent);color:var(--white);border-color:var(--accent);}

/* Empty */
.empty-state{text-align:center;padding:4rem 2rem;color:#bbb;}
.empty-state p{margin-top:.5rem;font-size:.88rem;}

/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:300;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
.modal-overlay.open{display:flex;}
.modal{background:var(--white);border-radius:20px;padding:2rem;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;box-shadow:0 30px 80px rgba(0,0,0,.2);}
.modal-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;}
.modal-top h2{font-family:'Space Mono',monospace;font-size:1rem;}
.modal-close{background:none;border:none;font-size:1.4rem;cursor:pointer;color:#aaa;line-height:1;}
.modal-close:hover{color:var(--black);}
.modal-info{background:var(--grey);border-radius:10px;padding:.9rem 1.1rem;font-size:.83rem;line-height:1.9;color:#555;margin-bottom:1.5rem;}
.modal-info strong{color:var(--black);}
.modal-actions{display:flex;gap:.75rem;margin-top:1.5rem;}
.btn-cancel{flex:1;background:none;border:1.5px solid #ddd;border-radius:999px;padding:.65rem;font-family:'DM Sans',sans-serif;font-size:.88rem;cursor:pointer;}
.btn-save{flex:2;background:var(--black);border:none;border-radius:999px;padding:.65rem 1.5rem;font-family:'DM Sans',sans-serif;font-size:.88rem;font-weight:700;color:var(--white);cursor:pointer;transition:opacity .2s;}
.btn-save:hover{opacity:.8;}

footer{background:var(--white);border-top:1px solid #eee;padding:1.2rem 2rem;display:flex;align-items:center;justify-content:space-between;font-size:.78rem;color:#aaa;margin-top:3rem;}
</style>
</head>
<body>

<?php if (!isset($_SESSION['admin'])): ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">E–LLUSION<span>.</span></div>
    <p class="login-sub">Espace administration — accès restreint</p>
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
        <input type="password" name="password" placeholder="••••••••" required/>
      </div>
      <button type="submit" class="btn-submit">Accéder au dashboard →</button>
    </form>
    <p class="login-hint">Mot de passe : <code>password</code></p>
  </div>
</div>

<?php else: ?>
<nav>
  <span style="display:flex;align-items:center">
    <a class="nav-logo" href="index.php">E-LLUSION</a>
    <span class="nav-badge">ADMIN</span>
  </span>
  <div class="nav-right">
    <a href="index.php">← Retour au site</a>
    <a href="inscription.php">Inscriptions</a>
    <form method="GET" style="margin:0">
      <input type="hidden" name="logout" value="1"/>
      <button type="submit" class="btn-logout">Déconnexion</button>
    </form>
  </div>
</nav>

<div class="admin-wrap">

  <h1 class="page-title">Dashboard</h1>
  <p class="page-sub">Exposition E-LLUSION · 18 & 19 juin 2026 · IUT de Chambéry</p>

  <?php if ($message): ?>
    <div class="alert <?= $msg_type ?>">
      <?= $msg_type==='success' ? '✅' : '⚠️' ?> <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <!-- Stats globales -->
  <div class="stats-grid">
    <div class="stat-card accent">
      <div class="stat-label">Inscriptions</div>
      <div class="stat-value"><?= $stats['total'] ?></div>
      <div class="stat-sub">réservations totales</div>
    </div>
    <div class="stat-card mint">
      <div class="stat-label">Visiteurs</div>
      <div class="stat-value"><?= $stats['personnes'] ?></div>
      <div class="stat-sub">personnes attendues</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Buffet jeudi</div>
      <div class="stat-value"><?= $stats['buffet'] ?></div>
      <div class="stat-sub">participants 18h30</div>
    </div>
    <?php foreach ($stats['salles'] as $s): ?>
    <div class="stat-card">
      <div class="stat-label">Salle <?= $s['numero'] ?></div>
      <div class="stat-value"><?= $s['nb'] ?></div>
      <div class="stat-sub"><?= $s['pers'] ?> personne(s)</div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Filtres -->
  <form method="GET" class="filters-bar">
    <label>Salle</label>
    <select name="salle">
      <option value="">Toutes</option>
      <?php foreach(['001','002','005','021'] as $n): ?>
        <option value="<?=$n?>" <?=($fs===$n)?'selected':''?>><?=$n?></option>
      <?php endforeach; ?>
    </select>
    <label>Date</label>
    <select name="date">
      <option value="">Toutes</option>
      <option value="2026-06-18" <?=($fd==='2026-06-18')?'selected':''?>>Jeudi 18 juin</option>
      <option value="2026-06-19" <?=($fd==='2026-06-19')?'selected':''?>>Vendredi 19 juin</option>
    </select>
    <label>Statut</label>
    <select name="statut">
      <option value="">Tous</option>
      <option value="confirmé" <?=($fstat==='confirmé')?'selected':''?>>Confirmé</option>
      <option value="modifié" <?=($fstat==='modifié')?'selected':''?>>Modifié</option>
      <option value="annulé" <?=($fstat==='annulé')?'selected':''?>>Annulé</option>
    </select>
    <button type="submit" class="btn-filter">Filtrer</button>
    <a href="admin.php" class="btn-reset">Réinitialiser</a>
    <span class="results-count"><?= count($inscriptions) ?> résultat(s)</span>
  </form>

  <!-- Tableau -->
  <div class="table-wrap">
    <div class="table-header">
      <span class="table-title">Liste des inscriptions</span>
    </div>
    <?php if (empty($inscriptions)): ?>
      <div class="empty-state">
        <div style="font-size:2rem">📭</div>
        <p>Aucune inscription trouvée.</p>
      </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Visiteur</th>
          <th>Profil</th>
          <th>Salle</th>
          <th>Date & Créneau</th>
          <th>Pers.</th>
          <th>Buffet</th>
          <th>Statut</th>
          <th>Inscrit le</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($inscriptions as $ins):
          $initials = strtoupper(substr($ins['nom'],0,1).substr($ins['prenom'],0,1));
          $date_fr = (new DateTime($ins['date_expo']))->format('d/m/Y') === '18/06/2026' ? 'Jeu. 18 juin' : 'Ven. 19 juin';
        ?>
        <tr>
          <td style="color:#ccc;font-family:'Space Mono',monospace;font-size:.72rem"><?= $ins['id_inscription'] ?></td>
          <td>
            <div class="td-name">
              <div class="avatar"><?= $initials ?></div>
              <div>
                <strong><?= htmlspecialchars($ins['nom'].' '.$ins['prenom']) ?></strong>
                <span><?= htmlspecialchars($ins['email']) ?></span>
              </div>
            </div>
          </td>
          <td style="font-size:.78rem;color:#666"><?= htmlspecialchars($ins['profil']) ?></td>
          <td><span class="badge badge-salle"><?= $ins['numero'] ?></span></td>
          <td>
            <div style="font-size:.82rem;font-weight:600"><?= $date_fr ?></div>
            <div style="font-size:.75rem;color:#999;font-family:'Space Mono',monospace"><?= substr($ins['heure_debut'],0,5) ?>–<?= substr($ins['heure_fin'],0,5) ?></div>
          </td>
          <td style="text-align:center;font-weight:700"><?= $ins['nb_personnes'] ?></td>
          <td style="text-align:center">
            <?php if ($ins['participe_buffet']): ?>
              <span class="badge badge-buffet">✓ Oui</span>
            <?php else: ?>
              <span style="color:#ddd">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php $sc='badge-pending';
              if($ins['statut']==='confirmé')$sc='badge-confirmed';
              elseif($ins['statut']==='modifié')$sc='badge-modified';
              elseif($ins['statut']==='annulé')$sc='badge-cancelled';
            ?>
            <span class="badge <?= $sc ?>"><?= htmlspecialchars($ins['statut']??'en attente') ?></span>
          </td>
          <td style="font-size:.75rem;color:#aaa"><?= (new DateTime($ins['date_inscription']))->format('d/m H:i') ?></td>
          <td>
            <div class="actions">
              <button class="btn-edit" onclick="openEdit(<?= htmlspecialchars(json_encode($ins)) ?>)">✏️ Modifier</button>
              <form method="POST" onsubmit="return confirm('Supprimer cette inscription ?')">
                <input type="hidden" name="delete_id" value="<?= $ins['id_inscription'] ?>"/>
                <button type="submit" class="btn-del">🗑️</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

</div>

<!-- Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-top">
      <h2>✏️ Modifier l'inscription</h2>
      <button class="modal-close" onclick="closeEdit()">×</button>
    </div>
    <div class="modal-info" id="modalInfo"></div>
    <form method="POST">
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
        <label for="edit_buffet" style="font-weight:400;font-size:.88rem;cursor:pointer">Participe au buffet du jeudi 18h30</label>
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
  <span style="font-family:'Space Mono',monospace;font-size:.7rem">SAE 203 · MMI1</span>
</footer>

<script>
function openEdit(ins) {
  document.getElementById('edit_id').value = ins.id_inscription;
  document.getElementById('edit_nb').value = ins.nb_personnes;
  document.getElementById('edit_statut').value = ins.statut || 'en attente';
  document.getElementById('edit_buffet').checked = ins.participe_buffet == 1;
  const df = ins.date_expo ? new Date(ins.date_expo).toLocaleDateString('fr-FR',{weekday:'long',day:'numeric',month:'long'}) : '';
  document.getElementById('modalInfo').innerHTML =
    `<strong>${ins.nom} ${ins.prenom}</strong><br>${ins.email}<br>Profil : ${ins.profil}<br>Salle ${ins.numero} · ${df} · ${(ins.heure_debut||'').slice(0,5)}–${(ins.heure_fin||'').slice(0,5)}`;
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
document.getElementById('editModal').addEventListener('click', e => { if(e.target===document.getElementById('editModal')) closeEdit(); });
</script>
<?php endif; ?>
</body>
</html>