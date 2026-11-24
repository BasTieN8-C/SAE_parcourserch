<?php
// public/geoloc.php

// Ces directives forcent le script a continuer en arriere-plan 
// meme si la requete web s'arrete ou que vous changez de page.
ignore_user_abort(true);
set_time_limit(0);

// Lancement de la veritable logique de geolocalisation
require_once __DIR__ . '/../app/scripts/geoloc_background.php';
?>