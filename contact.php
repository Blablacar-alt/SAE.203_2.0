<?php include __DIR__ . '/includes/nav.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-LLUSION — Contact</title>
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>


<main>

  <div class="page-header">
    <h1>CONTACT</h1>
    <p>Une question ? Besoin d'informations ? Contactez-nous !</p>
  </div>

  <div class="contact-grid">

    <div class="contact-card">
      <h3>Responsable du projet</h3>
      <div class="contact-item">
        <div class="contact-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
        <path d="M21 6C21 5.44772 20.5523 5 20 5H4C3.44772 5 3 5.44772 3 6V18C3 18.5523 3.44772 19 4 19H20C20.5523 19 21 18.5523 21 18V6ZM23 18C23 19.6569 21.6569 21 20 21H4C2.34315 21 1 19.6569 1 18V6C1 4.34315 2.34315 3 4 3H20C21.6569 3 23 4.34315 23 6V18Z" fill="black"/>
        <path d="M21.4637 6.15625C21.9299 5.86006 22.5474 5.99774 22.8436 6.46387C23.1398 6.93 23.0021 7.54755 22.536 7.84375L13.5663 13.5439L13.5604 13.5479C13.0927 13.8408 12.5518 13.9961 11.9999 13.9961C11.448 13.9961 10.9071 13.8408 10.4393 13.5479L10.4335 13.5439L1.46374 7.84375C0.997617 7.54755 0.859938 6.93 1.15613 6.46387C1.45233 5.99774 2.06988 5.86006 2.53601 6.15625L11.4999 11.8525C11.6495 11.9463 11.8234 11.9961 11.9999 11.9961C12.1764 11.9961 12.3493 11.9463 12.4989 11.8525L21.4637 6.15625Z" fill="black"/>
        </svg></div>
        <div>
          <h4>Email</h4>
          <a href="mailto:francois.piranda@univ-smb.fr">francois.piranda@univ-smb.fr</a>
          <p>Pour toute question concernant l'exposition ou les inscriptions</p>
        </div>
      </div>
      <div class="contact-item">
        <div class="contact-icon">📍</div>
        <div>
          <h4>Lieu</h4>
          <p>IUT de Chambéry — Université Savoie Mont Blanc<br/>Département MMI</p>
        </div>
      </div>
      <div class="contact-item">
        <div class="contact-icon">📅</div>
        <div>
          <h4>Dates de l'exposition</h4>
          <p>Jeudi 18 juin 2026 : 15h – 20h<br/>Vendredi 19 juin 2026 : 9h30 – 11h</p>
        </div>
      </div>
    </div>

    <div class="contact-card mint">
      <h3>Service des inscriptions</h3>
      <p style="font-size:.9rem;margin-bottom:1rem">Pour toute question concernant votre réservation, modification ou annulation :</p>
      <div class="contact-item">
        <div class="contact-icon">✉️</div>
        <div>
          <a href="mailto:contact.ellusion@univ-smb.fr">contact.ellusion@univ-smb.fr</a>
          <p>Un·e étudiant·e référent·e vous répondra dans les plus brefs délais.</p>
        </div>
      </div>
      <h3 style="margin-top:1.5rem">Suivez-nous</h3>
      <div class="contact-item">
        <div class="contact-icon">📸</div>
        <div>
          <h4>Instagram MMI Chambéry</h4>
          <a href="https://www.instagram.com/mmichambery/" target="_blank">@mmichambery</a>
        </div>
      </div>
      <div class="contact-item">
        <div class="contact-icon">🌐</div>
        <div>
          <h4>Site web MMI</h4>
          <a href="https://www.iut-chy.univ-smb.fr/" target="_blank">www.mmi-chambery.fr</a>
        </div>
      </div>
    </div>

    <div class="faq-card">
      <h3 style="margin-bottom:1.5rem">Questions fréquentes</h3>
      <div class="faq-item">
        <h4>Comment modifier ou annuler ma réservation ?</h4>
        <p>Utilisez le lien présent dans l'email de confirmation que vous avez reçu, ou contactez-nous à contact.ellusion@univ-smb.fr</p>
      </div>
      <div class="faq-item">
        <h4>Combien de temps dure la visite ?</h4>
        <p>Comptez environ 30 minutes par salle pour profiter pleinement de l'expérience immersive.</p>
      </div>
      <div class="faq-item">
        <h4>L'exposition est-elle accessible à tous ?</h4>
        <p>Oui ! L'exposition est ouverte à tous : étudiants, enseignants, personnel de l'université et visiteurs extérieurs. Certaines installations sont interactives et adaptées à tous les âges.</p>
      </div>
      <div class="faq-item">
        <h4>Puis-je visiter plusieurs salles ?</h4>
        <p>Chaque inscription correspond à une salle. Si vous souhaitez visiter plusieurs salles, merci de faire une réservation pour chaque salle qui vous intéresse.</p>
      </div>
    </div>

  </div>

</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>