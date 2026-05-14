# Buttercup Perfumery

> Three-tier e-commerce system for a fictional natural-perfume brand —
> Vue 3 storefront, Laravel 12 REST API, WPF 10 desktop admin. The
> backend stack runs entirely in Docker; the WPF client is a
> self-contained single-file `.exe` distributed alongside.

![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![Vue 3](https://img.shields.io/badge/Vue-3-4FC08D?logo=vuedotjs&logoColor=white)
![.NET 10](https://img.shields.io/badge/.NET-10-512BD4?logo=dotnet&logoColor=white)
![Docker Compose](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)

## Mit tartalmaz

| Mappa | Komponens | Technológia |
|-------|-----------|-------------|
| `api/`      | Háttérrendszer (REST API) | PHP 8.3 / Laravel 12 |
| `frontend/` | Webshop (kliens) | Vue.js 3 / Vite |
| `wpf/`      | Adminisztrációs asztali alkalmazás | C# / .NET 10 / WPF |
| `docker/`   | Reverse-proxy konfiguráció | nginx |
| `scripts/`  | Üzemeltetési scriptek (backup, stb.) | bash |

A teljes rendszer Docker Compose segítségével futtatható. A WPF alkalmazás
külön, a saját Windows gépen indul, és a Dockerben futó API-hoz csatlakozik.

## Funkciók egyben

**Webshop (Vue.js):**
- Termékkatalógus, szűrés, kosár (készlet-ellenőrzéssel)
- Regisztráció, bejelentkezés, jelszó-visszaállítás e-mailben
- Felhasználói fiók: rendelések, kívánságlista, címek, mentett kártyák
- Rendelés-folyamat, lemondás függő státuszú rendelésekre
- Hírlevél-feliratkozás
- Toast értesítések, oldal-szintű e-mail megerősítési banner
- Email-átirányítások, böngésző-tabok közötti kijelentkezés szinkronja

**Admin (WPF):**
- Dashboard valós idejű statisztikákkal
- Termékek, kategóriák, rendelések, kuponok CRUD
- Analitika nézet napi/órás bontásban, eszköz-statisztikákkal
- Kupon broadcast e-mail az ügyfeleknek (opt-in)
- Bejelentkezési token DPAPI titkosítással helyben
- Audit napló minden adminisztrációs eseményhez

**Backend (Laravel):**
- Sanctum bearer-token autentikáció
- Tranzakcionális rendelési folyamat, készlet visszaállítás lemondáskor
- Háttérben futó queue worker az e-mail küldéshez
- Resend SMTP integráció (branded mailable-k: verify, reset, welcome,
  order-placed, status-changed, coupon)
- Bejelentkezési kísérlet-számláló, fiók-zárolás 5 sikertelen után
- Login attempt-rate limit per email
- `/api/health` health check (DB + Redis)
- `/api/sitemap.xml` dinamikus sitemap
- Request-ID middleware nyomon követhető naplózáshoz
- `php artisan check:production` pre-flight ellenőrzés

## Architektúra

```
                       ┌─────────────────────────┐
                       │  Traefik / nginx proxy  │  :80 / :443
                       └────────┬────────────────┘
                                │
                  ┌─────────────┴─────────────┐
                  │                           │
                  ▼                           ▼
           ┌─────────────┐           ┌──────────────────┐
           │  web (nginx)│           │ nginx-api (nginx)│
           │  Vue dist   │           │  reverse-proxy   │
           └─────────────┘           └──────┬───────────┘
                                            │ FastCGI :9000
                                            ▼
                                     ┌──────────────┐
                                     │ api (php-fpm)│ Laravel 12
                                     └──┬────────┬──┘
                                        │        │
                              ┌─────────┘        └──────────┐
                              ▼                             ▼
                       ┌──────────────┐              ┌──────────┐
                       │ mysql:8.0    │              │ redis:7  │
                       │ data volume  │              │ cache +  │
                       └──────────────┘              │ queue    │
                                                     └──────────┘
                                                          ▲
                                              ┌───────────┴────────┐
                                              │ queue worker       │
                                              │ (php artisan       │
                                              │  queue:work)       │
                                              └────────────────────┘

        ┌──────────────────────┐
        │ WPF admin (Windows)  │  ──►  api.<host>/api (Sanctum)
        │ self-contained .exe  │
        └──────────────────────┘
```

A részletes ER-diagram a `dokumentacio/adatbazis-modell.md` fájlban
található; PNG formátumban a `dokumentacio/adatbazis-modell.png`-ben.

---

## 1. Előfeltételek

A kiértékelő gépen a következőkre van szükség:

| Eszköz | Verzió | Letöltés |
|--------|--------|----------|
| **Docker Desktop**     | 4.30+ | https://www.docker.com/products/docker-desktop/ |
| **.NET 10 SDK** *(a WPF lefordításához)* | 10.x | https://dotnet.microsoft.com/download |
| **Git** *(opcionális)* | bármilyen | https://git-scm.com/ |

A Docker Compose plugin a Docker Desktoppal együtt települ.

---

## 2. A rendszer indítása

### 2.1. Hosts fájl beállítása

A reverse proxy a `Host` fejléc alapján irányítja a kéréseket a webshopra
és az API-ra. Ehhez két helyi domain név kell. Adminisztrátorként nyisd
meg a `C:\Windows\System32\drivers\etc\hosts` fájlt és add hozzá:

```
127.0.0.1   buttercupperfumery.local
127.0.0.1   api.buttercupperfumery.local
```

### 2.2. Környezeti változók

Másold át az `.env.example` fájlt `.env` néven a projekt gyökerébe
(ahol a `docker-compose.yml` is van), és töltsd ki a kötelező mezőket:

```bash
cp .env.example .env
```

Generálj egy Laravel APP_KEY-t — a többi szolgáltatás indítása előtt:

```bash
docker compose run --rm api php artisan key:generate --show
```

Másold a kapott `base64:...` értéket az `.env` fájl `APP_KEY=` sorába.

A többi mezőt (MySQL jelszavak, tracking kulcs) tetszőleges, de erős
értékekkel töltsd ki. Példa:

```
APP_KEY=base64:GENERATED_VALUE_HERE
MYSQL_ROOT_PASSWORD=valami-eros-jelszo
MYSQL_DATABASE=parfum
MYSQL_USER=parfum
MYSQL_PASSWORD=valami-masik-eros-jelszo
TRACKING_KEY=$(openssl rand -base64 32)
```

### 2.3. A teljes stack indítása

```bash
docker compose up -d --build
```

Az első indítás 5–10 percet vehet igénybe (a Docker imagek építése).
A folyamat után 8 konténer fut:

| Konténer | Szerep |
|----------|--------|
| `buttercup-proxy`     | Belépési pont (port 80) |
| `buttercup-web`       | Vue.js statikus site nginx-en |
| `buttercup-nginx-api` | nginx a php-fpm előtt |
| `buttercup-api`       | Laravel php-fpm |
| `buttercup-queue`     | Háttérben futó queue worker (e-mail küldés) |
| `buttercup-scheduler` | Időzített feladatok |
| `buttercup-mysql`     | MySQL 8 adatbázis |
| `buttercup-redis`     | Redis cache + queue + session |

A státuszt ellenőrizheted:

```bash
docker compose ps
```

### 2.4. Adatbázis feltöltése mintaadatokkal

A migrációk automatikusan lefutnak a konténer indulásakor. A teszteléshez
szükséges mintaadatokat a következő paranccsal töltheted be:

```bash
docker compose exec api php artisan db:seed --force
```

Ezzel létrejönnek a kategóriák, termékek, néhány teszt vásárló és
rendelés, kuponok, és az **admin felhasználó**:

| Felhasználó | E-mail | Jelszó |
|-------------|--------|--------|
| Admin       | `admin@parfumeria.hu` | `Admin1234!` |
| Vásárló 1   | `anna.kovacs@example.hu` | `password` |
| Vásárló 2   | `peter.nagy@example.hu` | `password` |
| Vásárló 3   | `bence.szabo@example.hu` | `password` |
| Vásárló 4   | `eszter.toth@example.hu` | `password` |

### 2.5. Tesztelés

A webshop a következő címen érhető el:

> **http://buttercupperfumery.local**

A **stop** és **újraindítás** parancsai:

```bash
docker compose stop      # leállítás (adatok megmaradnak)
docker compose start     # újraindítás
docker compose down      # leállítás + konténerek törlése
docker compose down -v   # mindent töröl (adatbázis is!)
```

---

## 3. WPF admin alkalmazás

A WPF alkalmazás Windows asztali kliens, amely az API-hoz csatlakozik.

### 3.1. Fordítás

A WPF projekt a `wpf/` mappában található. Visual Studio 2022 vagy a
.NET CLI segítségével fordítható:

```bash
cd wpf/ParfumAdmin_WPF
dotnet publish -c Release -p:PublishProfile=FolderProfile
```

Ez egyetlen, önállóan futtatható `.exe` fájlt készít a
`bin/Release/net10.0-windows/win-x64/publish/` mappában.

### 3.2. Konfiguráció

Az alkalmazás melletti `appsettings.json` fájlban az API címe
beállítható. Alapértelmezetten lokális Dockerre mutat:

```json
{
  "Api": {
    "BaseUrl": "http://api.buttercupperfumery.local/api"
  }
}
```

### 3.3. Bejelentkezés

A futtatás után az **admin** kezdőfelhasználóval lehet bejelentkezni:

- E-mail: `admin@parfumeria.hu`
- Jelszó: `Admin1234!`

Sikeres bejelentkezés után a token a Windows DPAPI-jával titkosítva
mentődik a `%LOCALAPPDATA%\ParfumAdmin\` mappába, így az alkalmazás
újraindítása után nem kell újra bejelentkezni.

---

## 4. Tesztek

### 4.1. Backend (PHPUnit)

```bash
docker compose exec api php artisan test
```

A tesztek a `tests/` mappában találhatók, a fontosabb végpontokat
(autentikáció, termékek, rendelések, kívánságlista) lefedik.

### 4.2. Frontend (Vue / Vite build ellenőrzés)

```bash
docker compose exec web npm run build
```

A frontend `npm run build` parancsa az image építésekor lefut, így ha
sikeresen indult a `web` konténer, akkor a build is sikeres.

### 4.3. WPF (kézi tesztelés)

A WPF alkalmazás kézi tesztelési eljárása a
`dokumentacio/dokumentacio.docx` *Tesztelési dokumentáció* fejezetében
található.

---

## 5. Hibaelhárítás

### „Cannot connect to Docker daemon"
Indítsd el a Docker Desktopot, várd meg, amíg a tálcaikon zöld lesz.

### „Address already in use" — port 80
Egy másik program (Apache, IIS) használja a 80-as portot. Állítsd le
azt, vagy módosítsd a `docker-compose.yml`-ben a proxy `ports:` mezőjét.

### „buttercupperfumery.local not found"
A `hosts` fájl módosítását rendszergazdaként kell elvégezni, és a
böngésző gyorsítótárát törölni utána. Próbáld ki:

```bash
ping buttercupperfumery.local
```

A válasznak a `127.0.0.1`-re kell érkeznie.

### Adatbázis sérült/üres állapotba került
Töröld és építsd újra a teljes állapotot:

```bash
docker compose down -v
docker compose up -d --build
docker compose exec api php artisan db:seed --force
```

### A kiküldött e-mailek nem érkeznek meg
Alapértelmezetten a `MAIL_MAILER=log` mód aktív, vagyis a regisztrációs
és rendelés-visszaigazoló levelek a `storage/logs/laravel.log`-ba
kerülnek. Megtekintés:

```bash
docker compose exec api tail -200 storage/logs/laravel.log
```

Élesben SMTP szolgáltatás (Mailgun / Postmark / SendGrid) szükséges,
amelyhez az `.env` fájlban a `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`
értékeket kell beállítani.

---

## 6. Mappastruktúra

```
beadandók/
├── README.md                  Ez a fájl
├── docker-compose.yml         A teljes stack leírása
├── .env.example               Környezeti változók sablonja
├── docker/                    Reverse-proxy nginx konfiguráció
│   └── proxy/nginx.conf
├── api/                       Laravel backend
│   ├── app/                       Üzleti logika
│   ├── database/migrations/       Adatbázis-séma
│   ├── database/seeders/          Mintaadatok
│   ├── routes/api.php             API végpontok
│   └── tests/                     PHPUnit tesztek
├── frontend/                  Vue.js webshop
│   ├── src/
│   │   ├── components/                Komponensek
│   │   ├── components/account/        Felhasználói fiók nézetek
│   │   └── router/                    vue-router konfiguráció
│   └── public/
├── wpf/                       WPF admin alkalmazás
│   └── ParfumAdmin_WPF/
│       ├── Models/
│       ├── Services/
│       ├── ViewModels/
│       └── Views/
└── dokumentacio/              Dokumentáció
    ├── dokumentacio.docx          A részletes projekt-dokumentáció
    ├── adatbazis-modell.md        ER diagram (Mermaid)
    └── database-dump.sql          Adatbázis export (struktúra + minták)
```

---

## 7. Üzembe helyezés (production)

A `beadandók/` mappa egy futtatható Docker Compose stack — bármelyik
Linux gépre telepíthető, ahol Docker fut.

**Ajánlott szolgáltatók:**
- **Hetzner Cloud** — CX22 (€4.50/hó) bőven elég kapacitás
- **Resend** — tranzakcionális e-mail (verify, password reset, stb.),
  ingyenes szint: 100 mail/nap

**Telepítési lépések egy friss Ubuntu 24.04 VPS-en:**

1. **DNS:** `A` rekordok a domainre + `api.<domain>`-re a VPS IP-jére.
2. **Felhasználó + tűzfal:**
   ```bash
   adduser deploy
   usermod -aG sudo deploy
   ufw allow OpenSSH 80/tcp 443/tcp && ufw enable
   ```
3. **Docker telepítése:**
   ```bash
   curl -fsSL https://get.docker.com | sudo sh
   sudo usermod -aG docker deploy
   ```
4. **Projekt klónozása + `.env` kitöltése:**
   ```bash
   git clone <repo-url> buttercup
   cd buttercup/beadandók
   cp .env.example .env
   # nano .env  — APP_KEY, MAIL_*, MYSQL jelszók
   ```
5. **HTTPS — Caddy reverse proxy** a Docker stack ELŐTT, automatikus
   Let's Encrypt tanúsítvánnyal:
   ```caddyfile
   buttercupperfumery.hu, www.buttercupperfumery.hu {
       reverse_proxy localhost:8080
   }
   api.buttercupperfumery.hu {
       reverse_proxy localhost:8000
   }
   ```
6. **Stack indítása:**
   ```bash
   docker compose up -d --build
   docker compose exec api php artisan check:production
   ```
7. **Backup ütemezése:** `scripts/backup-db.sh` cronba (lásd a fájl
   tetején lévő használati megjegyzést).

A részletes, lépésről-lépésre útmutató a `dokumentacio/dokumentacio.docx`
*Üzembe helyezés* fejezetében található.

---

## 8. Licenc és kapcsolat

A projekt iskolai vizsgaremek, a forráskód oktatási célra szabadon
felhasználható. Bármilyen kérdéssel kapcsolatban a projekt készítője
elérhető e-mailen.
