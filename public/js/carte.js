document.addEventListener('DOMContentLoaded', function() 
{
    console.log("[CARTE] DOM charge");
    console.log("[CARTE] Leaflet defini?", typeof L);
    console.log("[CARTE] L.markerClusterGroup defini?", typeof L.markerClusterGroup);
    
    const conteneurCarte = document.getElementById('carte-interactive');
    if (!conteneurCarte) 
    {
        console.error("[CARTE] Conteneur 'carte-interactive' introuvable");
        return;
    }
    console.log("[CARTE] Conteneur carte trouve");

    const latitudeIut = 49.4938;
    const longitudeIut = 0.1077;
    const zoomInitial = 6;
    
    const carteCandidats = L.map('carte-interactive').setView([latitudeIut, longitudeIut], zoomInitial);
    console.log("[CARTE] Carte Leaflet initialisee");

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', 
    {
        maxZoom: 19,
        attribution: 'OpenStreetMap'
    }).addTo(carteCandidats);
    console.log("[CARTE] Tuiles OpenStreetMap chargees");

    setTimeout(function() 
    {
        carteCandidats.invalidateSize();
        console.log("[CARTE] Taille de carte invalidee");
    }, 300);

    if (typeof L.markerClusterGroup !== 'function') 
    {
        console.error("[CARTE] L.markerClusterGroup n'est pas une fonction. MarkerCluster n'est pas charge !");
        const affichageCompteurErreur = document.getElementById('compteur-dossiers');
        if (affichageCompteurErreur) 
        {
            affichageCompteurErreur.textContent = "ERREUR: MarkerCluster manquant";
        }
        return;
    }
    
    const groupeClusters = L.markerClusterGroup({
        maxClusterRadius: 40,
        spiderfyOnMaxZoom: true
    }).addTo(carteCandidats);
    console.log("[CARTE] Groupe de clusters cree");
    
    const selecteur = document.getElementById('selecteur-groupe');
    const affichageCompteur = document.getElementById('compteur-dossiers');
    
    let listeCandidats = [];
    let listeGroupes = [];

    fetch('/api/groupes')
    .then(function(reponse) 
    { 
        if (!reponse.ok) 
        {
            throw new Error('Erreur lors du chargement des groupes');
        }
        return reponse.json(); 
    })
    .then(function(donnees) 
    {
        console.log('[CARTE] Groupes recus:', donnees);
        listeGroupes = donnees;
        
        if (selecteur) 
        {
            const optionDefaut = selecteur.querySelector('option[value="tous"]');
            if (optionDefaut) 
            {
                selecteur.innerHTML = '';
                selecteur.appendChild(optionDefaut);
            }
            
            if (Array.isArray(donnees)) 
            {
                for (let i = 0; i < donnees.length; i++) 
                {
                    const groupe = donnees[i];
                    const option = document.createElement('option');
                    option.value = groupe.id;
                    option.textContent = groupe.nom;
                    selecteur.appendChild(option);
                }
            }
        }
    })
    .catch(function(erreur) 
    {
        console.error('[CARTE] Erreur chargement groupes:', erreur);
    });

    function initialiserCandidats()
    {
        console.log("[CARTE] Chargement des candidats depuis /api/candidats...");
        
        fetch('/api/candidats')
        .then(function(reponse) 
        { 
            console.log("[CARTE] Reponse HTTP:", reponse.status, reponse.statusText);
            if (!reponse.ok) 
            {
                throw new Error('Erreur HTTP: ' + reponse.status);
            }
            return reponse.json(); 
        })
        .then(function(donneesCandidats) 
        {
            console.log("[CARTE] Donnees brutes recues:", donneesCandidats.length, "candidats");
            
            listeCandidats = donneesCandidats.map(function(candidat) 
            {
                return {
                    ...candidat,
                    lat: parseFloat(candidat.lat),
                    lng: parseFloat(candidat.lng)
                };
            });
            
            const candidatsValides = listeCandidats.filter(function(candidat) 
            {
                return !isNaN(candidat.lat) && !isNaN(candidat.lng) && (candidat.lat !== 0 || candidat.lng !== 0);
            });
            
            console.log("[CARTE] Candidats charges:", listeCandidats.length);
            console.log("[CARTE] Candidats avec coords valides:", candidatsValides.length);
            console.log("[CARTE] Premiers candidats:", listeCandidats.slice(0, 3));
            
            if (candidatsValides.length === 0)
            {
                console.error("[CARTE] PROBLEME: Aucun candidat avec coordonnees valides !");
                if (affichageCompteur) 
                {
                    affichageCompteur.textContent = "0 (coords invalides)";
                }
                return;
            }
            
            mettreAJourAffichage('tous');
        })
        .catch(function(erreur) 
        {
            console.error("[CARTE] Erreur candidats:", erreur.message);
            console.error("[CARTE] Stack:", erreur.stack);
            if (affichageCompteur) 
            {
                affichageCompteur.textContent = 'Erreur API';
            }
        });
    }

    initialiserCandidats();

    if (selecteur) 
    {
        selecteur.addEventListener('change', function(evenement) 
        {
            mettreAJourAffichage(evenement.target.value);
        });
    }

    function mettreAJourAffichage(filtre) 
    {
        groupeClusters.clearLayers();
        let compteur = 0;
        let tableauMarqueurs = [];

        for (let i = 0; i < listeCandidats.length; i++) 
        {
            const candidat = listeCandidats[i];
            const lat = parseFloat(candidat.lat);
            const lng = parseFloat(candidat.lng);
            
            if (!isNaN(lat) && !isNaN(lng) && (lat !== 0 || lng !== 0)) 
            {
                const typeBacNormalise = String(candidat.type_bac || '').toLowerCase().trim();
                let afficher = false;
                
                if (filtre === 'tous') 
                {
                    afficher = true;
                }
                else if (filtre === 'techno' && typeBacNormalise.includes('techno')) 
                {
                    afficher = true;
                }
                else if (filtre === 'general' && (typeBacNormalise.includes('general') || typeBacNormalise.includes('général'))) 
                {
                    afficher = true;
                }

                if (afficher) 
                {
                    compteur++;
                    const marqueur = L.circleMarker([lat, lng], {
                        radius: 6,
                        fillColor: "#3b82f6",
                        color: "#fff",
                        weight: 1,
                        fillOpacity: 0.8
                    });
                    tableauMarqueurs.push(marqueur);
                }
            }
        }
        groupeClusters.addLayers(tableauMarqueurs);
        if (affichageCompteur) 
        {
            affichageCompteur.textContent = compteur;
        }
    }
});