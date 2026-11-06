<?php
/**
 * Erzeugt aus content/site.json die fertigen Dateien der Website:
 *
 *   index.html   Seite mit allen Inhalten direkt im HTML (lesbar für Google und
 *                KI-Suchmaschinen, die kein JavaScript ausführen) und strukturierten
 *                Daten (schema.org) für den Laden
 *   impressum.html, datenschutz.html
 *                rechtliche Seiten im gleichen Layout (Texte in lib/seiten/)
 *   sitemap.xml  für Suchmaschinen
 *   robots.txt   erlaubt allen Crawlern den Zugriff (in der Testversion: keinem)
 *   llms.txt     kompakte Zusammenfassung für KI-Assistenten (llmstxt.org)
 *
 * Aufruf: admin.php nach jedem Speichern, auf GitHub der Workflow
 * .github/workflows/website.yml, von Hand: php lib/render.php
 * Benötigt PHP 7.4 oder neuer.
 */

if (PHP_SAPI !== 'cli' && !defined('MS_RENDER')) {
    http_response_code(404);
    exit;
}

date_default_timezone_set('Europe/Zurich');

const MS_WEEKDAYS = ['Mo' => 'Monday', 'Di' => 'Tuesday', 'Mi' => 'Wednesday', 'Do' => 'Thursday', 'Fr' => 'Friday', 'Sa' => 'Saturday', 'So' => 'Sunday'];
const MS_DEFAULT_URL = 'https://www.modesuozzi.ch/';
const MS_PAGES = ['index.html' => 'index', 'impressum.html' => 'impressum', 'datenschutz.html' => 'datenschutz'];
const MS_OUTPUT = ['index.html', 'impressum.html', 'datenschutz.html', 'sitemap.xml', 'robots.txt', 'llms.txt'];

// ---------------------------------------------------------------- Hilfsfunktionen

function ms_e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Text an einem Pfad wie "besuch.telefon", getrimmt; '' wenn leer oder kein Text. */
function ms_str(array $c, $path)
{
    foreach (explode('.', $path) as $k) {
        if (!is_array($c) || !array_key_exists($k, $c)) {
            return '';
        }
        $c = $c[$k];
    }
    return is_scalar($c) ? trim((string) $c) : '';
}

/** Liste von Einträgen (Objekten) an einem Pfad. */
function ms_list(array $c, $path)
{
    foreach (explode('.', $path) as $k) {
        $c = is_array($c) ? ($c[$k] ?? null) : null;
    }
    return is_array($c) ? array_values(array_filter($c, 'is_array')) : [];
}

function ms_is_remote($p)
{
    return (bool) preg_match('~^https?://~i', $p);
}

/** "/images/x.webp" -> "images/x.webp" (relativ, damit es auch unter github.io/<repo>/ funktioniert). */
function ms_src($p)
{
    $p = trim((string) $p);
    return ($p === '' || ms_is_remote($p)) ? $p : ltrim($p, '/');
}

function ms_abs($p, $base)
{
    $s = ms_src($p);
    return ($s === '' || ms_is_remote($s)) ? $s : $base . $s;
}

/** width/height-Attribute für lokale Bilder (verhindert Springen beim Laden). */
function ms_size_attr($root, $p)
{
    $s = ms_src($p);
    if ($s === '' || ms_is_remote($s) || strpos($s, '..') !== false || !is_file("$root/$s")) {
        return '';
    }
    $i = @getimagesize("$root/$s");
    return $i ? ' width="' . (int) $i[0] . '" height="' . (int) $i[1] . '"' : '';
}

/** "+41 (0) 44 940 87 21" -> "+41449408721" (für tel:-Links und schema.org). */
function ms_tel($s)
{
    $t = (string) preg_replace('/[^\d+]/', '', (string) preg_replace('/\(\s*0\s*\)/', '', (string) $s));
    if (strpos($t, '00') === 0) {
        return '+' . substr($t, 2);
    }
    return ($t !== '' && $t[0] === '0') ? '+41' . substr($t, 1) : $t;
}

/** Zeitfenster aus "09:00–12:00, 14:00–18:30" -> [["09:00","12:00"], ["14:00","18:30"]]. */
function ms_slots($zeiten)
{
    preg_match_all('/(\d{1,2})[:.](\d{2})\s*[–—-]\s*(\d{1,2})[:.](\d{2})/u', (string) $zeiten, $m, PREG_SET_ORDER);
    return array_map(function ($x) {
        return [sprintf('%02d:%02d', $x[1], $x[2]), sprintf('%02d:%02d', $x[3], $x[4])];
    }, $m);
}

