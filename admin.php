<?php
/**
 * Admin-Konsole für modesuozzi.ch
 *
 * Bearbeitet content/site.json und lädt Bilder nach images/ hoch.
 * Voraussetzungen: PHP 7.4 oder neuer, Schreibrechte für content/, images/
 * und das Verzeichnis admin-data/ (wird beim ersten Aufruf angelegt).
 *
 * Beim ersten Aufruf wird das Passwort festgelegt. Danach liegt es als Hash
 * in admin-data/config.php. Passwort vergessen: diese Datei per FTP löschen
 * und admin.php erneut aufrufen.
 */

const CONTENT_FILE   = __DIR__ . '/content/site.json';
const IMAGE_DIR      = __DIR__ . '/images';
const IMAGE_URL      = '/images/';
const DATA_DIR       = __DIR__ . '/admin-data';
const BACKUP_DIR     = DATA_DIR . '/backups';
const CONFIG_FILE    = DATA_DIR . '/config.php';
const ATTEMPTS_FILE  = DATA_DIR . '/attempts.php';
const KEEP_BACKUPS   = 30;
const MAX_UPLOAD     = 8 * 1024 * 1024;
const SESSION_IDLE   = 2 * 3600;
const MAX_ATTEMPTS   = 5;
const LOCK_SECONDS   = 15 * 60;
const MIN_PASSWORD   = 10;
const GUARD          = "<?php exit; ?>\n"; // Dateien in admin-data/ sind nie direkt lesbar
const DAYS           = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

date_default_timezone_set('Europe/Zurich');

// ---------------------------------------------------------------- Felder

function f($name, $label, $type, $opt = [])
{
    return ['name' => $name, 'label' => $label, 'type' => $type] + $opt;
}

$artikel = [f('artikel', 'Artikel', 'string'), f('marke', 'Marke', 'string')];

$SCHEMA = [
    'seo' => ['label' => 'Allgemein', 'fields' => [
        f('titel', 'Seitentitel (Browser-Tab und Google)', 'string', ['max' => 70]),
        f('beschreibung', 'Kurzbeschreibung für Google', 'text', ['max' => 170, 'rows' => 3]),
        f('testversion', 'Testversion (vor Google verbergen)', 'bool', ['help' => 'Ausschalten, sobald die Seite unter modesuozzi.ch live ist.']),
    ]],
    'hero' => ['label' => 'Titelbereich', 'fields' => [
        f('oberzeile', 'Oberzeile', 'string'),
        f('titel', 'Überschrift', 'string'),
        f('text', 'Einleitung', 'text', ['rows' => 3]),
        f('bild', 'Titelbild', 'image', ['help' => 'Querformat, mindestens 1600 Pixel breit.']),
        f('bild_alt', 'Bildbeschreibung (für Blinde und Google)', 'string'),
        f('hinweis_titel', 'Hinweis auf dem Bild – Titel', 'string', ['help' => 'z. B. „Beachten Sie unser Herbstfenster!“ – leer lassen, um den Hinweis auszublenden.']),
        f('hinweis_text', 'Hinweis auf dem Bild – Text', 'string'),
    ]],
    'sortiment' => ['label' => 'Sortiment', 'fields' => [
        f('titel', 'Überschrift', 'string'),
        f('einleitung', 'Einleitung', 'text', ['rows' => 3]),
        f('aktuelle_saison', 'Aktuelle Saison hervorheben', 'select', [
            'options' => ['automatisch' => 'Automatisch', 'herbst_winter' => 'Herbst / Winter', 'fruehling_sommer' => 'Frühling / Sommer'],
            'help' => '„Automatisch“ = März–August Frühling/Sommer, September–Februar Herbst/Winter.',
        ]),
        f('herbst_winter', 'Herbst / Winter', 'list', ['fields' => $artikel, 'add' => 'Artikel hinzufügen']),
        f('fruehling_sommer', 'Frühling / Sommer', 'list', ['fields' => $artikel, 'add' => 'Artikel hinzufügen']),
    ]],
    'marken' => ['label' => 'Marken', 'fields' => [
        f('titel', 'Überschrift', 'string'),
        f('einleitung', 'Einleitung', 'text', ['rows' => 2]),
        f('liste', 'Marken', 'list', ['add' => 'Marke hinzufügen', 'fields' => [
            f('name', 'Name', 'string', ['required' => true]),
            f('link', 'Website (optional)', 'url'),
        ]]),
    ]],
    'ueber_uns' => ['label' => 'Über uns', 'fields' => [
        f('titel', 'Überschrift', 'string'),
        f('bild', 'Foto', 'image'),
        f('bild_alt', 'Bildbeschreibung', 'string'),
        f('bildunterschrift', 'Bildunterschrift', 'string'),
        f('text', 'Text', 'text', ['rows' => 7, 'help' => 'Absätze mit einer Leerzeile trennen.']),
        f('zitat', 'Zitat (optional)', 'string'),
    ]],
    'besuch' => ['label' => 'Besuch & Kontakt', 'fields' => [
        f('titel', 'Überschrift', 'string'),
        f('einleitung', 'Einleitung', 'text', ['rows' => 2]),
        f('firma', 'Firmenname', 'string'),
        f('strasse', 'Strasse', 'string'),
        f('ort', 'PLZ und Ort', 'string'),
        f('telefon', 'Telefon', 'string', ['help' => 'z. B. +41 44 940 87 21']),
        f('fax', 'Fax (optional)', 'string'),
        f('email', 'E-Mail', 'email'),
        f('oeffnungszeiten', 'Öffnungszeiten', 'list', ['add' => 'Zeile hinzufügen', 'fields' => [
            f('bezeichnung', 'Bezeichnung', 'string', ['placeholder' => 'z. B. Montag – Freitag']),
            f('zeiten', 'Zeiten', 'string', ['placeholder' => '09:00–12:00, 14:00–18:30 oder geschlossen']),
            f('tage', 'Gilt für', 'days'),
        ]]),
        f('laden_bild', 'Foto des Ladens', 'image'),
        f('laden_bild_alt', 'Bildbeschreibung', 'string'),
        f('laden_titel', 'Bildtitel', 'string'),
        f('laden_text', 'Bildtext', 'text', ['rows' => 3]),
    ]],
];

