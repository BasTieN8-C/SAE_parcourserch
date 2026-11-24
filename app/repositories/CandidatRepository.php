<?php
require_once __DIR__ . '/../core/Repository.php';
require_once __DIR__ . '/../entities/CandidatEntity.php';

class CandidatRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Repository::getInstance()->getPDO();
    }

    public function trouverTous(): array
    {
        // SELECT dans l'ordre PHYSIQUE réel de la table SQL:
        // id, numero_dossier, genre, departement, ville_lycee, specialite, type_bac, profil_candidat, lat, lng, note_lycee, note_avenir, note_dossier, date_inscription, groupe_id
        $requete = $this->pdo->query('
            SELECT 
                id, numero_dossier, genre, departement, ville_lycee,
                specialite, type_bac, profil_candidat, lat, lng,
                note_lycee, note_avenir, note_dossier, date_inscription, groupe_id
            FROM candidats 
            ORDER BY id ASC
        ');
        $elements = array();
        $debugFirst = true;

        while ($ligne = $requete->fetch(PDO::FETCH_ASSOC))
        {
            $specialiteBd = isset($ligne['specialite']) ? $ligne['specialite'] : 'Autre';
            
            // DEBUG: Afficher les paramètres pour le premier candidat
            if ($debugFirst) {
                error_log("[CANDIDAT REPO DEBUG] Paramètres passés au constructeur:");
                error_log("  1. id = " . (int) $ligne['id']);
                error_log("  2. numero_dossier = " . $ligne['numero_dossier']);
                error_log("  3. genre = " . $ligne['genre']);
                error_log("  4. departement = " . $ligne['departement']);
                error_log("  5. ville_lycee = " . $ligne['ville_lycee']);
                error_log("  6. type_bac = " . $ligne['type_bac']);
                error_log("  7. specialite = " . $specialiteBd);
                error_log("  8. profil_candidat = " . ($ligne['profil_candidat'] ?? 'Général'));
                error_log("  9. lat = " . (isset($ligne['lat']) ? (float) $ligne['lat'] : 'null'));
                error_log("  10. lng = " . (isset($ligne['lng']) ? (float) $ligne['lng'] : 'null'));
                error_log("  11. note_lycee = " . (isset($ligne['note_lycee']) ? (float) $ligne['note_lycee'] : 'null'));
                error_log("  12. note_avenir = " . (isset($ligne['note_avenir']) ? (float) $ligne['note_avenir'] : 'null'));
                error_log("  13. note_dossier = " . (isset($ligne['note_dossier']) ? (float) $ligne['note_dossier'] : 'null'));
                error_log("  14. date_inscription = " . ($ligne['date_inscription'] ?? 'null'));
                error_log("  15. groupe_id = " . (isset($ligne['groupe_id']) ? (int) $ligne['groupe_id'] : 'null'));
                $debugFirst = false;
            }
            
            // ⚠️ ATTENTION: L'ordre du constructeur doit correspondre à l'ordre du SELECT
            $elements[] = new CandidatEntity(
                (int) $ligne['id'],                                         // 1. id
                $ligne['numero_dossier'],                                   // 2. numero_dossier
                $ligne['genre'],                                            // 3. genre
                $ligne['departement'],                                       // 4. departement
                $ligne['ville_lycee'],                                       // 5. ville_lycee
                $ligne['type_bac'],                                         // 6. type_bac  
                $specialiteBd,                                              // 7. specialite  
                $ligne['profil_candidat'] ?? 'Général',                     // 8. profil_candidat
                isset($ligne['lat']) ? (float) $ligne['lat'] : null,         // 9. lat
                isset($ligne['lng']) ? (float) $ligne['lng'] : null,         // 10. lng
                isset($ligne['note_lycee']) ? (float) $ligne['note_lycee'] : null,      // 11. note_lycee
                isset($ligne['note_avenir']) ? (float) $ligne['note_avenir'] : null,    // 12. note_avenir
                isset($ligne['note_dossier']) ? (float) $ligne['note_dossier'] : null,  // 13. note_dossier
                $ligne['date_inscription'] ?? null,                         // 14. date_inscription
                isset($ligne['groupe_id']) ? (int) $ligne['groupe_id'] : null           // 15. groupe_id
            );
        }

        return $elements;
    }

    

    public function trouverCandidatsParGroupe(int $idGroupe): array
    {
        try 
        {
            $requete = $this->pdo->prepare('SELECT * FROM candidats WHERE groupe_id = :id');
            $requete->execute(array('id' => $idGroupe));
            
            $elements = array();
            while ($ligne = $requete->fetch(PDO::FETCH_ASSOC))
            {
                $elements[] = new CandidatEntity(
                    (int) $ligne['id'],
                    $ligne['numero_dossier'],
                    $ligne['genre'],
                    $ligne['departement'],
                    $ligne['ville_lycee'],
                    $ligne['type_bac'],
                    $ligne['specialite'] ?? 'Autre',
                    $ligne['profil_candidat'] ?? 'Général',
                    isset($ligne['lat']) ? (float) $ligne['lat'] : null,
                    isset($ligne['lng']) ? (float) $ligne['lng'] : null,
                    isset($ligne['note_lycee']) ? (float) $ligne['note_lycee'] : null,
                    isset($ligne['note_avenir']) ? (float) $ligne['note_avenir'] : null,
                    isset($ligne['note_dossier']) ? (float) $ligne['note_dossier'] : null,
                    $ligne['date_inscription'] ?? null,
                    (int) $ligne['groupe_id']
                );
            }
            return $elements;
        }
        catch (PDOException $erreur)
        {
            return array();
        }
    }

    public function affecterCandidatAuGroupe(int $idCandidat, ?int $idGroupe): bool
    {
        try 
        {
            $requete = $this->pdo->prepare('UPDATE candidats SET groupe_id = :gid WHERE id = :id');
            return $requete->execute(array(
                'gid' => $idGroupe,
                'id' => $idCandidat
            ));
        }
        catch (PDOException $erreur)
        {
            return false;
        }
    }

    public function affecterCandidatsAuGroupeParCriteres(int $groupeId, array $criteres): int
    {
        try 
        {
            // 1. On libère d'abord les candidats de CE groupe (utile si on modifie un groupe existant)
            $stmtVider = $this->pdo->prepare("UPDATE candidats SET groupe_id = NULL WHERE groupe_id = :groupe_id");
            $stmtVider->execute(array(':groupe_id' => $groupeId));
            
            // 2. On ne cible QUE les candidats libres (groupe_id IS NULL)
            $sql = "UPDATE candidats SET groupe_id = :id_groupe WHERE groupe_id IS NULL";
            $params = array(':id_groupe' => (int)$groupeId);

            // GESTION DU GENRE
            if (!empty($criteres['genre'])) 
            {
                $sql .= " AND genre = :genre";
                $params[':genre'] = trim($criteres['genre']);
            }

            // GESTION DES DEPARTEMENTS MULTIPLES
            if (!empty($criteres['departement'])) 
            {
                if (is_array($criteres['departement'])) 
                {
                    $conditionsDept = array();
                    $d = 0;
                    foreach ($criteres['departement'] as $dept) 
                    {
                        $cle = ":dept" . $d;
                        $conditionsDept[] = $cle;
                        $params[$cle] = trim($dept);
                        $d++;
                    }
                    $sql .= " AND departement IN (" . implode(", ", $conditionsDept) . ")";
                } 
                else 
                {
                    $sql .= " AND departement = :dept";
                    $params[':dept'] = trim($criteres['departement']);
                }
            }

            // GESTION DES BACS MULTIPLES (Correction : Utilisation de ILIKE)
            if (!empty($criteres['type_bac'])) 
            {
                if (is_array($criteres['type_bac'])) 
                {
                    $conditionsBac = array();
                    $b = 0;
                    foreach ($criteres['type_bac'] as $bac) 
                    {
                        $cle = ":bac" . $b;
                        $conditionsBac[] = "type_bac ILIKE " . $cle;
                        $params[$cle] = '%' . trim($bac) . '%';
                        $b++;
                    }
                    $sql .= " AND (" . implode(" OR ", $conditionsBac) . ")";
                } 
                else 
                {
                    $sql .= " AND type_bac ILIKE :bac";
                    $params[':bac'] = '%' . trim($criteres['type_bac']) . '%';
                }
            }

            // GESTION DES SPECIALITES MULTIPLES (Correction : Utilisation de ILIKE)
            if (!empty($criteres['specialites']) && is_array($criteres['specialites'])) 
            {
                $conditionsSpecialites = array();
                $s = 0;
                foreach ($criteres['specialites'] as $specialite) 
                {
                    if (!empty($specialite)) 
                    {
                        $cle = ":spe" . $s;
                        $conditionsSpecialites[] = "specialite ILIKE " . $cle;
                        $params[$cle] = '%' . trim($specialite) . '%';
                        $s++;
                    }
                }
                
                if (!empty($conditionsSpecialites)) 
                {
                    $sql .= " AND (" . implode(" OR ", $conditionsSpecialites) . ")";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount();
        } 
        catch (PDOException $e) 
        {
            error_log("Erreur PDO Affectation: " . $e->getMessage());
            return 0;
        }
    }

    public function vider(): string|bool
    {
        try
        {
            // TRUNCATE est plus efficace que DELETE et réinitialise les IDs
            $this->pdo->exec('TRUNCATE TABLE candidats RESTART IDENTITY CASCADE');
            return true;
        }
        catch (PDOException $erreur)
        {
            return "Erreur lors du nettoyage : " . $erreur->getMessage();
        }
    }

    public function insererPlusieurs(array $tableauCandidats): string|bool
    {
        try 
        {
            $this->pdo->beginTransaction();
            
            // INSERT avec TOUTES les colonnes : données de base + géolocalisation + notes + profil
            $requete = $this->pdo->prepare('INSERT INTO candidats (numero_dossier, genre, departement, ville_lycee, type_bac, specialite, profil_candidat, lat, lng, note_lycee, note_avenir, note_dossier, date_inscription) VALUES (:numero, :genre, :dep, :ville, :bac, :spe, :profil, :lat, :lng, :note_lycee, :note_avenir, :note_dossier, :date_inscription)');
            
            foreach ($tableauCandidats as $candidat)
            {
                $requete->execute(array(
                    'numero' => isset($candidat['numero_dossier']) ? $candidat['numero_dossier'] : '0',
                    'genre' => isset($candidat['genre']) ? $candidat['genre'] : 'M',
                    'dep' => isset($candidat['departement']) ? $candidat['departement'] : '00',
                    'ville' => isset($candidat['ville_lycee']) ? $candidat['ville_lycee'] : 'Inconnue',
                    'bac' => isset($candidat['type_bac']) ? $candidat['type_bac'] : 'General',
                    'spe' => isset($candidat['specialite']) ? $candidat['specialite'] : 'Autre',
                    'profil' => isset($candidat['profil_candidat']) ? $candidat['profil_candidat'] : 'Général',
                    'lat' => isset($candidat['lat']) ? $candidat['lat'] : 46.22,
                    'lng' => isset($candidat['lng']) ? $candidat['lng'] : 2.21,
                    'note_lycee' => isset($candidat['note_lycee']) ? floatval($candidat['note_lycee']) : null,
                    'note_avenir' => isset($candidat['note_avenir']) ? floatval($candidat['note_avenir']) : null,
                    'note_dossier' => isset($candidat['note_dossier']) ? floatval($candidat['note_dossier']) : null,
                    'date_inscription' => isset($candidat['date_inscription']) ? $candidat['date_inscription'] : null
                ));
            }
            
            $this->pdo->commit();
            return true;
        } 
        catch (PDOException $erreur) 
        {
            $this->pdo->rollBack();
            return $erreur->getMessage();
        }
    }

    public function recupererValeursUniques(string $colonne): array
    {
        $colonnesAutorisees = array('type_bac', 'specialite', 'departement', 'genre', 'profil_candidat');
        if (!in_array($colonne, $colonnesAutorisees)) return array();

        try {
            $requete = $this->pdo->query("SELECT DISTINCT $colonne FROM candidats WHERE $colonne IS NOT NULL ORDER BY $colonne ASC");
            return $requete ? $requete->fetchAll(PDO::FETCH_COLUMN) : array();
        } catch (PDOException $e) {
            return array();
        }
    }

    public function listerCandidatsParPage(int $limite, int $offset): array
    {
        $requete = $this->pdo->prepare('SELECT * FROM candidats ORDER BY id ASC LIMIT :limite OFFSET :offset');
        $requete->bindValue(':limite', $limite, PDO::PARAM_INT);
        $requete->bindValue(':offset', $offset, PDO::PARAM_INT);
        $requete->execute();

        $elements = array();
        while ($ligne = $requete->fetch(PDO::FETCH_ASSOC))
        {
            $elements[] = new CandidatEntity(
                (int) $ligne['id'],
                $ligne['numero_dossier'],
                $ligne['genre'],
                $ligne['departement'],
                $ligne['ville_lycee'],
                $ligne['type_bac'],
                $ligne['specialite'] ?? 'Autre',
                $ligne['profil_candidat'] ?? 'Général',
                isset($ligne['lat']) ? (float) $ligne['lat'] : null,
                isset($ligne['lng']) ? (float) $ligne['lng'] : null,
                isset($ligne['note_lycee']) ? (float) $ligne['note_lycee'] : null,
                isset($ligne['note_avenir']) ? (float) $ligne['note_avenir'] : null,
                isset($ligne['note_dossier']) ? (float) $ligne['note_dossier'] : null,
                $ligne['date_inscription'] ?? null,
                isset($ligne['groupe_id']) ? (int) $ligne['groupe_id'] : null
            );
        }

        return $elements;
    }

    public function compterTotalCandidats(): int
    {
        $requete = $this->pdo->query('SELECT COUNT(*) FROM candidats');
        return $requete ? (int) $requete->fetchColumn() : 0;
    }

    public function compterBacsGeneraux(): int
    {
        $requete = $this->pdo->query("SELECT COUNT(*) FROM candidats WHERE type_bac ILIKE '%general%'");
        return $requete ? (int) $requete->fetchColumn() : 0;
    }

    public function compterBacsTechno(): int
    {
        $requete = $this->pdo->query("SELECT COUNT(*) FROM candidats WHERE type_bac ILIKE '%techno%'");
        return $requete ? (int) $requete->fetchColumn() : 0;
    }

    public function recupererDepartementsUniques(): array
    {
        try
        {
            $requete = $this->pdo->query('SELECT DISTINCT departement FROM candidats WHERE departement IS NOT NULL AND departement != \'\' ORDER BY departement ASC');
            return $requete ? $requete->fetchAll(PDO::FETCH_COLUMN) : array();
        }
        catch (PDOException $erreur)
        {
            return array();
        }
    }

    /**
     * Met à jour la note_dossier pour tous les candidats d'un groupe
     * @param int $groupeId L'ID du groupe
     * @param float $noteDossier La note à attribuer (0-20)
     * @return int Nombre de candidats mis à jour, ou -1 en cas d'erreur
     */
    public function mettreAJourNoteDossierParGroupe(int $groupeId, float $noteDossier): int
    {
        try
        {
            $requete = $this->pdo->prepare('UPDATE candidats SET note_dossier = :note WHERE groupe_id = :groupe_id');
            $requete->execute(array(
                'note' => $noteDossier,
                'groupe_id' => $groupeId
            ));
            return $requete->rowCount();
        }
        catch (PDOException $erreur)
        {
            error_log("Erreur lors de la mise à jour des notes : " . $erreur->getMessage());
            return -1;
        }
    }
}