function ms_zeiten_html($zeiten)
{
    $parts = ms_slots($zeiten) ? preg_split('/\s*,\s*/u', $zeiten) : [$zeiten];
    return implode('<br>', array_map(function ($t) {
        return ms_e(preg_replace('/\s*[–—-]\s*/u', ' – ', $t, 1));
    }, $parts));
}

function ms_tage(array $row)
{
    $t = is_array($row['tage'] ?? null) ? array_filter($row['tage'], 'is_string') : [];
    return array_values(array_intersect(array_keys(MS_WEEKDAYS), $t));
}

/** Link aus Google Maps (360°-Foto) -> Street-View-Einbettung; '' wenn nicht erkennbar. */
function ms_tour_src($link)
{
    $link = trim((string) $link);
    if ($link === '') {
        return '';
    }
    if (preg_match('~^https://www\.google\.[a-z.]+/maps/embed\?~', $link)) {
        return $link;
    }
    if (!preg_match('/!1s([\w-]{10,})!2e/', $link, $id) || !preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $link, $at)) {
        return '';
    }
    $heading = preg_match('~,(\d+(?:\.\d+)?)h[,/]~', $link, $hm) ? $hm[1] : '0';
    return "https://www.google.com/maps/embed?pb=!4v0!6m8!1m7!1s{$id[1]}!2m2!1d{$at[1]}!2d{$at[2]}!3f{$heading}!4f0!5f0.75";
}

function ms_base_url(array $c)
{
    $u = ms_str($c, 'seo.adresse');
    if (!preg_match('~^https?://[^/\s]+~i', $u)) {
        $u = MS_DEFAULT_URL;
    }
    return rtrim($u, '/') . '/';
}

function ms_paragraphs($text)
{
    return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/', (string) $text)), 'strlen'));
}

function ms_season_now(array $c)
{
    $s = ms_str($c, 'sortiment.aktuelle_saison');
    if ($s === 'herbst_winter' || $s === 'fruehling_sommer') {
        return $s;
    }
    $m = (int) date('n');
    return ($m >= 3 && $m <= 8) ? 'fruehling_sommer' : 'herbst_winter';
}

// ---------------------------------------------------------------- Strukturierte Daten

