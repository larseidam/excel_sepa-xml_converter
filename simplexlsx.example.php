<?php
    include 'vendor/simplexlsx/simplexlsx.class.php';

    function correctMapStr($var)
    {
        if (preg_match('/UEM-\d{4}-\d{2}-\d{2}/', $var))
            return true;
        else
            return false;
    }

    header("Content-Type: text/html; charset=utf-8");

    $xlsx = new SimpleXLSX('Belegliste.xlsx');
    $headlineRow = $xlsx->rowsEx();
    $headlineRow = $headlineRow[1];
    $mapCloumn = array_search("Überwei-sungs-mappe vom", array_column($headlineRow, 'value'));

    $mapsAvaidable = array_filter(array_unique(array_column($xlsx->rows(), 28)), "correctMapStr");
    sort ($mapsAvaidable);
    $mapsAvaidable = array_reverse($mapsAvaidable);

    echo '<h1>$xlsx->rows()</h1>';
    echo '<pre>';
    var_dump($mapsAvaidable);
    //print_r( $xlsx->rows() );
    echo '</pre>';

    echo '<h1>$xlsx->rowsEx()</h1>';
    echo '<pre>';
    //print_r( $xlsx->rowsEx() );
    echo '</pre>';
?>
