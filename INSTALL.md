# Installationanleitung

## Vorraussetzungen

  * Einen Webserver mit PHP (z.B. nginx oder Apache)
  * GIT
  * Eine Datenbank. Folgende werden unterstützt: [PostgreSQL](http://www.postgresql.org), [MySQL](https://www.mysql.com)/[MariaDB](https://mariadb.org) und [SQLite](https://www.sqlite.org). Ich empfehle PostgreSQL, weil die beste Datenbank ever.

## Anleitung

Diese Anleitung geht davon aus das du SSH-Zugriff auf deinen Server hast. Wenn du Calcifer auf einem Shared-Hosting-Anbieter installieren willst, so ist dies auch möglich, aber etwas komplizierter und wird irgendwann später beschrieben.

  1. Das [Repo](https://phablab.krautspace.de/diffusion/C/calcifer.git) irgendwo hin clonen
  2. In das calcifer Verzeichnis wechseln.
  3. composer install
  4. Im Verzeichnis app/config die Datei parameters.yml.dist nach parameters.yml kopieren und anpassen.
  5. Dann die Tabellen erstellen: php app/console doctrine:schema:create
  6. Zum Schluss must du noch deinen Webserver [konfigurieren](http://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html) und dann ist calcifer auch schon erreichbar.