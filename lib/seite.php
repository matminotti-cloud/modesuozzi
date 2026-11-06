<?php
/*
 * Vorlage für index.html. Wird von lib/render.php mit den Inhalten aus
 * content/site.json befüllt – nicht direkt aufrufen.
 * Verfügbar: $c (Inhalte), $R (berechnete Werte, siehe ms_render_site).
 */
if (!isset($R, $c)) {
    http_response_code(404);
    exit;
}
$t = function ($path) use ($c) {
    return ms_e(ms_str($c, $path));
};
$has = function ($path) use ($c) {
    return ms_str($c, $path) !== '';
};
$img = function ($path, $altPath, $extra = '') use ($c, $R) {
    $src = ms_str($c, $path);
    return '<img src="' . ms_e(ms_src($src)) . '" alt="' . ms_e(ms_str($c, $altPath)) . '"' . ms_size_attr($R['root'], $src) . $extra . '>';
};
$title = ms_str($c, 'seo.titel') ?: (ms_str($c, 'besuch.firma') ?: 'Mode Suozzi');
$desc = ms_str($c, 'seo.beschreibung');
$ogImage = ms_abs(ms_str($c, 'hero.bild'), $R['base']);
$address = implode(', ', array_filter([ms_str($c, 'besuch.firma'), ms_str($c, 'besuch.strasse'), ms_str($c, 'besuch.ort')]));
$mapSrc = 'https://www.google.com/maps?q=' . rawurlencode($address) . '&output=embed';
$seasons = ['herbst_winter' => ['Herbst / Winter', 'Ab Herbst'], 'fruehling_sommer' => ['Frühling / Sommer', 'Ab Frühling']];
?>
<!DOCTYPE html>
<html lang="de-CH">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= ms_e($title) ?></title>
<meta name="description" content="<?= ms_e($desc) ?>">
<?php if (!empty($c['seo']['testversion'])): ?>
<meta name="robots" content="noindex, nofollow">
<?php else: ?>
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
<?php endif; ?>
<link rel="canonical" href="<?= ms_e($R['base']) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="de_CH">
<meta property="og:site_name" content="<?= $t('besuch.firma') ?>">
<meta property="og:title" content="<?= ms_e($title) ?>">
<meta property="og:description" content="<?= ms_e($desc) ?>">
<meta property="og:url" content="<?= ms_e($R['base']) ?>">
<?php if ($ogImage !== ''): ?>
<meta property="og:image" content="<?= ms_e($ogImage) ?>">
<meta property="og:image:alt" content="<?= $t('hero.bild_alt') ?>">
<meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
<meta name="theme-color" content="#273837">
<meta name="build" content="<?= ms_e($R['build']) ?>">
<link rel="icon" href="images/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:ital,opsz,wght@0,6..96,400;0,6..96,600;1,6..96,400&family=Jost:wght@400;500&display=swap" rel="stylesheet">
<script type="application/ld+json">
<?= $R['jsonld'] ?>

