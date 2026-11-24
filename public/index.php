<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// --- 1. DÉFINITION DE L'URL (Indispensable en haut) ---
$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --- 2. GESTION DU FAVICON (Évite les erreurs 404 inutiles) ---
if ($url === '/favicon.ico') {
    http_response_code(204);
    exit;
}

// --- 3. FICHIERS STATIQUES ---
if (php_sapi_name() === 'cli-server') 
{
    $cheminFichier = __DIR__ . $url;
    if (is_file($cheminFichier)) 
    {
        return false;
    }
}

$url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// --- 4. RACINE ET SÉCURITÉ ---
if ($url === '/') 
{
    header('Location: ' . (isset($_SESSION['utilisateur_id']) ? '/accueil' : '/connexion'));
    exit();
}

$routesPubliques = array('/connexion');

if (!isset($_SESSION['utilisateur_id']) && !in_array($url, $routesPubliques) && strpos($url, '/api/') !== 0) 
{
    header('Location: /connexion');
    exit();
}

// --- Routes réservées aux administrateurs ---
$routesAdmins = ['/import', '/groupes', '/api/importer', '/api/groupes', '/api/exporter'];
foreach ($routesAdmins as $routeAdmin) {
    if (strpos($url, $routeAdmin) === 0 && isset($_SESSION['utilisateur_role']) && $_SESSION['utilisateur_role'] !== 'administrateur') {
        http_response_code(403);
        die('Accès refusé. Vous n\'avez pas les permissions nécessaires.');
    }
}

