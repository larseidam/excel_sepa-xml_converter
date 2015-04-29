<?php
    /*
     * Funktion um die korrekten Inhalte der Überweisungsmappen-Spalte zu filtern
     */
    function correctMapStr($var)
    {
        global $config;
        if (preg_match($config['Allgemein']['mapregex'], $var))
            return true;
        else
            return false;
    }
?>