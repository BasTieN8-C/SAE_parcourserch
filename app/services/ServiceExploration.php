<?php

class ServiceExploration
{
    private $depotCandidat;
    private $depotGroupe;

    public function __construct()
    {
        require_once __DIR__ . '/../repositories/CandidatRepository.php';
        require_once __DIR__ . '/../repositories/GroupeRepository.php';
        
        $this->depotCandidat = new CandidatRepository();
        $this->depotGroupe = new GroupeRepository();
    }

    /**
     * Explore les candidats et les groupe selon les critères
     * @param array $criteres Les critères de regroupement
     * @return array Les candidats groupés et statistiques
     */
    public function explorerCandidatsParCriteres(array $criteres): array
    {
        $candidats = $this->depotCandidat->trouverCandidatsCorrespondantAuxCriteres($criteres);
        
        return array(
            'nombre' => count($candidats),
            'candidats' => array_map(function($c) { return $c->enTableau(); }, $candidats)
        );
    }

    /**
     * Génère des statistiques sur les groupes et les candidats
     * @return array Les statistiques
     */
    public function genererStatistiques(): array
    {
        $candidats = $this->depotCandidat->trouverTous();
        $groupes = $this->depotGroupe->trouverTous();

        $totalCandidats = count($candidats);
        $candidatsAssignes = 0;
        $candidatsSansGroupe = 0;

        foreach ($candidats as $candidat)
        {
            if ($candidat->obtenirGroupeId() !== null)
            {
                $candidatsAssignes++;
            }
            else
            {
                $candidatsSansGroupe++;
            }
        }

        return array(
            'total_candidats' => $totalCandidats,
            'candidats_assignes' => $candidatsAssignes,
            'candidats_sans_groupe' => $candidatsSansGroupe,
            'nombre_groupes' => count($groupes),
            'groupes' => array_map(function($g) { return $g->enTableau(); }, $groupes)
        );
    }

    /**
     * Explore les types de bac présents dans la base
     * @return array Statistiques par type de bac
     */
    public function explorerTypeBac(): array
    {
        $candidats = $this->depotCandidat->trouverTous();
        $typesBac = array();

        foreach ($candidats as $candidat)
        {
            $tableau = $candidat->enTableau();
            $type = $tableau['type_bac'];

            if (!isset($typesBac[$type]))
            {
                $typesBac[$type] = 0;
            }
            $typesBac[$type]++;
        }

        return $typesBac;
    }

    /**
     * Explore les spécialités présentes dans la base
     * @return array Statistiques par spécialité
     */
    public function explorerSpecialites(): array
    {
        $candidats = $this->depotCandidat->trouverTous();
        $specialites = array();

        foreach ($candidats as $candidat)
        {
            $tableau = $candidat->enTableau();
            $spec = $tableau['specialite'];

            if (!isset($specialites[$spec]))
            {
                $specialites[$spec] = 0;
            }
            $specialites[$spec]++;
        }

        return $specialites;
    }
}
?>
