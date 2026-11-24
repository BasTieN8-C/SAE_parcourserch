<?php
// app/core/Repository.php

// 1. On inclut le fichier de configuration global qui contient les accès
$cheminConfig = __DIR__ . '/../../config/config.php';
if (file_exists($cheminConfig)) 
{
    require_once $cheminConfig;
} 
else 
{
    // Sécurité : si on a pas encore créé le fichier config.php, on met des valeurs par défaut locales
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
    if (!defined('DB_PORT')) define('DB_PORT', '5432');
    if (!defined('DB_NAME')) define('DB_NAME', 'sae_parcoursup');
    if (!defined('DB_USER')) define('DB_USER', 'postgres');
    if (!defined('DB_PASS')) define('DB_PASS', '260120');
}

class Repository
{
    // Variable statique pour stocker l'unique instance de la connexion (Singleton)
    private static $instance = null;
    
    // L'objet PDO qui va dialoguer avec PostgreSQL
    private $pdo;

    // Le constructeur est privé : on ne peut pas faire "new Repository()" de l'extérieur
    private function __construct()
    {
        try 
        {
            // Construction de la chaîne de connexion (DSN) pour PostgreSQL
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            
            // Création de la connexion PDO
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS);
            
            // Configuration pour afficher proprement les erreurs SQL et renvoyer des tableaux associatifs
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } 
        catch (PDOException $erreur) 
        {
            // On arrête tout proprement si la base de données est injoignable
            die("<h1 style='color:red;'>Erreur de connexion a la base de donnees :</h1><p>" . $erreur->getMessage() . "</p><p>Verifiez que PostgreSQL est allume et que vos identifiants dans config.php sont corrects.</p>");
        }
    }

    // C'est cette méthode qu'on appelle partout pour récupérer la connexion (ex: Repository::getInstance()->getPDO())
    public static function getInstance()
    {
        if (self::$instance === null) 
        {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    // Méthode pour récupérer l'objet PDO prêt à l'emploi
    public function getPDO()
    {
        return $this->pdo;
    }
}
?>