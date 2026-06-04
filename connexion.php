<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // Vérifier si le visiteur existe dans la table
        $stmt = $pdo->prepare("SELECT * FROM visiteur WHERE email = ?");
        $stmt->execute([$email]);
        $visiteur = $stmt->fetch();

        if ($visiteur) {
            // Création de la session et redirection
            $_SESSION['visiteur_id'] = $visiteur['id_visiteur'];
            $_SESSION['visiteur_nom'] = $visiteur['nom'];
            $_SESSION['visiteur_prenom'] = $visiteur['prenom'];
            header("Location: gestion.php");
            exit;
        } else {
            $error = "Cet e-mail n'est associé à aucune réservation.";
        }
    } else {
        $error = "Veuillez saisir votre adresse e-mail.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>E-LLUSION — Connexion</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="style.css"/>
    <style>
        .login-container { max-width: 450px; margin: 8rem auto; padding: 2.5rem; background: var(--white); border: 1px solid var(--cyan); text-align: center; }
        .form-group { margin-bottom: 1.5rem; text-align: left; }
        .form-group label { display: block; font-family: 'Space Mono'; font-size: 0.8rem; margin-bottom: 0.5rem; text-transform: uppercase; }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid #ccc; font-size: 1rem; box-sizing: border-box; }
        .btn-cyber { background: var(--cyan); color: var(--ink); border: none; padding: 1rem; width: 100%; font-family: 'Space Mono'; font-weight: bold; text-transform: uppercase; cursor: pointer; }
        .btn-cyber:hover { background: var(--ink); color: var(--cyan); }
        .error { color: #d9534f; font-family: 'Space Mono'; font-size: 0.85rem; margin-bottom: 1rem; text-align: left; }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="login-container">
    <h2 style="font-family: 'Space Mono'; text-transform: uppercase;">Se connecter<span>.</span></h2>
    <p style="color: #666; font-size: 0.9rem; margin-bottom: 2rem;">Saisissez l'e-mail utilisé lors de votre inscription pour gérer vos réservations.</p>
    
    <?php if ($error): ?>
        <div class="error">// <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Adresse E-mail</label>
            <input type="email" id="email" name="email" class="form-control" required placeholder="exemple@domaine.com">
        </div>
        <button type="submit" class="btn-cyber">Accéder à mon espace</button>
    </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>