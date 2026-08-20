# Mapa parcel – okres Jičín (Viagem, interview úloha)

Webová aplikace zobrazující katastrální parcely ve vybraných katastrálních územích okresu Jičín na mapě. Po kliknutí na parcelu se zobrazí její základní údaje (číslo parcely, výměra, katastrální reference). Aplikace zůstává plynulá i při zobrazení celého pokrytého rozsahu.

Pokryté k.ú.: **Jičín**, **Robousy**, **Popovice u Jičína**, **Valdice**.

## Stack

- Backend: čisté PHP (bez frameworku), PDO, vlastní jednoduché třídy bez Composeru/autoloaderu (třídy se natahují ručně přes `require_once`).
- Databáze: MySQL/MariaDB (spatial typy, spatial indexy) – přes lokální XAMPP.
- Frontend: Leaflet + OpenStreetMap podklad, vanilla JavaScript (žádný build krok, žádný framework).
- Zdroj dat: ČÚZK INSPIRE WFS (`cp:CadastralParcel`, `cp:CadastralZoning`).

## Předpoklady

- PHP 8.1+ s rozšířeními: `pdo_mysql`, `curl`, `SimpleXML` (standardní součást XAMPP, nic se nedoinstalovává).
- MySQL/MariaDB (součást XAMPP).
- Žádné Composer balíčky ani jiné externí PHP závislosti.
- Internetové připojení – aplikace za běhu stahuje podkladovou mapu z OpenStreetMap a Leaflet z CDN; parcelní data jsou po prvotním nastavení uložená lokálně v MySQL a appka za běhu už na ČÚZK nesahá.

## Spuštění

1. Zkopíruj `config/config.example.php` do `config/config.php` (výchozí hodnoty sedí na běžnou lokální XAMPP instalaci – `root` bez hesla).
2. Naimportuj `sql/schema.sql` (phpMyAdmin → záložka SQL, nebo `mysql -u root < sql/schema.sql`).
3. Spusť `php scripts/sync_ku.php` – stáhne a uloží parcely pro všechna 4 nakonfigurovaná k.ú. Trvá řádově **jednotky minut** (přes 270 dílčích dotazů na ČÚZK WFS, viz zápisník níže proč).
4. Spusť `php scripts/fetch_ku_boundaries.php` – stáhne hranice jednotlivých k.ú. jako statický `public/data/ku_boundaries.geojson`.
5. Otevři `http://localhost/Viagem/public/index.html` (za předpokladu, že projekt je v `htdocs` a Apache/MySQL v XAMPP běží).

Appka po otevření **žádná data živě z ČÚZK nestahuje** – čte je z lokální MySQL cache naplněné kroky 3–4. Jde o vědomé rozhodnutí, zdůvodněné níže.

Pomocný diagnostický skript `scripts/test_wfs.php` (rychlý test `WfsClient` + `GmlParcelParser` na malém vzorku dat, s uložením do DB) není ke spuštění appky potřeba, nechávám ho v repu jako doklad postupu.

## Architektura ve zkratce

```
ČÚZK WFS  →  WfsClient (curl)  →  GmlParcelParser (XML → pole)  →  ParcelRepository (MySQL cache)
                                                                          │
public/index.html + app.js (Leaflet)  ←  public/api/parcels.php  ←──────┘
```

- `src/Wfs/` – komunikace s ČÚZK (`WfsClient`), parsování GML odpovědí (`GmlParcelParser` pro parcely, `GmlZoningParser` pro hranice k.ú.), dělení velkého území na menší dotazy (`BboxTiler`).
- `src/Geo/` – čistě výpočetní pomocníci nezávislí na zdroji dat: převod souřadnic (`PosListConverter`), zjednodušení geometrie (`DouglasPeucker`).
- `src/Db/` – PDO připojení (`Database`) a práce s daty (`ParcelRepository`, `KuCacheStatusRepository`).
- `scripts/` – CLI nástroje spouštěné ručně (sync dat, diagnostika).
- `public/` – webový root: `index.html` + `assets/js/app.js` (frontend), `api/parcels.php` (jediný HTTP endpoint appky).

