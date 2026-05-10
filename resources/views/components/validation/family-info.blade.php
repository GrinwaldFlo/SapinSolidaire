@props(['request'])

<h2 class="section-title">Informations de la famille</h2>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <div>
        <span class="detail-label">Nom :</span>
        <span class="ml-2 detail-value">{{ $request->family->first_name }} {{ $request->family->last_name }}</span>
    </div>
    <div>
        <span class="detail-label">Email :</span>
        <span class="ml-2 detail-value">{{ $request->family->email }}</span>
    </div>
    <div>
        <span class="detail-label">Téléphone :</span>
        <a href="tel:{{ $request->family->tel_phone }}" class="ml-2 inline-flex items-center gap-1 link">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.47 11.47 0 0 0 3.58.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.58a1 1 0 0 1-.25 1.01z"/></svg>
            {{ $request->family->formatted_phone }}
        </a>
    </div>
    <div>
        <span class="detail-label">Adresse :</span>
        <span class="ml-2 detail-value">{{ $request->family->full_address }}</span>
    </div>
</div>

<div class="flex items-center gap-2 mb-4">
    <span class="detail-label">Statut famille :</span>
    @if($request->status === 'pending')
        <span class="badge--pending">À valider</span>
    @elseif($request->status === 'validated')
        <span class="badge--validated">Validé</span>
    @endif
</div>

@if($request->proof_of_habitation_path)
    @php
        $proofUrl = route('admin.proof-of-habitation', $request);
        $isPdfProof = \Illuminate\Support\Str::endsWith(strtolower($request->proof_of_habitation_path), '.pdf');
    @endphp
    <div class="mb-4">
        <span class="detail-label">Justificatif de domicile :</span>
        <div class="mt-2">
            @if($isPdfProof)
                <div class="card-sm max-w-2xl">
                    <p class="text-sm text-muted mb-3">Document PDF reçu.</p>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ $proofUrl }}" target="_blank" rel="noopener noreferrer" class="btn-blue inline-flex items-center text-sm">
                            Ouvrir le PDF
                        </a>
                        <a href="{{ $proofUrl }}" download class="btn-gray inline-flex items-center text-sm">
                            Télécharger
                        </a>
                    </div>
                </div>
            @else
                <button type="button" @click="imageUrl = '{{ $proofUrl }}'; imageAlt = 'Justificatif de domicile'; showImageModal = true" class="inline-block cursor-pointer">
                    <img src="{{ $proofUrl }}" alt="Justificatif de domicile" class="max-w-sm max-h-64 rounded-lg border border-gray-200 dark:border-zinc-700 hover:opacity-90 transition">
                </button>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Cliquez sur l'image pour l'agrandir</p>
            @endif
        </div>
    </div>
@endif

{{ $slot }}