// ---------------------------------------------------------------- Hilfsfunktionen

function h($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function self_url()
{
    return strtok($_SERVER['REQUEST_URI'] ?? 'admin.php', '?');
}

function redirect($query = '')
{
    header('Location: ' . self_url() . $query, true, 303);
    exit;
}

function flash($msg = null, $type = 'ok')
{
    if ($msg !== null) {
        $_SESSION['flash'] = [$type, $msg];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function atomic_write($file, $content)
{
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

function protected_write($file, $string)
{
    return atomic_write($file, GUARD . $string);
}

function protected_read($file)
{
    if (!is_file($file)) {
        return null;
    }
    $s = (string) file_get_contents($file);
    return strpos($s, GUARD) === 0 ? substr($s, strlen(GUARD)) : $s;
}

function ensure_data_dir()
{
    foreach ([DATA_DIR, BACKUP_DIR] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    $ht = DATA_DIR . '/.htaccess';
    if (is_dir(DATA_DIR) && !is_file($ht)) {
        @file_put_contents($ht, "Require all denied\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
    }
}

function ini_bytes($v)
{
    $v = trim((string) $v);
    if ($v === '') {
        return 0;
    }
    $n = (float) $v;
    switch (strtolower(substr($v, -1))) {
        case 'g': $n *= 1024;
        case 'm': $n *= 1024;
        case 'k': $n *= 1024;
    }
    return (int) $n;
}

function max_upload_bytes()
{
    $limits = [MAX_UPLOAD];
    foreach (['upload_max_filesize', 'post_max_size'] as $k) {
        $b = ini_bytes(ini_get($k));
        if ($b > 0) {
            $limits[] = $b;
        }
    }
    return min($limits);
}

function mb_label($bytes)
{
    return rtrim(rtrim(number_format($bytes / 1048576, 1, '.', ''), '0'), '.') . ' MB';
}

function cut($s, $n)
{
    return preg_match('/^.{0,' . (int) $n . '}/su', $s, $m) ? $m[0] : '';
}

function one_line($s, $max = 300)
{
    if (!is_string($s)) {
        return '';
    }
    $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s);
    return $s === null ? '' : cut(trim($s), $max);
}

function multi_line($s, $max = 5000)
{
    if (!is_string($s)) {
        return '';
    }
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/u', '', $s);
    return $s === null ? '' : cut(trim($s), $max);
}

function slug($s)
{
    $s = strtr($s, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue', 'ß' => 'ss',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ì' => 'i', 'í' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ù' => 'u', 'ú' => 'u', 'ç' => 'c', 'ñ' => 'n']);
    $s = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($s)), '-');
    $s = substr($s, 0, 50);
    return $s === '' ? 'bild' : $s;
}

function client_key()
{
    return hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

// ---------------------------------------------------------------- Inhalte

function load_content(&$warning = null)
{
    if (!is_file(CONTENT_FILE)) {
        $warning = 'content/site.json wurde nicht gefunden.';
        return [];
    }
    $data = json_decode((string) file_get_contents(CONTENT_FILE), true);
    if (!is_array($data)) {
        $warning = 'content/site.json ist fehlerhaft und konnte nicht gelesen werden.';
        return [];
    }
    return $data;
}

function content_rev()
{
    return is_file(CONTENT_FILE) ? sha1_file(CONTENT_FILE) : '';
}

function list_images()
{
    $out = [];
    foreach (is_dir(IMAGE_DIR) ? scandir(IMAGE_DIR) : [] as $f) {
        if (preg_match('/^[A-Za-z0-9._-]+\.(jpe?g|png|webp|gif)$/i', $f) && is_file(IMAGE_DIR . '/' . $f)) {
            $out[] = IMAGE_URL . $f;
        }
    }
    natcasesort($out);
    return array_values($out);
}

function list_backups()
{
    $out = [];
    foreach (is_dir(BACKUP_DIR) ? scandir(BACKUP_DIR) : [] as $f) {
        if (preg_match('/^site-\d{8}-\d{6}(-\d+)?\.json\.php$/', $f)) {
            $out[] = $f;
        }
    }
    rsort($out, SORT_STRING);
    return $out;
}

function backup_label($file)
{
    preg_match('/^site-(\d{4})(\d{2})(\d{2})-(\d{2})(\d{2})(\d{2})/', $file, $m);
    return "$m[3].$m[2].$m[1], $m[4]:$m[5]:$m[6] Uhr";
}

function backup_current()
{
    if (!is_file(CONTENT_FILE)) {
        return true;
    }
    $base = 'site-' . date('Ymd-His');
    $name = $base . '.json.php';
    for ($i = 2; is_file(BACKUP_DIR . '/' . $name); $i++) {
        $name = "$base-$i.json.php";
    }
    if (!protected_write(BACKUP_DIR . '/' . $name, (string) file_get_contents(CONTENT_FILE))) {
        return false;
    }
    foreach (array_slice(list_backups(), KEEP_BACKUPS) as $old) {
        @unlink(BACKUP_DIR . '/' . $old);
    }
    return true;
}

function write_content(array $data)
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return $json !== false && atomic_write(CONTENT_FILE, $json . "\n");
}

function clean_value(array $f, $raw, $old, array &$errors, $where)
{
    $label = $where . $f['label'];
    switch ($f['type']) {
        case 'text':
            return multi_line($raw, $f['max'] ?? 5000);
        case 'bool':
            return $raw === '1';
        case 'select':
            if (is_string($raw) && isset($f['options'][$raw])) {
                return $raw;
            }
            return isset($f['options'][$old]) ? $old : array_key_first_compat($f['options']);
        case 'days':
            $raw = array_filter(is_array($raw) ? $raw : [], 'is_string');
            return array_values(array_intersect(DAYS, $raw));
        case 'url':
            $s = one_line($raw, 500);
            if ($s === '') {
                return '';
            }
            if (!preg_match('~^https?://~i', $s)) {
                $s = 'https://' . $s;
            }
            if (!filter_var($s, FILTER_VALIDATE_URL)) {
                $errors[] = "$label: „" . $s . '“ ist keine gültige Internetadresse.';
            }
            return $s;
        case 'email':
            $s = one_line($raw, 200);
            if ($s !== '' && !filter_var($s, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "$label: „" . $s . '“ ist keine gültige E-Mail-Adresse.';
            }
            return $s;
        case 'image':
            $s = one_line($raw, 500);
            if ($s === '') {
                return '';
            }
            if (preg_match('~^/images/([A-Za-z0-9._-]+)$~', $s, $m) && is_file(IMAGE_DIR . '/' . $m[1])) {
                return $s;
            }
            if (preg_match('~^https?://~i', $s) && filter_var($s, FILTER_VALIDATE_URL)) {
                return $s;
            }
            $errors[] = "$label: Bild nicht gefunden.";
            return is_string($old) ? $old : '';
        case 'list':
            $out = [];
            $n = 0;
            foreach (is_array($raw) ? $raw : [] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $n++;
                $item = [];
                $filled = false;
                foreach ($f['fields'] as $sub) {
                    $v = clean_value($sub, $row[$sub['name']] ?? null, null, $errors, "$label, Zeile $n – ");
                    $item[$sub['name']] = $v;
                    if ($sub['type'] !== 'days' && $v !== '') {
                        $filled = true;
                    }
                }
                if (!$filled) {
                    continue; // leere Zeilen werden ignoriert
                }
                foreach ($f['fields'] as $sub) {
                    if (!empty($sub['required']) && $item[$sub['name']] === '') {
                        $errors[] = "$label, Zeile $n: „{$sub['label']}“ darf nicht leer sein.";
                    }
                }
                $out[] = $item;
            }
            return $out;
        default: // string
            return one_line($raw, $f['max'] ?? 300);
    }
}

function array_key_first_compat(array $a)
{
    foreach ($a as $k => $_) {
        return $k;
    }
    return null;
}

function handle_upload(array $file, array &$errors, $label)
{
    $max = max_upload_bytes();
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) || $file['size'] > $max) {
        $errors[] = "$label: Die Datei ist zu gross (höchstens " . mb_label($max) . ').';
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = "$label: Das Hochladen ist fehlgeschlagen. Bitte erneut versuchen.";
        return null;
    }
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) {
        $errors[] = "$label: Nur Bilder im Format JPG, PNG oder WebP sind erlaubt.";
        return null;
    }
    if (!is_dir(IMAGE_DIR) || !is_writable(IMAGE_DIR)) {
        $errors[] = "$label: Der Ordner images/ ist auf dem Server nicht beschreibbar.";
        return null;
    }
    $base = slug(pathinfo((string) $file['name'], PATHINFO_FILENAME));
    $ext = $types[$info[2]];
    $name = "$base.$ext";
    for ($i = 2; file_exists(IMAGE_DIR . '/' . $name); $i++) {
        $name = "$base-$i.$ext";
    }
    if (!move_uploaded_file($file['tmp_name'], IMAGE_DIR . '/' . $name)) {
        $errors[] = "$label: Das Bild konnte nicht gespeichert werden.";
        return null;
    }
    @chmod(IMAGE_DIR . '/' . $name, 0644);
    return IMAGE_URL . $name;
}

// ---------------------------------------------------------------- Anmeldung

function load_attempts()
{
    $a = json_decode((string) protected_read(ATTEMPTS_FILE), true);
    $a = is_array($a) ? $a : [];
    foreach ($a as $k => $v) {
        if (!is_array($v) || ($v['t'] ?? 0) < time() - LOCK_SECONDS) {
            unset($a[$k]);
        }
    }
    return $a;
}

function save_attempts(array $a)
{
    protected_write(ATTEMPTS_FILE, json_encode($a));
}

function load_config()
{
    $c = json_decode((string) protected_read(CONFIG_FILE), true);
    return is_array($c) && !empty($c['hash']) ? $c : null;
}

function save_password($password)
{
    return protected_write(CONFIG_FILE, json_encode(['hash' => password_hash($password, PASSWORD_DEFAULT)]));
}

function check_new_password($pw, $repeat)
{
    if (strlen($pw) < MIN_PASSWORD) {
        return 'Das Passwort muss mindestens ' . MIN_PASSWORD . ' Zeichen lang sein.';
    }
    if (!hash_equals($pw, $repeat)) {
        return 'Die beiden Passwörter stimmen nicht überein.';
    }
    return null;
}

// ---------------------------------------------------------------- Ablauf

header('Content-Type: text/html; charset=utf-8');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
$NONCE = base64_encode(random_bytes(16));
header("Content-Security-Policy: default-src 'self'; img-src 'self' https: data: blob:; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src 'nonce-$NONCE'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('ms_admin');
session_set_cookie_params(['lifetime' => 0, 'path' => self_url(), 'secure' => $https, 'httponly' => true, 'samesite' => 'Strict']);
session_start();
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

ensure_data_dir();
$config = load_config();
$authed = false;
if (!empty($_SESSION['auth'])) {
    if (time() - ($_SESSION['last'] ?? 0) > SESSION_IDLE) {
        unset($_SESSION['auth']);
        flash('Sie wurden nach längerer Inaktivität abgemeldet.', 'info');
    } else {
        $authed = true;
        $_SESSION['last'] = time();
    }
}

$errors = [];
$formData = null;   // bei Fehlern: eingegebene Werte erneut anzeigen
$formRev = null;
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $errors[] = 'Die hochgeladenen Daten sind zu gross (höchstens ' . mb_label(max_upload_bytes()) . ' pro Bild). Es wurde nichts gespeichert.';
        $action = '';
    } elseif (!hash_equals($_SESSION['csrf'], (string) ($_POST['csrf'] ?? ''))) {
        $errors[] = 'Die Sitzung ist abgelaufen. Bitte die Seite neu laden und erneut versuchen.';
        $action = '';
    }
}

if ($action === 'setup' && !$config) {
    $pw = (string) ($_POST['password'] ?? '');
    $err = check_new_password($pw, (string) ($_POST['password2'] ?? ''));
    if ($err) {
        $errors[] = $err;
    } elseif (!is_dir(DATA_DIR) || !save_password($pw)) {
        $errors[] = 'Das Passwort konnte nicht gespeichert werden: Der Ordner admin-data/ ist auf dem Server nicht beschreibbar.';
    } else {
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
        $_SESSION['last'] = time();
        flash('Passwort festgelegt. Willkommen in der Admin-Konsole!');
        redirect();
    }
}

if ($action === 'login' && $config) {
    $attempts = load_attempts();
    $key = client_key();
    $entry = $attempts[$key] ?? ['n' => 0, 't' => time()];
    if ($entry['n'] >= MAX_ATTEMPTS) {
        $wait = (int) ceil(($entry['t'] + LOCK_SECONDS - time()) / 60);
        $errors[] = "Zu viele Fehlversuche. Bitte in $wait Minuten erneut versuchen.";
    } elseif (password_verify((string) ($_POST['password'] ?? ''), $config['hash'])) {
        unset($attempts[$key]);
        save_attempts($attempts);
        if (password_needs_rehash($config['hash'], PASSWORD_DEFAULT)) {
            save_password((string) $_POST['password']);
        }
        session_regenerate_id(true);
        $_SESSION['auth'] = true;
        $_SESSION['last'] = time();
        redirect();
    } else {
        $attempts[$key] = ['n' => $entry['n'] + 1, 't' => time()];
        save_attempts($attempts);
        sleep(1);
        $left = MAX_ATTEMPTS - $attempts[$key]['n'];
        $errors[] = 'Das Passwort ist falsch.' . ($left > 0 ? '' : ' Die Anmeldung ist jetzt für 15 Minuten gesperrt.');
    }
}

if ($authed) {
    if ($action === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('Sie wurden abgemeldet.', 'info');
        redirect();
    }

    if ($action === 'save') {
        $current = load_content();
        $raw = is_array($_POST['data'] ?? null) ? $_POST['data'] : [];

        foreach ($SCHEMA as $sec => $section) {
            foreach ($section['fields'] as $field) {
                $key = "upload_{$sec}__{$field['name']}";
                if ($field['type'] === 'image' && isset($_FILES[$key]['error']) && !is_array($_FILES[$key]['error'])) {
                    $path = handle_upload($_FILES[$key], $errors, "{$section['label']} – {$field['label']}");
                    if ($path !== null) {
                        $raw[$sec][$field['name']] = $path;
                    }
                }
            }
        }

        $new = $current;
        foreach ($SCHEMA as $sec => $section) {
            $old = is_array($current[$sec] ?? null) ? $current[$sec] : [];
            $in = is_array($raw[$sec] ?? null) ? $raw[$sec] : [];
            $out = $old;
            foreach ($section['fields'] as $field) {
                $out[$field['name']] = clean_value($field, $in[$field['name']] ?? null, $old[$field['name']] ?? null, $errors, $section['label'] . ' – ');
            }
            $new[$sec] = $out;
        }

        $rev = (string) ($_POST['rev'] ?? '');
        if (!$errors && $rev !== content_rev()) {
            $errors[] = 'Die Inhalte wurden inzwischen an einem anderen Ort gespeichert. Ihre Eingaben sind unten noch vorhanden: Prüfen Sie sie und klicken Sie nochmals auf „Speichern“, um die andere Version zu überschreiben.';
            $formRev = content_rev();
        }
        if (!$errors) {
            if (!backup_current()) {
                $errors[] = 'Die Sicherungskopie konnte nicht angelegt werden (Ordner admin-data/ nicht beschreibbar). Es wurde nichts gespeichert.';
            } elseif (!write_content($new)) {
                $errors[] = 'content/site.json konnte nicht gespeichert werden. Bitte die Schreibrechte auf dem Server prüfen.';
            } else {
                flash('Gespeichert. Die Änderungen sind sofort auf der Website sichtbar.');
                redirect();
            }
        }
        $formData = $new;
    }

    if ($action === 'restore') {
        $file = (string) ($_POST['file'] ?? '');
        $json = in_array($file, list_backups(), true) ? protected_read(BACKUP_DIR . '/' . $file) : null;
        $data = $json === null ? null : json_decode($json, true);
        if (!is_array($data)) {
            $errors[] = 'Diese Version konnte nicht gelesen werden.';
        } elseif (!backup_current() || !write_content($data)) {
            $errors[] = 'Die Version konnte nicht wiederhergestellt werden. Bitte die Schreibrechte prüfen.';
        } else {
            flash('Die Version vom ' . backup_label($file) . ' wurde wiederhergestellt.');
            redirect();
        }
    }

    if ($action === 'password') {
        $pw = (string) ($_POST['password'] ?? '');
        if (!$config || !password_verify((string) ($_POST['current'] ?? ''), $config['hash'])) {
            $errors[] = 'Das aktuelle Passwort ist falsch.';
        } elseif ($err = check_new_password($pw, (string) ($_POST['password2'] ?? ''))) {
            $errors[] = $err;
        } elseif (!save_password($pw)) {
            $errors[] = 'Das neue Passwort konnte nicht gespeichert werden.';
        } else {
            session_regenerate_id(true);
            flash('Das Passwort wurde geändert.');
            redirect();
        }
    }
}

// ---------------------------------------------------------------- Formular-Bausteine

function input_name(array $path)
{
    return 'data[' . implode('][', $path) . ']';
}

function field_id(array $path)
{
    return 'f-' . implode('-', $path);
}

function preview_src($v)
{
    if (!is_string($v) || $v === '') {
        return '';
    }
    return preg_match('~^https?://~i', $v) ? $v : ltrim($v, '/');
}

function render_field(array $f, $value, array $path, $compact = false)
{
    $name = input_name($path);
    $id = field_id($path);
    $help = isset($f['help']) ? '<small class="help">' . h($f['help']) . '</small>' : '';
    $ph = isset($f['placeholder']) ? ' placeholder="' . h($f['placeholder']) . '"' : '';
    $max = isset($f['max']) ? ' maxlength="' . (int) $f['max'] . '"' : '';
    $req = !empty($f['required']) ? ' required' : '';
    $cls = 'field field-' . $f['type'] . ($compact ? ' compact' : '');

    switch ($f['type']) {
        case 'bool':
            echo '<div class="' . $cls . '"><input type="hidden" name="' . h($name) . '" value="0">'
                . '<label class="check"><input type="checkbox" id="' . h($id) . '" name="' . h($name) . '" value="1"' . ($value ? ' checked' : '') . '> '
                . h($f['label']) . '</label>' . $help . '</div>';
            return;

        case 'days':
            $value = is_array($value) ? $value : [];
            echo '<fieldset class="' . $cls . '"><legend>' . h($f['label']) . '</legend><div class="days">';
            foreach (DAYS as $d) {
                echo '<label class="day"><input type="checkbox" name="' . h($name) . '[]" value="' . $d . '"' . (in_array($d, $value, true) ? ' checked' : '') . '><span>' . $d . '</span></label>';
            }
            echo '</div></fieldset>';
            return;

        case 'list':
            render_list($f, is_array($value) ? $value : [], $path);
            return;

        case 'image':
            render_image($f, is_string($value) ? $value : '', $path);
            return;
    }

    echo '<div class="' . $cls . '"><label for="' . h($id) . '">' . h($f['label']) . '</label>';
    if ($f['type'] === 'text') {
        echo '<textarea id="' . h($id) . '" name="' . h($name) . '" rows="' . (int) ($f['rows'] ?? 3) . '"' . $max . $ph . '>' . h($value) . '</textarea>';
    } elseif ($f['type'] === 'select') {
        echo '<select id="' . h($id) . '" name="' . h($name) . '">';
        foreach ($f['options'] as $k => $lbl) {
            echo '<option value="' . h($k) . '"' . ((string) $value === (string) $k ? ' selected' : '') . '>' . h($lbl) . '</option>';
        }
        echo '</select>';
    } else {
        $type = $f['type'] === 'email' ? 'email' : 'text';
        echo '<input type="' . $type . '" id="' . h($id) . '" name="' . h($name) . '" value="' . h(is_scalar($value) ? $value : '') . '"' . $max . $ph . $req . '>';
    }
    echo $help . '</div>';
}

function render_row(array $f, array $item, array $path, $key)
{
    echo '<div class="row" data-row><div class="row-fields cols-' . count($f['fields']) . '">';
    foreach ($f['fields'] as $sub) {
        render_field($sub, $item[$sub['name']] ?? ($sub['type'] === 'days' ? [] : ''), array_merge($path, [$key, $sub['name']]), true);
    }
    echo '</div><div class="row-tools">'
        . '<button type="button" class="icon" data-up title="Nach oben" aria-label="Nach oben">↑</button>'
        . '<button type="button" class="icon" data-down title="Nach unten" aria-label="Nach unten">↓</button>'
        . '<button type="button" class="icon danger" data-remove title="Entfernen" aria-label="Entfernen">✕</button>'
        . '</div></div>';
}

function render_list(array $f, array $items, array $path)
{
    $id = field_id($path);
    echo '<div class="field field-list"><span class="label">' . h($f['label']) . '</span>';
    echo '<div class="rows" id="' . h($id) . '">';
    foreach (array_values($items) as $i => $item) {
        render_row($f, is_array($item) ? $item : [], $path, (string) $i);
    }
    echo '</div><template id="tpl-' . h($id) . '">';
    render_row($f, [], $path, '__i__');
    echo '</template><button type="button" class="add" data-add="' . h($id) . '">+ ' . h($f['add'] ?? 'Hinzufügen') . '</button></div>';
}

function render_image(array $f, $value, array $path)
{
    static $images = null;
    if ($images === null) {
        $images = list_images();
    }
    $id = field_id($path);
    $src = preview_src($value);
    $options = $images;
    if ($value !== '' && !in_array($value, $options, true)) {
        array_unshift($options, $value);
    }
    echo '<div class="field field-image"><label for="' . h($id) . '">' . h($f['label']) . '</label><div class="img-box">';
    echo '<div class="preview' . ($src === '' ? ' empty' : '') . '"><img src="' . h($src) . '" alt="" data-preview="' . h($id) . '"><span>Kein Bild</span></div><div class="img-controls">';
    echo '<select id="' . h($id) . '" name="' . h(input_name($path)) . '" data-img-select>';
    echo '<option value="">— kein Bild —</option>';
    foreach ($options as $o) {
        $lbl = preg_match('~^https?://~i', $o) ? 'Externes Bild: ' . $o : basename($o);
        echo '<option value="' . h($o) . '"' . ($o === $value ? ' selected' : '') . '>' . h($lbl) . '</option>';
    }
    echo '</select>';
    echo '<label class="upload"><input type="file" name="upload_' . h(implode('__', $path)) . '" accept="image/jpeg,image/png,image/webp" data-upload="' . h($id) . '"><span>Neues Bild hochladen …</span></label>';
    echo '<small class="help">' . (isset($f['help']) ? h($f['help']) . ' ' : '') . 'JPG, PNG oder WebP, höchstens ' . h(mb_label(max_upload_bytes())) . '. Wird beim Speichern hochgeladen.</small>';
    echo '</div></div></div>';
}

// ---------------------------------------------------------------- Seite

$flash = flash();
$warning = null;
$content = $formData ?? load_content($warning);
$rev = $formRev ?? ($formData !== null ? (string) ($_POST['rev'] ?? '') : content_rev());

$problems = [];
if ($authed) {
    if ($warning) {
        $problems[] = $warning;
    }
    if (is_file(CONTENT_FILE) && !is_writable(CONTENT_FILE)) {
        $problems[] = 'content/site.json ist auf dem Server schreibgeschützt – Änderungen können nicht gespeichert werden.';
    }
    if (!is_dir(IMAGE_DIR) || !is_writable(IMAGE_DIR)) {
        $problems[] = 'Der Ordner images/ ist nicht beschreibbar – Bilder können nicht hochgeladen werden.';
    }
    if (!is_dir(BACKUP_DIR) || !is_writable(BACKUP_DIR)) {
        $problems[] = 'Der Ordner admin-data/ ist nicht beschreibbar – ohne Sicherungskopien wird nicht gespeichert.';
    }
}
$csrf = $_SESSION['csrf'];
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin · Mode Suozzi</title>
<link rel="icon" href="images/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,400&family=Jost:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --brand: #273837; --brand-deep: #1c2928; --gold: #b0974c; --gold-hover: #c4ab5f;
    --bg: #f3f1ed; --paper: #ffffff; --ink: #1c2626; --muted: #56625f; --line: rgba(39, 56, 55, .16);
    --field: #ffffff; --ok: #2f6b45; --ok-bg: #e6f1e9; --err: #9b2c2c; --err-bg: #f8e7e5; --info-bg: #ebeef0;
    color-scheme: light;
  }
  @media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
      --bg: #121a1a; --paper: #1a2424; --ink: #eceae6; --muted: #a9b0ad; --line: rgba(236, 234, 230, .14);
      --field: #141d1d; --ok: #8fd0a5; --ok-bg: #1d3326; --err: #f0a39b; --err-bg: #3a1f1d; --info-bg: #222d2d;
      color-scheme: dark;
    }
  }
  :root[data-theme="dark"] {
    --bg: #121a1a; --paper: #1a2424; --ink: #eceae6; --muted: #a9b0ad; --line: rgba(236, 234, 230, .14);
    --field: #141d1d; --ok: #8fd0a5; --ok-bg: #1d3326; --err: #f0a39b; --err-bg: #3a1f1d; --info-bg: #222d2d;
    color-scheme: dark;
  }
  * { box-sizing: border-box; }
  html { scroll-behavior: smooth; scroll-padding-top: 90px; }
  body { margin: 0; background: var(--bg); color: var(--ink); font: 16px/1.5 "Jost", system-ui, sans-serif; }
  a { color: inherit; }
  h1, h2 { font-family: "Bodoni Moda", Georgia, serif; font-weight: 400; margin: 0; }
  button, input, select, textarea { font: inherit; color: inherit; }

  .top { position: sticky; top: 0; z-index: 10; background: var(--brand); color: #f4f2ee; border-bottom: 1px solid rgba(176, 151, 76, .35); }
  .top .wrap { display: flex; align-items: center; gap: 16px; min-height: 64px; }
  .top img { height: 40px; width: auto; display: block; }
  .top h1 { font-size: 1.25rem; flex: 1; }
  .top a, .top button { color: #f4f2ee; text-decoration: none; background: none; border: 0; padding: 6px 4px; cursor: pointer; font-size: .95rem; }
  .top a:hover, .top button:hover { color: var(--gold); }
  .top form { margin: 0; }

  .wrap { width: min(1080px, 100% - 32px); margin-inline: auto; }
  .layout { display: grid; grid-template-columns: 200px 1fr; gap: 32px; padding: 32px 0 120px; }
  nav.toc { position: sticky; top: 96px; align-self: start; display: flex; flex-direction: column; gap: 2px; }
  nav.toc a { text-decoration: none; padding: 8px 12px; border-left: 2px solid transparent; color: var(--muted); }
  nav.toc a:hover { color: var(--ink); border-left-color: var(--gold); }

  .card { background: var(--paper); border: 1px solid var(--line); border-radius: 4px; padding: 24px; margin-bottom: 24px; }
  .card h2 { font-size: 1.5rem; margin-bottom: 18px; }
  .field { margin-bottom: 18px; }
  .field:last-child { margin-bottom: 0; }
  .field > label, .field .label, legend { display: block; font-weight: 500; margin-bottom: 6px; font-size: .95rem; }
  input[type=text], input[type=email], input[type=password], select, textarea {
    width: 100%; padding: 10px 12px; background: var(--field); border: 1px solid var(--line); border-radius: 3px;
  }
  textarea { resize: vertical; }
  input:focus, select:focus, textarea:focus { outline: 2px solid var(--gold); outline-offset: 0; border-color: var(--gold); }
  .help { display: block; color: var(--muted); font-size: .85rem; margin-top: 5px; }
  .check { display: flex; gap: 10px; align-items: center; font-weight: 500; cursor: pointer; }
  .check input { width: 18px; height: 18px; accent-color: var(--brand); }

  .rows { display: flex; flex-direction: column; gap: 8px; }
  .row { display: flex; gap: 10px; align-items: flex-start; padding: 12px; border: 1px solid var(--line); border-radius: 3px; background: var(--bg); }
  .row-fields { flex: 1; display: grid; gap: 10px; grid-template-columns: 1fr 1fr; min-width: 0; }
  .row-fields.cols-3 .field-days { grid-column: 1 / -1; }
  .row .field { margin: 0; }
  .row .field > label, .row legend { font-size: .8rem; font-weight: 400; color: var(--muted); margin-bottom: 3px; }
  .row-tools { display: flex; gap: 4px; padding-top: 22px; }
  .icon { width: 34px; height: 34px; border: 1px solid var(--line); background: var(--paper); border-radius: 3px; cursor: pointer; }
  .icon:hover { border-color: var(--gold); }
  .icon.danger:hover { border-color: var(--err); color: var(--err); }
  .row:first-child [data-up], .row:last-child [data-down] { visibility: hidden; }
  fieldset { border: 0; padding: 0; margin: 0; }
  .days { display: flex; flex-wrap: wrap; gap: 6px; }
  .day input { position: absolute; opacity: 0; pointer-events: none; }
  .day span { display: inline-block; min-width: 44px; text-align: center; padding: 6px 8px; border: 1px solid var(--line); border-radius: 3px; cursor: pointer; background: var(--paper); }
  .day input:checked + span { background: var(--brand); border-color: var(--brand); color: #f4f2ee; }
  .day input:focus-visible + span { outline: 2px solid var(--gold); }
  .add { margin-top: 10px; background: none; border: 1px dashed var(--line); padding: 8px 14px; border-radius: 3px; cursor: pointer; color: var(--ink); }
  .add:hover { border-color: var(--gold); }

  .img-box { display: grid; grid-template-columns: 220px 1fr; gap: 16px; align-items: start; }
  .preview { aspect-ratio: 4 / 3; background: var(--bg); border: 1px solid var(--line); border-radius: 3px; overflow: hidden; display: grid; place-items: center; }
  .preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .preview span { display: none; color: var(--muted); font-size: .9rem; }
  .preview.empty img { display: none; }
  .preview.empty span { display: block; }
  .img-controls { display: flex; flex-direction: column; gap: 10px; min-width: 0; }
  .upload { display: block; cursor: pointer; }
  .upload input { position: absolute; opacity: 0; width: 1px; height: 1px; }
  .upload span { display: inline-block; padding: 9px 14px; border: 1px solid var(--line); border-radius: 3px; background: var(--paper); max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .upload:hover span, .upload input:focus-visible + span { border-color: var(--gold); }
  .upload.chosen span { border-color: var(--gold); background: rgba(176, 151, 76, .12); }

  .btn { display: inline-block; padding: 11px 22px; border-radius: 3px; border: 1px solid transparent; cursor: pointer; font-weight: 500; text-decoration: none; }
  .btn-primary { background: var(--gold); color: var(--brand-deep); }
  .btn-primary:hover { background: var(--gold-hover); }
  .btn-ghost { background: none; border-color: var(--line); color: var(--ink); }
  .btn-ghost:hover { border-color: var(--gold); }
  .savebar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 10; background: var(--paper); border-top: 1px solid var(--line); box-shadow: 0 -6px 20px rgba(0, 0, 0, .08); }
  .savebar .wrap { display: flex; align-items: center; justify-content: flex-end; gap: 16px; min-height: 68px; }
  .savebar .state { color: var(--muted); font-size: .9rem; margin-right: auto; }
  .savebar .state.dirty { color: var(--ink); font-weight: 500; }

  .msg { padding: 12px 16px; border-radius: 3px; margin-bottom: 20px; }
  .msg.ok { background: var(--ok-bg); color: var(--ok); }
  .msg.err { background: var(--err-bg); color: var(--err); }
  .msg.info { background: var(--info-bg); }
  .msg ul { margin: 6px 0 0; padding-left: 20px; }

  .backups { list-style: none; margin: 0; padding: 0; }
  .backups li { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--line); }
  .backups li:last-child { border-bottom: 0; }
  .backups form { margin: 0; }
  .backups button { background: none; border: 1px solid var(--line); border-radius: 3px; padding: 5px 12px; cursor: pointer; }
  .backups button:hover { border-color: var(--gold); }
  .pw-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; align-items: end; }

  .auth { min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; background: var(--brand); background-image: repeating-linear-gradient(90deg, transparent 0 22px, rgba(176, 151, 76, .09) 22px 23px); }
  .auth .card { width: min(420px, 100%); margin: 0; }
  .auth .logo { display: block; height: 56px; margin: 0 auto 20px; }
  .auth h1 { text-align: center; font-size: 1.6rem; margin-bottom: 6px; }
  .auth p.sub { text-align: center; color: var(--muted); margin: 0 0 22px; }
  .auth .btn { width: 100%; margin-top: 6px; }

  @media (max-width: 860px) {
    .layout { grid-template-columns: 1fr; padding-top: 0; gap: 0; }
    nav.toc { position: sticky; top: 64px; z-index: 5; flex-direction: row; overflow-x: auto; background: var(--bg); margin: 0 -16px 16px; padding: 8px 16px; border-bottom: 1px solid var(--line); }
    nav.toc a { white-space: nowrap; border-left: 0; border-bottom: 2px solid transparent; padding: 6px 10px; }
    .img-box { grid-template-columns: 1fr; }
    .preview { max-width: 320px; }
    .pw-grid { grid-template-columns: 1fr; }
    .top .site-link { display: none; }
  }
  @media (max-width: 560px) {
    .card { padding: 18px 14px; }
    .row { flex-direction: column; }
    .row-fields { grid-template-columns: 1fr; width: 100%; }
    .row-tools { padding-top: 0; align-self: flex-end; }
    .savebar .state { display: none; }
    .savebar .btn { flex: 1; text-align: center; }
  }
</style>
</head>
<body>
<?php if (!$authed): ?>
<main class="auth">
  <div class="card">
    <img class="logo" src="images/logo.png" alt="Mode Suozzi">
    <?php if (!$config): ?>
      <h1>Admin einrichten</h1>
      <p class="sub">Legen Sie das Passwort für die Admin-Konsole fest.</p>
    <?php else: ?>
      <h1>Website-Inhalte</h1>
      <p class="sub">Bitte melden Sie sich an.</p>
    <?php endif; ?>
    <?php if ($flash): ?><div class="msg <?= h($flash[0]) ?>"><?= h($flash[1]) ?></div><?php endif; ?>
    <?php if ($errors): ?><div class="msg err"><?= h(implode(' ', $errors)) ?></div><?php endif; ?>
    <form method="post" autocomplete="on">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <?php if (!$config): ?>
        <input type="hidden" name="action" value="setup">
        <div class="field"><label for="pw">Neues Passwort</label><input type="password" id="pw" name="password" minlength="<?= MIN_PASSWORD ?>" autocomplete="new-password" required autofocus><small class="help">Mindestens <?= MIN_PASSWORD ?> Zeichen.</small></div>
        <div class="field"><label for="pw2">Passwort wiederholen</label><input type="password" id="pw2" name="password2" minlength="<?= MIN_PASSWORD ?>" autocomplete="new-password" required></div>
        <button class="btn btn-primary">Passwort festlegen</button>
      <?php else: ?>
        <input type="hidden" name="action" value="login">
        <input type="text" name="username" value="admin" autocomplete="username" hidden>
        <div class="field"><label for="pw">Passwort</label><input type="password" id="pw" name="password" autocomplete="current-password" required autofocus></div>
        <button class="btn btn-primary">Anmelden</button>
      <?php endif; ?>
    </form>
  </div>
</main>
<?php else: ?>
<header class="top">
  <div class="wrap">
    <img src="images/logo.png" alt="Mode Suozzi">
    <h1>Website-Inhalte</h1>
    <a class="site-link" href="./" target="_blank" rel="noopener">Website ansehen ↗</a>
    <form method="post"><input type="hidden" name="csrf" value="<?= h($csrf) ?>"><button name="action" value="logout">Abmelden</button></form>
  </div>
</header>

<div class="wrap layout">
  <nav class="toc" aria-label="Bereiche">
    <?php foreach ($SCHEMA as $sec => $section): ?><a href="#<?= h($sec) ?>"><?= h($section['label']) ?></a><?php endforeach; ?>
    <a href="#verwaltung">Versionen &amp; Passwort</a>
  </nav>

  <main>
    <?php if ($flash): ?><div class="msg <?= h($flash[0]) ?>" role="status"><?= h($flash[1]) ?></div><?php endif; ?>
    <?php if ($errors): ?>
      <div class="msg err" role="alert"><strong>Nicht gespeichert.</strong><ul><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php foreach ($problems as $p): ?><div class="msg err"><?= h($p) ?></div><?php endforeach; ?>

    <form method="post" enctype="multipart/form-data" id="editor" novalidate>
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="rev" value="<?= h($rev) ?>">
      <?php foreach ($SCHEMA as $sec => $section): $vals = is_array($content[$sec] ?? null) ? $content[$sec] : []; ?>
        <section class="card" id="<?= h($sec) ?>">
          <h2><?= h($section['label']) ?></h2>
          <?php foreach ($section['fields'] as $field) {
              render_field($field, $vals[$field['name']] ?? ($field['type'] === 'list' ? [] : ''), [$sec, $field['name']]);
          } ?>
        </section>
      <?php endforeach; ?>

      <div class="savebar">
        <div class="wrap">
          <span class="state" id="state">Keine ungespeicherten Änderungen</span>
          <a class="btn btn-ghost" href="<?= h(self_url()) ?>" id="discard">Verwerfen</a>
          <button class="btn btn-primary" type="submit">Speichern</button>
        </div>
      </div>
    </form>

    <section class="card" id="verwaltung">
      <h2>Frühere Versionen</h2>
      <?php $backups = array_slice(list_backups(), 0, 10); ?>
      <?php if (!$backups): ?>
        <p class="help">Noch keine. Vor jedem Speichern wird automatisch eine Sicherungskopie angelegt (die letzten <?= KEEP_BACKUPS ?> bleiben erhalten).</p>
      <?php else: ?>
        <p class="help" style="margin-top:0">Stand vor dem jeweiligen Speichern. Beim Wiederherstellen wird die aktuelle Version ebenfalls gesichert.</p>
        <ul class="backups">
          <?php foreach ($backups as $b): ?>
            <li><span><?= h(backup_label($b)) ?></span>
              <form method="post" data-confirm="Version vom <?= h(backup_label($b)) ?> wiederherstellen? Nicht gespeicherte Änderungen gehen verloren.">
                <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="file" value="<?= h($b) ?>">
                <button>Wiederherstellen</button>
              </form></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2>Passwort ändern</h2>
      <form method="post" class="pw-grid">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>"><input type="hidden" name="action" value="password">
        <input type="text" name="username" value="admin" autocomplete="username" hidden>
        <div class="field"><label for="cur">Aktuelles Passwort</label><input type="password" id="cur" name="current" autocomplete="current-password" required></div>
        <div class="field"><label for="npw">Neues Passwort</label><input type="password" id="npw" name="password" minlength="<?= MIN_PASSWORD ?>" autocomplete="new-password" required></div>
        <div class="field"><label for="npw2">Wiederholen</label><input type="password" id="npw2" name="password2" minlength="<?= MIN_PASSWORD ?>" autocomplete="new-password" required></div>
        <div><button class="btn btn-ghost">Passwort ändern</button></div>
      </form>
    </section>
  </main>
</div>

<script nonce="<?= h($NONCE) ?>">
(function () {
  var form = document.getElementById('editor');
  var state = document.getElementById('state');
  var dirty = <?= $formData !== null ? 'true' : 'false' ?>;
  var counter = 0;

  function markDirty() {
    dirty = true;
    state.textContent = 'Ungespeicherte Änderungen';
    state.classList.add('dirty');
  }
  if (dirty) markDirty();
  form.addEventListener('input', markDirty);
  form.addEventListener('change', markDirty);
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  document.getElementById('discard').addEventListener('click', function (e) {
    if (dirty && !confirm('Alle ungespeicherten Änderungen verwerfen?')) e.preventDefault(); else dirty = false;
  });
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!confirm(f.dataset.confirm)) e.preventDefault(); else dirty = false;
    });
  });

  document.addEventListener('click', function (e) {
    var b = e.target.closest('button');
    if (!b) return;
    if (b.dataset.add) {
      var tpl = document.getElementById('tpl-' + b.dataset.add);
      var key = 'n' + Date.now().toString(36) + (counter++);
      document.getElementById(b.dataset.add).insertAdjacentHTML('beforeend', tpl.innerHTML.split('__i__').join(key));
      var rows = document.getElementById(b.dataset.add).children;
      var input = rows[rows.length - 1].querySelector('input[type=text]');
      if (input) input.focus();
      markDirty();
      return;
    }
    var row = b.closest('[data-row]');
    if (!row) return;
    if (b.hasAttribute('data-remove')) { row.remove(); markDirty(); }
    else if (b.hasAttribute('data-up') && row.previousElementSibling) { row.parentNode.insertBefore(row, row.previousElementSibling); markDirty(); }
    else if (b.hasAttribute('data-down') && row.nextElementSibling) { row.parentNode.insertBefore(row.nextElementSibling, row); markDirty(); }
  });

  function setPreview(id, src) {
    var img = document.querySelector('[data-preview="' + id + '"]');
    img.src = src || '';
    img.parentNode.classList.toggle('empty', !src);
  }
  document.querySelectorAll('[data-img-select]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var up = document.querySelector('[data-upload="' + sel.id + '"]');
      up.value = '';
      up.parentNode.classList.remove('chosen');
      up.nextElementSibling.textContent = 'Neues Bild hochladen …';
      var v = sel.value;
      setPreview(sel.id, /^https?:/i.test(v) ? v : v.replace(/^\/+/, ''));
    });
  });
  document.querySelectorAll('[data-upload]').forEach(function (up) {
    up.addEventListener('change', function () {
      var file = up.files[0];
      if (!file) return;
      up.parentNode.classList.add('chosen');
      up.nextElementSibling.textContent = '📎 ' + file.name;
      setPreview(up.dataset.upload, URL.createObjectURL(file));
    });
  });
})();
</script>
<?php endif; ?>
</body>
</html>
