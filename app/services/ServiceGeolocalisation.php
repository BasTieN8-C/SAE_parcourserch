<?php

class ServiceGeolocalisation
{
    private float $latitudeIutLeHavre;
    private float $longitudeIutLeHavre;
    private int $rayonTerreKm;
    private string $apiUrl = 'https://api-adresse.data.gouv.fr/search/';
    private array $cache = array();

    public function __construct()
    {
        $this->latitudeIutLeHavre = 49.4938;
        $this->longitudeIutLeHavre = 0.1077;
        $this->rayonTerreKm = 6371;
    }

    /**
     * Récupère les coordonnées d'une ville via l'API data.gouv.fr
     * @param string $ville Nom de la ville
     * @param string $codePostal Code postal (optionnel)
     * @return array|null Array avec 'lat' et 'lng', ou null si pas trouvé
     */
    public function obtenirCoordonneeDepuisVille(string $ville, string $codePostal = ''): ?array
    {
        if (empty($ville)) {
            return null;
        }

        // Créer une clé de cache
        $cleCache = md5($ville . $codePostal);
        
        // Vérifier le cache
        if (isset($this->cache[$cleCache])) {
            return $this->cache[$cleCache];
        }

        try {
            // Construire la requête
            $params = array(
                'q' => $ville,
                'limit' => 1,
                'type' => 'municipality'
            );

            if (!empty($codePostal)) {
                $params['postcode'] = $codePostal;
            }

            $url = $this->apiUrl . '?' . http_build_query($params);

            // Faire la requête avec timeout court (2 sec max pour laisser l'API répondre)
            $contexte = stream_context_create(array(
                'http' => array(
                    'method' => 'GET',
                    'timeout' => 2,
                    'user_agent' => 'ParcourSearch/1.0'
                )
            ));

            $reponse = @file_get_contents($url, false, $contexte);
            
            if ($reponse === false) {
                return null;
            }

            $donnees = json_decode($reponse, true);

            if (!isset($donnees['features']) || empty($donnees['features'])) {
                $this->cache[$cleCache] = null;
                return null;
            }

            // Extraire les coordonnées du premier résultat
            $feature = $donnees['features'][0];
            
            if (!isset($feature['geometry']['coordinates'])) {
                return null;
            }

            $coords = $feature['geometry']['coordinates'];
            
            $resultat = array(
                'lat' => (float) $coords[1],
                'lng' => (float) $coords[0]
            );

            // Mettre en cache
            $this->cache[$cleCache] = $resultat;
            
            return $resultat;
        } 
        catch (Exception $e) {
            return null;
        }
    }

    /**
     * Récupère les coordonnées avec fallback sur dictionnaire si l'API échoue
     * IMPORTANT : L'API est toujours appelée sauf si la ville est 'Inconnue'
     */
    public function obtenirCoordonneesAvecFallback(string $ville, string $codePostal, string $departement): array
    {
        $villeOriginal = $ville;
        $ville = trim($ville);
        
        // Cas spécial : si la ville est "Inconnue" ou vide, pas d'appel API
        if (empty($ville) || strtolower($ville) === 'inconnue') {
            error_log('[GEO] Fallback (ville vide/inconnue) pour dept=' . $departement . ' CP=' . $codePostal);
            return $this->obtenirCoordonneesParDepartement($departement);
        }

        // CAS NORMAL : Appeler l'API data.gouv.fr d'abord
        error_log('[GEO] Appel API pour ville="' . $ville . '" CP=' . $codePostal . ' dept=' . $departement);
        $coords = $this->obtenirCoordonneeDepuisVille($ville, $codePostal);
        
        if ($coords !== null) {
            // L'API a réussi ! Ajouter une petite variation pour éviter le chevauchement
            error_log('[GEO] API succès ! lat=' . $coords['lat'] . ' lng=' . $coords['lng']);
            $coords['lat'] += (mt_rand(-5, 5) / 1000);
            $coords['lng'] += (mt_rand(-5, 5) / 1000);
            return $coords;
        }

        // L'API a échoué (timeout/erreur) → Fallback sur dictionnaire par département
        error_log('[GEO] API échouée, fallback vers dictionnaire pour dept=' . $departement);
        return $this->obtenirCoordonneesParDepartement($departement);
    }

