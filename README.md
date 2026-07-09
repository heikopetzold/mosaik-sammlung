What this is
A small, self-hosted PHP web application to manage a mosaic collection (öffentliches Frontend + Admin-Interface). Ziel ist, Mosaik‑Datensätze zu speichern/anzeigen und Bild-Uploads im Verzeichnis public/uploads zu verwalten.

Stack
Language(s): PHP (100%)
Framework / runtime: Plain PHP (keine offensichtliche Full‑Stack‑Framework‑Abhängigkeit; klassische PHP‑Webapp)
Notable libraries: Composer (composer.json vorhanden) — Abhängigkeiten werden über Composer verwaltet; .lando.yml zeigt eine optionale Lando‑Entwicklungsumgebung; benutzt vermutlich PDO oder native PHP‑DB‑Zugriffe (DB‑Klassen im src/ zeigen DB‑Code).
How it's organized
Code
.gitignore              - git ignore rules
.lando.yml              - Lando devcontainer / local dev config (optional)
composer.json           - Composer manifest (PHP dependencies)
composer.lock
config.php              - zentrale Konfiguration (Datenbank, Pfade) — editieren nötig
database.sql            - Schema / Initialdaten für die Datenbank
public/                 - Web‑root
  index.php             - öffentliche Seite / Frontend entrypoint
  admin.php             - Admin‑Interface
  login.php             - Login
  logout.php
  uploads/              - hochgeladene Dateien (Bilder)
src/                    - Anwendungsquellcode
  Classes/
    Database.php        - DB‑Verbindung / Helper
    MosaicRepository.php- Datenzugriff für Mosaik‑Entitäten
  Interfaces/
    MosaicRepositoryInterface.php - Interface für das Repository
  Facades/
    DB.php              - Fassade für DB‑Zugriff
  Enums/                - kleine Enums (Availability, Condition, Month, PublicationType, Series)
How it fits together: public/index.php und public/admin.php sind die HTTP‑Entrypoints. src/Classes/Database.php stellt die DB‑Verbindung bereit; MosaicRepository implementiert das Lesen/Schreiben der Mosaik‑Daten (Interface in src/Interfaces). Uploads werden unter public/uploads gespeichert; database.sql enthält das Schema/Seed. Composer verwaltet Abhängigkeiten, .lando.yml bietet eine Möglichkeit, lokal mit Lando zu starten.

How to run it
Kurzanleitung von frischem Clone bis zur lauffähigen Instanz (Linux/macOS). Passe DB‑Zugangsdaten an und sorge dafür, dass PHP, Composer und MySQL/MariaDB installiert sind.

Repo klonen
Code
git clone https://github.com/heikopetzold/mosaik-sammlung.git
cd mosaik-sammlung
Abhängigkeiten installieren
Wenn Composer installiert ist:
Code
composer install
(Wenn composer.json keine externen Pakete enthält, passiert nichts — Composer ist trotzdem das Standardwerkzeug.)

Datenbank anlegen und Schema importieren
Beispiel (ersetze dbuser, dbpass):
Code
mysql -u root -p -e "CREATE DATABASE mosaik_sammlung CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u dbuser -p mosaik_sammlung < database.sql
Alternativ in einem GUI (phpMyAdmin, TablePlus, Sequel Pro) die Datei database.sql importieren.

Konfiguration
Öffne die Datei config.php im Projektroot und trage die DB‑Zugangsdaten sowie ggf. Basis‑URL/Upload‑Pfad ein. Falls die Datei bereits Werte enthält, passe diese an. Falls du Umgebungsvariablen bevorzugst, kannst du config.php so anpassen, dass getenv(...) gelesen wird.
Beispiel für eine einfache config.php (falls du eine Beispiel‑Datei anlegen willst):

config.sample.php
<?php
return [
  'db' => [
    'host' => '127.0.0.1',
    'user' => 'dbuser',
    'pass' => 'dbpass',
Upload‑Ordner berechtigungen setzen
Code
mkdir -p public/uploads
chmod 775 public/uploads
# optional: chown -R www-data:www-data public/uploads   # je nach Webserver
Anwendung starten
Einfach mit PHP‑Built‑in‑Server (für Entwicklung):
Code
php -S localhost:8000 -t public
Öffne dann http://localhost:8000 im Browser. Admin‑Interface: http://localhost:8000/admin.php (Login über public/login.php).

Oder lokal mit Lando (wenn Lando installiert und .lando.yml konfiguriert ist):
Code
lando start
# evtl. lando info oder lando url zeigt die lokale URL
Wartung / Weiteres
Wenn Anmeldedaten fehlen: prüfe database.sql auf Default‑User/Seed‑Daten oder lege in der DB manuell einen Admin‑User an.
Logs / Fehler: PHP‑Error‑Log oder Anzeige aktivieren (für Entwicklung).
Wenn Composer‑Pakete hinzugefügt werden: nach composer.json‑Änderung composer install / composer update.
Try asking
Soll ich den Inhalt von composer.json prüfen und dir genau sagen, welche Abhängigkeiten installiert werden?
Möchtest du, dass ich eine beispielhafte env‑basierte config.php aus config.php extrahiere (z. B. .env‑Unterstützung) und die Start‑Anleitung dafür ergänze?
Soll ich database.sql durchsehen und die Tabellen/Initialdatensätze dokumentieren (z. B. Admin‑Login‑Daten, Tabellenstruktur, Indizes), damit du die DB leichter verifizieren kannst?
