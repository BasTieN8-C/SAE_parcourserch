<?php
require_once __DIR__ . '/../app/core/Repository.php';

try 
{
    $pdo = Repository::getInstance()->getPDO();
    
    // On génère le vrai hachage sécurisé pour le mot de passe "admin123"
    $mdpHache = password_hash('admin123', PASSWORD_DEFAULT);
    
    // On nettoie l'ancien test et on insère le bon
    $pdo->exec("DELETE FROM utilisateurs WHERE identifiant = 'admin'");
    $pdo->exec("INSERT INTO utilisateurs (identifiant, mot_de_passe, role) VALUES ('admin', '" . $mdpHache . "', 'administrateur')");
    
    echo "Compte admin créé avec succès ! Tu peux te connecter avec admin / admin123.";
} 
catch (Exception $e) 
{
    echo "Erreur : " . $e->getMessage();
}
?>