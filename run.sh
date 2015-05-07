#!/bin/bash

function generateSepaXmlFile {
  RETURNMASSAGE=$(php main.php --map="$1")
  RETURNSTATUS=$?

  if [ $RETURNSTATUS -eq 0 ]; then
    echo $RETURNMASSAGE
  elif [ $RETURNSTATUS -eq 2 ]; then
    echo "Überweisungsmappen-Bezeichner $1 ist in Excel-Tabelle nicht vorhanden!"
  elif [ $RETURNSTATUS -eq 3 ]; then
    read -p"Datei $1.xml ist vorhanden! Überschreiben? (j/n)? " response
    if [ "$response" == "j" ]; then
      php main.php -f --map="$1"
      echo "Datei $1.xml erzeugt!"
    else
      echo "Keine Datei erzeugt!"
    fi
  else
    echo "Keine Datei erzeugt!"
  fi
}

MAPS=$(php main.php -l)
PS3="Auswahl der Überweisungsmappe oder direkte Eingabe (z.B. UEM-2015-05-02): "
select map in $MAPS exit; do
  echo -e "Überweisungsmappe: $map"
  if [ -n "$map" ]; then
    case "$map" in
      exit) exit 0;;
      *) generateSepaXmlFile $map
      exit 0;;
    esac
  elif [[ "$REPLY" =~ ^UEM-[0-9]{4}-[0-9]{2}-[0-9]{2}$  ]]; then
    generateSepaXmlFile $REPLY
    exit 0
  elif [ "$REPLY" = "quit" -o "$REPLY" = "q" -o "$REPLY" = "exit" -o "$REPLY" = "e"  ]; then
    exit 0
  else
    echo "Fehlerhafte Eingabe!" >&2
  fi
done