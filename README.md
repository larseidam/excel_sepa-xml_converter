Excel-SEPA-Konverter
====================

Programm zur Umwandlung von Excel Daten in das SEPA-XML Format.

Anwendungsfall
--------------
  Tabelle mit Überweisungsdaten (Empfängername, IBAN, BIC, Betrag usw.)
  soll in ein Bankverwaltungsprogramm (z.B. Quicken) geladen werden. Um
  dies zu ermöglicheine ist ein Austauschformat zu benutzten, in diesem
  Fall SEPA-XML. Damit können sowohl Lastschriften als auch Überweisungen
  ausgetauscht werden. Da nicht immer alle Zeilen einer Excel-Tabelle
  in eine xml Datei portiert werden sollen, werden so genannten
  Überweisungsmappen-Bezeichner (z.B.: UEM-2015-05-02) verwendet. Dadurch
  kann jede Zeile mit einem Bezeichner versehen werden. Im Programm wird
  dann später ein Überweisungsmappen-Bezeichner ausgewählt für den eine
  SEPA-XML Datei erstellt werden soll.

Einschränkung
--------------
  Quicken importiert Überweisungen nur für ein Auftragskonto erlaubt,
  sind alle Überweisungen im einem Auftragskonto (in der Konfiguration
  Importkonto genannt)


Anforderungen
-------------
  PHP Konsolen Interpreter (Version >= 5.3.2)


Installation
------------

####Windows
* download PHP Binary from [Windows PHP Website](http://windows.php.net/download/)  
  **Direktlinks:**
    * [PHP Binary 32-bit](http://windows.php.net/downloads/releases/php-5.6.9-nts-Win32-VC11-x86.zip)
    * [PHP Binary 64-bit](http://windows.php.net/downloads/releases/php-5.6.9-nts-Win32-VC11-x64.zip)
* runtergeladene Zip-Datei in den Ordner `php-binary` im Projektordner entpacken
* ...

####Linux
* Ausführen des Installations-Scripts (eventuell vorher ausführbar machen mit `chmod +x install.sh`)  
  `./install.sh`


Benutzung
------------
  Zuerst kann der Benutzer eine Liste aller in der Excel-Tabelle
  enthaltenen Überweisungsmappen-Bezeichner anzeigen lassen. Folgender
  Befehl erzeugt die Liste:
  
  `php main.php --list`

  mögliche Ausgabe:
  
    UEM-2015-05-02
    UEM-2015-04-02
    UEM-2014-02-02

  Nun kann der Benutzer einen Bezeichner auswählen und das Programm erneut
  starten, um für diesen Bezeichner alle Überweiungen in eine SEPA-XML Datei
  zu transformieren. Folgender Befehl erzeugt eine SEPA-XML Datei für den
  Bezeichner UEM-2015-05-02:
  
  `php main.php --map=UEM-2015-05-02`

  mögliche Ausgabe:
  
    Zeile 12: IBYLADEM1001 ist kein gültiger BIC und wird deswegen nicht übernommen!
    Anzahl an Zeilen in der Excel-Tabelle: 10
    Anzahl an Überweisungen: 10
    Gesamtsumme: 1377.45

  Danach ist eine UEM-2015-05-02.xml erzeugt worden und auf dem Bildschirm
  erscheinen Information, zu aufgetretenen Problemen und verarbeiteten Überweisungen.

  Weitere Programm-Parameter können mit folgendem Befehl angezeigt werden;
  
  `php main.php --help`


Benutzeroberfläche
------------------

####Windows
  Für eine bessere Benutzterfreundlichkeit existiert ein Powershell-Skript, welches eine minmale Benutzeroberfläche
  bereitstellt und die Bedienung etwas erleichtert. Voraussetzung ist allerdings das man die rechte besitzt
  Powershell-Skripte auszuführen.  
  Zum Start des Skripts folgenden Befehl in die Konsole eingeben:  
  `powershell <projektpfad>\run.ps1`
  
####Linux
  Für eine bessere Benutzterfreundlichkeit existiert ein Bash-Skript, welches eine minimale Benutzerführung bereitstellt
  und die Bedienung etwas erleichtert.
  Zum Start des Skripts folgenden Befehl in die Konsole eingeben (eventuell vorher ausführbar machen mit `chmod +x run.sh`):  
  `run.sh`


Konfigurationsmöglichkeiten (config.ini)
---------------------------
###Allgemein
  * Speicherort der Excel-Tabelle
  * Zeilennummer mit den Spaltenbezeichnungen
  * Spaltenname in der die Überweisungsmappen-Bezeichner stehen
  * Speicherort der xml Dateien
  * Speicherort der log Dateien

###Datenprüfung (Regulärer Ausdrücke für die syntaktische Überprüfung von)
  * Überweisungsmappen-Bezeichner
  * Betrag
  * IBAN
  * BIC

###Sender-Konto
  * IBAN des Importkontos
  * BIC des Importkontos
  * Name des Importkontos
  * Firmenname des Importkontos

###Spaltennamen in Excel-Tabelle
  * Spaltenname für die IBANs
  * Spaltenname für die BICs
  * Spaltenname für die Empfängernamen
  * Spaltenname für den Betrag
  * Spaltenname für die erste Zeile des Verwendungszwecks
  * Spaltenname für die zweite Zeile des Verwendungszwecks

###Verwendungszweck
  * Prefix für Verwendungszweck Zeile 1
  * Prefix für Verwendungszweck Zeile 2
  * Trennzeichen zwichen Verwendungszweck Zeile 1 und Zeile 2


Verwendete Bibliotheken
-------------------------
  Zum Auslesen der Daten aus der Excel-Tabelle wird die
  PHP-Klasse [simplexlsx](http://www.phpclasses.org/package/6279-PHP-Parse-and-retrieve-data-from-Excel-XLS-files.html)
  von Sergey Shuchkin verwendet.  
  Zum Erzeugen der SEPA-XML Datei wird die Bilbiothek [php-sepa-xml](https://github.com/digitick/php-sepa-xml) von Digitick verwendet.


Exit-Codes des Programms
---------------------------
  * 1 Keine MAP angegeben.
  * 2 MAP-Bezeichner nicht in Excel-Tabelle enthalten.
  * 3 XML Datei schon vorhanden.