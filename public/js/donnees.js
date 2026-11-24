document.addEventListener('DOMContentLoaded', function() 
{
    const corpsTableau = document.getElementById('corps-tableau');
    const compteurTotal = document.getElementById('compteur-total');

    if (!corpsTableau) 
    {
        return;
    }

    function chargerDonnees() 
    {
        fetch('/api/candidats')
            .then(function(reponse) 
            {
                return reponse.json();
            })
            .then(function(candidats) 
            {
                afficherTableau(candidats);
            })
            .catch(function(erreur) 
            {
                console.error("Erreur de chargement :", erreur);
                corpsTableau.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Erreur de chargement des données.</td></tr>';
            });
    }

    function afficherTableau(candidats) 
    {
        corpsTableau.innerHTML = '';
        compteurTotal.textContent = candidats.length;

        if (candidats.length === 0) 
        {
            corpsTableau.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">Aucun candidat trouvé. Veuillez importer un fichier.</td></tr>';
            return;
        }

        const candidatsAAfficher = candidats.slice(0, 200);

        candidatsAAfficher.forEach(function(candidat) 
        {
            const ligne = document.createElement('tr');
            ligne.className = 'hover:bg-gray-50 transition-colors';
            
            ligne.innerHTML = `
                <td class="px-6 py-4 text-sm font-medium text-gray-900">${candidat.numero_dossier}</td>
                <td class="px-6 py-4 text-sm text-gray-600">${candidat.genre}</td>
                <td class="px-6 py-4 text-sm text-gray-600">${candidat.departement}</td>
                <td class="px-6 py-4 text-sm text-gray-600 truncate max-w-xs" title="${candidat.ville_lycee}">${candidat.ville_lycee}</td>
                <td class="px-6 py-4 text-sm text-gray-600">
                    <span class="px-2 py-1 bg-gray-100 rounded-full text-xs font-medium border border-gray-200">
                        ${candidat.type_bac || 'Général'}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm font-bold text-gray-900">${candidat.note_lycee || '-'}</td>
            `;
            
            corpsTableau.appendChild(ligne);
        });

        if (candidats.length > 200) 
        {
            const ligneAvertissement = document.createElement('tr');
            ligneAvertissement.innerHTML = `<td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 italic">Affichage des 200 premiers résultats sur ${candidats.length}.</td>`;
            corpsTableau.appendChild(ligneAvertissement);
        }
    }

    chargerDonnees();
});