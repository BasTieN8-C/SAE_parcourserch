document.addEventListener('DOMContentLoaded', function () {

    /* ─── Éléments DOM ─── */
    const statTotal       = document.getElementById('stat-total');
    const statGeneral     = document.getElementById('stat-general');
    const statTechno      = document.getElementById('stat-techno');
    const tbody           = document.getElementById('tableau-candidats');
    const paginationWrap  = document.getElementById('pagination');
    const resultatsInfo   = document.getElementById('resultats-info');

    const filtreSearch    = document.getElementById('filtre-search');
    const filtreGenre     = document.getElementById('filtre-genre');
    const filtreBac       = document.getElementById('filtre-bac');
    const filtreDept      = document.getElementById('filtre-dept');
    const filtreSpecialite = document.getElementById('filtre-specialite');
    const filtreProfil    = document.getElementById('filtre-profil');
    const btnSearch       = document.getElementById('btn-search');
    const btnReset        = document.getElementById('btn-reset');

    /* ─── État ─── */
    const PAR_PAGE = 50;
    let tousLesCandidats = [];
    let candidatsFiltres = [];
    let pageCourante = 1;

    /* ─── Chargement ─── */
    if (!tbody) return;
    chargerDonnees();

    function chargerDonnees() {
        fetch('/api/candidats')
            .then(r => { if (!r.ok) throw new Error('API error'); return r.json(); })
            .then(data => {
                tousLesCandidats = data;
                remplirFiltresOptions(data);
                afficherStatistiquesGlobales(data);
                appliquerFiltres();
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="7" class="loader-cell" style="color:#ef4444;">Impossible de charger les données. Veuillez importer un fichier Excel.</td></tr>';
            });
    }

    /* ─── Remplir les <select> de filtres ─── */
    function remplirFiltresOptions(candidats) {
        const bacs      = [...new Set(candidats.map(c => String(c.type_bac || '').trim()).filter(Boolean))].sort();
        const depts     = [...new Set(candidats.map(c => String(c.departement || '').trim()).filter(Boolean))].sort();
        const specs     = [...new Set(candidats.map(c => String(c.specialite || '').trim()).filter(Boolean))].sort();
        const profils   = [...new Set(candidats.map(c => String(c.profil_candidat || '').trim()).filter(Boolean))].sort();

        remplirSelect(filtreBac, bacs);
        remplirSelect(filtreDept, depts);
        remplirSelect(filtreSpecialite, specs);
        remplirSelect(filtreProfil, profils);
    }

    function remplirSelect(selectEl, valeurs) {
        valeurs.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v;
            opt.textContent = v;
            selectEl.appendChild(opt);
        });
    }

    function afficherStatistiquesGlobales(candidats) {
        const total = candidats.length;
        let nbGeneral = 0;
        let nbTechno = 0;
        candidats.forEach(c => {
            const bac = String(c.type_bac || '').toLowerCase();
            if (bac.includes('general') || bac.includes('général')) nbGeneral++;
            else if (bac.includes('techno')) nbTechno++;
        });
        if (statTotal) statTotal.textContent = total;
        if (statGeneral) statGeneral.textContent = nbGeneral;
        if (statTechno) statTechno.textContent = nbTechno;
    }

    /* ─── Filtrage ─── */
    function appliquerFiltres() {
        const search  = filtreSearch.value.toLowerCase().trim();
        const genre   = filtreGenre.value;
        const bac     = filtreBac.value;
        const dept    = filtreDept.value;
        const spec    = filtreSpecialite.value;
        const profil  = filtreProfil.value;

        candidatsFiltres = tousLesCandidats.filter(c => {
            if (genre && String(c.genre || '').trim() !== genre) return false;
            if (bac   && String(c.type_bac || '').trim() !== bac) return false;
            if (dept  && String(c.departement || '').trim() !== dept) return false;
            if (spec  && String(c.specialite || '').trim() !== spec) return false;
            if (profil && String(c.profil_candidat || '').trim() !== profil) return false;
            if (search) {
                const hay = [c.numero_dossier, c.ville_lycee, c.departement, c.specialite, c.type_bac, c.profil_candidat]
                    .map(v => String(v || '').toLowerCase()).join(' ');
                if (!hay.includes(search)) return false;
            }
            return true;
        });

        pageCourante = 1;
        afficherPage();
    }

    /* ─── Affichage page ─── */
    function afficherPage() {
        const total       = candidatsFiltres.length;
        const nbPages     = Math.max(1, Math.ceil(total / PAR_PAGE));
        pageCourante      = Math.min(pageCourante, nbPages);
        const debut       = (pageCourante - 1) * PAR_PAGE;
        const fin         = Math.min(debut + PAR_PAGE, total);
        const page        = candidatsFiltres.slice(debut, fin);

        /* Info résultats */
        if (resultatsInfo) {
            const filtreActif = Boolean(
                filtreSearch.value.trim() ||
                filtreGenre.value ||
                filtreBac.value ||
                filtreDept.value ||
                filtreSpecialite.value ||
                filtreProfil.value
            );

            if (total === tousLesCandidats.length) {
                resultatsInfo.textContent = filtreActif
                    ? total + ' résultat(s) trouvé(s)'
                    : total + ' candidats au total';
            } else {
                resultatsInfo.textContent = total + ' résultat(s) — affichage ' + (debut + 1) + '–' + fin;
            }
        }

        /* Lignes */
        tbody.innerHTML = '';
        if (page.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="padding:2.5rem; text-align:center; color:#94a3b8;">Aucun candidat ne correspond aux filtres sélectionnés.</td></tr>';
        } else {
            page.forEach(c => {
                const tr = document.createElement('tr');
                tr.className = 'border-b hover:bg-gray-50 transition-colors text-sm';
                const typeBac = String(c.type_bac || '').toLowerCase();
                let badgeClass = 'badge-autre';
                if (typeBac.includes('general') || typeBac.includes('général')) badgeClass = 'badge-general';
                else if (typeBac.includes('techno')) badgeClass = 'badge-techno';
                else if (typeBac.includes('pro')) badgeClass = 'badge-pro';

                const spec = (c.specialite && c.specialite !== 'Autre') ? c.specialite.toUpperCase() : '—';

                tr.innerHTML =
                    '<td style="padding:0.7rem 1rem; font-weight:600; font-size:0.82rem;">' + (c.numero_dossier || '—') + '</td>' +
                    '<td style="padding:0.7rem 1rem; font-size:0.82rem;">' + (c.genre || '—') + '</td>' +
                    '<td style="padding:0.7rem 1rem; font-size:0.82rem;">' + (c.departement || '—') + '</td>' +
                    '<td style="padding:0.7rem 1rem; font-size:0.82rem;">' + (c.ville_lycee || '—') + '</td>' +
                    '<td style="padding:0.7rem 1rem;"><span class="badge-bac ' + badgeClass + '">' + (c.type_bac || '—') + '</span></td>' +
                    '<td style="padding:0.7rem 1rem; font-size:0.82rem;">' + spec + '</td>' +
                    '<td style="padding:0.7rem 1rem; font-size:0.82rem; color:#64748b;">' + (c.profil_candidat || '—') + '</td>';
                tbody.appendChild(tr);
            });
        }

        /* Pagination */
        afficherPagination(nbPages);
    }

    /* ─── Pagination ─── */
    function afficherPagination(nbPages) {
        if (!paginationWrap) return;
        paginationWrap.innerHTML = '';
        if (nbPages <= 1) return;

        const creer = (label, page, desactive, actif) => {
            const btn = document.createElement('button');
            btn.className = 'page-btn' + (actif ? ' active' : '');
            btn.textContent = label;
            btn.disabled = desactive;
            if (!desactive && !actif) {
                btn.addEventListener('click', () => {
                    pageCourante = page;
                    afficherPage();
                    paginationWrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            }
            return btn;
        };

        paginationWrap.appendChild(creer('«', 1, pageCourante === 1, false));
        paginationWrap.appendChild(creer('‹', pageCourante - 1, pageCourante === 1, false));

        /* Pages visibles autour de la page courante */
        let debut = Math.max(1, pageCourante - 2);
        let fin   = Math.min(nbPages, pageCourante + 2);
        if (pageCourante <= 3)   fin   = Math.min(nbPages, 5);
        if (pageCourante >= nbPages - 2) debut = Math.max(1, nbPages - 4);

        if (debut > 1) {
            paginationWrap.appendChild(creer('1', 1, false, false));
            if (debut > 2) {
                const dots = document.createElement('span');
                dots.textContent = '…';
                dots.style.cssText = 'color:#94a3b8; font-size:0.8rem; padding: 0 0.25rem;';
                paginationWrap.appendChild(dots);
            }
        }

        for (let p = debut; p <= fin; p++) {
            paginationWrap.appendChild(creer(p, p, false, p === pageCourante));
        }

        if (fin < nbPages) {
            if (fin < nbPages - 1) {
                const dots = document.createElement('span');
                dots.textContent = '…';
                dots.style.cssText = 'color:#94a3b8; font-size:0.8rem; padding: 0 0.25rem;';
                paginationWrap.appendChild(dots);
            }
            paginationWrap.appendChild(creer(nbPages, nbPages, false, false));
        }

        paginationWrap.appendChild(creer('›', pageCourante + 1, pageCourante === nbPages, false));
        paginationWrap.appendChild(creer('»', nbPages, pageCourante === nbPages, false));
    }

    /* ─── Écouteurs filtres ─── */
    btnSearch && btnSearch.addEventListener('click', appliquerFiltres);

    btnReset && btnReset.addEventListener('click', () => {
        filtreSearch.value = '';
        filtreGenre.value  = '';
        filtreBac.value    = '';
        filtreDept.value   = '';
        filtreSpecialite.value = '';
        filtreProfil.value = '';
        appliquerFiltres();
    });
});