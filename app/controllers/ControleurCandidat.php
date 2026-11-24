<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../repositories/CandidatRepository.php';
require_once __DIR__ . '/../services/ServiceGeolocalisation.php';

class ControleurCandidat extends Controller
{
    private CandidatRepository $depot;
    private ServiceGeolocalisation $serviceGeoloc;

    public function __construct()
    {
        $this->depot = new CandidatRepository();
        $this->serviceGeoloc = new ServiceGeolocalisation();
    }

    public function lister()
    {
        $entites = $this->depot->trouverTous();
        $donneesTableau = array();

        foreach ($entites as $entite)
        {
            $donneesTableau[] = $entite->enTableau();
        }

        $this->json($donneesTableau);
    }

    public function listerAvecNotesFinales()
    {
        require_once __DIR__ . '/../services/ServiceEvaluerDossier.php';
        
        $entites = $this->depot->trouverTous();
        $serviceEval = class_exists('ServiceEvaluationDossier') ? new ServiceEvaluationDossier() : null;
        $donneesTableau = array();

        foreach ($entites as $entite)
        {
            $donnees = $entite->enTableau();
            
            if ($serviceEval)
            {
                $noteLycee = $entite->obtenirNoteLycee() ?? 10.0;
                $noteAvenir = $entite->obtenirNoteAvenir() ?? 10.0;
                $noteDossier = $donnees['groupe_id'] ? 12.0 : 10.0; // Note fictive si groupe assigné
                
                $donnees['note_globale'] = $serviceEval->calculerNoteGlobale(
                    (float)$noteLycee,
                    (float)$noteAvenir,
                    (float)$noteDossier
                );
            }
            
            $donneesTableau[] = $donnees;
        }

        $this->json($donneesTableau);
    }

    public function importer()
    {
        $this->requireRole('administrateur');
        // Augmenter le timeout pour l'import (appels API data.gouv.fr)
        set_time_limit(120);
        
        $donneesBrutes = file_get_contents("php://input");
        $candidatsRecus = json_decode($donneesBrutes, true);

        if (!$candidatsRecus || !is_array($candidatsRecus))
        {
            $this->json(array("succes" => false, "message" => "Aucune donnee valide."), 400);
        }

        $candidatsAvecGeoloc = array();

        foreach ($candidatsRecus as $candidat) 
        {
            $ville = isset($candidat['ville_lycee']) ? trim($candidat['ville_lycee']) : 'Inconnue';
            $codePostal = isset($candidat['code_postal']) ? strval(trim($candidat['code_postal'])) : '76000';
            
            // S'assurer que le code postal a bien 5 chiffres
            $codePostal = str_pad($codePostal, 5, '0', STR_PAD_LEFT);
            
            // Extraire le département correctement (vérifier d'abord 3 chiffres pour outre-mer)
            $departement = '76'; // Défaut
            
            if (in_array(substr($codePostal, 0, 3), array('971', '972', '973', '974', '976'))) {
                $departement = substr($codePostal, 0, 3);
                error_log('[IMPORT] Outre-mer détecté: CP=' . $codePostal . ' → dept=' . $departement);
            } else {
                $departement = substr($codePostal, 0, 2);
            }
            
            if (isset($candidat['departement']) && !empty($candidat['departement'])) {
                $departement = $candidat['departement'];
            }
            
            // Utiliser le service pour obtenir les coordonnées via l'API data.gouv.fr
            error_log('[IMPORT] Candidat: ville="' . $ville . '" CP=' . $codePostal . ' dept=' . $departement);
            $coords = $this->serviceGeoloc->obtenirCoordonneesAvecFallback($ville, $codePostal, $departement);
            
            $candidat['lat'] = $coords['lat'];
            $candidat['lng'] = $coords['lng'];
            
            $candidatsAvecGeoloc[] = $candidat;
        }

        $vidage = $this->depot->vider();
        
        if ($vidage !== true) 
        {
            $this->json(array("succes" => false, "message" => $vidage), 500);
        }
        
        // On insère le tableau qui contient maintenant lat et lng
        $resultat = $this->depot->insererPlusieurs($candidatsAvecGeoloc);

        if ($resultat === true)
        {
            $this->json(array("succes" => true, "message" => count($candidatsAvecGeoloc) . " dossiers importés et géolocalisés."));
        }
        else
        {
            $this->json(array("succes" => false, "message" => $resultat));
        }
    }

    public function exporter()
    {
        $this->requireRole('administrateur');
        require_once __DIR__ . '/../services/ServiceExportation.php';
        
        $serviceExport = new ServiceExportation();
        $csvContent = $serviceExport->exporterCandidatsCsv();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="candidats_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo "\xEF\xBB\xBF"; // BOM UTF-8 pour Excel
        echo $csvContent;
    }

    
}
?>