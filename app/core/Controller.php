<?php

require_once __DIR__ . '/../../vendor/autoload.php';

abstract class Controller
{
    /**
     * Vérifier si l'utilisateur actuel a l'un des rôles spécifiés
     */
    protected function hasRole(...$roles): bool
    {
        $userRole = $_SESSION['utilisateur_role'] ?? null;
        return $userRole && in_array($userRole, $roles);
    }

    /**
     * Demander un rôle spécifique - rediriger si non autorisé
     */
    protected function requireRole(...$roles): void
    {
        if (!$this->hasRole(...$roles)) {
            http_response_code(403);
            die('Accès refusé. Vous n\'avez pas les permissions nécessaires.');
        }
    }

    protected function view(string $nomVue, string $titre, array $donnees = [])
    {
        $chargeur = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../views');
        $environnementTwig = new \Twig\Environment($chargeur);

        $donnees['title'] = $titre;
        $donnees['role'] = $_SESSION['utilisateur_role'] ?? 'utilisateur';
        
        echo $environnementTwig->render($nomVue . '.html.twig', $donnees);
    }

    protected function json($donnees, int $statut = 200)
    {
        // Nettoyer le buffer de sortie pour éviter les erreurs avant le JSON
        while (ob_get_level()) ob_end_clean();
        
        http_response_code($statut);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode($donnees);
        
        exit;
    }

    protected function redirectTo(string $url)
    {
        header('Location: ' . $url);
        
        exit;
    }
}
?>