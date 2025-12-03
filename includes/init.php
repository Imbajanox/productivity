<?php
/**
 * Produktivitätstool - Initialisierung
 * 
 * Diese Datei am Anfang jeder Seite einbinden
 */

// Alle benötigten Dateien laden
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Session starten
startSession();

// Remember-Me Token prüfen
checkRememberToken();
