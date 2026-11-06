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

## Note

- **Modalità di test:** "Testversion" (in *Allgemein*) è attiva e aggiunge `noindex`, quindi Google non indicizza la copia di prova. Va disattivata quando il sito va online su modesuozzi.ch.
- **Foto:** caricare foto orizzontali di almeno 1600 px di larghezza per l'immagine principale. La console non le ridimensiona: conviene evitare file oltre ~1 MB.
- **Anteprima locale:** aprendo `index.html` con doppio clic si vede una copia incorporata dei contenuti. Per vedere le modifiche reali serve un server, per esempio `python3 -m http.server` nella cartella.
- **Foto dei titolari:** è ancora caricata dal vecchio sito. Va sostituita caricandola dalla console (*Über uns → Foto*).