</script>
<style>
  :root {
    --brand: #273837;
    --gold: #b0974c;
    --brand-deep: #1c2928;
    --chalk: #fbfaf8;
    --paper: #ffffff;
    --stone: #e7e5e1;
    --pin: #9aa59f;
    --ink: #1c2626;
    --muted: #56625f;
    --accent: #7d6a2e;
    --line: rgba(39, 56, 55, .16);
    --stripe: rgba(176, 151, 76, .09);
    color-scheme: light;
    box-sizing: border-box;
    padding-top: env(safe-area-inset-top, 0px);
    padding-bottom: env(safe-area-inset-bottom, 0px);
  }
  @media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
      --chalk: #141c1c; --paper: #1a2424; --stone: #243131;
      --ink: #eceae6; --muted: #a9b0bd; --accent: #c9ae63;
      --line: rgba(236, 234, 230, .14); color-scheme: dark;
    }
  }
  :root[data-theme="dark"] {
    --chalk: #141c1c; --paper: #1a2424; --stone: #243131;
    --ink: #eceae6; --muted: #a9b0bd; --accent: #c9ae63;
    --line: rgba(236, 234, 230, .14); color-scheme: dark;
  }
  *, *::before, *::after { box-sizing: inherit; }
  html { scroll-behavior: smooth; scroll-padding-top: calc(env(safe-area-inset-top, 0px) + 72px); }
  @media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } }
  body {
    margin: 0; background: var(--chalk); color: var(--ink);
    font: 400 17px/1.6 "Jost", "Helvetica Neue", Arial, sans-serif;
    -webkit-font-smoothing: antialiased;
  }
  img { max-width: 100%; display: block; }
  a { color: inherit; }
  a:focus-visible, button:focus-visible { outline: 2px solid var(--gold); outline-offset: 3px; }
  h1, h2, h3 { font-family: "Bodoni Moda", "Didot", "Bodoni 72", Georgia, serif; font-weight: 400; line-height: 1.1; margin: 0; }
  .wrap { width: min(1120px, 100% - 48px); margin-inline: auto; }

  /* Header */
  body::before { content: ""; position: fixed; inset: 0 0 auto; height: env(safe-area-inset-top, 0px); z-index: 10; background: var(--brand); }
  header {
    position: sticky; top: env(safe-area-inset-top, 0px); z-index: 9;
    background: var(--brand); border-bottom: 1px solid rgba(176, 151, 76, .35);
  }
  .bar { display: flex; align-items: center; justify-content: space-between; gap: 24px; min-height: 76px; }
  .logo { display: flex; align-items: center; text-decoration: none; }
  .logo img { height: 52px; width: auto; }
  .logo span { font-family: "Bodoni Moda", Georgia, serif; font-size: 1.5rem; color: var(--gold); }
  nav ul { list-style: none; display: flex; gap: 28px; margin: 0; padding: 0; }
  nav a { text-decoration: none; font-size: .95rem; color: #d6dcd8; padding: 6px 0; border-bottom: 1px solid transparent; }
  nav a:hover { color: var(--gold); border-bottom-color: var(--gold); }
  .menu-btn { display: none; background: none; border: 1px solid rgba(176, 151, 76, .5); color: var(--gold); font: inherit; padding: 6px 14px; border-radius: 2px; cursor: pointer; }
  @media (max-width: 760px) {
    .menu-btn { display: block; }
    nav { position: absolute; left: 0; right: 0; top: 100%; background: var(--brand); border-bottom: 1px solid rgba(176, 151, 76, .35); display: none; }
    nav.open { display: block; }
    nav ul { flex-direction: column; gap: 0; padding: 8px 24px 16px; }
    nav a { display: block; padding: 12px 0; font-size: 1.05rem; }
  }

  /* Hero: pinstripe cloth */
  .hero {
    background-color: var(--brand);
    background-image: repeating-linear-gradient(90deg, transparent 0 22px, var(--stripe) 22px 23px);
    color: #f4f2ee;
  }
  .hero-grid { display: grid; grid-template-columns: 1fr 1.25fr; gap: 48px; align-items: center; min-height: min(78vh, 680px); }
  .hero-text { padding: 72px 0; display: flex; flex-direction: column; justify-content: center; }
  .since { font-family: "Bodoni Moda", Georgia, serif; font-style: italic; font-size: 1.15rem; color: var(--gold); margin: 0 0 20px; }
  .hero h1 { font-size: clamp(2.6rem, 6vw, 4.6rem); letter-spacing: -.01em; }
  .hero p.lead { font-size: 1.15rem; max-width: 34ch; color: #d6dcd8; margin: 24px 0 36px; }
  .cta { display: flex; flex-wrap: wrap; gap: 14px; }
  .btn { display: inline-block; text-decoration: none; padding: 13px 24px; border-radius: 2px; font-weight: 500; }
  .btn-light { background: var(--gold); color: var(--brand-deep); }
  .btn-light:hover { background: #c4ab5f; }
  .btn-ghost { border: 1px solid rgba(176, 151, 76, .6); color: #f4f2ee; }
  .btn-ghost:hover { border-color: var(--gold); color: var(--gold); }
  .hero-img { position: relative; margin-bottom: 64px; }
  /* Querformat 16:9 (Tagesfoto der Ladenfront); Ausschnitt leicht nach oben, damit Vordach und Schrift im Bild bleiben */
  .hero-img img { display: block; width: 100%; height: auto; aspect-ratio: 16 / 9; object-fit: cover; object-position: 50% 40%; border-radius: 2px; }
  .window-note {
    position: absolute; left: -24px; bottom: -64px; max-width: 300px; box-shadow: 0 8px 24px rgba(0, 0, 0, .25);
    background: var(--gold); color: var(--brand-deep); padding: 14px 18px; border-radius: 2px;
    font-family: "Bodoni Moda", Georgia, serif; font-size: 1.1rem; line-height: 1.3;
  }
  .window-note small { display: block; font-family: "Jost", Arial, sans-serif; font-size: .85rem; opacity: .85; margin-top: 4px; }
  @media (max-width: 860px) {
    .hero-grid { grid-template-columns: 1fr; gap: 0; min-height: 0; }
    .hero-text { padding: 56px 0 40px; }
    .hero-img { margin: 0 -24px; }
    .window-note { position: static; max-width: none; border-radius: 0; box-shadow: none; padding: 14px 24px; }
  }

  /* Sections */
  section.block { padding: 96px 0; }
  .block + .block { border-top: 1px solid var(--line); }
  .head { display: grid; grid-template-columns: 1fr 1.4fr; gap: 48px; margin-bottom: 56px; align-items: end; }
  .head h2 { font-size: clamp(2rem, 4vw, 3rem); }
  .head p { margin: 0; color: var(--muted); max-width: 52ch; }
  @media (max-width: 760px) { .head { grid-template-columns: 1fr; gap: 16px; margin-bottom: 36px; } section.block { padding: 64px 0; } }

  /* Sortiment */
  .seasons { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border: 1px solid var(--line); background: var(--paper); }
  .season { padding: 40px; }
  .season + .season { border-left: 1px solid var(--line); }
  .season h3 { font-size: 1.7rem; margin-bottom: 6px; }
  .season .state { font-size: .9rem; color: var(--accent); margin: 0 0 24px; }
  .season ul { list-style: none; padding: 0; margin: 0; }
  .season li { display: flex; justify-content: space-between; gap: 16px; padding: 14px 0; border-top: 1px solid var(--line); }
  .season li span:last-child { color: var(--muted); font-size: .95rem; text-align: right; }
  .season.off { color: var(--muted); }
  @media (max-width: 760px) { .seasons { grid-template-columns: 1fr; } .season + .season { border-left: 0; border-top: 1px solid var(--line); } .season { padding: 28px 24px; } }

  /* Marken */
  .brands { display: flex; flex-wrap: wrap; gap: 6px 0; margin: 0; padding: 0; list-style: none; }
  .brands li { font-family: "Bodoni Moda", Georgia, serif; font-size: clamp(1.5rem, 3.4vw, 2.5rem); line-height: 1.35; }
  .brands li:not(:last-child)::after { content: "/"; color: var(--pin); margin: 0 .45em; font-style: italic; }
  .brands a { text-decoration: none; }
  .brands a:hover { color: var(--accent); }

  /* Über uns */
  .about { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: center; }
  .about figure { margin: 0; }
  .about img { width: 100%; border-radius: 2px; filter: saturate(.9); }
  .about figcaption { font-size: .9rem; color: var(--muted); margin-top: 10px; }
  .about h2 { font-size: clamp(2rem, 4vw, 3rem); margin-bottom: 24px; }
  .about p { max-width: 46ch; color: var(--muted); }
  .about p.quote { font-family: "Bodoni Moda", Georgia, serif; font-style: italic; font-size: 1.5rem; line-height: 1.35; color: var(--ink); margin-top: 32px; }
  @media (max-width: 860px) { .about { grid-template-columns: 1fr; gap: 32px; } }

  /* Virtueller Rundgang */
  .tour { aspect-ratio: 16 / 9; border: 1px solid var(--line); border-radius: 2px; overflow: hidden; background: var(--stone); }
  .tour iframe { width: 100%; height: 100%; border: 0; display: block; }
  @media (max-width: 560px) { .tour { aspect-ratio: 4 / 5; } }

  /* Besuch */
  .visit { display: grid; grid-template-columns: 1fr 1fr 1.3fr; gap: 40px; }
  .visit h3 { font-size: 1.35rem; margin-bottom: 16px; }
  .visit address { font-style: normal; }
  .visit p { margin: 0 0 6px; }
  .hours { width: 100%; border-collapse: collapse; }
  .hours th, .hours td { text-align: left; font-weight: 400; padding: 8px 0; border-top: 1px solid var(--line); vertical-align: top; }
  .hours td { text-align: right; color: var(--muted); }
  .hours tr.today th, .hours tr.today td { color: var(--ink); font-weight: 500; }
  .status { display: inline-flex; align-items: center; gap: 8px; margin-top: 16px; font-size: .95rem; }
  .status::before { content: ""; width: 9px; height: 9px; border-radius: 50%; background: var(--pin); }
  .status.open::before { background: #3c8a5a; }
  .map { border: 1px solid var(--line); min-height: 300px; overflow: hidden; border-radius: 2px; }
  .map iframe { width: 100%; height: 100%; min-height: 300px; border: 0; display: block; }
  .storefront { margin: 48px 0 0; display: grid; grid-template-columns: 1.4fr 1fr; gap: 32px; align-items: end; }
  .storefront img { width: 100%; height: auto; border-radius: 2px; }
  .storefront figcaption { color: var(--muted); max-width: 36ch; }
  .storefront figcaption strong { display: block; font-family: "Bodoni Moda", Georgia, serif; font-weight: 400; font-size: 1.35rem; color: var(--ink); margin-bottom: 8px; }
  @media (max-width: 760px) { .storefront { grid-template-columns: 1fr; gap: 16px; } }
  @media (max-width: 960px) { .visit { grid-template-columns: 1fr 1fr; } .map { grid-column: 1 / -1; } }
  @media (max-width: 560px) { .visit { grid-template-columns: 1fr; } }

  footer { background: var(--brand-deep); color: #b7c0bc; padding: 40px 0; font-size: .9rem; }
  footer .wrap { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 16px; }
  footer a { color: var(--gold); }

  .about .text p + p { margin-top: 0; }
</style>
</head>
<body>

<header>
  <div class="wrap bar">
    <a class="logo" href="#top" aria-label="<?= $t('besuch.firma') ?>, zum Seitenanfang">
      <img src="images/logo.png" alt="<?= $t('besuch.firma') ?>"<?= ms_size_attr($R['root'], 'images/logo.png') ?>>
    </a>
    <button class="menu-btn" aria-expanded="false" aria-controls="nav">Menü</button>
    <nav id="nav" aria-label="Hauptnavigation">
      <ul>
        <li><a href="#sortiment"><?= $t('sortiment.titel') ?: 'Sortiment' ?></a></li>
        <li><a href="#marken">Marken</a></li>
        <li><a href="#ueber-uns"><?= $t('ueber_uns.titel') ?: 'Über uns' ?></a></li>
<?php if ($R['tour']): ?>
        <li><a href="#rundgang">Rundgang</a></li>
<?php endif; ?>
        <li><a href="#besuch"><?= $t('besuch.titel') ?: 'Besuch' ?></a></li>
      </ul>
    </nav>
  </div>
</header>

<main id="top">
  <section class="hero">
    <div class="wrap hero-grid">
      <div class="hero-text">
<?php if ($has('hero.oberzeile')): ?>
        <p class="since"><?= $t('hero.oberzeile') ?></p>
<?php endif; ?>
        <h1><?= $t('hero.titel') ?></h1>
        <p class="lead"><?= $t('hero.text') ?></p>
        <div class="cta">
          <a class="btn btn-light" href="#besuch">Öffnungszeiten &amp; Anfahrt</a>
<?php if ($R['tel']): ?>
          <a class="btn btn-ghost" href="tel:<?= ms_e($R['tel']) ?>"><?= $t('besuch.telefon') ?> anrufen</a>
<?php endif; ?>
        </div>
      </div>
<?php if ($has('hero.bild')): ?>
      <div class="hero-img">
        <?= $img('hero.bild', 'hero.bild_alt', ' fetchpriority="high"') ?>

<?php if ($has('hero.hinweis_titel')): ?>
        <p class="window-note"><span><?= $t('hero.hinweis_titel') ?></span><?php if ($has('hero.hinweis_text')): ?><small><?= $t('hero.hinweis_text') ?></small><?php endif; ?></p>
<?php endif; ?>
      </div>
<?php endif; ?>
    </div>
  </section>

  <section class="block" id="sortiment">
    <div class="wrap">
      <div class="head">
        <h2><?= $t('sortiment.titel') ?></h2>
        <p><?= $t('sortiment.einleitung') ?></p>
      </div>
      <div class="seasons" data-current="<?= ms_e(ms_str($c, 'sortiment.aktuelle_saison') ?: 'automatisch') ?>">
<?php foreach ($seasons as $key => [$label, $later]): $now = $key === $R['season']; ?>
        <article class="season<?= $now ? '' : ' off' ?>" data-season="<?= $key ?>">
          <h3><?= $label ?></h3>
          <p class="state"><?= $now ? 'Jetzt im Laden' : $later ?></p>
          <ul>
<?php foreach (ms_list($c, "sortiment.$key") as $it): ?>
            <li><span><?= ms_e($it['artikel'] ?? '') ?></span><span><?= ms_e($it['marke'] ?? '') ?></span></li>
<?php endforeach; ?>
          </ul>
        </article>
<?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="block" id="marken">
    <div class="wrap">
      <div class="head">
        <h2><?= $t('marken.titel') ?></h2>
        <p><?= $t('marken.einleitung') ?></p>
      </div>
      <ul class="brands">
<?php foreach (ms_list($c, 'marken.liste') as $m): $link = trim((string) ($m['link'] ?? '')); ?>
        <li><?php if (ms_is_remote($link)): ?><a href="<?= ms_e($link) ?>" target="_blank" rel="noopener"><?= ms_e($m['name'] ?? '') ?></a><?php else: ?><?= ms_e($m['name'] ?? '') ?><?php endif; ?></li>
<?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="block" id="ueber-uns">
    <div class="wrap about">
<?php if ($has('ueber_uns.bild')): ?>
      <figure>
        <?= $img('ueber_uns.bild', 'ueber_uns.bild_alt', ' loading="lazy" decoding="async"') ?>

        <figcaption><?= $t('ueber_uns.bildunterschrift') ?></figcaption>
      </figure>
<?php endif; ?>
      <div>
        <h2><?= $t('ueber_uns.titel') ?></h2>
        <div class="text">
<?php foreach (ms_paragraphs(ms_str($c, 'ueber_uns.text')) as $p): ?>
          <p><?= ms_e($p) ?></p>
<?php endforeach; ?>
        </div>
<?php if ($has('ueber_uns.zitat')): ?>
        <p class="quote"><?= $t('ueber_uns.zitat') ?></p>
<?php endif; ?>
      </div>
    </div>
  </section>

<?php if ($R['tour']): ?>
  <section class="block" id="rundgang">
    <div class="wrap">
      <div class="head">
        <h2><?= $t('rundgang.titel') ?></h2>
        <p><?= $t('rundgang.text') ?></p>
      </div>
      <div class="tour">
        <iframe src="<?= ms_e($R['tour']) ?>" title="Virtueller Rundgang durch den Laden (Google Street View)" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </div>
  </section>

<?php endif; ?>
  <section class="block" id="besuch">
    <div class="wrap">
      <div class="head">
        <h2><?= $t('besuch.titel') ?></h2>
        <p><?= $t('besuch.einleitung') ?></p>
      </div>
      <div class="visit">
        <div>
          <h3>Adresse</h3>
          <address>
            <p><?= $t('besuch.firma') ?><br><?= $t('besuch.strasse') ?><br><?= $t('besuch.ort') ?></p>
<?php if ($R['tel']): ?>
            <p style="margin-top:16px">Telefon <a href="tel:<?= ms_e($R['tel']) ?>"><?= $t('besuch.telefon') ?></a></p>
<?php endif; ?>
<?php if ($has('besuch.fax')): ?>
            <p>Fax <?= $t('besuch.fax') ?></p>
<?php endif; ?>
<?php if ($has('besuch.email')): ?>
            <p>E-Mail <a href="mailto:<?= $t('besuch.email') ?>"><?= $t('besuch.email') ?></a></p>
<?php endif; ?>
          </address>
        </div>
        <div>
          <h3>Öffnungszeiten</h3>
          <table class="hours">
            <tbody id="hours">
<?php foreach (ms_list($c, 'besuch.oeffnungszeiten') as $row): ?>
              <tr data-tage="<?= ms_e(implode(' ', ms_tage($row))) ?>" data-zeiten="<?= ms_e($row['zeiten'] ?? '') ?>"><th scope="row"><?= ms_e($row['bezeichnung'] ?? '') ?></th><td><?= ms_zeiten_html((string) ($row['zeiten'] ?? '')) ?></td></tr>
<?php endforeach; ?>
            </tbody>
          </table>
          <p class="status" id="status" hidden></p>
        </div>
        <div class="map">
          <iframe src="<?= ms_e($mapSrc) ?>" title="Karte: <?= ms_e(implode(', ', array_filter([ms_str($c, 'besuch.strasse'), ms_str($c, 'besuch.ort')]))) ?>" loading="lazy"></iframe>
        </div>
      </div>
<?php if ($has('besuch.laden_bild')): ?>
      <figure class="storefront">
        <?= $img('besuch.laden_bild', 'besuch.laden_bild_alt', ' loading="lazy" decoding="async"') ?>

        <figcaption><strong><?= $t('besuch.laden_titel') ?></strong><span><?= $t('besuch.laden_text') ?></span></figcaption>
      </figure>
<?php endif; ?>
    </div>
  </section>
</main>

<footer>
  <div class="wrap">
    <span>© <span id="year"><?= date('Y') ?></span> <?= ms_e(implode(' · ', array_filter([ms_str($c, 'besuch.firma'), ms_str($c, 'besuch.strasse'), ms_str($c, 'besuch.ort')]))) ?></span>
<?php if ($R['tel']): ?>
    <a href="tel:<?= ms_e($R['tel']) ?>"><?= $t('besuch.telefon') ?></a>
<?php endif; ?>
  </div>
</footer>

<script>
// Inhalte stehen fertig im HTML. Das Skript ergänzt nur, was vom heutigen Datum abhängt.

// Sortiment: aktuelle Saison hervorheben
const seasons = document.querySelector('.seasons');
if (seasons) {
  const month = new Date().getMonth() + 1;
  let current = seasons.dataset.current;
  if (current !== 'herbst_winter' && current !== 'fruehling_sommer') current = month >= 3 && month <= 8 ? 'fruehling_sommer' : 'herbst_winter';
  seasons.querySelectorAll('.season').forEach(art => {
    const key = art.dataset.season, isNow = key === current;
    art.classList.toggle('off', !isNow);
    art.querySelector('.state').textContent = isNow ? 'Jetzt im Laden' : (key === 'herbst_winter' ? 'Ab Herbst' : 'Ab Frühling');
  });
}

// Öffnungszeiten: heutigen Tag markieren, "Jetzt geöffnet" / "Zurzeit geschlossen"
const DAYS = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
const parts = new Intl.DateTimeFormat('de-CH', { timeZone: 'Europe/Zurich', weekday: 'short', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).formatToParts(new Date());
const today = DAYS.indexOf(parts.find(p => p.type === 'weekday').value.slice(0, 2));
const now = +parts.find(p => p.type === 'hour').value * 60 + +parts.find(p => p.type === 'minute').value;
const toMin = s => { const [h, m] = s.split(/[:.]/).map(Number); return h * 60 + (m || 0); };
let open = false;
document.querySelectorAll('#hours tr').forEach(tr => {
  if (!tr.dataset.tage.split(' ').map(d => DAYS.indexOf(d)).includes(today)) return;
  tr.classList.add('today');
  const slots = [...tr.dataset.zeiten.matchAll(/(\d{1,2}[:.]\d{2})\s*[–—-]\s*(\d{1,2}[:.]\d{2})/g)];
  if (slots.some(m => now >= toMin(m[1]) && now < toMin(m[2]))) open = true;
});
const st = document.getElementById('status');
if (document.querySelector('#hours tr')) { st.hidden = false; st.textContent = open ? 'Jetzt geöffnet' : 'Zurzeit geschlossen'; st.classList.toggle('open', open); }
document.getElementById('year').textContent = new Date().getFullYear();

// Mobiles Menü
const btn = document.querySelector('.menu-btn'), nav = document.getElementById('nav');
btn.addEventListener('click', () => btn.setAttribute('aria-expanded', nav.classList.toggle('open')));
nav.addEventListener('click', e => { if (e.target.tagName === 'A') { nav.classList.remove('open'); btn.setAttribute('aria-expanded', false); } });
</script>
</body>
</html>
