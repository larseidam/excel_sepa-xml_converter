<?php
    include 'vendor/simplexlsx/simplexlsx.class.php';
    include 'functions.php';

    header("Content-Type: text/html; charset=utf-8");

    // Konfiguration einlesen
    $config = parse_ini_file("config.ini", true);

    // einlesen der Excel-Tabelle
    $xlsx = new SimpleXLSX($config['Allgemein']['tabellenpfad']);
    // extrahieren der Kopfzeile mit Spaltennamen
    $headlineRow = $xlsx->rowsEx();
    $headlineRow = $headlineRow[$config['Allgemein']['namenszeile'] - 1];

    // Ermittlung der Splate für die Überweisungsmappen
    $mapCloumn = array_search($config['Allgemein']['mapspaltenname'], array_column($headlineRow, 'value'));

    // Ermittlung welche verschiedenen Arten von Überweisungsmappen-Bezeichnungen existieren
    $mapsAvailable = array_filter(array_unique(array_column($xlsx->rows(), $mapCloumn)), "correctMapStr");
    // Überweisungsmappen-Bezeichnungen sortieren und invertieren damit, aktuellste Bezeichnung ganz oben steht
    sort ($mapsAvailable);
    $mapsAvailable = array_reverse($mapsAvailable);

    $currentMap = $mapsAvailable[0];

    // alle Zeilen einer bestimmten Überweisungsmappen-Bezeichnung ermitteln
    $currentMapRows = array_keys(array_column($xlsx->rows(), $mapCloumn), $currentMap);


    echo '<h1>$xlsx->rows()</h1>';
    echo '<pre>';
    //var_dump($config['Allgemein']['tabellenpfad']);
    var_dump($currentMapRows);
    //print_r( $xlsx->rows() );
    echo '</pre>';

    echo '<h1>$xlsx->rowsEx()</h1>';
    echo '<pre>';
    //print_r( $xlsx->rowsEx() );
    echo '</pre>';
?>
