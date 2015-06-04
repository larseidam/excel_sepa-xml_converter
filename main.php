<?php
/**
 * Programm zur Umwandlung von Excel Daten in das SEPA-XML Format.
 *
 * Anwendungsfall:
 *   Tabelle mit Überweisungsdaten (Empfängername, IBAN, BIC, Betrag usw.)
 *   soll in ein Bankverwaltungsprogramm (z.B. Quicken) geladen werden. Um
 *   dies zu ermöglicheine ist ein Austauschformat zu benutzten, in diesem
 *   Fall SEPA-XML. Damit können sowohl Lastschriften als auch Überweisungen
 *   ausgetauscht werden. Da nicht immer alle Zeilen einer Excel-Tabelle
 *   in eine xml Datei portiert werden sollen, werden so genannten Überweisungs-
 *   mappen-Bezeichner (z.B.: UEM-2015-05-02) verwendet. Dadurch kann jede
 *   Zeile mit einem Bezeichner versehen werden. Im Programm wird dann später
 *   ein Überweisungsmappen-Bezeichner ausgewählt für den eine SEPA-XML Datei
 *   erstellt werden soll.
 *
 *
 * Einschränkung:
 *   Quicken importiert Überweisungen nur für ein Auftragskonto erlaubt,
 *   sind alle Überweisungen im einem Auftragskonto (in der Konfiguration
 *   Importkonto genannt)
 *
 *
 * Anforderungen:
 *   PHP Konsolen Interpreter (Version >= 5.3.2)
 *
 * Benutzung:
 *   Zuerst kann der Benutzer eine Liste aller in der Excel-Tabelle
 *   enthaltenen Überweisungsmappen-Bezeichner anzeigen lassen. Folgender
 *   Befehl erzeugt die Liste:
 *     php main.php --list
 *
 *     mögliche Ausgabe:
 *       UEM-2015-05-02
 *       UEM-2015-04-02
 *       UEM-2014-02-02
 *
 *   Nun kann der Benutzer einen Bezeichner auswählen und das Programm erneut
 *   starten, um für diesen Bezeichner alle Überweiungen in eine SEPA-XML Datei
 *   zu transformieren. Folgender Befehl erzeugt eine SEPA-XML Datei für den
 *   Bezeichner UEM-2015-05-02:
 *     php main.php --map=UEM-2015-05-02
 *
 *     mögliche Ausgabe:
 *       Zeile 12: IBYLADEM1001 ist kein gültiger BIC und wird deswegen nicht übernommen!
 *       Anzahl an Zeilen in der Excel-Tabelle: 10
 *       Anzahl an Überweisungen: 10
 *       Gesamtsumme: 1377.45
 *
 *   Danach ist eine UEM-2015-05-02.xml erzeugt worden und auf dem Bildschirm
 *   erscheinen Information, zu aufgetretenen Problemen und verarbeiteten Überweisungen.
 *
 *   Weitere Programm-Parameter können mit folgendem Befehl angezeigt werden;
 *     php main.php --help
 *
 *
 * Konfigurationsmöglichkeiten (config.ini):
 *   Speicherort der Excel-Tabelle
 *   Zeilennummer mit den Spaltenbezeichnungen
 *   Spaltenname in der die Überweisungsmappen-Bezeichner stehen
 *   Speicherort der xml Dateien
 *   Speicherort der log Dateien
 *
 *   Regulärer Ausdruck für die syntaktische Überprüfung der Überweisungsmappen-Bezeichner
 *   Regulärer Ausdruck für die syntaktische Überprüfung des Betrages
 *   Regulärer Ausdruck für die syntaktische Überprüfung der IBAN
 *   Regulärer Ausdruck für die syntaktische Überprüfung der BIC
 *
 *   IBAN des Importkontos (Sender-Konto)
 *   BIC des Importkontos (Sender-Konto)
 *   Name des Importkontos (Sender-Konto)
 *   Firmenname des Importkontos (Sender-Konto)
 *
 *   Spaltenname für die IBANs
 *   Spaltenname für die BICs
 *   Spaltenname für die Empfängernamen
 *   Spaltenname für den Betrag
 *   Spaltenname für die erste Zeile des Verwendungszwecks
 *   Spaltenname für die zweite Zeile des Verwendungszwecks
 *
 *   Prefix für Verwendungszweck Zeile 1
 *   Prefix für Verwendungszweck Zeile 2
 *   Trennzeichen zwichen Verwendungszweck Zeile 1 und Zeile 2
 *
 *
 * Verwendete Bibliotheken:
 *   Zum Auslesen der Daten aus der Excel-Tabelle wird die
 *   PHP-Klasse simplexlsx von Sergey Shuchkin verwendet.
 *   http://www.phpclasses.org/package/6279-PHP-Parse-and-retrieve-data-from-Excel-XLS-files.html
 *
 *   Zum Erzeugen der SEPA-XML Datei wird die Bilbiothek php-sepa-xml von Digitick verwendet.
 *   https://github.com/digitick/php-sepa-xml
 *
 *
 * Exit-Codes:
 *   1 Keine MAP angegeben.
 *   2 MAP-Bezeichner nicht in Excel-Tabelle enthalten.
 *   3 XML Datei schon vorhanden.
 *
 *
 * Created by Lars Eidam.
 * User: Lars Eidam
 * Date: 05.05.15
 */
    include 'lib/simplexlsx/simplexlsx.class.php';
    include 'functions.php';

    require_once 'vendor/autoload.php';
    use Digitick\Sepa\DomBuilder\DomBuilderFactory;

    // Hearderinformation content-type und charset setzen
    header("Content-Type: text/plain; charset=UTF-8");

    // Zeitzone setzen
    date_default_timezone_set("Europe/Berlin");

    // Konfiguration einlesen
    $config = parse_ini_file("config.ini", true);

    // Array mit Ausgabe Nachrichten
    $messages = array();

    // Array mit Übergabeparametern
    $optionArray = getopt("fhlm::", array("force", "help", "list", "map::"));

    // Konsolen-Hilfstext ausgeben
    if (0 == count($optionArray) || isset($optionArray["h"]) || isset($optionArray["help"])) {
        echo(generateHelpStr());
        exit;
    }

    // einlesen der Excel-Tabelle
    $xlsx = new SimpleXLSX($config['Allgemein']['tabellenpfad']);
    // extrahieren der Kopfzeile mit Spaltennamen
    $headlineRow = $xlsx->rowsEx();
    $headlineRow = $headlineRow[$config['Allgemein']['namenszeile'] - 1];

    // Ermittlung der Spaltenummer für die Überweisungsmappen
    $mapColumnNumber = array_search($config['Allgemein']['mapspaltenname'], array_column($headlineRow, 'value'));

    // Ermittlung welche verschiedenen Arten von Überweisungsmappen-Bezeichnungen existieren
    $mapsAvailable = array_filter(array_unique(array_column($xlsx->rows(), $mapColumnNumber)), "correctMapStr");
    // Überweisungsmappen-Bezeichnungen sortieren und invertieren, damit aktuellste Bezeichnung immer ganz oben steht
    sort ($mapsAvailable);
    $mapsAvailable = array_reverse($mapsAvailable);

    if (isset($optionArray["l"]) || isset($optionArray["list"])) {
        echo implode($mapsAvailable, PHP_EOL);
        echo PHP_EOL;
        exit(0);
    }
    if (isset($optionArray["m"]) || isset($optionArray["map"])) {
        $currentMap = "";
        if (isset($optionArray["m"]) && false !== $optionArray["m"]) {
            $currentMap = $optionArray["m"];
        } elseif(isset($optionArray["map"]) && false !== $optionArray["map"]) {
            $currentMap = $optionArray["map"];
        } else {
            echo "Sie müssen eine Map angeben!" . PHP_EOL;
            exit(1);
        }
        if (false === array_search($currentMap, $mapsAvailable)) {
            echo "Überweisungsmappen-Bezeichner " . $currentMap . " ist in Excel-Tabelle nicht vorhanden!" . PHP_EOL;
            echo "Folgende Bezeichner sind vorhanden:" . PHP_EOL;
            echo implode($mapsAvailable, PHP_EOL);
            echo PHP_EOL;
            exit(2);
        }
        // alle Zeilennummern einer bestimmten Überweisungsmappen-Bezeichnung ermitteln
        $currentMapRowNumbers = array_keys(array_column($xlsx->rows(), $mapColumnNumber), $currentMap);
        // alle Zeilen einer bestimmten Überweisungsmappen-Bezeichnung ermitteln
        $currentMapRows = array_intersect_key($xlsx->rows(), array_flip($currentMapRowNumbers));

        // Array für alle notwendigen Splatennumern und mit Überweisungs-Bezeichner als Schlüssel
        $neededColumnNumbers = array();
        $headlineRowValues = array_column($headlineRow, 'value');
        array_walk($headlineRowValues, "filterNewLine");

        foreach ($config['Spaltenamen'] as $bankTransferName => $columnName) {
;
            if ("" != $columnName && false != array_search($columnName, $headlineRowValues)) {
                // Ermittlung der Spalte für die Überweisungsmappen
                $neededColumnNumbers[$bankTransferName] = array_search($columnName, $headlineRowValues);
            }
        }

        // SEPA-Datei mit den Überweisungen aus der Excel-Tabelle erzeugen lassen
        $sepaFile = getSepaFile($currentMap, $currentMapRows, $neededColumnNumbers);

        // DomBuilder erzeugen um mit desen Hilfe aus der SEPA-Datei eine XML Datei zu generieren
        $sepaFileDomBuilder = DomBuilderFactory::createDomBuilder($sepaFile);

        if (file_exists ( $config['Allgemein']['sepapfad'] . $currentMap . ".xml" )
            && false == isset($optionArray["f"])
            && false ==  isset($optionArray["force"])) {
            echo "Die Datei " . $currentMap . ".xml ist schon vorhanden." . PHP_EOL . "Zum überschreiben bitte Option -f oder --force benutzen." . PHP_EOL;
            exit(3);
        } else {
            // XML-Datei erzeugen, mit der Form 'UEM-2015-05-02.xml'
            $sepaXMLFileHandle = fopen($config['Allgemein']['sepapfad'] . $currentMap . ".xml", "w");
            fwrite($sepaXMLFileHandle, $sepaFileDomBuilder->asXml());
            fclose($sepaXMLFileHandle);

            // XML-Datei erzeugen, mit der Form 'UEM-2015-05-02.xml'
            $messageFileHandle = fopen($config['Allgemein']['logpfad'] . $currentMap . "_" . date("Y.m.d-H.i.s") . ".log", "w");
            fwrite($messageFileHandle, implode($messages, PHP_EOL));
            fclose($messageFileHandle);
        }
    }
    if (0 != count($messages)) {
        echo PHP_EOL;
        echo "Folgende Probleme sind während der Erstellung der SEPA-XML-Datei aufgetreten:" . PHP_EOL;
        echo implode($messages, PHP_EOL);
        echo PHP_EOL;
    }
    exit(0);
?>
