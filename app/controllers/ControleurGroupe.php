<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../repositories/GroupeRepository.php';
require_once __DIR__ . '/../repositories/CandidatRepository.php';
// --- AJOUT DE L'IMPORT DU SERVICE ---
require_once __DIR__ . '/../services/ServiceAssignationGroupes.php'; 

class ControleurGroupe extends Controller
{
    private GroupeRepository $depot;
    private $serviceAssignation; // On enlève le type strict pour éviter les erreurs si le fichier charge mal

    public function __construct()
    {
        $this->depot = new GroupeRepository();
        
        // On vérifie si la classe existe avant de l'instancier pour éviter le crash fatal
        if (class_exists('ServiceAssignationGroupes')) {
            $this->serviceAssignation = new ServiceAssignationGroupes();
        } else {
            $this->serviceAssignation = null;
        }
    }

    /**
     * Récupère les départements uniques pour le select du JS
     */
    public function listeDepartements()
    {
        if (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $repoCandidat = new CandidatRepository();
            $departements = $repoCandidat->recupererDepartementsUniques();
            $resultat = is_array($departements) ? $departements : [];
            echo json_encode($resultat);
            exit; 
        }
        catch (Throwable $e) 
        {
            echo json_encode(["erreur_php" => $e->getMessage()]);
            exit;
        }
    }

