<?php include __DIR__ . '/includes/nav.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-LLUSION — Accueil</title>
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css"/>
</head>
<body>

<main>

 <section id="accueil" class="active">

  <div class="hero">
    <div class="carousel-container">
      <div class="carousel-track">
        <img src="img/Image 1.jpg" alt="Image 1">
        <img src="img/Image 2.jpg" alt="Image 2">
        <img src="img/Image 3.webp" alt="Image 3">
        <img src="img/Image 4.jpg" alt="Image 4">
        <img src="img/Image 5.jpg" alt="Image 5">
        <img src="img/Image 6.webp" alt="Image 6">
      </div>
    </div>

    <div class="hero-content">
      <h1 class="hero-title">E–LLUSION<span>.</span></h1>
      <p class="hero-sub">
        Une exposition d'art interactive et multimédia pensée par les étudiant·es
        <strong>MMI1</strong> de l'Université Savoie Mont Blanc.
      </p>
      <div class="hero-buttons">
        <a href="inscription.php" class="btn-primary">S'inscrire →</a>
        <a href="salles.php" class="btn-outline">Découvrir l'expo</a>
      </div>
      <div class="hero-meta">
        <span><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none">
        <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="#EEEEEE" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M16 2V6M8 2V6M3 10H21M5 4H19C20.1046 4 21 4.89543 21 6V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V6C3 4.89543 3.89543 4 5 4Z" stroke="#00BBAA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg> 18 &amp; 19 juin 2026</span>
        <span><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 54 60" fill="none">
        <path d="M29.4038 0.375C16.7131 0.374988 4.375 10.5689 4.375 25.6841C4.375 38.6206 19.5359 54.2465 24.821 58.375L28.0945 58.375C28.9451 58.375 29.7728 58.1048 30.4441 57.5823C35.0493 53.997 53.375 38.9 53.375 25.6841C53.375 10.5689 42.0944 0.375012 29.4038 0.375Z" fill="#0B5243" stroke="#0B5243" stroke-width="0.75" stroke-linejoin="round"/>
        <path d="M0.375 25.8121C0.375 38.531 17.9189 53.4786 23.6302 58.0077C24.675 58.8362 26.1196 58.8225 27.1461 57.9714C32.6614 53.3993 49.375 38.4972 49.375 25.8121C49.375 10.6205 38.0944 0.375012 25.4038 0.375C12.7131 0.374988 0.375 10.6205 0.375 25.8121Z" fill="#0CC79F" stroke="#0B5243" stroke-width="0.75" stroke-linejoin="round"/>
        <path d="M24.875 37.375C31.7786 37.375 37.375 31.7786 37.375 24.875C37.375 17.9714 31.7786 12.375 24.875 12.375C17.9714 12.375 12.375 17.9714 12.375 24.875C12.375 31.7786 17.9714 37.375 24.875 37.375Z" fill="#FBF9ED"/>
        </svg> IUT de Chambéry — USMB</span>
        <span><svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 66 66" fill="none">
        <path d="M32.9947 0C33.9768 0 34.9334 0.320543 35.7159 0.914079C36.4001 1.43317 36.9205 2.13492 37.2171 2.9356L37.3296 3.28717L37.3471 3.34693L42.0898 21.7516C42.2238 22.2702 42.495 22.7439 42.8738 23.1227C43.2528 23.5014 43.7262 23.7728 44.2449 23.9067L62.6496 28.6493L62.6988 28.6634C63.5288 28.8926 64.2742 29.3526 64.8469 29.9888L65.0824 30.2736L65.2933 30.5759C65.7547 31.2975 66 32.1402 66 33.0018C65.9997 33.986 65.6786 34.9433 65.0824 35.7264C64.4859 36.5097 63.6479 37.0746 62.6988 37.3366L62.6496 37.3507L44.2449 42.0933C43.7262 42.2271 43.2527 42.4987 42.8738 42.8773C42.4951 43.2561 42.2239 43.7299 42.0898 44.2484L37.3436 62.6531C37.3386 62.6726 37.3315 62.6934 37.3261 62.7128C37.0614 63.6586 36.4948 64.4924 35.7124 65.0859C34.9303 65.6791 33.9763 65.9997 32.9947 66C32.0126 66 31.056 65.6794 30.2736 65.0859C29.4914 64.4924 28.9245 63.6584 28.6599 62.7128C28.6544 62.6933 28.6474 62.6727 28.6423 62.6531L23.8996 44.2484C23.7656 43.7299 23.4944 43.256 23.1156 42.8773C22.7367 42.4988 22.2632 42.2271 21.7445 42.0933L3.3399 37.3471C3.31666 37.3411 3.29268 37.3361 3.26959 37.3296C2.32774 37.0621 1.49734 36.4935 0.907047 35.7124C0.391375 35.0294 0.083482 34.2153 0.0140628 33.3674L0 33.0018L0.0140628 32.6361C0.0829766 31.787 0.390639 30.9715 0.907047 30.2876L1.1426 30.0029C1.70941 29.3682 2.44615 28.9042 3.26959 28.6704C3.29271 28.6639 3.31664 28.6589 3.3399 28.6529L21.7445 23.9032L22.1242 23.7766C22.4943 23.6264 22.8312 23.4033 23.1156 23.1192C23.4947 22.7404 23.7655 22.2669 23.8996 21.748L28.6458 3.34693L28.6634 3.28717C28.9281 2.34142 29.4947 1.50762 30.2771 0.914079L30.5795 0.703138C31.2991 0.244903 32.1365 0.000306947 32.9947 0ZM29.7111 23.2492C29.3087 24.8053 28.4957 26.2264 27.3591 27.3626C26.2219 28.499 24.799 29.3097 23.2422 29.7111L10.5014 32.9982L23.2422 36.2854H23.2457C24.8019 36.6872 26.2224 37.4976 27.3591 38.6339C28.4963 39.7711 29.3093 41.1934 29.7111 42.7508L32.9912 55.4881L36.2784 42.7508C36.6801 41.1934 37.4931 39.7711 38.6304 38.6339C39.7675 37.4971 41.1903 36.6871 42.7472 36.2854L55.4916 32.9982L42.7472 29.7146C41.1902 29.3129 39.7675 28.503 38.6304 27.3661C37.6349 26.3707 36.8871 25.1572 36.4471 23.8258L36.2784 23.2492L32.9947 10.5084L29.7111 23.2492Z" fill="rgba(12, 199, 159, 1)"/>
        <path d="M54 18.8309V6.16543C54 4.41653 55.3425 3 57 3C58.6575 3 60 4.41653 60 6.16543V18.8309C60 20.5798 58.6575 22 57 22C55.3425 22 54 20.5798 54 18.8309Z" fill="rgba(12, 199, 159, 1)"/>
        <path d="M62.9977 11C64.6545 11 66 12.3425 66 14C66 15.6575 64.6545 17 62.9977 17H50.9988C49.342 17 48 15.6575 48 14C48 12.3425 49.342 11 50.9988 11H62.9977Z" fill="rgba(12, 199, 159, 1)"/>
        <path d="M6 54.2508V48.7492C6 47.2303 7.3425 46 9 46C10.6575 46 12 47.2303 12 48.7492V54.2508C11.9994 55.7692 10.6571 57 9 57C7.34289 57 6.00063 55.7692 6 54.2508Z" fill="rgba(12, 199, 159, 1)"/>
        <path d="M12.0009 49C13.6574 49.0005 15 50.1191 15 51.5C15 52.8809 13.6574 53.9995 12.0009 54H5.99912C4.3421 54 3 52.8813 3 51.5C3 50.1187 4.3421 49 5.99912 49H12.0009Z" fill="rgba(12, 199, 159, 1)"/>
        </svg> 4 salles immersives</span>
      </div>
    </div>
  </div>

  <div class="explorer-section">
    <div class="explorer-label">Explorer</div>
    <div class="cards">
      <a href="salles.php" class="card">
        <div class="card-dot"></div>
        <h3>Présentation</h3>
        <p>Découvrez les 4 salles immersives et leurs univers uniques créés par les étudiants MMI1.</p>
      </a>
      <a href="inscription.php" class="card">
        <div class="card-dot"></div>
        <h3>Participer</h3>
        <p>Inscrivez-vous pour vivre l'expérience E-LLUSION lors de l'exposition des 18 et 19 juin.</p>
      </a>
    </div>
  </div>

  <div class="about-section">
    <h2>Une expérience interactive et multimédia</h2>
    <p>E-LLUSION est une exposition conçue par les étudiants de première année du département MMI (Métiers du Multimédia et de l'Internet) de l'Université Savoie Mont Blanc. À travers 4 salles immersives, découvrez des installations interactives alliant art numérique, technologies multimédia et créativité. Chaque salle propose une expérience unique pensée pour questionner notre rapport au monde numérique et à la perception de la réalité.</p>
  </div>

</section>

<footer>
  <span>© 2026 E-LLUSION — MMI1 Université Savoie Mont Blanc</span>
  <div style="display:flex;gap:1.5rem">
    <a href="https://instagram.com/mmi_annecy" target="_blank">@mmichambery</a>
    <a href="https://www.mmi-annecy.fr" target="_blank">Site MMI</a>
  </div>
</footer>

</body>
</html>