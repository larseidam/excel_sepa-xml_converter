# Filename: run.ps1

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

[void] [System.Reflection.Assembly]::LoadWithPartialName("System.Windows.Forms")
[void] [System.Reflection.Assembly]::LoadWithPartialName("System.Drawing")

# Funktion zeigt eine Textausgabe und einen Beenden Button und wenn $restart gleich $TRUE dann auch einen Button zur erneuten Auswahl
Function showTextDialog ($text, $secondButtonText, $firstButtonText, $size = 300)
{
    $returnValue = $FALSE

    $objForm = New-Object System.Windows.Forms.Form
    $objForm.Text = "SEPA-xls2xml - Meldung"
    $objForm.Size = New-Object System.Drawing.Size($size,[int] ($size / 2))
    $objForm.StartPosition = "CenterScreen"

    $objForm.KeyPreview = $True
    $ExitButton = New-Object System.Windows.Forms.Button

    if ($firstButtonText) {
        $objForm.Add_KeyDown({if ($_.KeyCode -eq "Enter") {
            $returnValue=$TRUE
            $objForm.Close()
        }})
        $objForm.Add_KeyDown({if ($_.KeyCode -eq "Escape") {
            $returnValue=$FALSE
            $objForm.Close()
        }})

        $OKButton = New-Object System.Windows.Forms.Button
        $OKButton.Location = New-Object System.Drawing.Size([int] (($size / 2) - 80), [int] ($size / 2 - 80))
        $OKButton.Size = New-Object System.Drawing.Size(80,23)
        $OKButton.Text = $firstButtonText
        $OKButton.Add_Click({
            $returnValue = $TRUE
            $objForm.Close()
        })
        $objForm.Controls.Add($OKButton)

        $ExitButton.Location = New-Object System.Drawing.Size([int] ($size / 2), [int] ($size / 2 - 80))

    } else {
        $objForm.Add_KeyDown({if ($_.KeyCode -eq "Enter") {
            $returnValue = $FALSE
            $objForm.Close()
        }})
        $ExitButton.Location = New-Object System.Drawing.Size([int] (($size / 2) - (80 / 2)), [int] ($size / 2 - 80))
    }

    $ExitButton.Size = New-Object System.Drawing.Size(80,23)
    $ExitButton.Text = $secondButtonText
    $ExitButton.Add_Click({$objForm.Close()})
    $objForm.Controls.Add($ExitButton)

    if ($size -gt 300) {
        $objText = New-Object System.Windows.Forms.TextBox
        $objText.Location = New-Object System.Drawing.Size(10,20)
        $objText.Size = New-Object System.Drawing.Size([int] ($size - 40), [int] ($size / 2 - 120))
        $objText.Multiline = $TRUE;
        $objText.ReadOnly = $TRUE;
        $objText.ScrollBars = "Vertical";
    } else {
        $objText = New-Object System.Windows.Forms.Label
        $objText.Location = New-Object System.Drawing.Size(10,20)
        $objText.Size = New-Object System.Drawing.Size([int] ($size - 10), [int] ($size / 6))
    }
    $objText.Text = $text
    $objForm.Controls.Add($objText)

    $objForm.Topmost = $True

    $objForm.Add_Shown({$objForm.Activate()})

    [void] $objForm.ShowDialog()

    return $returnValue
}

