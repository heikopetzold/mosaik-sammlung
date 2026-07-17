Prompt
Mache mir einen Plan für eine Webseite. Diese soll meine Mosaiksammlung abbilden.

Environment:
Ich möchte mit lando starten, bauen und stoppen können. 
Es soll über einen docker container lauffähig sein. 
Weiterhin möchte ich auch composer nutzen können. 
Die Architektur soll php sein, mit Facaden, Klassen, Enums und Interfaces. 
Die Datenhaltung soll über eine DB erfolgen. 
Lege mir für alle relevanten die Credentials (z.B.: DB, Benutzername, Passwort) eine .env an und stelle sicher, dass diese ausgelesen wird.
Dabei gibt es zwei Hauptserien

Frontend:
Angezeigt wird der Titel, Erscheinungsjahr, Serie, Typ, Nummer, Erscheinungsmonat und bild. Das Bild soll als Gesamtbild gerrendert sein. Ich möchte Filtern nach Hauptserie, Zustand und Jahr.
Beim Klick auf das Bild soll u.a. auch das Bild, das Zustandsbild und die Beschreibung angezeigt werden

Adminseite:
Es soll eine Adminseite geben, in die ich diese Werte eintragen kann.
Die Adminseite soll passwortgeschützt sein.
Ich möchte einen Datensatz anlegen, updaten und löschen können. Ich möchte auch einen Filter nach den Hauptserien haben
Das Formular soll folgende Felder enthalten:
-Titel,
-Typ (Heft, Buch). Default ist Heft.
-Hauptserie (Abrafaxe, Digedags). Default ist Digedags.
-Nummer
-Verfügbarkeit (Vorhanden, Fehlt). Default ist Vorhanden.
-Zustand (Sehr gut, Fehlerhaft, Genäht). Default ist Sehr gut
-Erscheinungsjahr
-Erscheinungsmonat
-Bildupload
-Bildupload aktueller Zustand
-Beschreibung
Die Tatenbankfelder sollen dementsprechend id, title, type,series, issue_number, availability, item_condition, release_year, release_month, description, image_path, image_path_current_condition.
Relevante Felder sollen auch über einen Index verfügen.
Die Admincredentials sollen sein:
Benutzer: admin
Pwd: admin123

#############################################


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