function ms_jsonld(array $c, $base)
{
    $name = ms_str($c, 'besuch.firma') ?: 'Mode Suozzi';
    $store = [
        '@type' => 'ClothingStore',
        '@id' => $base . '#laden',
        'name' => $name,
        'url' => $base,
        'description' => ms_str($c, 'seo.beschreibung'),
        'logo' => $base . 'images/logo.png',
        'image' => array_values(array_filter([
            ms_abs(ms_str($c, 'hero.bild'), $base),
            ms_abs(ms_str($c, 'besuch.laden_bild'), $base),
        ])),
        'telephone' => ms_tel(ms_str($c, 'besuch.telefon')),
        'faxNumber' => ms_tel(ms_str($c, 'besuch.fax')),
        'email' => ms_str($c, 'besuch.email'),
    ];

    $address = ['@type' => 'PostalAddress', 'streetAddress' => ms_str($c, 'besuch.strasse'), 'addressCountry' => 'CH'];
    if (preg_match('/^(\d{4,5})\s+(.+)$/u', ms_str($c, 'besuch.ort'), $m)) {
        $address['postalCode'] = $m[1];
        $address['addressLocality'] = $m[2];
    } else {
        $address['addressLocality'] = ms_str($c, 'besuch.ort');
    }
    $store['address'] = $address;

    if (preg_match('/^\s*(-?\d{1,2}(?:\.\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', ms_str($c, 'besuch.koordinaten'), $m)) {
        $store['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $m[1], 'longitude' => (float) $m[2]];
    }
    if ($maps = ms_str($c, 'besuch.google_maps')) {
        $store['hasMap'] = $maps;
        $store['sameAs'] = [$maps];
    }

    $hours = [];
    foreach (ms_list($c, 'besuch.oeffnungszeiten') as $row) {
        $days = array_map(function ($d) {
            return 'https://schema.org/' . MS_WEEKDAYS[$d];
        }, ms_tage($row));
        foreach ($days ? ms_slots($row['zeiten'] ?? '') : [] as $slot) {
            $hours[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days, 'opens' => $slot[0], 'closes' => $slot[1]];
        }
    }
    $store['openingHoursSpecification'] = $hours;

    $store['brand'] = array_map(function ($b) {
        $o = ['@type' => 'Brand', 'name' => trim((string) $b['name'])];
        if (ms_is_remote((string) ($b['link'] ?? ''))) {
            $o['url'] = $b['link'];
        }
        return $o;
    }, array_values(array_filter(ms_list($c, 'marken.liste'), function ($b) {
        return trim((string) ($b['name'] ?? '')) !== '';
    })));

    $store = array_filter($store, function ($v) {
        return $v !== '' && $v !== [];
    });

    $site = [
        '@type' => 'WebSite',
        '@id' => $base . '#website',
        'url' => $base,
        'name' => $name,
        'inLanguage' => 'de-CH',
        'publisher' => ['@id' => $base . '#laden'],
    ];

    return json_encode(['@context' => 'https://schema.org', '@graph' => [$store, $site]],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
}

// ---------------------------------------------------------------- Textdateien

function ms_robots(array $c, $base)
{
    if (!empty($c['seo']['testversion'])) {
        return "# Testversion: Suchmaschinen ausgeschlossen (in der Admin-Konsole unter \"Allgemein\" ausschalten)\nUser-agent: *\nDisallow: /\n";
    }
    return "# Suchmaschinen und KI-Assistenten sind willkommen.\n"
        . "User-agent: *\nAllow: /\nDisallow: /admin.php\nDisallow: /admin-data/\nDisallow: /lib/\n\n"
        . "Sitemap: {$base}sitemap.xml\n";
}

function ms_sitemap($base)
{
    $urls = '';
    foreach (array_keys(MS_PAGES) as $file) {
        $urls .= "  <url><loc>" . ms_e($file === 'index.html' ? $base : $base . $file) . "</loc><lastmod>" . date('Y-m-d') . "</lastmod></url>\n";
    }
    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
        . $urls
        . "</urlset>\n";
}

function ms_llms(array $c, $base)
{
    $line = function ($s) {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $s));
    };
    $name = ms_str($c, 'besuch.firma') ?: 'Mode Suozzi';
    $out = "# $name\n\n";
    if ($d = ms_str($c, 'seo.beschreibung')) {
        $out .= '> ' . $line($d) . "\n\n";
    }
    $intro = trim(ms_str($c, 'hero.titel') . ' ' . ms_str($c, 'hero.text'));
    if ($intro !== '') {
        $out .= $line($intro) . "\n\n";
    }

    $out .= "## Adresse und Kontakt\n\n";
    $addr = implode(', ', array_filter([$name, ms_str($c, 'besuch.strasse'), ms_str($c, 'besuch.ort'), 'Schweiz']));
    $out .= "- Adresse: $addr\n";
    if ($x = ms_str($c, 'besuch.einleitung')) {
        $out .= '- Lage: ' . $line($x) . "\n";
    }
    foreach (['telefon' => 'Telefon', 'fax' => 'Fax', 'email' => 'E-Mail'] as $k => $label) {
        if ($x = ms_str($c, "besuch.$k")) {
            $out .= "- $label: $x\n";
        }
    }
    $out .= "- Website: $base\n";
    if ($x = ms_str($c, 'besuch.google_maps')) {
        $out .= "- Google Maps: $x\n";
    }

    $hours = ms_list($c, 'besuch.oeffnungszeiten');
    if ($hours) {
        $out .= "\n## Öffnungszeiten\n\n";
        foreach ($hours as $row) {
            $out .= '- ' . $line($row['bezeichnung'] ?? '') . ': ' . $line($row['zeiten'] ?? '') . "\n";
        }
    }

    $out .= "\n## " . (ms_str($c, 'sortiment.titel') ?: 'Sortiment') . "\n\n";
    if ($x = ms_str($c, 'sortiment.einleitung')) {
        $out .= $line($x) . "\n\n";
    }
    foreach (['herbst_winter' => 'Herbst / Winter', 'fruehling_sommer' => 'Frühling / Sommer'] as $k => $label) {
        $items = array_map(function ($it) use ($line) {
            $a = $line($it['artikel'] ?? '');
            $b = $line($it['marke'] ?? '');
            return $b !== '' ? "$a ($b)" : $a;
        }, ms_list($c, "sortiment.$k"));
        if ($items) {
            $out .= "- $label: " . implode(', ', $items) . "\n";
        }
    }

    $brands = array_filter(array_map(function ($b) use ($line) {
        return $line($b['name'] ?? '');
    }, ms_list($c, 'marken.liste')), 'strlen');
    if ($brands) {
        $out .= "\n## " . (ms_str($c, 'marken.titel') ?: 'Marken') . "\n\n";
        if ($x = ms_str($c, 'marken.einleitung')) {
            $out .= $line($x) . "\n\n";
        }
        $out .= implode(', ', $brands) . "\n";
    }

    $about = ms_paragraphs(ms_str($c, 'ueber_uns.text'));
    if ($about) {
        $out .= "\n## " . (ms_str($c, 'ueber_uns.titel') ?: 'Über uns') . "\n\n" . implode("\n\n", array_map($line, $about)) . "\n";
    }
    return $out;
}

