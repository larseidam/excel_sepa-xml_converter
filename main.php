<?php
/**
 * Created by Lars Eidam.
 * User: Lars Eidam
 * Date: 05.05.15
 * Time: 14:02
 *
 * Exit-Codes:
 *  1 Keine MAP angegeben.
 *  2 MAP-Bezeichner nicht in Excel-Tabelle enthalten.
 *  3 XML Datei schon vorhanden.
 */
    include 'vendor/simplexlsx/simplexlsx.class.php';
    include 'functions.php';

    require_once 'vendor/autoload.php';
    use Digitick\Sepa\DomBuilder\DomBuilderFactory;

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
    // Überweisungsmappen-Bezeichnungen sortieren und invertieren damit, aktuellste Bezeichnung ganz oben steht
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
        foreach ($config['Spaltenamen'] as $bankTransferName => $columnName) {
            if ("" != $columnName && false != array_search($columnName, array_column($headlineRow, 'value'))) {
                // Ermittlung der Spalte für die Überweisungsmappen
                $neededColumnNumbers[$bankTransferName] = array_search($columnName, array_column($headlineRow, 'value'));
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
        echo "Folgende Probleme sind während der Erstellung der SEPA-XML-Datei aufgetreten:" . PHP_EOL . "  ";
        echo implode($messages, PHP_EOL . "  ");
        echo PHP_EOL;
    }
    exit(0);
?>
