<div class="space-y-10">

    <div class="flex items-center justify-between">
        <h1 class="section-title">🎨 CSS Showcase</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">Aperçu de toutes les classes CSS disponibles</p>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- NOTICES / BANNERS --}}
    {{-- ------------------------------------------------------------------ --}}
    <section class="space-y-4">
        <h2 class="section-title">Notices &amp; Banners</h2>

        <div class="space-y-3">
            <div class="notice-info">
                <strong>.notice-info</strong> — Bannière d'information. Utilisée pour afficher des informations générales.
            </div>
            <div class="notice-success">
                <strong>.notice-success</strong> — Bannière de succès. Utilisée pour confirmer une action réussie.
            </div>
            <div class="notice-warning">
                <strong>.notice-warning</strong> — Bannière d'avertissement. Utilisée pour alerter l'utilisateur.
            </div>
            <div class="notice-error">
                <strong>.notice-error</strong> — Bannière d'erreur. Utilisée pour signaler un problème.
            </div>
        </div>
    </section>

    {{-- ------------------------------------------------------------------ --}}
    {{-- TYPOGRAPHY --}}
    {{-- ------------------------------------------------------------------ --}}
    <section class="space-y-4">
        <h2 class="section-title">Typographie</h2>

        <div class="card space-y-4">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">.section-title</p>
                <p class="section-title">Titre de section avec bordure basse</p>
            </div>

            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">.field-label</p>
                <label class="field-label">Libellé de champ de formulaire</label>
            </div>

            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">.field-error</p>
                <p class="field-error">Message d'erreur de validation sous un champ</p>
            </div>

            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-1">text-sm text-gray-500 (muted)</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Texte secondaire / description</p>
            </div>
        </div>
    </section>

    {{-- ------------------------------------------------------------------ --}}
    {{-- FORM FIELDS --}}
    {{-- ------------------------------------------------------------------ --}}
    <section class="space-y-4">
        <h2 class="section-title">Champs de formulaire</h2>

        <div class="card space-y-6">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">.field-input — état normal</p>
                <label class="field-label">Prénom</label>
                <input type="text" class="field-input" placeholder="Exemple de champ texte" />
            </div>

            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">.field-input-error — état erreur</p>
                <label class="field-label">E-mail</label>
                <input type="email" class="field-input-error" value="mauvaise-valeur" />
                <p class="field-error">Ce champ est invalide.</p>
            </div>

            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">.field-input — select</p>
                <label class="field-label">Rôle</label>
                <select class="field-input">
                    <option>Admin</option>
                    <option>Validateur</option>
                    <option>Organisateur</option>
                </select>
            </div>

            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">.field-input — textarea</p>
                <label class="field-label">Commentaire</label>
                <textarea class="field-input" rows="3" placeholder="Votre message..."></textarea>
            </div>
        </div>
    </section>

    {{-- ------------------------------------------------------------------ --}}
    {{-- BUTTONS --}}
    {{-- ------------------------------------------------------------------ --}}
    <section class="space-y-4">
        <h2 class="section-title">Boutons</h2>

        <div class="card space-y-6">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">.btn-primary</p>
                <button class="btn-primary">Enregistrer (btn-primary)</button>
            </div>

            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">.btn-primary — disabled</p>
                <button class="btn-primary" disabled>Désactivé (btn-primary:disabled)</button>
            </div>

            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">.btn-secondary</p>
                <button class="btn-secondary">Annuler (btn-secondary)</button>
            </div>

            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">.btn-confirm</p>
                <button class="btn-confirm">Confirmer (btn-confirm)</button>
            </div>

            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">Boutons ad-hoc (utilisés dans dev-tools)</p>
                <div class="flex flex-wrap gap-2">
                    <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">Indigo</button>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm">Blue</button>
                    <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">Green</button>
                    <button class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm">Purple</button>
                </div>
            </div>
        </div>
    </section>

    {{-- ------------------------------------------------------------------ --}}
    {{-- CARDS --}}
    {{-- ------------------------------------------------------------------ --}}
    <section class="space-y-4">
        <h2 class="section-title">Cartes</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">.card</p>
                <div class="card">
                    <p class="text-gray-700 dark:text-gray-300">Contenu d'une carte (.card)</p>
                </div>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">.card-sm</p>
                <div class="card-sm">
                    <p class="text-gray-700 dark:text-gray-300">Contenu d'une petite carte (.card-sm)</p>
                </div>
            </div>
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-2">.stat-card</p>
                <div class="stat-card">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Familles</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">42</div>
                </div>
            </div>
        </div>
    </section>

    {{-- ------------------------------------------------------------------ --}}
    {{-- TABLE --}}
    {{-- ------------------------------------------------------------------ --}}
    <section class="space-y-4">
        <h2 class="section-title">Tableau</h2>

        <div class="table-container">
            <table class="min-w-full">
                <thead class="bg-gray-50 dark:bg-zinc-700">
                    <tr>
                        <th class="table-header">.table-header — Nom</th>
                        <th class="table-header">.table-header — Statut</th>
                        <th class="table-header">.table-header — Date</th>
                    </tr>
                </thead>
                <tbody class="table-divider">
                    <tr>
                        <td class="table-cell">Dupont Marie (.table-cell)</td>
                        <td class="table-cell-muted">En attente (.table-cell-muted)</td>
                        <td class="table-cell-muted">01/12/2025</td>
                    </tr>
                    <tr>
                        <td class="table-cell">Martin Jean (.table-cell)</td>
                        <td class="table-cell-muted">Validé (.table-cell-muted)</td>
                        <td class="table-cell-muted">02/12/2025</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    {{-- ------------------------------------------------------------------ --}}
    {{-- LAYOUT HELPERS --}}
    {{-- ------------------------------------------------------------------ --}}
    <section class="space-y-4">
        <h2 class="section-title">Helpers de mise en page</h2>

        <div class="card space-y-6">
            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">.agenda-item</p>
                <div class="space-y-2">
                    <div class="agenda-item">
                        <span class="text-green-500">✓</span>
                        <span class="text-gray-700 dark:text-gray-300">Étape 1 — Inscription de la famille</span>
                    </div>
                    <div class="agenda-item">
                        <span class="text-yellow-500">⏳</span>
                        <span class="text-gray-700 dark:text-gray-300">Étape 2 — Validation par un bénévole</span>
                    </div>
                    <div class="agenda-item">
                        <span class="text-gray-400">📦</span>
                        <span class="text-gray-700 dark:text-gray-300">Étape 3 — Remise des cadeaux</span>
                    </div>
                </div>
            </div>

            <div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">.slot-pill</p>
                <div class="flex flex-wrap gap-2">
                    <div class="slot-pill">🕐 09h00 – 10h00</div>
                    <div class="slot-pill">🕑 10h00 – 11h00</div>
                    <div class="slot-pill">🕒 14h00 – 15h00</div>
                </div>
            </div>
        </div>
    </section>

</div>
