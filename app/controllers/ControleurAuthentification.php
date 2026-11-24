<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../repositories/UtilisateurRepository.php';

class ControleurAuthentification extends Controller
{
    public function afficherConnexion()
    {
        $this->view('connexion', 'Authentification');
    }

    public function traiterConnexion()
    {
        if (isset($_POST['identifiant']) && isset($_POST['mot_de_passe']))
        {
            $depotUtilisateur = new UtilisateurRepository();
            $utilisateur = $depotUtilisateur->trouverParIdentifiant($_POST['identifiant']);

            // Sécurité stricte : On utilise uniquement password_verify pour comparer le hash
            if ($utilisateur && password_verify($_POST['mot_de_passe'], $utilisateur['mot_de_passe']))
            {
                if (session_status() === PHP_SESSION_NONE) session_start();
                
                // Prévention contre la fixation de session
                session_regenerate_id(true);
                
                $_SESSION['utilisateur_id'] = $utilisateur['id'];
                $_SESSION['utilisateur_role'] = $utilisateur['role'];
                $_SESSION['utilisateur_identifiant'] = $utilisateur['identifiant'];
                
                $this->redirectTo('/');
                return;
            }
            
            // Message générique pour ne pas indiquer si c'est l'identifiant ou le mot de passe qui est faux
            $this->view('connexion', 'Authentification', array('erreur' => 'Identifiants incorrects.'));
            return;
        }
        
        $this->view('connexion', 'Authentification');
    }

    public function deconnexion()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Destruction complète et sécurisée de la session
        $_SESSION = array();
        session_destroy();
        
        $this->redirectTo('/connexion');
    }
}
?>