    private function obtenirCoordonneesParDepartement(string $departement): array
    {
        $dictionnaire = $this->obtenirDictionnaireDepartements();
        
        if (isset($dictionnaire[$departement])) {
            $latLng = $dictionnaire[$departement];
            error_log('[GEO] Dictionnaire trouvé pour dept=' . $departement . ' lat=' . $latLng['lat'] . ' lng=' . $latLng['lng']);
            return array(
                'lat' => $latLng['lat'] + (mt_rand(-5, 5) / 1000),
                'lng' => $latLng['lng'] + (mt_rand(-5, 5) / 1000)
            );
        }

        // Fallback par défaut (centre de la France) - PROBLÈME ?: trop souvent utilisé
        error_log('[GEO] ⚠️  DEPT PAS TROUVÉ=' . $departement . ' → Centre France par défaut!');
        return array(
            'lat' => 46.22 + (mt_rand(-5, 5) / 1000),
            'lng' => 2.21 + (mt_rand(-5, 5) / 1000)
        );
    }

    private function obtenirDictionnaireDepartements(): array
    {
        return array(
            // Île-de-France
            "75" => array("lat" => 48.8566, "lng" => 2.3522),  // Paris
            "77" => array("lat" => 48.5282, "lng" => 2.6454),  // Seine-et-Marne
            "78" => array("lat" => 48.7965, "lng" => 2.1349),  // Yvelines
            "91" => array("lat" => 48.6292, "lng" => 2.4379),  // Essonne
            "92" => array("lat" => 48.7622, "lng" => 2.2200),  // Hauts-de-Seine
            "93" => array("lat" => 48.9555, "lng" => 2.4469),  // Seine-Saint-Denis
            "94" => array("lat" => 48.7738, "lng" => 2.5063),  // Val-de-Marne
            "95" => array("lat" => 49.0096, "lng" => 2.0480),  // Val-d'Oise
            
            // Normandie
            "76" => array("lat" => 49.4442, "lng" => 1.0993),  // Le Havre
            "27" => array("lat" => 49.1817, "lng" => 1.2108),  // Évreux
            
            // Bretagne/Pays de Loire
            "14" => array("lat" => 49.2825, "lng" => -0.2700), // Caen
            "50" => array("lat" => 49.6408, "lng" => -1.5047), // Saint-Lô
            "61" => array("lat" => 48.4450, "lng" => 0.1839),  // Alençon
            
            // Outre-mer
            "971" => array("lat" => 16.2500, "lng" => -61.5300), // Guadeloupe
            "972" => array("lat" => 14.6500, "lng" => -61.0000), // Martinique
            "973" => array("lat" => 4.0000, "lng" => -53.0000),  // Guyane
            "974" => array("lat" => -21.1300, "lng" => 55.5300), // Réunion
            "976" => array("lat" => -11.8700, "lng" => 43.6700), // Mayotte
        );
    }

    public function calculerDistanceDepuisIut(float $latitudeCandidat, float $longitudeCandidat): float
    {
        // Conversion des degrés en radians
        $latIutRad = deg2rad($this->latitudeIutLeHavre);
        $lngIutRad = deg2rad($this->longitudeIutLeHavre);
        $latCandidatRad = deg2rad($latitudeCandidat);
        $lngCandidatRad = deg2rad($longitudeCandidat);

        // Application de la formule de Haversine
        $differenceLatitudes = $latCandidatRad - $latIutRad;
        $differenceLongitudes = $lngCandidatRad - $lngIutRad;

        $calculIntermediaireA = sin($differenceLatitudes / 2) * sin($differenceLatitudes / 2) +
                                cos($latIutRad) * cos($latCandidatRad) *
                                sin($differenceLongitudes / 2) * sin($differenceLongitudes / 2);

        $calculIntermediaireC = 2 * atan2(sqrt($calculIntermediaireA), sqrt(1 - $calculIntermediaireA));

        $distanceEnKilometres = $this->rayonTerreKm * $calculIntermediaireC;

        return round($distanceEnKilometres, 1);
    }
}
?>