<?php
// app/scripts/geoloc_background.php
require_once __DIR__ . '/../core/Repository.php';

try 
{
    $pdo = Repository::getInstance()->getPDO();

    $requete = $pdo->query("SELECT DISTINCT ville_lycee FROM candidats WHERE lat IS NULL OR lat = 46.22 OR lat = 0");
    $villes = $requete->fetchAll(PDO::FETCH_COLUMN);

    foreach ($villes as $ville) 
    {
        if (empty($ville) || $ville === 'Non renseignee') continue;

        $url = 'https://api-adresse.data.gouv.fr/search/?q=' . urlencode($ville) . '&type=municipality&limit=1';
        
        // NOUVEAU : On force un délai maximum de 2 secondes pour ne jamais rester bloqué !
        $contexte = stream_context_create(array(
            "http" => array(
                "header" => "User-Agent: SAE-Parcoursup-Bot/1.0\r\n",
                "timeout" => 2 
            )
        ));
        
        $reponse = @file_get_contents($url, false, $contexte);
        
        if ($reponse) 
        {
            $donnees = json_decode($reponse, true);
            
            if (!empty($donnees['features'])) 
            {
                $coords = $donnees['features'][0]['geometry']['coordinates'];
                
                $miseAJour = $pdo->prepare("UPDATE candidats SET lat = :lat, lng = :lng WHERE ville_lycee = :ville");
                $miseAJour->execute(array(
                    'lat' => $coords[1] + (mt_rand(-10, 10) / 10000),
                    'lng' => $coords[0] + (mt_rand(-10, 10) / 10000),
                    'ville' => $ville
                ));
            }
        }
        
        // Pause très courte
        usleep(100000); 
    }
} 
catch (Exception $erreur) 
{
    error_log("Erreur Worker Geoloc : " . $erreur->getMessage());
}
?>