# Funktion erzeugt Überweisungsmappen-Auswahl und zeigt diese an
Function showMapSelectBoxDialog ($maps)
{
    $objForm = New-Object System.Windows.Forms.Form
    $objForm.Text = "SEPA-xls2xml - Auswahl"
    $objForm.Size = New-Object System.Drawing.Size(300,150)
    $objForm.StartPosition = "CenterScreen"

    $objForm.KeyPreview = $True
    $objForm.Add_KeyDown({if ($_.KeyCode -eq "Enter")
        {$x=$obComboBox.Text;$objForm.Close()}})
    $objForm.Add_KeyDown({if ($_.KeyCode -eq "Escape")
        {$x=$FALSE;$objForm.Close()}})

    $OKButton = New-Object System.Windows.Forms.Button
    $OKButton.Location = New-Object System.Drawing.Size(75,80)
    $OKButton.Size = New-Object System.Drawing.Size(75,23)
    $OKButton.Text = "OK"
    $OKButton.Add_Click({$x=$obComboBox.Text;$objForm.Close()})
    $objForm.Controls.Add($OKButton)

    $CancelButton = New-Object System.Windows.Forms.Button
    $CancelButton.Location = New-Object System.Drawing.Size(150,80)
    $CancelButton.Size = New-Object System.Drawing.Size(75,23)
    $CancelButton.Text = "Abbruch"
    $CancelButton.Add_Click({$objForm.Close()})
    $objForm.Controls.Add($CancelButton)

    $objLabel = New-Object System.Windows.Forms.Label
    $objLabel.Location = New-Object System.Drawing.Size(10,20)
    $objLabel.Size = New-Object System.Drawing.Size(280,20)
    $objLabel.Text = "Bitte eine Überweisungsmappe auswählen:"
    $objForm.Controls.Add($objLabel)

    $obComboBox = New-Object System.Windows.Forms.ComboBox
    $obComboBox.Location = New-Object System.Drawing.Size(10,40)
    $obComboBox.Size = New-Object System.Drawing.Size(260,10)

    ForEach( $map in $maps )
    {
        [void] $obComboBox.Items.Add($map)
    }
    $obComboBox.SelectedItem = $maps[0]
    $objForm.Controls.Add($obComboBox)

    $objForm.Topmost = $True

    $objForm.Add_Shown({$objForm.Activate()})
    [void] $objForm.ShowDialog()

    return $x
}

# Funktion erzeugt SEPA-XML-FILE
Function generateSepaXmlFile ($selectedMap, $architecture) {
    $returnMassage = & php-binary-$architecture\php.exe main.php --map=$selectedMap
    $returnMassageWithNL = ""
    $returnStatus = $lastexitcode
    $returnValue = $FALSE

    if ($returnStatus -eq 0) {
        $returnMassageWithNL = $returnMassage -join "`r`n"
        $returnValue = showTextDialog $returnMassageWithNL "Beenden" $NULL 600
    } elseif ($returnStatus -eq 2) {
        $returnValue = showTextDialog "Überweisungsmappen-Bezeichner $selectedMap ist in Excel-Tabelle nicht vorhanden!" "Beenden" "Wiederholen"
    } elseif ($returnStatus -eq 3) {
         $returnValue = showTextDialog "Datei $selectedMap.xml ist vorhanden! Überschreiben?" "Nein" "Ja"
         if ($returnValue) {
            $returnMassage = & php-binary-$architecture\php.exe main.php -force --map=$selectedMap
            $returnStatus = $lastexitcode
            if ($returnStatus -eq 0) {
                $returnMassageWithNL = $returnMassage -join "`r`n"
                $returnValue = showTextDialog $returnMassageWithNL "Beenden" $NULL 600
            } else {
                $returnValue = showTextDialog "Keine Datei erzeugt!" "Beenden"
            }
         } else {
              $returnValue = showTextDialog "Keine Datei erzeugt!" "Beenden"
          }
    } else {
        $returnValue = showTextDialog "Keine Datei erzeugt!" "Beenden"
    }

    return $returnValue
}

# Rechner-Architektur ermitteln um entsprechendes php binary zu verwenden
$architecture = "x32"
if ($ENV:PROCESSOR_ARCHITECTURE -eq "AMD64") {
    $architecture = "x64"
}

# alle verfügbaren Überweisungsmappen-Bezeichner ermitteln
$maps = & php-binary-$architecture\php.exe main.php -l

$noexit = $TRUE
while ($noexit) {
    # Auswahldialog für Überweisungsmappen-Bezeichner anzeigen und ausgewählten Bezeichner speichern
    $selectedMap = showMapSelectBoxDialog $maps

    # ausgewählten Überweisungsmappen-Bezeichner auf syntaktische Korrektheit prüfen
    if (-NOT $selectedMap) {
        $noexit = $FALSE
    } elseif (-NOT ($selectedMap -match '^UEM-[0-9]{4}-[0-9]{2}-[0-9]{2}$')) {
        $noexit = showTextDialog "Kein korrekter Überweisungsmappen-Bezeichner" "Beenden" "Wiederholen"
    } else {
        $noexit = generateSepaXmlFile $selectedMap $architecture
    }
}


# end of script