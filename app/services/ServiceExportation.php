<?php
require_once __DIR__ . '/../repositories/CandidatRepository.php';

class ServiceExportation
{
    private CandidatRepository $depotCandidat;

    public function __construct()
    {
        $this->depotCandidat = new CandidatRepository();
    }

    public function exporterCandidatsCsv(): string
    {
        $candidats = $this->depotCandidat->trouverTous();
        
        if (empty($candidats))
        {
            return "";
        }

        $fichier = fopen('php://memory', 'r+');
        if ($fichier === false) {
            return "";
        }

        $entetes = [
            "Numéro Dossier",
            "Genre",
            "Département",
            "Ville Lycée",
            "Type Bac",
            "Spécialité",
            "Profil",
            "Note Lycée",
            "Note Avenir",
            "Note Dossier",
            "Moyenne Générale",
            "Groupe",
            "Date Inscription"
        ];
        fputcsv($fichier, $entetes, ';', '"', '\\');

        foreach ($candidats as $candidat)
        {
            $donnees = $candidat->enTableau();
            
            // Formater les notes: afficher 'N/C' (Non Communiqué) si null, sinon arrondir à 2 décimales
            $noteLycee = $donnees['note_lycee'] !== null ? number_format($donnees['note_lycee'], 2, ',', '') : 'N/C';
            $noteAvenir = $donnees['note_avenir'] !== null ? number_format($donnees['note_avenir'], 2, ',', '') : 'N/C';
            $noteDossier = $donnees['note_dossier'] !== null ? number_format($donnees['note_dossier'], 2, ',', '') : 'N/C';
            $noteMoyenne = $donnees['note_finale'] !== null ? number_format($donnees['note_finale'], 2, ',', '') : 'N/C';

            $ligne = [
                $donnees['numero_dossier'],
                $donnees['genre'],
                $donnees['departement'],
                $donnees['ville_lycee'],
                $donnees['type_bac'],
                $donnees['specialite'],
                $donnees['profil_candidat'],
                $noteLycee,
                $noteAvenir,
                $noteDossier,
                $noteMoyenne,
                $donnees['groupe_id'] ?? '',
                $donnees['date_inscription'] ?? ''
            ];
            fputcsv($fichier, $ligne, ';', '"', '\\');
        }

        rewind($fichier);
        $contenu = stream_get_contents($fichier);
        fclose($fichier);

        return $contenu !== false ? $contenu : "";
    }
}