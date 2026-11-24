<?php
require_once __DIR__ . '/../core/Repository.php';
require_once __DIR__ . '/../entities/GroupeEntity.php';

class GroupeRepository
{
    private PDO $pdo;

    public function __construct()
    {
        // Utilisation du Singleton selon le cahier des charges
        $this->pdo = Repository::getInstance()->getPDO();
    }

    private function creerEntiteDepuisLigne(array $ligne): GroupeEntity
    {
        $criteresDecode = json_decode($ligne['criteres'], true);
        
        if (!is_array($criteresDecode))
        {
            $criteresDecode = array();
        }

        return new GroupeEntity(
            (int) $ligne['id'],
            $ligne['nom'],
            $ligne['couleur'],
            $criteresDecode,
            isset($ligne['note_groupe']) ? (float) $ligne['note_groupe'] : null
        );
    }

    public function trouverTous(): array
    {
        try
        {
            $stmt = $this->pdo->query("
                SELECT 
                    g.id,
                    g.nom,
                    g.couleur,
                    g.criteres,
                    g.note_groupe,
                    COUNT(c.id) as nb_candidats
                FROM groupes g
                LEFT JOIN candidats c ON c.groupe_id = g.id
                GROUP BY g.id, g.nom, g.couleur, g.criteres, g.note_groupe
                ORDER BY g.id DESC
            ");
            if (!$stmt) return [];
            
            $groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($groupes as &$g)
            {
                if (!empty($g['criteres']))
                {
                    // On s'assure que le JSON en base est décodé en tableau PHP
                    $decoded = json_decode($g['criteres'], true);
                    $g['criteres'] = is_array($decoded) ? $decoded : [];
                }
                else
                {
                    $g['criteres'] = [];
                }
                // Convertir le count en entier
                $g['nb_candidats'] = (int)$g['nb_candidats'];
            }
            return $groupes;
        }
        catch (PDOException $e)
        {
            error_log("Erreur GroupeRepository::trouverTous(): " . $e->getMessage());
            return [];
        }
    }

    public function ajouter(string $nom, string $couleur, array $criteres)
    {
        try {
            $requetePreparee = $this->pdo->prepare('INSERT INTO groupes (nom, couleur, criteres) VALUES (:nom, :couleur, :criteres)');
            $success = $requetePreparee->execute(array(
                'nom' => $nom,
                'couleur' => $couleur,
                'criteres' => json_encode($criteres)
            ));
            
            return $success ? $this->pdo->lastInsertId() : false; // Renvoie l'ID !
        } catch (PDOException $erreur) {
            return false;
        }
    }

    public function supprimerParId(int $identifiant): bool
    {
        try
        {
            $requetePreparee = $this->pdo->prepare('DELETE FROM groupes WHERE id = :id');
            
            return $requetePreparee->execute(array('id' => $identifiant));
        }
        catch (PDOException $erreur)
        {
            return false;
        }
    }

    public function trouverParId(int $id): ?GroupeEntity
    {
        try
        {
            $requetePreparee = $this->pdo->prepare('SELECT * FROM groupes WHERE id = :id');
            $requetePreparee->execute(array('id' => $id));
            $ligne = $requetePreparee->fetch(PDO::FETCH_ASSOC);
            
            if ($ligne)
            {
                return $this->creerEntiteDepuisLigne($ligne);
            }
            return null;
        }
        catch (PDOException $erreur)
        {
            return null;
        }
    }

    public function mettreAJourNote(int $id, float $noteGroupe): bool
    {
        try
        {
            // 1. Mettre à jour la note du groupe
            $requeteGroupes = $this->pdo->prepare('UPDATE groupes SET note_groupe = :note WHERE id = :id');
            $succesGroupes = $requeteGroupes->execute(array(
                'note' => $noteGroupe,
                'id' => $id
            ));

            // 2. Mettre à jour la note_dossier de tous les candidats du groupe
            $requeteCandidats = $this->pdo->prepare('UPDATE candidats SET note_dossier = :note WHERE groupe_id = :id');
            $succesCandidats = $requeteCandidats->execute(array(
                'note' => $noteGroupe,
                'id' => $id
            ));

            return $succesGroupes && $succesCandidats;
        }
        catch (PDOException $erreur)
        {
            error_log("Erreur SQL : " . $erreur->getMessage()); 
            return false;
        }
    }
}
?>