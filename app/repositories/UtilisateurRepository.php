<?php
require_once __DIR__ . '/../core/Repository.php';

class UtilisateurRepository
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Repository::getInstance()->getPDO();
    }

    public function trouverParIdentifiant($identifiant)
    {
        $requete = "SELECT * FROM utilisateurs WHERE identifiant = :identifiant";
        $declaration = $this->pdo->prepare($requete);
        $declaration->execute(array(':identifiant' => $identifiant));
        
        return $declaration->fetch(PDO::FETCH_ASSOC);
    }
}
?>