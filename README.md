# modesuozzi.ch

Sito statico one-page per Mode Suozzi, Uster. I contenuti si modificano dalla console admin Pages CMS, senza toccare il codice.

## Struttura

| Percorso | Cosa contiene |
| --- | --- |
| `index.html` | Layout, stile e logica (orari "aperto ora", stagione automatica). Legge i testi da `content/site.json`. |
| `content/site.json` | **Tutti i contenuti modificabili**: testi, marchi, assortimento, orari, contatti, percorsi delle foto. |
| `images/` | Logo e foto. Gli upload dalla console finiscono qui. |
| `.pages.yml` | Configurazione della console admin (campi, etichette in tedesco per il negozio). |

## Pubblicazione su GitHub Pages

1. Crea un repository e carica **tutto il contenuto della cartella**, compreso `.pages.yml` (è un file nascosto: in macOS premi Cmd+Shift+. per vederlo).
2. Vai su *Settings → Pages → Deploy from a branch → `main` / root*.
3. Il sito sarà disponibile su `https://<utente>.github.io/<repo>/`.

## Console admin (Pages CMS)

1. Apri https://app.pagescms.org e accedi con GitHub.
2. Installa la GitHub App di Pages CMS sul repository.
3. Apri il repository: nel menu compare **"Website-Inhalte"**, con le sezioni Allgemein, Titelbereich, Sortiment, Marken, Über uns e Besuch.
4. Ogni salvataggio crea un commit; GitHub Pages ripubblica il sito in circa un minuto.

**Accesso per il negozio:** dalla console puoi invitare i titolari come *collaborator* via e-mail. Non serve che abbiano un account GitHub e possono modificare solo contenuti e foto, non la configurazione.

## Console admin su IPS.ch (`admin.php`)

Sull'hosting IPS.ch il sito si modifica da `https://modesuozzi.ch/admin.php`. Serve PHP 7.4 o superiore. Interfaccia in tedesco, con gli stessi campi della console Pages CMS.

1. Carica via FTP tutto il contenuto del repository nella cartella del sito (di solito `public_html` o `httpdocs`).
2. Verifica che PHP possa scrivere in `content/` e `images/`. Su un hosting condiviso di solito è già così; altrimenti imposta i permessi a 755 (cartelle) e 644 (file).
3. **Apri subito `admin.php`** e scegli la password (minimo 10 caratteri). Finché la password non è impostata, chiunque apra la pagina può farlo.

Cosa fa:

- salva i testi in `content/site.json`: le modifiche sono online subito, senza pubblicazione;
- carica le foto in `images/` (solo JPG, PNG o WebP, verificati lato server; massimo 8 MB o il limite PHP dell'hosting, se più basso);
- prima di ogni salvataggio crea una copia di sicurezza in `admin-data/backups/` (restano le ultime 30), ripristinabile dalla sezione *Frühere Versionen*;
- blocca l'accesso per 15 minuti dopo 5 password sbagliate e chiude la sessione dopo 2 ore di inattività.

`admin-data/` viene creata alla prima apertura, contiene password (hash), tentativi di accesso e backup ed è esclusa da git. **Password dimenticata:** cancella `admin-data/config.php` via FTP e riapri `admin.php` per sceglierne una nuova.

**Attenzione:** le modifiche fatte da `admin.php` restano sul server IPS e non tornano su GitHub. Se in seguito ricarichi i file del repository via FTP, non sovrascrivere `content/site.json` né `images/`, altrimenti perdi le modifiche del negozio.

## Note

- **Modalità di test:** "Testversion" (in *Allgemein*) è attiva e aggiunge `noindex`, quindi Google non indicizza la copia di prova. Va disattivata quando il sito va online su modesuozzi.ch.
- **Foto:** caricare foto orizzontali di almeno 1600 px di larghezza per l'immagine principale. La console non le ridimensiona: conviene evitare file oltre ~1 MB.
- **Anteprima locale:** aprendo `index.html` con doppio clic si vede una copia incorporata dei contenuti. Per vedere le modifiche reali serve un server, per esempio `python3 -m http.server` nella cartella.
- **Foto dei titolari:** è ancora caricata dal vecchio sito. Va sostituita caricandola dalla console (*Über uns → Foto*).
