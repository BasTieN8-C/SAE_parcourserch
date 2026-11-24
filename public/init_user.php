<?php
require_once __DIR__ . '/../app/core/Repository.php';

try 
{
    $pdo = Repository::getInstance()->getPDO();
    
    // Créer un utilisateur normal avec le mot de passe "user456"
    $mdpHache = password_hash('user456', PASSWORD_DEFAULT);
    
    // Nettoyer l'ancien et insérer le nouveau
    $pdo->exec("DELETE FROM utilisateurs WHERE identifiant = 'utilisateur'");
    $pdo->exec("INSERT INTO utilisateurs (identifiant, mot_de_passe, role) VALUES ('utilisateur', '" . $mdpHache . "', 'utilisateur')");
    
    echo "<strong>Compte utilisateur créé avec succès !</strong><br><br>";
    echo "Tu peux te connecter avec :<br>";
    echo "• <strong>Admin</strong> : admin / admin123<br>";
    echo "• <strong>Utilisateur</strong> : utilisateur / user456<br><br>";
    echo "➜ <a href='/connexion'>Aller à la connexion</a>";
} 
catch (Exception $e) 
{
    echo "Erreur : " . $e->getMessage();
}
?>
