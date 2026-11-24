document.addEventListener('DOMContentLoaded', function() 
{
    const zoneDepot = document.getElementById('zone-depot');
    const champFichier = document.getElementById('champ-fichier');
    const boutonImporter = document.getElementById('btn-importer');
    
    const apercuFichier = document.getElementById('apercu-fichier');
    const nomFichier = document.getElementById('nom-fichier');
    const boiteMessage = document.getElementById('boite-message');
    const texteMessage = document.getElementById('texte-message');
    const barreProgression = document.getElementById('barre-progression');
    const fillProgression = document.getElementById('fill-progression');
    const resumeImport = document.getElementById('resume-import');
    const statTotal = document.getElementById('stat-total');

    if (!zoneDepot || !champFichier || !boutonImporter) return;

    zoneDepot.addEventListener('click', () => champFichier.click());
    zoneDepot.addEventListener('dragover', (e) => { e.preventDefault(); zoneDepot.style.borderColor = '#3b82f6'; });
    zoneDepot.addEventListener('dragleave', (e) => { e.preventDefault(); zoneDepot.style.borderColor = '#d1d5db'; });
    zoneDepot.addEventListener('drop', (e) => {
        e.preventDefault();
        zoneDepot.style.borderColor = '#d1d5db';
        if (e.dataTransfer.files.length > 0) {
            champFichier.files = e.dataTransfer.files;
            champFichier.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    champFichier.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            nomFichier.textContent = e.target.files[0].name;
            apercuFichier.style.display = 'inline-flex';
            boutonImporter.disabled = false;
            boutonImporter.style.opacity = '1';
            boiteMessage.style.display = 'none';
            resumeImport.style.display = 'none';
            barreProgression.style.display = 'none';
        }
    });

    // LA FONCTION MAGIQUE CONTRE LES ACCENTS
    function normaliserTexte(texte) {
        if (texte === null || texte === undefined) return '';
        return String(texte).normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
    }

    function trouverColonne(ligne, motsCles) {
        for (const colonne in ligne) {
            const colonneNorm = normaliserTexte(colonne);
            for (const mot of motsCles) {
                if (colonneNorm.includes(normaliserTexte(mot))) return ligne[colonne];
            }
        }
        return null;
    }

    boutonImporter.addEventListener('click', () => {
        if (champFichier.files.length === 0) return;
        boutonImporter.disabled = true;
        resumeImport.style.display = 'none';
        barreProgression.style.display = 'block';
        
        try {
            if (typeof XLSX === 'undefined') throw new Error('Librairie XLSX non chargée.');
            
            fillProgression.style.width = '10%';
            
            const lecteurFichier = new FileReader();
            lecteurFichier.onload = function(e) {
                try {
                    fillProgression.style.width = '30%';
                    const classeurExcel = XLSX.read(new Uint8Array(e.target.result), { type: 'array' });
                    const lignesBrutes = XLSX.utils.sheet_to_json(classeurExcel.Sheets[classeurExcel.SheetNames[0]]);
                    const candidatsNettoyes = [];

                    fillProgression.style.width = '50%';

                    const specialitesTechno = ['STL', 'STI2D', 'STMG', 'ST2A', 'STHR', 'S-SI', 'S-ISN', 'STD2A'];
                    const specialitesProf = ['CAP', 'BEP', 'BAC PRO', 'BACPRO'];

                    for (let i = 0; i < lignesBrutes.length; i++) {
                        const ligne = lignesBrutes[i];
                        
                        let codePostal = String(trouverColonne(ligne, ['codepostal', 'code postal']) || '75000').trim().substring(0, 5).padStart(5, '0');
                        let ville = String(trouverColonne(ligne, ['commune etablissement', 'commune', 'ville']) || 'Paris').trim();
                        let numeroDossier = String(trouverColonne(ligne, ['candidat', 'numero dossier']) || i);
                        let departement = codePostal.startsWith('97') ? codePostal.substring(0, 3) : codePostal.substring(0, 2);

                        let typeBac = 'General';
                        let specialite = 'Autre';
                        let noteLycee = null, noteAvenir = null, noteDossier = null;
                        let profilCandidat = 'Général', genre = 'M', dateInscription = null;

                        for (const colonne in ligne) {
                            const valeur = String(ligne[colonne]).trim();
                            const valNorm = normaliserTexte(valeur);
                            const colNorm = normaliserTexte(colonne); // "Série Diplôme" devient "serie diplome"
                            
                            if (colNorm.includes('note') && colNorm.includes('lycee')) noteLycee = parseFloat(valeur.replace(',','.'));
                            if (colNorm.includes('note') && (colNorm.includes('avenir') || colNorm.includes('fiche'))) noteAvenir = parseFloat(valeur.replace(',','.'));
                            if (colNorm.includes('note') && colNorm.includes('dossier')) noteDossier = parseFloat(valeur.replace(',','.'));
                            
                            if (colNorm.includes('profil') || colNorm.includes('statut')) if(valeur) profilCandidat = valeur;
                            if (colNorm.includes('civilite') || colNorm.includes('sexe') || colNorm.includes('genre')) {
                                if (valNorm === 'f' || valNorm.includes('mme') || valNorm.includes('femme')) genre = 'F';
                            }
                            
                            // DÉTECTION DU TYPE DE BAC
                            if (colNorm.includes('type') || colNorm.includes('serie') || colNorm.includes('bac')) {
                                if (valNorm.includes('techno') || valNorm.includes('sti2d') || valNorm.includes('stmg')) typeBac = 'Technologique';
                                else if (valNorm.includes('pro') || valNorm.includes('cap')) typeBac = 'Professionnel';
                                else if (valNorm.includes('gen')) typeBac = 'General';
                            }
                            
                            // DÉTECTION SPÉCIALITÉ (On ignore "abandonné")
                            if ((colNorm.includes('specialite') || colNorm.includes('serie')) && !colNorm.includes('abandonne')) {
                                const spec = valeur.toUpperCase();
                                if (specialitesTechno.some(s => spec.includes(s))) typeBac = 'Technologique';
                                else if (specialitesProf.some(s => spec.includes(s))) typeBac = 'Professionnel';
                                if (spec && spec !== 'UNDEFINED') specialite = spec;
                            }
                        }

                        candidatsNettoyes.push({
                            numero_dossier: numeroDossier, genre: genre, departement: departement, code_postal: codePostal, ville_lycee: ville,
                            type_bac: typeBac, specialite: specialite, profil_candidat: profilCandidat,
                            note_lycee: noteLycee, note_avenir: noteAvenir, note_dossier: noteDossier, date_inscription: dateInscription
                        });
                    }

                    fillProgression.style.width = '75%';
                    
                    // Envoi au serveur (CORRIGÉ)
                    fetch('/api/importer', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(candidatsNettoyes)
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('Erreur serveur ' + res.status);
                        return res.json();
                    })
                    .then(data => {
                        if (data.succes) {
                            fillProgression.style.width = '100%';
                            boiteMessage.style.display = 'none';
                            resumeImport.style.display = 'block';
                            statTotal.textContent = data.candidats_importes || candidatsNettoyes.length;
                        } else {
                            afficherErreur(data.message);
                        }
                    })
                    .catch(err => afficherErreur("Impossible de joindre le serveur. Détail: " + err.message));

                } catch (err) {
                    afficherErreur('Erreur traitement : ' + err.message);
                }
            };
            lecteurFichier.readAsArrayBuffer(champFichier.files[0]);
        } catch (err) {
            afficherErreur(err.message);
        }
    });

    function afficherErreur(msg) {
        boiteMessage.style.display = 'block';
        boiteMessage.style.backgroundColor = '#fef2f2';
        boiteMessage.style.borderColor = '#ef4444';
        texteMessage.style.color = '#ef4444';
        texteMessage.textContent = msg;
        barreProgression.style.display = 'none';
        boutonImporter.disabled = false;
    }
});