## Zápisník

### Rozhodnutí a proč

- **Zdroj dat: ČÚZK INSPIRE WFS.** Endpoint jsem si ověřil živě (`GetCapabilities` + reálný `GetFeature` dotaz), ne jen podle dokumentace. Zvolil jsem harmonizovanou (zdarma, bez registrace) variantu místo placené neharmonizované – daň za to je chybějící druh pozemku a vlastnická data, což mi navíc vyhovuje kvůli GDPR (appka vlastníky nezobrazuje).
- **Předstažení dat** Původně jsem plánoval hybrid (cache-aside: doplnit chybějící data živě při requestu), nakonec jsem zvolil jednorázový dávkový sync (`sync_ku.php`) spouštěný ručně před prvním použitím. Rozsah je pevný a malý (4 k.ú.), takže dopředu naplněná cache dává větší smysl než řešit průběžné doplňování a riskovat zaseknutí appky při první návštěvě.
- **MySQL/MariaDB, ne PostgreSQL/PostGIS.** PostGIS je pro geodata silnější, ale vyžadoval by Docker. MySQL je součástí XAMPP bez jediného extra kroku – u téhle jednoduché appky, jsem upřednostnil nulovou instalační bariéru.
- **Vlastní Douglas-Peucker algoritmus pro zjednodušení geometrie**, protože MySQL nemá `ST_Simplify`. Ukládám rovnou obě verze (`geom_full`, `geom_simplified`), backend vybírá podle aktuálního zoomu.
- **Plynulost přes víc vrstev ochrany současně:** prostorový index, načítání jen podle výřezu mapy, minimální práh přiblížení (inspirovaný chováním `nahlizenidokn.cuzk.cz`), a popisky parcel podmíněné zoomem i hustotou dat zároveň – čistě zoomový práh sám o sobě nestačil.
- **Vizuální hranice k.ú. navíc**, nad rámec zadání – pomáhá se zorientovat u parcel blízko hranice dvou území. Stahuje se jednorázově a staticky (`fetch_ku_boundaries.php`), protože administrativní hranice se prakticky nemění.
- **Výběr k.ú.:** Jičín + bezprostředně sousední Robousy, Popovice u Jičína, Valdice – mix městské a venkovské hustoty parcel pro reálný test plynulosti. Rozšíření o další k.ú. je jen řádek v configu, žádná změna kódu.

### Co mě překvapilo

- `STARTINDEX` (stránkování WFS 2.0) u téhle služby vůbec nefunguje, přestože je to podle specifikace standardní součást – zjistil jsem to reálným testem, server to sám přiznává v `GetCapabilities` (`ImplementsResultPaging = FALSE`). Řešení: dělení území na dlaždice (`BboxTiler`) místo stránkování.
- Souřadnice v GML odpovědi jsou v pořadí lat/lon, zatímco WKT/GeoJSON čekají lon/lat.
- Klasická PHP záludnost: číselné stringové klíče pole se tiše přetypují na integer, což mi rozbilo přísné porovnání (`===`) v `array_filter()` a potichu (bez chyby) ukládalo nulové parcely.


### Co bych s větším časem řešil jinak

- Composer + PSR-4 autoloading místo ručních `require_once` (při refaktoringu jsem musel dohledávat chybějící includy).
- Adaptivní dlaždicování podle skutečné hustoty parcel, ne pevná mřížka.
- Pro otevřené území (celý okres/republika) bych spíš než cache-aside řešil naplánovanou (cron) dávkovou aktualizaci celé databáze přes noc – katastr je konečná, známá množina dat, takže dopředné pravidelné stažení dává větší smysl než řešit pomalou první návštěvu nového místa za běhu.
- Srozumitelnější chování frontendu při výpadku ČÚZK/DB nebo nenaplněné cache (teď appka jen tiše zůstane bez dat).