// --- 5. ROUTAGE DES PAGES (HTML) ---
if ($url === '/connexion') 
{
    require_once __DIR__ . '/../app/controllers/ControleurAuthentification.php';
    $controleur = new ControleurAuthentification();
    ($_SERVER['REQUEST_METHOD'] === 'POST') ? $controleur->traiterConnexion() : $controleur->afficherConnexion();
}
elseif ($url === '/deconnexion') 
{
    require_once __DIR__ . '/../app/controllers/ControleurAuthentification.php';
    $controleur = new ControleurAuthentification();
    $controleur->deconnexion();
}
elseif (in_array($url, ['/accueil', '/import', '/tableau-bord', '/carte', '/groupes'])) 
{
    if ($url === '/accueil') {
        require_once __DIR__ . '/../app/controllers/IndexController.php';
        (new IndexController())->index();
    } else {
        require_once __DIR__ . '/../app/controllers/ControleurNavigation.php';
        $controleur = new ControleurNavigation();
        if ($url === '/import') $controleur->afficherImport();
        elseif ($url === '/tableau-bord') $controleur->afficherTableauDeBord();
        elseif ($url === '/carte') $controleur->afficherCarte();
        elseif ($url === '/groupes') $controleur->afficherGroupes();
    }
}
elseif ($url === '/api/candidats' && $_SERVER['REQUEST_METHOD'] === 'GET') 
{
    require_once __DIR__ . '/../app/controllers/ControleurCandidat.php';
    $controleur = new ControleurCandidat();
    $controleur->lister();
}
elseif ($url === '/api/importer' && $_SERVER['REQUEST_METHOD'] === 'POST') 
{
    require_once __DIR__ . '/../app/controllers/ControleurCandidat.php';
    $controleur = new ControleurCandidat();
    $controleur->importer();
}
elseif ($url === '/api/exporter' && $_SERVER['REQUEST_METHOD'] === 'GET') 
{
    require_once __DIR__ . '/../app/controllers/ControleurCandidat.php';
    $controleur = new ControleurCandidat();
    $controleur->exporter();
}
elseif ($url === '/api/groupes' && $_SERVER['REQUEST_METHOD'] === 'GET') 
{
    require_once __DIR__ . '/../app/controllers/ControleurGroupe.php';
    $controleur = new ControleurGroupe();
    $controleur->lister();
}
elseif ($url === '/api/groupes/creer' && $_SERVER['REQUEST_METHOD'] === 'POST') 
{
    require_once __DIR__ . '/../app/controllers/ControleurGroupe.php';
    $controleur = new ControleurGroupe();
    $controleur->creer();
}
elseif ($url === '/api/groupes/supprimer' && $_SERVER['REQUEST_METHOD'] === 'DELETE') 
{
    require_once __DIR__ . '/../app/controllers/ControleurGroupe.php';
    $controleur = new ControleurGroupe();
    $controleur->supprimer();
}
elseif ($url === '/api/departements' && $_SERVER['REQUEST_METHOD'] === 'GET') 
{
    require_once __DIR__ . '/../app/controllers/ControleurGroupe.php';
    $controleur = new ControleurGroupe();
    $controleur->listeDepartements();
}
elseif ($url === '/api/groupes/noter' && $_SERVER['REQUEST_METHOD'] === 'POST') 
{
    require_once __DIR__ . '/../app/controllers/ControleurGroupe.php';
    $controleur = new ControleurGroupe();
    $controleur->noter();
}
// --- 6. ROUTES DE L'API JSON ---
elseif (strpos($url, '/api/') === 0) 
{
    header('Content-Type: application/json');

    // Routes liées aux GROUPES, FILTRES et DÉPARTEMENTS
    if (strpos($url, '/api/groupes') === 0 || $url === '/api/filtres' || $url === '/api/departements') {
        require_once __DIR__ . '/../app/controllers/ControleurGroupe.php';
        $controleur = new ControleurGroupe();
        
        // 1. Routes spécifiques (prioritaires)
        if ($url === '/api/groupes/creer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $controleur->creer();
        } 
        elseif ($url === '/api/groupes/supprimer') {
            $controleur->supprimer();
        }
        elseif ($url === '/api/groupes/noter' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $controleur->noter();
        } 
        elseif ($url === '/api/groupes/assigner' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $controleur->assignerCandidats();
        } 
        elseif ($url === '/api/groupes/candidats') {
            $controleur->obtenirCandidatsGroupeAvecNotes();
        }
        // 2. Routes de lecture de données
        elseif ($url === '/api/filtres') {
            $controleur->obtenirFiltresDynamiques();
        } 
        elseif ($url === '/api/departements') {
            $controleur->listeDepartements();
        }
        // 3. Route par défaut pour les groupes (la liste)
        elseif ($url === '/api/groupes' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            $controleur->lister();
        }
        elseif ($url === '/api/groupes/supprimer') {
            $controleur->supprimer();
        }
        else {
            http_response_code(404);
            echo json_encode(["erreur" => "Action groupe introuvable"]);
        }
    } 
    // Routes liées à la CARTE
    elseif (strpos($url, '/api/carte') === 0) {
        require_once __DIR__ . '/../app/controllers/ControleurCarte.php';
        $controleur = new ControleurCarte();

        if ($url === '/api/carte/candidats') {
            $controleur->obtenirCandidatsJson();
        } else {
            http_response_code(404);
            echo json_encode(["erreur" => "Route carte introuvable"]);
        }
    }
    // Routes liées aux CANDIDATS
    elseif (strpos($url, '/api/candidats') === 0 || $url === '/api/importer' || $url === '/api/exporter') {
        require_once __DIR__ . '/../app/controllers/ControleurCandidat.php';
        $controleur = new ControleurCandidat();
        
        if ($url === '/api/candidats/notes') {
            $controleur->listerAvecNotesFinales();
        } elseif ($url === '/api/candidats') {
            $controleur->lister();
        } elseif ($url === '/api/importer') {
            $controleur->importer();
        } elseif ($url === '/api/exporter') {
            $controleur->exporter();
        } else {
            http_response_code(404);
            echo json_encode(["erreur" => "Action candidat introuvable"]);
        }
    }
    
    // Route DEBUG pour vérifier les candidats
    elseif ($url === '/api/debug/candidats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        require_once __DIR__ . '/../app/controllers/ControleurGroupe.php';
        $controleur = new ControleurGroupe();
        $controleur->debugCandidats();
    }
    else {
        http_response_code(404);
        echo json_encode(["erreur" => "Route API inexistante"]);
    }
    exit;
}else 
{
    http_response_code(404);
    echo "Erreur 404 : Page introuvable.";
}
?>