// ---------------------------------------------------------------- Erzeugen

/** Kennung von Inhalt + Vorlage: zeigt admin.php, ob index.html neu erzeugt werden muss. */
function ms_build_id(array $c)
{
    $tpl = '';
    foreach (array_merge([__FILE__, __DIR__ . '/seite.php'], glob(__DIR__ . '/seiten/*.php') ?: []) as $f) {
        $tpl .= str_replace("\r\n", "\n", (string) @file_get_contents($f));
    }
    return substr(sha1(json_encode($c) . $tpl), 0, 16);
}

function ms_load(string $root, &$error = null)
{
    $json = @file_get_contents($root . '/content/site.json');
    $c = $json === false ? null : json_decode($json, true);
    if (!is_array($c)) {
        $error = 'content/site.json fehlt oder ist kein gültiges JSON.';
        return null;
    }
    return $c;
}

/** true, wenn index.html nicht zum aktuellen Inhalt bzw. zur aktuellen Vorlage passt. */
function ms_is_stale(string $root)
{
    $c = ms_load($root);
    if ($c === null) {
        return false;
    }
    $html = (string) @file_get_contents($root . '/index.html');
    return strpos($html, '<meta name="build" content="' . ms_build_id($c) . '">') === false;
}

function ms_write(string $file, string $content)
{
    if (is_file($file) && (string) @file_get_contents($file) === $content) {
        return true;
    }
    $tmp = $file . '.tmp-' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $content, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    @chmod($file, 0644);
    return true;
}

/**
 * Schreibt die Seiten (MS_PAGES), sitemap.xml, robots.txt und llms.txt nach $root.
 * Gibt true zurück oder false mit einer Fehlermeldung in $error.
 */
function ms_render_site(string $root, &$error = null)
{
    $c = ms_load($root, $error);
    if ($c === null) {
        return false;
    }
    $base = ms_base_url($c);
    $common = [
        'root' => $root,
        'base' => $base,
        'build' => ms_build_id($c),
        'jsonld' => ms_jsonld($c, $base),
        'tel' => ms_tel(ms_str($c, 'besuch.telefon')),
        'tour' => ms_tour_src(ms_str($c, 'rundgang.link')),
        'season' => ms_season_now($c),
    ];

    $files = [];
    $changed = !is_file("$root/sitemap.xml");
    foreach (MS_PAGES as $file => $page) {
        $html = ms_render_page($c, $common + ['page' => $page], $error);
        if ($html === null) {
            return false;
        }
        $files[$file] = $html;
        $changed = $changed || (string) @file_get_contents("$root/$file") !== $html;
    }
    $files['robots.txt'] = ms_robots($c, $base);
    $files['llms.txt'] = ms_llms($c, $base);
    // Die Sitemap bekommt nur ein neues Datum, wenn sich eine Seite wirklich geändert hat.
    if ($changed) {
        $files['sitemap.xml'] = ms_sitemap($base);
    }
    foreach ($files as $name => $content) {
        if (!ms_write("$root/$name", $content)) {
            $error = "$name konnte nicht geschrieben werden. Bitte die Schreibrechte im Hauptordner der Website prüfen.";
            return false;
        }
    }
    return true;
}

/** Eine Seite aus lib/seite.php erzeugen; null mit Fehlermeldung in $error, wenn die Vorlage scheitert. */
function ms_render_page(array $c, array $R, &$error = null)
{
    ob_start();
    try {
        include __DIR__ . '/seite.php';
        return (string) ob_get_clean();
    } catch (Throwable $t) {
        ob_end_clean();
        $error = 'Fehler in der Vorlage lib/seite.php (' . $R['page'] . '): ' . $t->getMessage();
        return null;
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $ok = ms_render_site(dirname(__DIR__), $err);
    fwrite($ok ? STDOUT : STDERR, $ok ? "Website erzeugt: " . implode(', ', MS_OUTPUT) . "\n" : "Fehler: $err\n");
    exit($ok ? 0 : 1);
}
