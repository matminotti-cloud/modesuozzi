<?php
/*
 * Inhalt von datenschutz.html (eingebunden von lib/seite.php).
 * ENTWURF nach dem Schweizer Datenschutzgesetz (DSG, in Kraft seit 1. September 2023).
 * Alle mit TODO markierten Stellen müssen vom Inhaber geprüft bzw. ergänzt werden.
 */
if (!isset($R, $c)) {
    http_response_code(404);
    exit;
}
?>
    <h1>Datenschutzerklärung</h1>
    <!-- TODO: vom Inhaber prüfen – Entwurf ohne Gewähr, vor der Veröffentlichung vollständig prüfen (keine Rechtsberatung). -->
    <p class="todo-box">Entwurf: Die markierten Angaben müssen vom Inhaber noch ergänzt oder bestätigt werden.</p>
    <p class="lead">Wir nehmen den Schutz Ihrer Personendaten ernst. Hier erfahren Sie, welche Daten beim Besuch dieser Website bearbeitet werden und welche Rechte Sie haben. Massgebend ist das Schweizer Datenschutzgesetz (DSG).</p>

    <h2>Verantwortlich</h2>
    <address>
      <p><?= $t('besuch.firma') ?><br><span class="todo">[Vor- und Nachname der verantwortlichen Person]</span><br><?= $t('besuch.strasse') ?><br><?= $t('besuch.ort') ?><br>Schweiz</p>
      <p><?php if ($R['tel']): ?>Telefon <a href="tel:<?= ms_e($R['tel']) ?>"><?= $t('besuch.telefon') ?></a><br><?php endif; ?><?php if ($has('besuch.email')): ?>E-Mail <a href="mailto:<?= $t('besuch.email') ?>"><?= $t('besuch.email') ?></a><?php endif; ?></p>
    </address>
    <!-- TODO: vom Inhaber prüfen – Name der verantwortlichen Person ergänzen. -->

    <h2>Besuch dieser Website</h2>
    <!-- TODO: vom Inhaber prüfen – Name und Sitz des Hosting-Anbieters, Speicherdauer der Server-Protokolle beim Anbieter erfragen. -->
    <p>Beim Aufruf der Website speichert der Server unseres Hosting-Anbieters (<span class="todo">[Name und Sitz des Hosting-Anbieters]</span>) automatisch technische Angaben in Protokolldateien: IP-Adresse, Datum und Uhrzeit, aufgerufene Seite, Browser und Betriebssystem. Diese Daten brauchen wir für den sicheren Betrieb der Website. Sie werden nach <span class="todo">[Speicherdauer]</span> gelöscht.</p>
    <p>Die Website selbst setzt keine Cookies und verwendet keine Analyse- oder Werbedienste. Die Schriften sind auf unserem eigenen Server gespeichert; dafür wird keine Verbindung zu Dritten aufgebaut.</p>

    <h2>Kontakt per E-Mail oder Telefon</h2>
    <p>Wenn Sie uns schreiben oder anrufen, verwenden wir Ihre Angaben nur, um Ihre Anfrage zu beantworten. Wir geben sie nicht an Dritte weiter.</p>

    <h2>Eingebundene Dienste von Google</h2>
    <!-- TODO: vom Inhaber prüfen – Angaben zu Google (Anbieter, Datenübermittlung in die USA) vor der Veröffentlichung bestätigen. -->
    <p>Auf dieser Website sind Dienste von Google eingebunden. Anbieter ist Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland. Daten können auch an die Google LLC in den USA übermittelt werden.</p>
    <ul>
      <li><strong>Google Maps:</strong> Die Karte im Abschnitt «Besuch» wird direkt von Google geladen.</li>
<?php if ($R['tour']): ?>
      <li><strong>Google Street View:</strong> Der virtuelle Rundgang durch den Laden wird von Google Maps geladen.</li>
<?php endif; ?>
    </ul>
    <p>Dabei erhält Google mindestens Ihre IP-Adresse sowie Angaben zu Browser und aufgerufener Seite. Google Maps kann zudem Cookies setzen. Wie Google diese Daten bearbeitet, steht in der <a href="https://policies.google.com/privacy?hl=de" target="_blank" rel="noopener noreferrer">Datenschutzerklärung von Google<span class="sr-only"> (öffnet in neuem Tab)</span></a>.</p>

    <h2>Links auf andere Websites</h2>
    <p>Links zu den Websites unserer Marken öffnen sich in einem neuen Tab. Für die Datenbearbeitung auf diesen Websites sind die jeweiligen Betreiber verantwortlich.</p>

    <h2>Ihre Rechte</h2>
    <p>Sie können jederzeit Auskunft über Ihre bei uns gespeicherten Personendaten verlangen und deren Berichtigung oder Löschung fordern. Schreiben Sie uns dazu an die oben genannte Adresse oder per E-Mail. Sie haben zudem das Recht, sich beim Eidgenössischen Datenschutz- und Öffentlichkeitsbeauftragten (EDÖB) zu beschweren.</p>

    <h2>Änderungen</h2>
    <!-- TODO: vom Inhaber prüfen – Datum beim Veröffentlichen eintragen. -->
    <p>Wir passen diese Datenschutzerklärung an, wenn sich die Website oder die rechtlichen Vorgaben ändern. Stand: <span class="todo">[Datum]</span></p>
