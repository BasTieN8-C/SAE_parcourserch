<?php
class GroupeEntity
{
    private int $id;
    private string $nom;
    private string $couleur;
    private array $criteres;
    private ?float $noteGroupe;

    public function __construct(int $id, string $nom, string $couleur, array $criteres, ?float $noteGroupe = null)
    {
        $this->id = $id;
        $this->nom = $nom;
        $this->couleur = $couleur;
        $this->criteres = $criteres;
        $this->noteGroupe = $noteGroupe;
    }

    public function obtenirId(): int
    {
        return $this->id;
    }

    public function obtenirNom(): string
    {
        return $this->nom;
    }

    public function obtenirCouleur(): string
    {
        return $this->couleur;
    }

    public function obtenirCriteres(): array
    {
        return $this->criteres;
    }

    public function obtenirNoteGroupe(): ?float
    {
        return $this->noteGroupe;
    }

    public function definirNoteGroupe(?float $noteGroupe): void
    {
        $this->noteGroupe = $noteGroupe;
    }

    // Fonction utilitaire pour transformer l'objet en tableau pour l'envoi JSON
    public function enTableau(): array
    {
        return array(
            'id' => $this->id,
            'nom' => $this->nom,
            'couleur' => $this->couleur,
            'criteres' => $this->criteres,
            'note_groupe' => $this->noteGroupe
        );
    }
}
?>