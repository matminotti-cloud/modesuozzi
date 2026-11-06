<?php
/*
 * Vorlage für index.html, impressum.html und datenschutz.html. Wird von
 * lib/render.php mit den Inhalten aus content/site.json befüllt – nicht direkt aufrufen.
 * Verfügbar: $c (Inhalte), $R (berechnete Werte, siehe ms_render_site; $R['page'] = welche Seite).
 * Die Texte der rechtlichen Seiten stehen in lib/seiten/.
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
$page = $R['page'] ?? 'index';
$isHome = $page === 'index';
$home = $isHome ? '' : './'; // Links zu Abschnitten der Startseite
$firma = ms_str($c, 'besuch.firma') ?: 'Mode Suozzi';
$legal = [
    'impressum' => ['Impressum', "Impressum von $firma, " . ms_str($c, 'besuch.strasse') . ', ' . ms_str($c, 'besuch.ort') . '.'],
    'datenschutz' => ['Datenschutzerklärung', "Datenschutzerklärung von $firma: welche Daten beim Besuch der Website bearbeitet werden, auch durch eingebundene Dienste von Google."],
];
$title = ms_str($c, 'seo.titel') ?: $firma;
$desc = ms_str($c, 'seo.beschreibung');
$canonical = $R['base'];
if (!$isHome) {
    $title = $legal[$page][0] . ' – ' . $firma;
    $desc = $legal[$page][1];
    $canonical = $R['base'] . $page . '.html';
}
$ogImage = ms_abs(ms_str($c, 'hero.bild'), $R['base']);
$address = implode(', ', array_filter([ms_str($c, 'besuch.firma'), ms_str($c, 'besuch.strasse'), ms_str($c, 'besuch.ort')]));
$mapSrc = 'https://www.google.com/maps?q=' . rawurlencode($address) . '&output=embed';
$seasons = ['herbst_winter' => ['Herbst / Winter', 'Ab Herbst'], 'fruehling_sommer' => ['Frühling / Sommer', 'Ab Frühling']];
$seasonShort = ['herbst_winter' => 'Herbst/Winter', 'fruehling_sommer' => 'Frühling/Sommer'];
$seasonSetting = ms_str($c, 'sortiment.aktuelle_saison') ?: 'automatisch';
// Wochenplan aus site.json für das Skript (Index 0 = Sonntag … 6 = Samstag)
$week = array_fill(0, 7, []);
$dayIndex = array_flip(['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa']);
foreach (ms_list($c, 'besuch.oeffnungszeiten') as $row) {
    foreach (ms_tage($row) as $d) {
        foreach (ms_slots($row['zeiten'] ?? '') as $slot) {
            $week[$dayIndex[$d]][] = $slot;
        }
    }
}
foreach ($week as $i => $slots) {
    sort($slots);
    $week[$i] = $slots;
}
$hasHours = (bool) array_filter($week);
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
<link rel="canonical" href="<?= ms_e($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="de_CH">
<meta property="og:site_name" content="<?= $t('besuch.firma') ?>">
<meta property="og:title" content="<?= ms_e($title) ?>">
<meta property="og:description" content="<?= ms_e($desc) ?>">
<meta property="og:url" content="<?= ms_e($canonical) ?>">
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
<?php if ($isHome): ?>
<script type="application/ld+json">
<?= $R['jsonld'] ?>

</script>
<?php endif; ?>
<style>
  :root {
    --brand: #273837;
    --gold: #b0974c;
    /* Gold für Text auf dunklem Grün. #b0974c erreicht auf #273837 nur 4,32:1 (zu wenig für WCAG AA).
       #ccb46a (gleicher Farbton, heller), nach der WCAG-Formel berechnet: 6,04:1 auf --brand #273837,
       5,27:1 auf den Nadelstreifen im Titelbereich, 7,37:1 auf --brand-deep #1c2928 (Fusszeile). */
    --gold-on-dark: #ccb46a;
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
  img { max-width: 100%; height: auto; display: block; }
  a { color: inherit; }
  a:focus-visible, button:focus-visible { outline: 2px solid var(--gold); outline-offset: 3px; }
  .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0; }
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
  .logo span { font-family: "Bodoni Moda", Georgia, serif; font-size: 1.5rem; color: var(--gold-on-dark); }
  #nav { margin-left: auto; }
  #nav ul { list-style: none; display: flex; gap: 28px; margin: 0; padding: 0; }
  #nav a { text-decoration: none; font-size: .95rem; color: #d6dcd8; padding: 6px 0; border-bottom: 1px solid transparent; }
  #nav a:hover, #nav a[aria-current="true"] { color: var(--gold-on-dark); border-bottom-color: var(--gold-on-dark); }
  .bar-end { display: flex; align-items: center; gap: 10px; }
  .call-btn {
    display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 0 16px; white-space: nowrap;
    border: 1px solid rgba(204, 180, 106, .65); border-radius: 2px; color: var(--gold-on-dark);
    text-decoration: none; font-weight: 500; font-size: .95rem;
  }
  .call-btn:hover { background: var(--gold); border-color: var(--gold); color: var(--brand-deep); }
  .call-btn svg { width: 16px; height: 16px; flex: none; }
  .menu-btn { display: none; min-height: 44px; background: none; border: 1px solid rgba(176, 151, 76, .5); color: var(--gold-on-dark); font: inherit; padding: 6px 14px; border-radius: 2px; cursor: pointer; }
  @media (max-width: 900px) {
    .menu-btn { display: block; }
    #nav { position: absolute; left: 0; right: 0; top: 100%; background: var(--brand); border-bottom: 1px solid rgba(176, 151, 76, .35); display: none; }
    #nav.open { display: block; }
    #nav ul { flex-direction: column; gap: 0; padding: 8px 24px 16px; }
    #nav a { display: block; padding: 12px 0; font-size: 1.05rem; }
    .logo { margin-right: auto; }
  }
  @media (max-width: 400px) {
    .bar { gap: 10px; }
    .logo img { height: 44px; }
    .call-btn { padding: 0 12px; }
    .menu-btn { padding: 6px 12px; }
  }

  /* Hero: pinstripe cloth */
  .hero {
    background-color: var(--brand);
    background-image: repeating-linear-gradient(90deg, transparent 0 22px, var(--stripe) 22px 23px);
    color: #f4f2ee;
  }
  /* Höhen so bemessen, dass beide Buttons auch bei 1366×768 und 963×666 ohne Scrollen sichtbar sind */
  .hero-grid { display: grid; grid-template-columns: 1.1fr 1fr; gap: 48px; align-items: center; min-height: min(72vh, 640px); padding-block: clamp(28px, 5vh, 56px); }
  .hero-text { display: flex; flex-direction: column; justify-content: center; }
  .since { font-family: "Bodoni Moda", Georgia, serif; font-style: italic; font-size: 1.15rem; color: var(--gold-on-dark); margin: 0 0 16px; }
  .hero h1 { font-size: clamp(2.4rem, min(5.4vw, 8.5vh), 4.6rem); letter-spacing: -.01em; }
  .hero p.lead { font-size: 1.15rem; max-width: 34ch; color: #d6dcd8; margin: 20px 0 28px; }
  .cta { display: flex; flex-wrap: wrap; align-items: stretch; gap: 12px; }
  .btn {
    display: inline-flex; flex-direction: column; justify-content: center; align-items: center; min-height: 48px;
    text-decoration: none; padding: 10px 20px; border-radius: 2px; font-weight: 500; font-size: .95rem; line-height: 1.3; white-space: nowrap;
  }
  .btn small { font-size: .8rem; font-weight: 400; opacity: .85; letter-spacing: .01em; }
  .hero .status { color: #d6dcd8; margin-top: 18px; }
  .hero .status::before { background: #9aa59f; }
  .hero .status.open::before { background: #5fd38d; box-shadow: 0 0 0 3px rgba(95, 211, 141, .22); }
  .btn-light { background: var(--gold); color: var(--brand-deep); }
  .btn-light:hover { background: #c4ab5f; }
  .btn-ghost { border: 1px solid rgba(176, 151, 76, .6); color: #f4f2ee; }
  .btn-ghost:hover { border-color: var(--gold-on-dark); color: var(--gold-on-dark); }
  .hero-img { margin: 0; }
  /* Querformat 16:9 (Tagesfoto der Ladenfront); Ausschnitt leicht nach oben, damit Vordach und Schrift im Bild bleiben */
  .hero-img img { display: block; width: 100%; height: auto; aspect-ratio: 16 / 9; object-fit: cover; object-position: 50% 40%; border-radius: 2px; }
  /* Hinweis unter dem Bild (Bildunterschrift statt Aufkleber auf dem Foto) */
  .window-note {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0 20px;
    margin-top: 12px; padding-top: 4px; border-top: 1px solid rgba(176, 151, 76, .35); color: #d6dcd8; font-size: .95rem;
  }
  .window-note .note-title { font-family: "Bodoni Moda", Georgia, serif; font-style: italic; font-size: 1.15rem; color: var(--gold-on-dark); padding: 8px 0; }
  .window-note a { display: inline-flex; align-items: center; min-height: 44px; color: #f4f2ee; text-decoration: underline; text-decoration-color: rgba(176, 151, 76, .6); text-underline-offset: 4px; }
  .window-note a:hover { color: var(--gold-on-dark); text-decoration-color: currentColor; }
  @media (max-width: 600px) {
    .cta { flex-direction: column; }
    .btn { width: 100%; }
  }
  @media (max-width: 860px) {
    .hero-grid { grid-template-columns: 1fr; gap: 0; min-height: 0; padding-block: 0; }
    .hero-text { padding: 48px 0 36px; }
    .hero-img { margin: 0 -24px; padding-bottom: 8px; }
    .hero-img img { border-radius: 0; }
    .window-note { margin: 0; padding: 6px 24px 0; border-top: 0; }
  }

  /* Sections */
  section.block { padding: 96px 0; }
  .block + .block { border-top: 1px solid var(--line); }
  .head { display: grid; grid-template-columns: 1fr 1.4fr; gap: 48px; margin-bottom: 56px; align-items: end; }
  .head h2 { font-size: clamp(2rem, 4vw, 3rem); }
  .head p { margin: 0; color: var(--muted); max-width: 52ch; }
  @media (max-width: 760px) { .head { grid-template-columns: 1fr; gap: 16px; margin-bottom: 36px; } section.block { padding: 64px 0; } }

  /* Sortiment: eine Liste; was nur in einer Saison gilt, trägt data-only und wird je nach Saison ausgeblendet */
  .assortment { display: grid; grid-template-columns: 1fr 2fr; gap: 16px 48px; border: 1px solid var(--line); background: var(--paper); padding: 40px; }
  .assortment[data-season="herbst_winter"] [data-only="fruehling_sommer"],
  .assortment[data-season="fruehling_sommer"] [data-only="herbst_winter"] { display: none; }
  .assortment .state { display: inline-block; font-size: .85rem; font-weight: 500; letter-spacing: .04em; text-transform: uppercase; color: var(--accent); margin: 0 0 10px; }
  .assortment h3 { font-size: 1.9rem; }
  .assortment ul { list-style: none; padding: 0; margin: 0; }
  .assortment li { display: flex; justify-content: space-between; gap: 16px; padding: 14px 0; border-top: 1px solid var(--line); }
  .assortment li:last-child { border-bottom: 1px solid var(--line); }
  .assortment li .brand { color: var(--muted); font-size: .95rem; text-align: right; }
  .assortment .next { grid-column: 2; margin: 4px 0 0; font-size: .95rem; color: var(--muted); }
  .assortment .next strong { font-weight: 500; color: var(--ink); }
  .assortment .next[hidden] { display: none; }
  @media (max-width: 760px) { .assortment { grid-template-columns: 1fr; padding: 28px 24px; } .assortment .next { grid-column: auto; } }

  /* Marken */
  .brands { display: flex; flex-wrap: wrap; gap: 6px 0; margin: 0; padding: 0; list-style: none; }
  .brands li { font-family: "Bodoni Moda", Georgia, serif; font-size: clamp(1.5rem, 3.4vw, 2.5rem); line-height: 1.35; }
  .brands li:not(:last-child)::after { content: "/"; color: var(--pin); margin: 0 .45em; font-style: italic; }
  .brands a { text-decoration: underline; text-decoration-color: transparent; text-decoration-thickness: 1px; text-underline-offset: .18em; transition: color .15s, text-decoration-color .15s; }
  .brands a:hover, .brands a:focus-visible { color: var(--accent); text-decoration-color: var(--gold); }
  .brands .ext { font-family: "Jost", system-ui, sans-serif; font-size: .45em; vertical-align: super; margin-left: .2em; color: var(--pin); }
  .brands a:hover .ext, .brands a:focus-visible .ext { color: var(--accent); }

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
  .hours th { padding-left: 12px; box-shadow: inset 3px 0 0 transparent; }
  .hours td { text-align: right; color: var(--muted); }
  .hours .slot { display: block; }
  .hours tr.today th, .hours tr.today td { color: var(--ink); font-weight: 600; }
  .hours tr.today th { box-shadow: inset 3px 0 0 var(--gold); }
  .visit .fax { font-size: .85rem; color: var(--muted); }
  .status { display: inline-flex; align-items: center; gap: 8px; margin: 16px 0 0; font-size: .95rem; }
  .status[hidden] { display: none; }
  .status::before { content: ""; flex: none; width: 9px; height: 9px; border-radius: 50%; background: var(--pin); }
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

  footer { background: var(--brand-deep); color: #b7c0bc; padding: 48px 0 28px; font-size: .9rem; }
  footer p { margin: 0; }
  footer a { color: var(--gold-on-dark); text-underline-offset: .2em; }
  footer a:hover { color: #fff; }
  .foot-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 28px 48px; }
  .foot-title { font-family: "Bodoni Moda", Georgia, serif; font-size: 1.15rem; color: var(--gold-on-dark); margin-bottom: 8px !important; }
  .foot-hours { margin: 0; display: grid; gap: 4px; }
  .foot-hours div { display: flex; gap: 12px; }
  .foot-hours dt { min-width: 3.6em; color: #d6dcd8; }
  .foot-hours dd { margin: 0; }
  footer .foot-grid a { display: inline-block; padding: 6px 0; }
  .foot-bottom { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px 24px; margin-top: 36px; padding-top: 18px; border-top: 1px solid rgba(255, 255, 255, .12); }
  footer .legal-links { display: flex; gap: 20px; }
  footer .legal-links a { display: inline-block; padding: 10px 0; }

  .about .text p + p { margin-top: 0; }

  /* Impressum, Datenschutz */
  .legal { padding: 72px 0 96px; }
  .legal .wrap { max-width: 760px; }
  .legal h1 { font-size: clamp(2.2rem, 5vw, 3.2rem); margin-bottom: 16px; }
  .legal h2 { font-size: 1.45rem; margin: 44px 0 12px; }
  .legal p, .legal li { color: var(--muted); }
  .legal p { margin: 0 0 12px; }
  .legal ul { padding-left: 1.2em; margin: 0 0 12px; }
  .legal address { font-style: normal; color: var(--ink); }
  .legal a { color: var(--accent); text-underline-offset: 3px; }
  .legal .lead { font-size: 1.05rem; }
  /* Platzhalter, die der Inhaber noch ausfüllen bzw. prüfen muss */
  .todo { background: rgba(176, 151, 76, .18); color: var(--ink); padding: 1px 6px; border-radius: 2px; }
  .todo-box { border-left: 3px solid var(--gold); background: rgba(176, 151, 76, .1); color: var(--ink); padding: 12px 16px; margin: 24px 0; font-size: .95rem; }
</style>
</head>
<body data-saison="<?= ms_e($seasonSetting) ?>">

<header>
  <div class="wrap bar">
    <a class="logo" href="<?= $isHome ? '#top' : './' ?>" aria-label="<?= $t('besuch.firma') ?>, <?= $isHome ? 'zum Seitenanfang' : 'zur Startseite' ?>">
      <img src="images/logo.png" alt="<?= $t('besuch.firma') ?>"<?= ms_size_attr($R['root'], 'images/logo.png') ?>>
    </a>
    <nav id="nav" aria-label="Hauptnavigation">
      <ul>
        <li><a href="<?= $home ?>#sortiment"><?= $t('sortiment.titel') ?: 'Sortiment' ?></a></li>
        <li><a href="<?= $home ?>#marken">Marken</a></li>
        <li><a href="<?= $home ?>#ueber-uns"><?= $t('ueber_uns.titel') ?: 'Über uns' ?></a></li>
<?php if ($R['tour']): ?>
        <li><a href="<?= $home ?>#rundgang">Rundgang</a></li>
<?php endif; ?>
        <li><a href="<?= $home ?>#besuch"><?= $t('besuch.titel') ?: 'Besuch' ?></a></li>
      </ul>
    </nav>
    <div class="bar-end">
<?php if ($R['tel']): ?>
      <a class="call-btn" href="tel:<?= ms_e($R['tel']) ?>" aria-label="Anrufen: <?= $t('besuch.telefon') ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>Anrufen</a>
<?php endif; ?>
      <button class="menu-btn" aria-expanded="false" aria-controls="nav">Menü</button>
    </div>
  </div>
</header>

<?php if (!$isHome): ?>
<main id="top" class="legal">
  <div class="wrap">
<?php include __DIR__ . '/seiten/' . $page . '.php'; ?>
  </div>
</main>
<?php else: ?>
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
          <a class="btn btn-ghost" href="tel:<?= ms_e($R['tel']) ?>"><span>Anrufen</span><small><?= $t('besuch.telefon') ?></small></a>
<?php endif; ?>
        </div>
<?php if ($hasHours): ?>
        <p class="status" data-open-status hidden></p>
<?php endif; ?>
      </div>
<?php if ($has('hero.bild')): ?>
      <figure class="hero-img">
        <?= $img('hero.bild', 'hero.bild_alt', ' fetchpriority="high"') ?>

        <figcaption class="window-note">
          <span class="note-title">Neu im Schaufenster: <span data-season-short><?= $seasonShort[$R['season']] ?></span></span>
<?php if ($R['tour']): ?>
          <a href="#rundgang">Laden virtuell ansehen&nbsp;<span aria-hidden="true">→</span></a>
<?php endif; ?>
        </figcaption>
      </figure>
<?php endif; ?>
    </div>
  </section>

  <section class="block" id="sortiment">
    <div class="wrap">
      <div class="head">
        <h2><?= $t('sortiment.titel') ?></h2>
        <p><?= $t('sortiment.einleitung') ?></p>
      </div>
<?php $other = $R['season'] === 'herbst_winter' ? 'fruehling_sommer' : 'herbst_winter'; ?>
      <div class="assortment" data-season="<?= $R['season'] ?>">
        <div>
          <p class="state">Jetzt im Laden</p>
          <h3><?php foreach ($seasons as $key => [$label]): ?><span data-only="<?= $key ?>"><?= $label ?></span><?php endforeach; ?></h3>
        </div>
        <ul>
<?php foreach (ms_sortiment($c) as $it):
    $hw = $it['herbst_winter'];
    $fs = $it['fruehling_sommer'];
    $only = $hw === null ? 'fruehling_sommer' : ($fs === null ? 'herbst_winter' : ''); ?>
          <li<?= $only ? ' data-only="' . $only . '"' : '' ?>><span class="art"><?= ms_e($it['artikel']) ?></span><?php if ($only): ?><span class="brand"><?= ms_e($it[$only]) ?></span><?php elseif ($hw === $fs): ?><span class="brand"><?= ms_e($hw) ?></span><?php else: ?><span class="brand" data-only="herbst_winter"><?= ms_e($hw) ?></span><span class="brand" data-only="fruehling_sommer"><?= ms_e($fs) ?></span><?php endif; ?></li>
<?php endforeach; ?>
        </ul>
        <p class="next" data-next hidden></p>
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
        <li><?php if (ms_is_remote($link)): ?><?php if (!empty($m['pruefen'])): ?><!-- TODO: Link prüfen --><?php endif; ?><a href="<?= ms_e($link) ?>" target="_blank" rel="noopener noreferrer"><?= ms_e($m['name'] ?? '') ?><span class="ext" aria-hidden="true">↗</span><span class="sr-only"> (öffnet in neuem Tab)</span></a><?php else: ?><?= ms_e($m['name'] ?? '') ?><?php endif; ?></li>
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
            <p class="fax">Fax <?= $t('besuch.fax') ?></p>
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
<?php if ($hasHours): ?>
          <p class="status" data-open-status hidden></p>
<?php endif; ?>
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
<?php endif; ?>

<footer>
  <div class="wrap">
    <div class="foot-grid">
      <div>
        <p class="foot-title"><?= $t('besuch.firma') ?></p>
        <p><?= $t('besuch.strasse') ?><br><?= $t('besuch.ort') ?></p>
      </div>
      <div>
        <p class="foot-title">Kontakt</p>
        <p><?php if ($R['tel']): ?><a href="tel:<?= ms_e($R['tel']) ?>"><?= $t('besuch.telefon') ?></a><br><?php endif; ?><?php if ($has('besuch.email')): ?><a href="mailto:<?= $t('besuch.email') ?>"><?= $t('besuch.email') ?></a><?php endif; ?></p>
      </div>
<?php $kurz = ms_zeiten_kurz($c); if ($kurz): ?>
      <div>
        <p class="foot-title">Öffnungszeiten</p>
        <dl class="foot-hours">
<?php foreach ($kurz as [$tage, $zeiten]): ?>
          <div><dt><?= ms_e($tage) ?></dt><dd><?= ms_e($zeiten) ?></dd></div>
<?php endforeach; ?>
        </dl>
      </div>
<?php endif; ?>
    </div>
    <div class="foot-bottom">
      <span>© <span id="year"><?= date('Y') ?></span> <?= $t('besuch.firma') ?></span>
      <nav class="legal-links" aria-label="Rechtliches">
        <a href="impressum.html"<?= $page === 'impressum' ? ' aria-current="page"' : '' ?>>Impressum</a>
        <a href="datenschutz.html"<?= $page === 'datenschutz' ? ' aria-current="page"' : '' ?>>Datenschutz</a>
      </nav>
    </div>
  </div>
</footer>

<script>
// Inhalte stehen fertig im HTML. Das Skript ergänzt nur, was vom heutigen Datum abhängt.

// Saison: März–August Frühling/Sommer, September–Februar Herbst/Winter (Monat nach Schweizer Zeit),
// ausser in der Admin-Konsole ist eine Saison fest eingestellt.
function currentSeason() {
  const fixed = document.body.dataset.saison;
  if (fixed === 'herbst_winter' || fixed === 'fruehling_sommer') return fixed;
  const month = +new Intl.DateTimeFormat('en', { timeZone: 'Europe/Zurich', month: 'numeric' }).format(new Date());
  return month >= 3 && month <= 8 ? 'fruehling_sommer' : 'herbst_winter';
}
const SEASON_SHORT = { herbst_winter: 'Herbst/Winter', fruehling_sommer: 'Frühling/Sommer' };
document.querySelectorAll('[data-season-short]').forEach(el => { el.textContent = SEASON_SHORT[currentSeason()]; });

// Sortiment: aktuelle Saison zeigen, Abweichungen der anderen Saison kompakt darunter
// («Ab Frühling: Schuhe von Galizio Torresi»), gebildet aus derselben Liste im HTML
const assortment = document.querySelector('.assortment');
if (assortment) {
  const cur = currentSeason(), other = cur === 'herbst_winter' ? 'fruehling_sommer' : 'herbst_winter';
  assortment.dataset.season = cur;
  const diffs = [...assortment.querySelectorAll('li')].map(li => {
    const art = li.querySelector('.art').textContent;
    const brand = li.dataset.only === other ? li.querySelector('.brand') : li.querySelector(`.brand[data-only="${other}"]`);
    if (!brand) return null;
    return brand.textContent ? `${art} von ${brand.textContent}` : art;
  }).filter(Boolean);
  const next = assortment.querySelector('[data-next]');
  next.textContent = '';
  if (diffs.length) {
    const label = document.createElement('strong');
    label.textContent = other === 'fruehling_sommer' ? 'Ab Frühling: ' : 'Ab Herbst: ';
    next.append(label, diffs.join(', '));
  }
  next.hidden = !diffs.length;
}

// Öffnungszeiten (aus content/site.json): Index 0 = Sonntag … 6 = Samstag
const OPENING_HOURS = <?= json_encode($week, JSON_HEX_TAG | JSON_UNESCAPED_SLASHES) ?>;
// Feiertage, an denen der Laden geschlossen bleibt (ISO-Datum JJJJ-MM-TT), z. B.:
//   '2026-12-25', // Weihnachten
//   '2026-12-26', // Stephanstag
const HOLIDAYS = [
];
const DAYS = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
const DAY_NAMES = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
const toMin = s => { const [h, m] = s.split(':').map(Number); return h * 60 + m; };

// Datum und Uhrzeit in Zürich, unabhängig von der Zeitzone des Geräts
function zurichNow(date = new Date()) {
  const p = {};
  new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Zurich', year: 'numeric', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric', hourCycle: 'h23' })
    .formatToParts(date).forEach(x => { p[x.type] = +x.value; });
  const day = Date.UTC(p.year, p.month - 1, p.day);
  return { day, weekday: new Date(day).getUTCDay(), minutes: (p.hour % 24) * 60 + p.minute };
}

// Zeitfenster eines Tages (offset 0 = heute, 1 = morgen …), an Feiertagen keine
function slotsOn(z, offset) {
  const d = new Date(z.day + offset * 864e5);
  return HOLIDAYS.includes(d.toISOString().slice(0, 10)) ? [] : (OPENING_HOURS[d.getUTCDay()] || []);
}

// Gemeinsame Logik für die Anzeige im Titelbereich und bei «Besuch»
function openingStatus(date = new Date()) {
  const z = zurichNow(date);
  const today = slotsOn(z, 0);
  for (let i = 0; i < today.length; i++) {
    const [from, to] = today[i];
    if (z.minutes < toMin(from)) {
      return { open: false, text: i === 0 ? `Geschlossen · öffnet heute um ${from}` : `Mittagspause · öffnet um ${from}` };
    }
    if (z.minutes < toMin(to)) return { open: true, text: `Jetzt geöffnet · bis ${to}` };
  }
  for (let k = 1; k <= 14; k++) {
    const next = slotsOn(z, k);
    if (next.length) {
      const when = k === 1 ? 'morgen' : DAY_NAMES[new Date(z.day + k * 864e5).getUTCDay()];
      return { open: false, text: `Geschlossen · öffnet ${when} um ${next[0][0]}` };
    }
  }
  return { open: false, text: 'Zurzeit geschlossen' };
}

function updateOpening() {
  const s = openingStatus();
  document.querySelectorAll('[data-open-status]').forEach(el => {
    el.textContent = s.text;
    el.classList.toggle('open', s.open);
    el.hidden = false;
  });
  const wd = DAYS[zurichNow().weekday];
  document.querySelectorAll('#hours tr').forEach(tr => {
    const isToday = tr.dataset.tage.split(' ').includes(wd);
    tr.classList.toggle('today', isToday);
    if (isToday) tr.setAttribute('aria-current', 'date'); else tr.removeAttribute('aria-current');
  });
}
updateOpening();
// Jede Minute aktualisieren (auf den Minutenwechsel abgestimmt) und beim Zurückkehren in den Tab
setTimeout(() => { updateOpening(); setInterval(updateOpening, 60000); }, 60000 - Date.now() % 60000 + 50);
document.addEventListener('visibilitychange', () => { if (!document.hidden) updateOpening(); });
document.getElementById('year').textContent = new Date().getFullYear();

// Mobiles Menü
const btn = document.querySelector('.menu-btn'), nav = document.getElementById('nav');
btn.addEventListener('click', () => btn.setAttribute('aria-expanded', nav.classList.toggle('open')));
nav.addEventListener('click', e => { if (e.target.tagName === 'A') { nav.classList.remove('open'); btn.setAttribute('aria-expanded', false); } });

// Navigation: Menüpunkt des sichtbaren Abschnitts markieren (aria-current)
const navLinks = [...nav.querySelectorAll('a[href^="#"]')];
const navSections = navLinks.map(a => document.getElementById(a.getAttribute('href').slice(1))).filter(Boolean);
if ('IntersectionObserver' in window && navSections.length) {
  const inView = new Set();
  // Als sichtbar gilt der Abschnitt, der durch einen schmalen Streifen bei 40 % der Fensterhöhe läuft
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) inView.add(e.target.id); else inView.delete(e.target.id); });
    const active = navSections.find(s => inView.has(s.id));
    navLinks.forEach(a => {
      if (active && a.getAttribute('href') === '#' + active.id) a.setAttribute('aria-current', 'true');
      else a.removeAttribute('aria-current');
    });
  }, { rootMargin: '-40% 0px -55% 0px' });
  navSections.forEach(s => io.observe(s));
}
</script>
</body>
</html>
