<?php
require_once __DIR__ . '/../repositories/CandidatRepository.php';
require_once __DIR__ . '/../repositories/GroupeRepository.php';
require_once __DIR__ . '/ServiceEvaluerDossier.php'; 

class ServiceAssignationGroupes
{
    private CandidatRepository $depotCandidat;
    private GroupeRepository $depotGroupe;
    private $serviceEvaluation; 

    public function __construct()
    {
        $this->depotCandidat = new CandidatRepository();
        $this->depotGroupe = new GroupeRepository();
        
        if (class_exists('ServiceEvaluationDossier')) 
        {
            $this->serviceEvaluation = new ServiceEvaluationDossier();
        } 
        else 
        {
            $this->serviceEvaluation = null; 
        }
    }

    public function assignerCandidatsAuGroupe(int $groupeId, array $criteres): int
    {
        try 
        {
            $criteresValides = is_array($criteres) ? $criteres : array();
            return $this->depotCandidat->affecterCandidatsAuGroupeParCriteres($groupeId, $criteresValides);
        } 
        catch (Throwable $erreur) 
        {
            error_log("Erreur Assignation: " . $erreur->getMessage());
            return 0;
        }
    }

    public function obtenirCandidatsGroupeAvecNotes(int $groupeId): array
    {
        $groupe = $this->depotGroupe->trouverParId($groupeId);
        if ($groupe === null) 
        {
            return array();
        }

        $candidats = $this->depotCandidat->trouverCandidatsParGroupe($groupeId);
        
        // Sécurisation vitale : Si aucune note n'est attribuée, on passe 10 par défaut.
        $noteDossier = $groupe->obtenirNoteGroupe() ?? 10.0; 

        $candidatsEvalues = array();

        foreach ($candidats as $candidat)
        {
            $tableauCandidat = $candidat->enTableau();

            if ($this->serviceEvaluation !== null)
            {
                $noteLycee = $candidat->obtenirNoteLycee() ?? 10.0;
                $noteAvenir = $candidat->obtenirNoteAvenir() ?? 10.0;

                $tableauCandidat['note_groupe'] = $noteDossier;
                $tableauCandidat['note_globale'] = $this->serviceEvaluation->calculerNoteGlobale(
                    (float)$noteLycee,
                    (float)$noteAvenir,
                    (float)$noteDossier
                );
            } 
            else 
            {
                $tableauCandidat['note_groupe'] = $noteDossier;
                $tableauCandidat['note_globale'] = null;
            }

            $candidatsEvalues[] = $tableauCandidat;
        }

        return $candidatsEvalues;
    }

    public function reinitialiserAssignations(): bool
    {
        try
        {
            $candidats = $this->depotCandidat->trouverTous();
            foreach ($candidats as $candidat)
            {
                $this->depotCandidat->affecterCandidatAuGroupe($candidat->obtenirId(), null);
            }
            return true;
        }
        catch (Exception $erreur)
        {
            return false;
        }
    }
}
?>