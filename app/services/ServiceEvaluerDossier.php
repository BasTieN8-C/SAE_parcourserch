<?php

class ServiceEvaluationDossier
{
    private float $poidsLycee;
    private float $poidsAvenir;
    private float $poidsDossier;

    public function __construct()
    {
        $this->poidsLycee = 2 / 11;
        $this->poidsAvenir = 1 / 11;
        $this->poidsDossier = 8 / 11;
    }

    public function calculerNoteGlobale(float $noteLycee, float $noteFicheAvenir, float $noteDossier): float
    {
        $scoreLycee = $noteLycee * $this->poidsLycee;
        $scoreAvenir = $noteFicheAvenir * $this->poidsAvenir;
        $scoreDossier = $noteDossier * $this->poidsDossier;

        $noteFinale = $scoreLycee + $scoreAvenir + $scoreDossier;

        return round($noteFinale, 2);
    }

    public function evaluerGroupeDeCandidats(array $candidats, float $noteDossierAppliquee): array
    {
        $candidatsEvalues = array();

        foreach ($candidats as $candidat)
        {
            if (is_object($candidat))
            {
                $noteLycee = $candidat->obtenirNoteLycee() ?? 10.0;
                $noteAvenir = $candidat->obtenirNoteAvenir() ?? 10.0;
                
                $candidatTableau = $candidat->enTableau();
            }
            else
            {
                $noteLycee = isset($candidat['note_lycee']) ? floatval($candidat['note_lycee']) : 10.0;
                $noteAvenir = isset($candidat['note_avenir']) ? floatval($candidat['note_avenir']) : 10.0;
                
                $candidatTableau = $candidat;
            }

            $noteGlobale = $this->calculerNoteGlobale($noteLycee, $noteAvenir, $noteDossierAppliquee);

            $candidatTableau['note_dossier'] = $noteDossierAppliquee;
            $candidatTableau['note_globale'] = $noteGlobale;

            $candidatsEvalues[] = $candidatTableau;
        }

        return $candidatsEvalues;
    }

    public function evaluerCandidat(float $noteLycee, float $noteAvenir, float $noteDossier): array
    {
        $noteGlobale = $this->calculerNoteGlobale($noteLycee, $noteAvenir, $noteDossier);

        return array(
            'note_lycee' => round($noteLycee, 2),
            'note_avenir' => round($noteAvenir, 2),
            'note_dossier' => round($noteDossier, 2),
            'note_globale' => $noteGlobale
        );
    }

    public function obtenirPoids(): array
    {
        return array(
            'lycee' => $this->poidsLycee,
            'avenir' => $this->poidsAvenir,
            'dossier' => $this->poidsDossier
        );
    }
}
?>