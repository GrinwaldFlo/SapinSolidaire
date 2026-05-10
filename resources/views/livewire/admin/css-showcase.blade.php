<div class="space-y-10">

    <div class="flex items-center justify-between">
        <h1 class="section-title">🎨 CSS Showcase</h1>
        <p class="text-muted">Aperçu de toutes les classes CSS disponibles dans app.css</p>
    </div>

    {{-- NOTICES / BANNERS --}}
    <section class="space-y-4">
        <h2 class="section-title">Notices &amp; Banners</h2>
        <div class="space-y-3">
            <div class="notice-info"><strong>.notice-info</strong> — Bannière d'information.</div>
            <div class="notice-success"><strong>.notice-success</strong> — Bannière de succès.</div>
            <div class="notice-warning"><strong>.notice-warning</strong> — Bannière d'avertissement.</div>
            <div class="notice-error"><strong>.notice-error</strong> — Bannière d'erreur.</div>
        </div>
    </section>

    {{-- TYPOGRAPHY --}}
    <section class="space-y-4">
        <h2 class="section-title">Typographie</h2>
        <div class="card space-y-4">
            <div>
                <p class="sub-label mb-1">.section-title</p>
                <p class="section-title">Titre de section avec bordure basse</p>
            </div>
            <div>
                <p class="sub-label mb-1">.sub-label</p>
                <p class="sub-label">Sous-titre en majuscules espacées</p>
            </div>
            <div>
                <p class="sub-label mb-1">.field-label</p>
                <label class="field-label">Libellé de champ de formulaire</label>
            </div>
            <div>
                <p class="sub-label mb-1">.field-error</p>
                <p class="field-error">Message d'erreur de validation sous un champ</p>
            </div>
            <div>
                <p class="sub-label mb-1">.text-muted</p>
                <p class="text-muted">Texte secondaire / description</p>
            </div>
            <div>
                <p class="sub-label mb-1">.detail-label / .detail-value</p>
                <span class="detail-label">Prénom :</span>
                <span class="ml-2 detail-value">Marie Dupont</span>
            </div>
            <div>
                <p class="sub-label mb-1">.link</p>
                <a href="#" class="link">Lien standard (link)</a>
            </div>
        </div>
    </section>

    {{-- STAT LABELS --}}
    <section class="space-y-4">
        <h2 class="section-title">Labels de statistiques</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="stat-card">
                <p class="sub-label mb-1">.label-value</p>
                <div class="label-title">Familles</div>
                <div class="label-value">42</div>
            </div>
            <div class="stat-card">
                <p class="sub-label mb-1">.label-value--warning</p>
                <div class="label-title">En attente</div>
                <div class="label-value--warning">8</div>
            </div>
            <div class="stat-card">
                <p class="sub-label mb-1">.label-value--success</p>
                <div class="label-title">Validés</div>
                <div class="label-value--success">34</div>
            </div>
            <div class="stat-card">
                <p class="sub-label mb-1">.label-value--info</p>
                <div class="label-title">Info</div>
                <div class="label-value--info">12</div>
            </div>
            <div class="stat-card">
                <p class="sub-label mb-1">.label-value--yellow</p>
                <div class="label-title">Avertissement</div>
                <div class="label-value--yellow">5</div>
            </div>
            <div class="stat-card">
                <p class="sub-label mb-1">.label-value--purple</p>
                <div class="label-title">Imprimés</div>
                <div class="label-value--purple">20</div>
            </div>
        </div>
    </section>

    {{-- FORM FIELDS --}}
    <section class="space-y-4">
        <h2 class="section-title">Champs de formulaire</h2>
        <div class="card space-y-6">
            <div>
                <p class="sub-label mb-2">.field-input — état normal</p>
                <label class="field-label">Prénom</label>
                <input type="text" class="field-input" placeholder="Exemple de champ texte" />
            </div>
            <div>
                <p class="sub-label mb-2">.field-input-error — état erreur</p>
                <label class="field-label">E-mail</label>
                <input type="email" class="field-input-error" value="mauvaise-valeur" />
                <p class="field-error">Ce champ est invalide.</p>
            </div>
            <div>
                <p class="sub-label mb-2">.field-input — select</p>
                <label class="field-label">Rôle</label>
                <select class="field-input">
                    <option>Admin</option>
                    <option>Validateur</option>
                    <option>Organisateur</option>
                </select>
            </div>
            <div>
                <p class="sub-label mb-2">.field-input — textarea</p>
                <label class="field-label">Commentaire</label>
                <textarea class="field-input" rows="3" placeholder="Votre message..."></textarea>
            </div>
        </div>
    </section>

    {{-- BUTTONS --}}
    <section class="space-y-4">
        <h2 class="section-title">Boutons</h2>
        <div class="card space-y-4">
            <div>
                <p class="sub-label mb-2">.btn-primary</p>
                <button class="btn-primary">Enregistrer (btn-primary)</button>
            </div>
            <div>
                <p class="sub-label mb-2">.btn-primary — disabled</p>
                <button class="btn-primary" disabled>Désactivé (btn-primary:disabled)</button>
            </div>
            <div>
                <p class="sub-label mb-2">.btn-secondary</p>
                <button class="btn-secondary">Annuler (btn-secondary)</button>
            </div>
            <div>
                <p class="sub-label mb-2">.btn-confirm</p>
                <button class="btn-confirm">Confirmer (btn-confirm)</button>
            </div>
            <div>
                <p class="sub-label mb-2">.btn-blue</p>
                <button class="btn-blue">Action bleue (btn-blue)</button>
            </div>
            <div>
                <p class="sub-label mb-2">.btn-warning</p>
                <button class="btn-warning">Demander correction (btn-warning)</button>
            </div>
            <div>
                <p class="sub-label mb-2">.btn-danger</p>
                <button class="btn-danger">Supprimer (btn-danger)</button>
            </div>
            <div>
                <p class="sub-label mb-2">.btn-gray</p>
                <button class="btn-gray">Neutre (btn-gray)</button>
            </div>
        </div>
    </section>

    {{-- BADGES --}}
    <section class="space-y-4">
        <h2 class="section-title">Badges de statut</h2>
        <div class="card flex flex-wrap gap-3">
            <span class="badge--pending">.badge--pending — À valider</span>
            <span class="badge--validated">.badge--validated — Validé</span>
            <span class="badge--rejected">.badge--rejected — Rejeté</span>
            <span class="badge--printed">.badge--printed — Imprimé</span>
            <span class="badge--received">.badge--received — Reçu</span>
            <span class="badge--given">.badge--given — Remis</span>
            <span class="badge--info">.badge--info — Info / Rôle</span>
            <span class="badge--neutral">.badge--neutral — Neutre</span>
            <span class="badge--warning">.badge--warning — Avertissement</span>
        </div>
    </section>

    {{-- CARDS --}}
    <section class="space-y-4">
        <h2 class="section-title">Cartes</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="sub-label mb-2">.card</p>
                <div class="card">
                    <p class="detail-value">Contenu d'une carte (.card)</p>
                    <p class="text-muted">Padding large, ombre importante.</p>
                </div>
            </div>
            <div>
                <p class="sub-label mb-2">.card-sm</p>
                <div class="card-sm">
                    <p class="detail-value">Petite carte (.card-sm)</p>
                    <p class="text-muted">Padding réduit, même ombre.</p>
                </div>
            </div>
            <div>
                <p class="sub-label mb-2">.stat-card</p>
                <div class="stat-card">
                    <div class="label-title">Familles</div>
                    <div class="label-value">42</div>
                </div>
            </div>
            <div>
                <p class="sub-label mb-2">.card-footer</p>
                <div class="card-footer">
                    Pied de page ou lien centré (.card-footer)
                </div>
            </div>
        </div>
    </section>

    {{-- TABLE --}}
    <section class="space-y-4">
        <h2 class="section-title">Tableau</h2>
        <div class="table-container">
            <table class="min-w-full">
                <thead>
                    <tr>
                        <th class="table-header">Nom (.table-header)</th>
                        <th class="table-header">Statut</th>
                        <th class="table-header">Date</th>
                    </tr>
                </thead>
                <tbody class="table-divider">
                    <tr>
                        <td class="table-cell">Dupont Marie (.table-cell)</td>
                        <td class="table-cell"><span class="badge--pending">À valider</span></td>
                        <td class="table-cell-muted">01/12/2025 (.table-cell-muted)</td>
                    </tr>
                    <tr>
                        <td class="table-cell">Martin Jean (.table-cell)</td>
                        <td class="table-cell"><span class="badge--validated">Validé</span></td>
                        <td class="table-cell-muted">02/12/2025 (.table-cell-muted)</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="table-empty">.table-empty — Aucune donnée disponible</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- MODAL STRUCTURE --}}
    <section class="space-y-4">
        <h2 class="section-title">Structure de modale (statique)</h2>
        <div class="card">
            <p class="text-muted mb-4">Exemple de structure (.modal-panel / .modal-header / .modal-footer) sans overlay (.modal-backdrop).</p>
            <div class="modal-panel relative static max-w-md mx-auto">
                <div class="modal-header">
                    <span class="detail-value">Titre de la modale</span>
                    <button class="text-muted">&times;</button>
                </div>
                <div class="p-4">
                    <p class="text-muted">Contenu de la modale.</p>
                </div>
                <div class="modal-footer flex justify-end gap-2">
                    <button class="btn-secondary">Annuler</button>
                    <button class="btn-confirm">Confirmer</button>
                </div>
            </div>
        </div>
    </section>

    {{-- LAYOUT HELPERS --}}
    <section class="space-y-4">
        <h2 class="section-title">Helpers de mise en page</h2>
        <div class="card space-y-6">
            <div>
                <p class="sub-label mb-3">.agenda-item</p>
                <div class="space-y-2">
                    <div class="agenda-item">
                        <span class="text-green-500">✓</span>
                        <span class="detail-value">Étape 1 — Inscription de la famille</span>
                    </div>
                    <div class="agenda-item">
                        <span class="text-yellow-500">⏳</span>
                        <span class="detail-value">Étape 2 — Validation par un bénévole</span>
                    </div>
                    <div class="agenda-item">
                        <span class="text-muted">📦</span>
                        <span class="text-muted">Étape 3 — Remise des cadeaux</span>
                    </div>
                </div>
            </div>
            <div>
                <p class="sub-label mb-3">.slot-pill</p>
                <div class="flex flex-wrap gap-2">
                    <div class="slot-pill">🕐 09h00 – 10h00</div>
                    <div class="slot-pill">🕑 10h00 – 11h00</div>
                    <div class="slot-pill">🕒 14h00 – 15h00</div>
                </div>
            </div>
        </div>
    </section>

</div>
