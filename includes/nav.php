<?php
// On vérifie si une session est déjà active avant de la démarrer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// On détermine si l'utilisateur est connecté (présence de visiteur_id dans la session)
$is_connected = isset($_SESSION['visiteur_id']);
?>
<nav class="main-nav">
  <div class="nav-container">
    <a href="index.php" class="nav-logo" data-text="E-LLUSION">E-LLUSION</a>
    
    <div class="nav-links">
      <a href="index.php" class="nav-item">Accueil</a>
      <a href="salles.php" class="nav-item">Les Salles</a>
      
      <?php if ($is_connected): ?>
        <a href="gestion.php" class="nav-item">Mon Compte</a>
        <a href="logout.php" class="nav-item" style="color: #d9534f;">Déconnexion</a>
      <?php else: ?>
        <a href="connexion.php" class="nav-item">Se connecter</a>
        <a href="inscription.php" class="btn-reserver">S'inscrire</a>
      <?php endif; ?>
      
    </div>
  </div>
</nav>