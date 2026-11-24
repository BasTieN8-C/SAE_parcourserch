<?php
require_once __DIR__ . '/../repositories/CandidatRepository.php';

class ControleurImport
{
    private array $dictionnaireCoordonnees;
    private CandidatRepository $depotCandidat;

    public function __construct()
    {
        $this->depotCandidat = new CandidatRepository();
        $this->dictionnaireCoordonnees = array(
            "76" => array("lat" => 49.65, "lng" => 1.05),
            "27" => array("lat" => 49.02, "lng" => 1.15),
            "14" => array("lat" => 49.18, "lng" => -0.37),
            "50" => array("lat" => 49.11, "lng" => -1.09),
            "61" => array("lat" => 48.43, "lng" => 0.09)
        );
    }

    public function traiterFichierEnvoi()
    {
        header('Content-Type: application/json');

        $donneesBrutes = file_get_contents("php://input");
        $candidatsRecus = json_decode($donneesBrutes, true);

        if (!$candidatsRecus || !is_array($candidatsRecus))
        {
            echo json_encode(array("succes" => false, "message" => "Aucune donnee valide n'a ete recue."));
            return;
        }

        $candidatsAInserer = array();

        foreach ($candidatsRecus as $candidat)
        {
            $departement = isset($candidat['departement']) ? $candidat['departement'] : '00';
            
            $latitude = 46.22;
            $longitude = 2.21;

            if (isset($candidat['lat']) && isset($candidat['lng']))
            {
                $latitude = floatval($candidat['lat']);
                $longitude = floatval($candidat['lng']);
            }
            elseif (isset($this->dictionnaireCoordonnees[$departement]))
            {
                $latitude = $this->dictionnaireCoordonnees[$departement]['lat'] + (mt_rand(-10, 10) / 100);
                $longitude = $this->dictionnaireCoordonnees[$departement]['lng'] + (mt_rand(-10, 10) / 100);
            }

            $candidatsAInserer[] = array(
                "numero_dossier" => $candidat['numero_dossier'] ?? '0',
                "genre" => $candidat['genre'] ?? 'M',
                "departement" => $departement,
                "ville_lycee" => $candidat['ville_lycee'] ?? 'Inconnue',
                "type_bac" => $candidat['type_bac'] ?? 'General',
                "specialite" => $candidat['specialite'] ?? 'Autre',
                "profil_candidat" => $candidat['profil_candidat'] ?? 'Général',
                "lat" => $latitude,
                "lng" => $longitude,
                "note_lycee" => isset($candidat['note_lycee']) ? floatval($candidat['note_lycee']) : null,
                "note_avenir" => isset($candidat['note_avenir']) ? floatval($candidat['note_avenir']) : null,
                "note_dossier" => isset($candidat['note_dossier']) ? floatval($candidat['note_dossier']) : null,
                "date_inscription" => $candidat['date_inscription'] ?? null
            );
        }

        $this->depotCandidat->vider();
        $resultat = $this->depotCandidat->insererPlusieurs($candidatsAInserer);

        if ($resultat === true)
        {
            echo json_encode(array("succes" => true, "message" => count($candidatsAInserer) . " candidats importes et geolocalises avec succes."));
        }
        else
        {
            $messageErreur = is_string($resultat) ? "Erreur SQL : " . $resultat : "Erreur inconnue.";
            echo json_encode(array("succes" => false, "message" => $messageErreur));
        }
    }
}
?>