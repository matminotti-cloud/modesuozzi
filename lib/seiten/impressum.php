<?php
/*
 * Inhalt von impressum.html (eingebunden von lib/seite.php).
 * ENTWURF: Alle mit TODO markierten Stellen müssen vom Inhaber geprüft bzw. ergänzt werden.
 */
if (!isset($R, $c)) {
    http_response_code(404);
    exit;
}
?>
    <h1>Impressum</h1>
    <!-- TODO: vom Inhaber prüfen – Entwurf ohne Gewähr, vor der Veröffentlichung vollständig prüfen (keine Rechtsberatung). -->
    <p class="todo-box">Entwurf: Die markierten Angaben müssen vom Inhaber noch ergänzt oder bestätigt werden.</p>

    <h2>Kontakt</h2>
    <address>
      <p><?= $t('besuch.firma') ?><br><?= $t('besuch.strasse') ?><br><?= $t('besuch.ort') ?><br>Schweiz</p>
<?php if ($R['tel']): ?>
      <p>Telefon <a href="tel:<?= ms_e($R['tel']) ?>"><?= $t('besuch.telefon') ?></a><?php if ($has('besuch.email')): ?><br>E-Mail <a href="mailto:<?= $t('besuch.email') ?>"><?= $t('besuch.email') ?></a><?php endif; ?></p>
<?php endif; ?>
    </address>

    <h2>Vertretungsberechtigte Person</h2>
    <!-- TODO: vom Inhaber prüfen – Vor- und Nachname der Inhaberin / des Inhabers bzw. der vertretungsberechtigten Person(en). -->
    <p><span class="todo">[Vor- und Nachname]</span></p>

    <h2>Firma und Handelsregister</h2>
    <!-- TODO: vom Inhaber prüfen – Firmenname genau wie im Handelsregister (im Google-Eintrag steht «Herrenmode V. Suozzi»), Rechtsform, UID (CHE-…), MWST-Nummer. Nicht eingetragene Angaben entfernen. -->
    <p>Firmenname gemäss Handelsregister: <span class="todo">[Firmenname]</span><br>
      Rechtsform: <span class="todo">[z. B. Einzelunternehmen]</span><br>
      Unternehmens-Identifikationsnummer (UID): <span class="todo">[CHE-…]</span><br>
      Mehrwertsteuernummer: <span class="todo">[CHE-… MWST]</span></p>

    <h2>Haftungsausschluss</h2>
    <!-- TODO: vom Inhaber prüfen – Standardformulierung, bei Bedarf rechtlich prüfen lassen. -->
    <p>Wir prüfen die Inhalte dieser Website sorgfältig. Für die Richtigkeit, Vollständigkeit und Aktualität der Angaben, insbesondere zu Sortiment, Marken und Öffnungszeiten, können wir dennoch keine Gewähr übernehmen. Massgebend sind die Auskünfte im Laden.</p>

    <h2>Links auf andere Websites</h2>
    <p>Diese Website verweist auf Websites der Marken, die wir führen, sowie auf Google Maps. Für deren Inhalte sind ausschliesslich die jeweiligen Betreiber verantwortlich.</p>

    <h2>Urheberrechte</h2>
    <!-- TODO: vom Inhaber prüfen – Wer hat die Fotos gemacht? Bildnachweis ergänzen; Rechte an Logos und Markennamen bestätigen. -->
    <p>Texte, Fotos und Logo auf dieser Website gehören <?= $t('besuch.firma') ?> oder den jeweiligen Rechteinhabern. Markennamen gehören den jeweiligen Herstellern.</p>
    <p>Bildnachweis: <span class="todo">[Fotografin / Fotograf]</span></p>
