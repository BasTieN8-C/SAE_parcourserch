<?php
require_once __DIR__ . '/../repositories/CandidatRepository.php';

class ControleurCarte
{
    private CandidatRepository $repositoryCandidat;

    public function __construct()
    {
        $this->repositoryCandidat = new CandidatRepository();
    }

    public function renvoyerCandidats()
    {
        header('Content-Type: application/json');
        
        $candidats = $this->repositoryCandidat->trouverTous();
        
        // Convertir les entités en tableaux pour JSON
        $candidatsJson = array_map(function($candidat) {
            return $candidat->enTableau();
        }, $candidats);
        
        echo json_encode($candidatsJson);
    }

    public function obtenirCandidatsJson()
    {
        header('Content-Type: application/json');
        
        $candidats = $this->repositoryCandidat->trouverTous();
        
        // Convertir les entités en tableaux pour JSON
        $candidatsJson = array_map(function($candidat) {
            return $candidat->enTableau();
        }, $candidats);
        
        echo json_encode($candidatsJson);
    }
}
?>