    public function lister()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        
        $groupes = $this->depot->trouverTous();
        // On s'assure de renvoyer un tableau propre
        echo json_encode($groupes ?: []);
        exit;
    }

    public function debugCandidats()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        try {
            $repoCandidat = new CandidatRepository();
            $total = $repoCandidat->compterTotalCandidats();
            
            echo json_encode([
                "total_candidats" => $total,
                "succes" => true
            ]);
        } catch (Throwable $e) {
            echo json_encode([
                "succes" => false,
                "erreur" => $e->getMessage()
            ]);
        }
        exit;
    }

    public function creer()
    {
        $this->requireRole('administrateur');
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $donnees = json_decode(file_get_contents("php://input"), true);

        try {
            // VALIDATION: Nom du groupe
            if (empty($donnees['nom']) || !is_string($donnees['nom'])) {
                echo json_encode(["succes" => false, "message" => "Nom du groupe obligatoire et invalide"]);
                exit;
            }
            
            $nom = trim($donnees['nom']);
            if (strlen($nom) > 255) {
                echo json_encode(["succes" => false, "message" => "Nom du groupe trop long (max 255 caractères)"]);
                exit;
            }
            if (strlen($nom) < 2) {
                echo json_encode(["succes" => false, "message" => "Nom du groupe trop court (min 2 caractères)"]);
                exit;
            }

            // VALIDATION: Couleur du groupe
            if (empty($donnees['couleur']) || !is_string($donnees['couleur'])) {
                echo json_encode(["succes" => false, "message" => "Couleur du groupe obligatoire et invalide"]);
                exit;
            }
            
            $couleur = trim($donnees['couleur']);
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $couleur)) {
                echo json_encode(["succes" => false, "message" => "Format de couleur invalide (doit être #RRGGBB)"]);
                exit;
            }

            // Critères (optionnel mais doit être un tableau)
            $criteres = [];
            if (!empty($donnees['criteres'])) {
                if (!is_array($donnees['criteres'])) {
                    echo json_encode(["succes" => false, "message" => "Les critères doivent être un tableau"]);
                    exit;
                }
                
                // Nettoyer les critères vides
                foreach ($donnees['criteres'] as $cle => $valeur) {
                    if (!empty($valeur)) {
                        if (is_array($valeur)) {
                        $criteres[$cle] = array_values(array_filter($valeur, function($v) { return !empty($v); }));
                        } else {
                            $criteres[$cle] = trim($valeur);
                        }
                    }
                }
            }

            // Logging
            error_log("[GROUPE CRÉATION] Nom: $nom | Couleur: $couleur | Critères: " . json_encode($criteres));

            // 1. Insertion du groupe dans la table 'groupes'
            $idGroupe = $this->depot->ajouter($nom, $couleur, $criteres);

            if (!$idGroupe) {
                echo json_encode(["succes" => false, "message" => "Erreur BD: impossible de créer le groupe"]);
                exit;
            }

            // 2. Assignation des candidats qui correspondent aux critères
            $nb = 0;
            if ($this->serviceAssignation) {
                $nb = $this->serviceAssignation->assignerCandidatsAuGroupe((int)$idGroupe, $criteres);
            }

            // SÉCURITÉ: Si aucun candidat ne correspond ET qu'il y a des critères, c'est suspect
            if ($nb === 0 && !empty($criteres)) {
                // Ne pas supprimer le groupe, mais avertir
                error_log("[GROUPE AVERTISSEMENT] Groupe $idGroupe créé mais 0 candidats assignés | Critères: " . json_encode($criteres));
                echo json_encode([
                    "succes" => true,
                    "message" => "⚠️ Groupe créé mais AUCUN candidat ne correspond à vos critères. Vérifiez les valeurs!",
                    "id" => $idGroupe,
                    "nb_candidats" => 0,
                    "warning" => true
                ]);
            } else {
                error_log("[GROUPE CRÉÉ] ID: $idGroupe | Nom: '$nom' | Candidats assignés: $nb");

                echo json_encode([
                    "succes" => true,
                    "message" => "Groupe créé avec $nb candidat(s).",
                    "id" => $idGroupe,
                    "nb_candidats" => $nb
                ]);
            }

        } catch (Throwable $e) {
            error_log("[GROUPE ERREUR FATALE] " . $e->getMessage() . "\n" . $e->getTraceAsString());
            echo json_encode([
                "succes" => false, 
                "message" => "Erreur serveur: " . $e->getMessage()
            ]);
        }
        exit;
    }

    public function obtenirFiltresDynamiques()
    {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        $repo = new CandidatRepository();
        echo json_encode([
            'departements' => $repo->recupererValeursUniques('departement'),
            'bacs'         => $repo->recupererValeursUniques('type_bac'),
            'specialites'  => $repo->recupererValeursUniques('specialite'),
            'genres'       => $repo->recupererValeursUniques('genre'),
            'profils'      => $repo->recupererValeursUniques('profil_candidat')
        ]);
        exit;
    }

    public function supprimer()
    {
        $this->requireRole('administrateur');
        // On nettoie le tampon pour éviter tout texte parasite avant le JSON
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');

        // On récupère l'ID soit dans l'URL (?id=...), soit dans le corps de la requête
        $identifiant = 0;
        if (isset($_GET['id'])) {
            $identifiant = (int)$_GET['id'];
        } else {
            // Au cas où le JS envoie du JSON au lieu d'un paramètre d'URL
            $donnees = json_decode(file_get_contents("php://input"), true);
            $identifiant = isset($donnees['id']) ? (int)$donnees['id'] : 0;
        }

        if ($identifiant <= 0) {
            echo json_encode(["succes" => false, "message" => "ID de groupe invalide ou manquant."]);
            exit;
        }

        try {
            $resultat = $this->depot->supprimerParId($identifiant);
            echo json_encode(["succes" => $resultat, "message" => $resultat ? "Groupe supprimé" : "Erreur SQL lors de la suppression"]);
        } catch (Throwable $e) {
            echo json_encode(["succes" => false, "message" => $e->getMessage()]);
        }
        exit;
    }

    public function noter()
    {
        $this->requireRole('administrateur');
        $donneesBrutes = file_get_contents("php://input");
        $donnees = json_decode($donneesBrutes, true);
        $idGroupe = isset($donnees['id_groupe']) ? (int) $donnees['id_groupe'] : 0;
        $noteDossier = isset($donnees['note_dossier']) ? (float) $donnees['note_dossier'] : -1;

        if ($idGroupe === 0 || $noteDossier < 0 || $noteDossier > 20) {
            $this->json(array("succes" => false, "message" => "Donnees de note invalides."), 400);
            return;
        }

        $resultat = $this->depot->mettreAJourNote($idGroupe, $noteDossier);
        if ($resultat === true) {
            $this->json(array("succes" => true, "message" => "Note mise à jour."));
        } else {
            $this->json(array("succes" => false, "message" => "Erreur SQL."));
        }
    }

    public function assignerCandidats()
    {
        $this->requireRole('administrateur');
        if (!$this->serviceAssignation) {
            $this->json(array("succes" => false, "message" => "Service d'assignation non disponible."), 500);
            return;
        }

        $donneesBrutes = file_get_contents("php://input");
        $donnees = json_decode($donneesBrutes, true);
        $idGroupe = isset($donnees['id_groupe']) ? (int) $donnees['id_groupe'] : 0;
        $criteres = isset($donnees['criteres']) ? $donnees['criteres'] : array();

        if ($idGroupe === 0) {
            $this->json(array("succes" => false, "message" => "ID groupe manquant."), 400);
            return;
        }

        $nombre = $this->serviceAssignation->assignerCandidatsAuGroupe($idGroupe, $criteres);
        $this->json(array("succes" => true, "nombre_assigne" => $nombre));
    }

    public function obtenirCandidatsGroupeAvecNotes()
    {
        if (!$this->serviceAssignation) {
            $this->json(array("succes" => false, "message" => "Service non disponible."), 500);
            return;
        }

        $idGroupe = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($idGroupe === 0) {
            $this->json(array("succes" => false, "message" => "ID manquant."), 400);
           return;
        }

        $candidats = $this->serviceAssignation->obtenirCandidatsGroupeAvecNotes($idGroupe);
        $this->json($candidats);
    }
}