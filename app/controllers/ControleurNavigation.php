<?php
require_once __DIR__ . '/../core/Controller.php';

class ControleurNavigation extends Controller
{
    public function afficherAccueil()
    {
        $this->view('accueil', 'Accueil - SAE S4.01');
    }

    public function afficherImport()
    {
        $this->requireRole('administrateur');
        $this->view('import', 'Import des donnees');
    }

    public function afficherTableauDeBord()
    {
        $limite = 50;

        if (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0)
        {
            $pageActuelle = (int) $_GET['page'];
        }
        else
        {
            $pageActuelle = 1;
        }

        $offset = ($pageActuelle - 1) * $limite;

        require_once __DIR__ . '/../repositories/CandidatRepository.php';
        $repository = new CandidatRepository();

        // On récupère toutes les données (Candidats + Statistiques)
        $candidats = $repository->listerCandidatsParPage($limite, $offset);
        $totalCandidats = $repository->compterTotalCandidats();
        $totalBacGeneral = $repository->compterBacsGeneraux();
        $totalBacTechno = $repository->compterBacsTechno();

        $totalPages = $totalCandidats > 0 ? (int) ceil($totalCandidats / $limite) : 1;

        $donnees = array(
            'candidats' => $candidats,
            'pageActuelle' => $pageActuelle,
            'totalPages' => $totalPages,
            'totalCandidats' => $totalCandidats,
            'totalBacGeneral' => $totalBacGeneral,
            'totalBacTechno' => $totalBacTechno
        );

        $this->view('tableau_bord', 'Tableau de bord', $donnees);
    }

    public function afficherCarte()
    {
        $this->view('carte', 'Carte Interactive');
    }

    public function afficherGroupes()
    {
        $this->requireRole('administrateur');
        $this->view('groupes', 'Gestion des Groupes');
    }
}
?>