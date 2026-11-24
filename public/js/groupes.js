document.addEventListener('DOMContentLoaded', function() 
{
    if (window.scriptGroupesInitialise) 
    {
        return;
    }
    window.scriptGroupesInitialise = true;

    // --- ELEMENTS DU DOM ---
    const formulaire = document.getElementById('formulaire-groupe');
    const listeGroupes = document.getElementById('liste-groupes');
    
    // Elements du formulaire de creation
    const champNom = document.getElementById('nom-groupe');
    const champCouleur = document.getElementById('couleur-groupe');
    const selecteurDepartement = document.getElementById('filtre-departements');
    const selecteurBac = document.getElementById('filtre-bac');
    const selecteurGenre = document.getElementById('filtre-genre');
    const selecteurBoursier = document.getElementById('filtre-boursier');
    const selecteurSpecialites = document.getElementById('filtre-specialites');

    const formNotation = document.getElementById('formulaire-noter-groupe');
    const selecteurGroupeNoter = document.getElementById('groupe-a-noter');
    const champNote = document.getElementById('note-dossier');
    const compteurGroupes = document.getElementById('sidebar-count');

    let estEnCoursDeCreation = false; 
    let toutesLesSpecialites = [];

    if (!formulaire || !listeGroupes) return;

    // INITIALISATION
    chargerGroupes();
    initialiserFiltresDynamiques();

    // Filtrage dynamique des specialites lors d'un clic sur un type de bac
    if (selecteurBac) 
    {
        selecteurBac.addEventListener('change', function() 
        {
            mettreAJourSpecialites();
        });
    }

    formulaire.addEventListener('submit', function(evenement) 
    {
        evenement.preventDefault();
        
        if (estEnCoursDeCreation) return;
        estEnCoursDeCreation = true;

        const criteres = {};
        
        const typesBac = [];
        if (selecteurBac)
        {
            for (let i = 0; i < selecteurBac.options.length; i++)
            {
                const option = selecteurBac.options[i];
                if (option.selected && option.value !== "") typesBac.push(option.value);
            }
        }
        if (typesBac.length > 0) criteres.type_bac = typesBac;

        const departements = [];
        if (selecteurDepartement)
        {
            for (let i = 0; i < selecteurDepartement.options.length; i++)
            {
                const option = selecteurDepartement.options[i];
                if (option.selected && option.value !== "") departements.push(option.value);
            }
        }
        if (departements.length > 0) criteres.departement = departements;

        if (selecteurGenre && selecteurGenre.value) criteres.genre = selecteurGenre.value;
        if (selecteurBoursier && selecteurBoursier.value) criteres.boursier = selecteurBoursier.value;
        
        const specialites = [];
        if (selecteurSpecialites)
        {
            for (let i = 0; i < selecteurSpecialites.options.length; i++)
            {
                const option = selecteurSpecialites.options[i];
                if (option.selected && option.value !== "") specialites.push(option.value);
            }
        }
        if (specialites.length > 0) criteres.specialites = specialites;

        const groupeACreer = {
            nom: champNom.value,
            couleur: champCouleur.value,
            criteres: criteres
        };
        
        enregistrerGroupe(groupeACreer);
    });

    if (formNotation) 
    {
        formNotation.addEventListener('submit', function(evenement) 
        {
            evenement.preventDefault();
            if (!selecteurGroupeNoter.value) 
            {
                alert("Veuillez selectionner un groupe.");
                return;
            }

            fetch('/api/groupes/noter', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_groupe: selecteurGroupeNoter.value,
                    note_dossier: champNote.value
                })
            })
            .then(function(reponse) { return reponse.json(); })
            .then(function(resultat) 
            {
                if (resultat.succes) 
                {
                    alert("Note enregistree !");
                    champNote.value = '';
                    chargerGroupes();
                } 
                else 
                {
                    alert("Erreur : " + resultat.message);
                }
            })
            .catch(function(erreur) { alert("Erreur de connexion au serveur."); });
        });
    }

    // --- FONCTIONS ---

    function initialiserFiltresDynamiques() 
    {
        fetch('/api/filtres')
        .then(function(reponse) { 
            if (!reponse.ok) throw new Error("Erreur reseau");
            return reponse.json(); 
        })
        .then(function(donnees) 
        {
            try {
                // 1. Remplissage des départements
                let donneesDept = donnees.departements || donnees.departement || [];
                remplirSelecteur('filtre-departements', donneesDept, "-- Tous --");
                
                // 2. Extraction ultra-sécurisée des spécialités
                let specs = donnees.specialites || donnees.specialite || donnees.specialites_candidat || [];
                
                if (!Array.isArray(specs) && typeof specs === 'object') {
                    specs = Object.values(specs);
                }

                toutesLesSpecialites = [];
                for (let i = 0; i < specs.length; i++) {
                    let spec = specs[i];
                    
                    if (typeof spec === 'object' && spec !== null) {
                        spec = spec.specialite || spec.libelle || spec.nom || Object.values(spec)[0];
                    }
                    
                    if (spec !== null && spec !== undefined && String(spec).trim() !== '') {
                        toutesLesSpecialites.push(String(spec).trim());
                    }
                }

                // 3. Affichage à l'écran
                mettreAJourSpecialites();

            } catch (erreurFormatage) {
                console.error("[GROUPES] Erreur lors du parsing des données :", erreurFormatage);
            }
        })
        .catch(function(erreur)
        {
            console.error("[GROUPES] L'appel à l'API /api/filtres a échoué :", erreur);
        });
    }

    function mettreAJourSpecialites()
    {
        if (!selecteurSpecialites) return;
        
        const typesBacSelectionnes = [];
        if (selecteurBac)
        {
            for (let i = 0; i < selecteurBac.options.length; i++)
            {
                const option = selecteurBac.options[i];
                if (option.selected && option.value !== "")
                {
                    typesBacSelectionnes.push(String(option.value).toLowerCase());
                }
            }
        }
        
        selecteurSpecialites.innerHTML = '';
        
        const motsClesTechno = ['sti2d', 'stmg', 'stl', 'st2a', 'sthr', 'ingénierie', 'innovation', 'management', 'gestion', 'techno', 'st2s'];
        const motsClesPro = ['pro', 'cap', 'bep', 'métier', 'maintenance', 'commerce', 'logistique', 'électro', 'soins', 'accompagnement'];
        
        let nbAjoutes = 0;

        for (let j = 0; j < toutesLesSpecialites.length; j++)
        {
            const spec = toutesLesSpecialites[j];
            const specLower = String(spec).toLowerCase();
            let estTechno = false;
            let estPro = false;
            
            for (let i = 0; i < motsClesTechno.length; i++) 
            {
                if (specLower.includes(motsClesTechno[i])) estTechno = true;
            }
            
            for (let i = 0; i < motsClesPro.length; i++) 
            {
                if (specLower.includes(motsClesPro[i])) estPro = true;
            }
            
            let afficher = false;
            
            if (typesBacSelectionnes.length === 0) 
            {
                afficher = true;
            }
            else 
            {
                for (let i = 0; i < typesBacSelectionnes.length; i++) 
                {
                    const typeBac = typesBacSelectionnes[i];
                    
                    if (typeBac.includes('techno') && estTechno) afficher = true;
                    if (typeBac.includes('pro') && estPro) afficher = true;
                    if ((typeBac.includes('gen') || typeBac.includes('gén')) && !estTechno && !estPro) afficher = true;
                }
            }
            
            if (afficher)
            {
                const option = document.createElement('option');
                option.value = spec;
                option.textContent = spec;
                selecteurSpecialites.appendChild(option);
                nbAjoutes++;
            }
        }

        if (nbAjoutes === 0)
        {
            const option = document.createElement('option');
            option.value = "";
            
            if (toutesLesSpecialites.length === 0) {
                option.textContent = "-- Erreur : Données introuvables --";
            } else {
                option.textContent = "-- Aucune spécialité correspondante --";
            }
            
            selecteurSpecialites.appendChild(option);
        }
    }

    function chargerGroupes() 
    {
        fetch('/api/groupes')
        .then(function(reponse) { return reponse.json(); })
        .then(function(groupes) 
        {
            afficherGroupes(groupes);
            peuplerSelecteurNotation(groupes);
        });
    }

    function enregistrerGroupe(groupe) 
    {
        fetch('/api/groupes/creer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(groupe)
        })
        .then(function(reponse) { return reponse.json(); })
        .then(function(resultat) 
        {
            estEnCoursDeCreation = false;
            
            if (resultat.succes) 
            {
                formulaire.reset();
                mettreAJourSpecialites();
                chargerGroupes();
            } 
            else 
            {
                alert("Erreur : " + resultat.message);
            }
        })
        .catch(function(erreur) 
        {
            estEnCoursDeCreation = false;
            alert("Erreur reseau ou serveur.");
        });
    }

    function supprimerGroupe(id)
    {
        if (confirm("Supprimer ce groupe ?"))
        {
            fetch('/api/groupes/supprimer?id=' + id, { method: 'DELETE' })
            .then(function(reponse) { return reponse.json(); })
            .then(function(resultat) 
            {
                if (resultat.succes) chargerGroupes();
                else alert("Erreur : " + resultat.message);
            });
        }
    }

    function afficherGroupes(groupes) 
    {
        listeGroupes.innerHTML = '';
        
        if (!groupes || groupes.length === 0)
        {
            listeGroupes.innerHTML = '<div style="text-align: center; color: #9ca3b8; padding: 2rem 1rem; font-size: 0.85rem;">Aucun groupe cree</div>';
            if (compteurGroupes) compteurGroupes.textContent = '0';
            return;
        }

        if (compteurGroupes) compteurGroupes.textContent = groupes.length;

        groupes.forEach(function(groupe)
        {
            const div = document.createElement('div');
            const nbCandidats = groupe.nb_candidats || 0;
            const couleur = groupe.couleur || '#3b82f6';
            
            div.style.cssText = 'background: white; border: 1px solid #e5e7eb; border-radius: 0.375rem; padding: 0.75rem; margin-bottom: 0.5rem; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 0.75rem;';
            div.onclick = function() { supprimerGroupe(groupe.id); };
            div.onmouseover = function() { this.style.boxShadow = '0 2px 6px rgba(0,0,0,0.08)'; };
            div.onmouseout = function() { this.style.boxShadow = 'none'; };
            
            const boiteCouleur = document.createElement('div');
            boiteCouleur.style.cssText = 'width: 12px; height: 12px; border-radius: 3px; background: ' + couleur + '; flex-shrink: 0;';
            
            const info = document.createElement('div');
            info.style.cssText = 'flex: 1; min-width: 0;';
            info.innerHTML = '<div style="font-size: 0.875rem; font-weight: 600; color: #111; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + groupe.nom + '</div>' +
                           '<div style="font-size: 0.75rem; color: #9ca3b8; margin-top: 2px;">' + nbCandidats + ' candidats</div>';
            
            div.appendChild(boiteCouleur);
            div.appendChild(info);
            listeGroupes.appendChild(div);
        });
    }

    function peuplerSelecteurNotation(groupes)
    {
        if (!selecteurGroupeNoter) return;
        selecteurGroupeNoter.innerHTML = '<option value="">-- Selectionner --</option>';
        
        if (groupes && groupes.length > 0)
        {
            groupes.forEach(function(groupe)
            {
                const option = document.createElement('option');
                option.value = groupe.id;
                option.textContent = groupe.nom;
                selecteurGroupeNoter.appendChild(option);
            });
        }
    }

    function remplirSelecteur(idSelecteur, donnees, labelParDefaut)
    {
        const element = document.getElementById(idSelecteur);
        if (!element) return;
        
        element.innerHTML = '';
        if (labelParDefaut)
        {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = labelParDefaut;
            element.appendChild(option);
        }
        
        // --- NOUVEAUTÉ : Accepter les objets JSON ({"0": "75", "1": "76"}) en forçant la conversion en tableau
        let tableauDonnees = donnees;
        if (!Array.isArray(donnees) && typeof donnees === 'object' && donnees !== null) {
            tableauDonnees = Object.values(donnees);
        }

        if (Array.isArray(tableauDonnees))
        {
            for (let i = 0; i < tableauDonnees.length; i++)
            {
                let valeur = tableauDonnees[i];
                
                // --- NOUVEAUTÉ : Gérer le cas où la base de données renvoie un tableau d'objets [{departement: "75"}]
                if (typeof valeur === 'object' && valeur !== null) {
                    valeur = valeur.departement || valeur.nom || Object.values(valeur)[0];
                }

                if (valeur !== null && valeur !== undefined)
                {
                    const chaineValeur = String(valeur).trim();
                    if (chaineValeur !== '')
                    {
                        const option = document.createElement('option');
                        option.value = chaineValeur;
                        option.textContent = chaineValeur;
                        element.appendChild(option);
                    }
                }
            }
        }
    }
});