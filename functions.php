<?php
    require_once 'vendor/autoload.php';
    use Digitick\Sepa\TransferFile\CustomerCreditTransferFile;
    use Digitick\Sepa\TransferInformation\CustomerCreditTransferInformation;
    use Digitick\Sepa\GroupHeader;
    use Digitick\Sepa\PaymentInformation;

    /*
     * Funktion um die korrekten Inhalte der Überweisungsmappen-Spalte zu filtern
     * $var String Überweisungsmappen-Bezeichner
     */
    function correctMapStr($var)
    {
        global $config;
        if (1 == preg_match($config['Kontrollregex']['mapregex'], $var))
            return true;
        else
            return false;
    }
// TODO: Kommentar schreiben
    function filterNewLine(&$item1, $key)
    {
        $item1 = str_replace("\n","\\n", $item1);
    }

    /*
     * Funktion erzeugt Sepa File
     * @param $currentMap String Name des zubearbeitenten Überweisungs-Bezeichner
     * @param $currentMapRows Array von den zubearbeitenten Zeilen aus der Excel-Tabelle
     * @param $neededColumnNumbers Array von den notwendigen Spalten aus der Excel-Tabelle
     * @return CustomerCreditTransferFile Sepa-File-Handler mit den Überweisungen
     */
    function getSepaFile($currentMap, $currentMapRows, $neededColumnNumbers)
    {
        global $config;
        global $messages;

        // Erstellung der initzialen Überweisungsparameter
        $groupHeader = new GroupHeader('SEPA File Identifier', $config['Importkonto']['firmenname']);
        $sepaFile = new CustomerCreditTransferFile($groupHeader);

        // Zahlungsinformationen (Sender) erstellen
        $paymentInformation = new PaymentInformation(
            $currentMap, // Payment Info ID
            $config['Importkonto']['iban'], // IBAN the money is transferred from
            $config['Importkonto']['bic'], // BIC
            $config['Importkonto']['name'] // Debitor Name
        );

        foreach ($currentMapRows as $currentMapRowNumber => $currentMapRow) {
            // einzelnen Spalten auf Vorhandensein prüfen
            $betrag = isset($neededColumnNumbers['betrag']) ? number_format($currentMapRow[$neededColumnNumbers['betrag']], 2, ".", "") : "";
            $iban = isset($neededColumnNumbers['iban']) ? $currentMapRow[$neededColumnNumbers['iban']] : "";
            $name = isset($neededColumnNumbers['name']) ? $currentMapRow[$neededColumnNumbers['name']] : "";
            $bic = isset($neededColumnNumbers['bic']) ? $currentMapRow[$neededColumnNumbers['bic']] : "";
            $zweck1 = isset($neededColumnNumbers['zweck1']) ? $currentMapRow[$neededColumnNumbers['zweck1']] : "";
            $zweck2 = isset($neededColumnNumbers['zweck2']) ? $currentMapRow[$neededColumnNumbers['zweck2']] : "";
            $zweck = "" != $zweck2 ? $zweck1 . " " . $zweck2 : $zweck1;

            if (1 != preg_match($config['Kontrollregex']['betragregex'], $betrag)){
                $messages[] = "Zeile " . ($currentMapRowNumber + 1) . ": " . $betrag . " ist kein gültiger Betrag und wird deswegen nicht übernommen!";
                $betrag = "";
            }
            $iban = str_replace(" ", "", $iban);
            if (1 != preg_match($config['Kontrollregex']['ibanregex'], $iban)){
                $messages[] = "Zeile " . ($currentMapRowNumber + 1) . ": " . $iban . " ist kein gültiger IBAN und wird deswegen nicht übernommen!";
                $iban = "";
            }
            $bic = str_replace(" ", "", $bic);
            if (1 != preg_match($config['Kontrollregex']['bicregex'], $bic)){
                $messages[] = "Zeile " . ($currentMapRowNumber + 1) . ": " . $bic . " ist kein gültiger BIC und wird deswegen nicht übernommen!";
                $bic = "";
            }

            // Zahlungsinformationen (Empfänger) anlegen
            $transfer = new CustomerCreditTransferInformation(
                $betrag, // Amount
                $iban, //IBAN of creditor
                $name //Name of Creditor
            );
            $transfer->setBic($bic); // Set the BIC explicitly
            $transfer->setRemittanceInformation($zweck);

            // Zahlungsinformationen zusammenfügen
            $paymentInformation->addTransfer($transfer);
        }
        $messages[] = "";
        $messages[] = "Anzahl an Zeilen in der Excel-Tabelle: " . count($currentMapRows);
        $messages[] = "Anzahl an Überweisungen: " . $paymentInformation->getNumberOfTransactions();
        $messages[] = "Gesamtsumme: " . (($paymentInformation->getControlSumCents()) / 100);

        // Zahlungsinformationen zur SEPA-Datei hinzufügen
        $sepaFile->addPaymentInformation($paymentInformation);

        return $sepaFile;
    }

    /**
     * Erzeugt Konsolen-Hilftext
     */
    function generateHelpStr()
    {
        return "Script zur Erzeugung einer SEPA-XML-Datei aus einer Excel-Tabelle." . PHP_EOL
        . "Aufruf:" . PHP_EOL
        . "\tphp main.php [OPTION …]" . PHP_EOL
        . PHP_EOL
        . "Hilfeoptionen:" . PHP_EOL
        . "-h, --help\t\t\tHilfeoptionen anzeigen" . PHP_EOL
        . PHP_EOL
        . "Anwendungsoptionen:" . PHP_EOL
        . "-f, --force\t\t\tÜberschreiben vorhandener Dateien erzwingen." . PHP_EOL
        . "-l, --list\t\t\tListe der verfügbaren Überweisungsmappen-Bezeichner ausgeben." . PHP_EOL
        . "-m <Bezeichner>," . PHP_EOL
        . " --map=<Bezeichner>\t\tSEPA-Datei für bestimmten Überweisungsmappen-Bezeichner erstellen. (z.B.: UEM-2015-05-02)" . PHP_EOL;
    }
?>