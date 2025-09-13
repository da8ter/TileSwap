# TileSwap
Umschalten des Link-Ziels anhand einer Integer-Variable (Tile Switch). Das Modul erstellt und verwaltet automatisch einen Link unterhalb der Modulinstanz. Beim Setzen der (versteckten) Variable auf den Index (1..N) wird das Linkziel auf das entsprechende Ziel geändert. Optional kann ein Auto-Reset nach einer konfigurierbaren Zeit das Linkziel auf das ursprüngliche Ziel zurücksetzen.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Präsentation](#5-statusvariablen-und-präsentation)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Automatische Erstellung und Verwaltung eines Link-Objekts unterhalb der Instanz (Ident `TSWAP_LINK`)
* Integer-Variable `Tile Switch` (1..N) steuert das Linkziel; die Variable ist standardmäßig verborgen
* Verwendung der neuen Variablen-Präsentationen (Aufzählung/Enumeration) mit einer Option pro Listeneintrag; Farben sind transparent (-1)
* Konfigurierbare Liste von Ziel-Objekten (Dummy-Instanzen/Instanzen oder beliebige Objekte)
* Optionaler Auto-Reset (Timer), der nach Ablauf das ursprüngliche Linkziel wiederherstellt

### 2. Voraussetzungen

- IP-Symcon ab Version 8.0

### 3. Software-Installation

* Über den Module Store das 'TileSwap'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'TileSwap'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name               | Beschreibung
------------------ | ------------------
Automatischer Link | Das Modul erzeugt automatisch einen Link (`TSWAP_LINK`) unterhalb der Instanz
Ziel-Liste         | Liste der Ziel-Objekte. Die Position (1..N) entspricht dem Wert der Variable `Tile Switch`
Auto-Reset         | Aktiviert einen Timer, der nach Ablauf das Linkziel auf das ursprüngliche Ziel zurücksetzt
Rücksetzen nach (s)| Zeit in Sekunden bis zum automatischen Rücksetzen (nur wenn Auto-Reset aktiv)

### 5. Statusvariablen und Präsentation

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name         | Typ     | Beschreibung
------------- | ------- | ------------
Tile Switch  | Integer | Auswahl des Ziel-Eintrags (1..N). Löst die Umschaltung des Linkziels aus. Die Variable ist standardmäßig verborgen.

#### Präsentation

Die Variable nutzt die Darstellung „Aufzählung/Enumeration“ mit einer Option pro Listeneintrag. Die Optionen werden dynamisch aus der Ziel-Liste erzeugt (Caption = Objektname, Farbe = transparent/-1).

### 6. Visualisierung

In der Visualisierung verwendest du den automatisch erzeugten Link (`TSWAP_LINK`) unterhalb der Instanz. Die Steuer-Variable `Tile Switch` ist verborgen und zur internen/skriptgesteuerten Bedienung gedacht.

### 7. PHP-Befehlsreferenz

Umschalten per PHP:

```
IPS_RequestAction($InstanzID, 'TileSwitch', 4); // Schaltet auf 4. Eintrag und ändert das Linkziel
```

Hinweis: Direkte SetValue()-Aufrufe auf die Variable lösen keine Aktion aus. Bitte über `IPS_RequestAction` oder das WebFront schalten.