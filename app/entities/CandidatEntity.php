<?php
class CandidatEntity
{
    private int $id;
    private string $numero_dossier;
    private string $genre;
    private string $departement;
    private string $ville_lycee;
    private string $type_bac;
    private string $specialite;
    private string $profil_candidat;
    private ?float $lat;
    private ?float $lng;
    private ?float $note_lycee;
    private ?float $note_avenir;
    private ?float $note_dossier;
    private ?string $date_inscription;
    private ?int $groupe_id;

    public function __construct(int $id, string $numero_dossier, string $genre, string $departement, string $ville_lycee, string $type_bac, string $specialite, string $profil_candidat, ?float $lat = null, ?float $lng = null, ?float $note_lycee = null, ?float $note_avenir = null, ?float $note_dossier = null, ?string $date_inscription = null, ?int $groupe_id = null)
    {
        $this->id = $id;
        $this->numero_dossier = $numero_dossier;
        $this->genre = $genre;
        $this->departement = $departement;
        $this->ville_lycee = $ville_lycee;
        $this->type_bac = $type_bac;
        $this->specialite = $specialite;
        $this->profil_candidat = $profil_candidat;
        $this->lat = $lat ?? 46.22;
        $this->lng = $lng ?? 2.21;
        $this->note_lycee = $note_lycee;
        $this->note_avenir = $note_avenir;
        $this->note_dossier = $note_dossier;
        $this->date_inscription = $date_inscription;
        $this->groupe_id = $groupe_id;
    }

    public function obtenirId(): int
    {
        return $this->id;
    }

    public function obtenirNumDossier(): string
    {
        return $this->numero_dossier;
    }

    public function obtenirProfilCandidat(): string
    {
        return $this->profil_candidat;
    }

    public function obtenirNoteLycee(): ?float
    {
        return $this->note_lycee;
    }

    public function obtenirNoteAvenir(): ?float
    {
        return $this->note_avenir;
    }

    public function obtenirNoteDossier(): ?float
    {
        return $this->note_dossier;
    }

    public function obtenirDateInscription(): ?string
    {
        return $this->date_inscription;
    }

    public function obtenirGroupeId(): ?int
    {
        return $this->groupe_id;
    }

    public function definirGroupeId(?int $groupe_id): void
    {
        $this->groupe_id = $groupe_id;
    }

    public function obtenirLat(): ?float
    {
        return $this->lat;
    }

    public function obtenirLng(): ?float
    {
        return $this->lng;
    }

    /**
     * Calcule la note finale avec coefficients:
     * - 2/11 Note Lycée
     * - 1/11 Note Fiche Avenir
     * - 8/11 Note Dossier (prépondérante)
     * 
     * Retourne null si une des trois notes est manquante
     */
    public function calculerNoteFinale(): ?float
    {
        // On ne peut pas calculer de moyenne si le dossier n'a pas été évalué par le groupe
        if ($this->note_dossier === null) {
            return null;
        }

        // Si la note lycée ou avenir manque (ex: réorientés), on met 10 par défaut
        $noteLyceeVal = $this->note_lycee ?? 10.0;
        $noteAvenirVal = $this->note_avenir ?? 10.0;

        $moyenne = ($noteLyceeVal * (2/11)) + ($noteAvenirVal * (1/11)) + ($this->note_dossier * (8/11));
        
        return round($moyenne, 2);
    }

    public function enTableau(): array
    {
        return array(
            'id' => $this->id,
            'numero_dossier' => $this->numero_dossier,
            'genre' => $this->genre,
            'departement' => $this->departement,
            'ville_lycee' => $this->ville_lycee,
            'type_bac' => $this->type_bac,
            'specialite' => $this->specialite,
            'profil_candidat' => $this->profil_candidat,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'note_lycee' => $this->note_lycee,
            'note_avenir' => $this->note_avenir,
            'note_dossier' => $this->note_dossier,
            'date_inscription' => $this->date_inscription,
            'note_finale' => $this->calculerNoteFinale(),
            'groupe_id' => $this->groupe_id
        );
    }
}
?>