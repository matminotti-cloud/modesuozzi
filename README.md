# modesuozzi.ch

Sito statico one-page per Mode Suozzi, Uster. I contenuti si modificano dalla console admin Pages CMS, senza toccare il codice.

## Struttura

| Percorso | Cosa contiene |
| --- | --- |
| `content/site.json` | **Tutti i contenuti modificabili**: testi, marchi, assortimento, orari, contatti, percorsi delle foto. |
| `lib/seite.php` | Modello della pagina: layout, stile e il poco JavaScript che serve (orari "aperto ora", stagione corrente). |
| `lib/render.php` | Generatore: da `site.json` + modello crea `index.html`, `impressum.html`, `datenschutz.html`, `sitemap.xml`, `robots.txt` e `llms.txt`. |
| `lib/seiten/impressum.php`, `lib/seiten/datenschutz.php` | Testi di Impressum e Datenschutz (bozze con segnaposto `TODO: vom Inhaber prüfen`, da far verificare al proprietario). |
| `index.html`, `impressum.html`, `datenschutz.html`, `sitemap.xml`, `robots.txt`, `llms.txt` | **File generati, non modificarli a mano**: vengono riscritti a ogni salvataggio. |
| `images/` | Logo e foto. Gli upload dalla console finiscono qui. |
| `fonts/` | Font del sito (Bodoni Moda, Jost) in WOFF2, solo caratteri latini, con licenza OFL. Ospitati qui per non caricare nulla da Google. |
| `.pages.yml` | Configurazione della console admin (campi, etichette in tedesco per il negozio). |

## SEO e ricerca con l'intelligenza artificiale

I contenuti sono scritti direttamente nell'HTML, non caricati con JavaScript: così li leggono anche i crawler delle IA (ChatGPT, Claude, Perplexity), che di solito non eseguono JavaScript. Inoltre il generatore produce:

- **dati strutturati schema.org** (`ClothingStore`): indirizzo, coordinate, telefono, orari, marchi, collegamento al profilo Google Maps. Google li usa per la scheda del negozio, le IA per rispondere a domande tipo "negozio di abbigliamento uomo a Uster aperto sabato";
- **Open Graph**: anteprima con foto quando il link viene condiviso su WhatsApp, Facebook ecc.;
- **`llms.txt`**: riassunto testuale del negozio pensato per gli assistenti IA;
- **`sitemap.xml`** e **`robots.txt`**: aperti a tutti i crawler; con "Testversion" attiva bloccano invece l'indicizzazione.

Ogni volta che si salva (da `admin.php` o da Pages CMS) i file vengono rigenerati. Per rigenerarli a mano: `php lib/render.php`.

## Pubblicazione su GitHub Pages

1. Crea un repository e carica **tutto il contenuto della cartella**, compreso `.pages.yml` (è un file nascosto: in macOS premi Cmd+Shift+. per vederlo).
2. Vai su *Settings → Pages → Source: GitHub Actions*.
3. A ogni push il workflow `.github/workflows/website.yml` controlla i file PHP, rigenera la pagina, salva i file generati nel repository e pubblica il sito su `https://<utente>.github.io/<repo>/`.

## Console admin (Pages CMS)

1. Apri https://app.pagescms.org e accedi con GitHub.
2. Installa la GitHub App di Pages CMS sul repository.
3. Apri il repository: nel menu compare **"Website-Inhalte"**, con le sezioni Allgemein, Titelbereich, Sortiment, Marken, Über uns e Besuch.
4. Ogni salvataggio crea un commit; il workflow rigenera e ripubblica il sito in circa un minuto.

**Accesso per il negozio:** dalla console puoi invitare i titolari come *collaborator* via e-mail. Non serve che abbiano un account GitHub e possono modificare solo contenuti e foto, non la configurazione.

## Console admin su IPS.ch (`admin.php`)

Sull'hosting IPS.ch il sito si modifica da `https://modesuozzi.ch/admin.php`. Serve PHP 7.4 o superiore. Interfaccia in tedesco, con gli stessi campi della console Pages CMS.

1. Carica via FTP tutto il contenuto del repository nella cartella del sito (di solito `public_html` o `httpdocs`).
2. Verifica che PHP possa scrivere nella cartella principale (per `index.html`, `impressum.html`, `datenschutz.html`, `sitemap.xml`, `robots.txt`, `llms.txt`), in `content/` e in `images/`. Su un hosting condiviso di solito è già così; altrimenti imposta i permessi a 755 (cartelle) e 644 (file).
3. Apri `admin.php`: alla prima apertura crea un codice di configurazione nel file `admin-data/setup-code.php`. Scarica il file via FTP, copia il codice (seconda riga), inseriscilo nella pagina e scegli la password (minimo 10 caratteri). Senza accesso FTP nessuno può impostare la password; dopo l'uso il file viene cancellato.

Cosa fa:

- salva i testi in `content/site.json` e rigenera subito `index.html` e gli altri file: le modifiche sono online immediatamente. Se carichi via FTP file nuovi, apri `admin.php` una volta e la pagina si aggiorna da sola;
- carica le foto in `images/` (solo JPG, PNG o WebP, verificati lato server; massimo 8 MB o il limite PHP dell'hosting, se più basso);
- prima di ogni salvataggio crea una copia di sicurezza in `admin-data/backups/` (restano le ultime 30), ripristinabile dalla sezione *Frühere Versionen*;
- blocca l'accesso per 15 minuti dopo 5 password sbagliate dallo stesso indirizzo, e per tutti i dispositivi nuovi dopo 30 errori in totale. I dispositivi che hanno già fatto accesso negli ultimi 90 giorni non subiscono il blocco globale, così un attacco non può chiudere fuori il negozio. Chiude la sessione dopo 2 ore di inattività. In caso di emergenza, cancella `admin-data/attempts.php` via FTP per togliere ogni blocco.

`admin-data/` viene creata alla prima apertura, contiene password (hash), tentativi di accesso e backup ed è esclusa da git. **Password dimenticata:** cancella `admin-data/config.php` via FTP e riapri `admin.php` per sceglierne una nuova.

**Attenzione:** le modifiche fatte da `admin.php` restano sul server IPS e non tornano su GitHub. Se in seguito ricarichi i file del repository via FTP, non sovrascrivere `content/site.json` né `images/`, altrimenti perdi le modifiche del negozio.

## Note

- **Modalità di test:** "Testversion" (in *Allgemein*) è attiva: aggiunge `noindex` e blocca i crawler in `robots.txt`, quindi né Google né le IA indicizzano la copia di prova. Va disattivata quando il sito va online su modesuozzi.ch, poi conviene registrare il sito in Google Search Console e Bing Webmaster Tools (Bing alimenta anche la ricerca di ChatGPT) e inviare `sitemap.xml`.
- **Foto:** caricare foto orizzontali di almeno 1600 px di larghezza per l'immagine principale. La console non le ridimensiona: conviene evitare file oltre ~1 MB.
- **Anteprima locale:** basta aprire `index.html` con doppio clic. Dopo aver modificato `content/site.json` a mano, rigenera con `php lib/render.php`.
- **Google Business Profile:** nome, indirizzo e telefono devono essere identici sul sito e sul profilo Google. È il segnale più importante per la ricerca locale e per le risposte delle IA.
- **Foto dei titolari:** è ancora caricata dal vecchio sito. Va sostituita caricandola dalla console (*Über uns → Foto*).
