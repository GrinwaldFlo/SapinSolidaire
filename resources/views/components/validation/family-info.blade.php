@props(['request'])

<h2 class="section-title">Informations de la famille</h2>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <div>
        <span class="detail-label">Nom :</span>
        <span class="detail-inline detail-value">{{ $request->family->first_name }} {{ $request->family->last_name }}</span>
    </div>
    <div>
        <span class="detail-label">Email :</span>
        <x-admin.contact.email :email="$request->family->email" class="detail-inline" />
    </div>
    <div>
        <span class="detail-label">Téléphone :</span>
        <x-admin.contact.phone :tel="$request->family->tel_phone" :formatted="$request->family->formatted_phone" class="detail-inline" />
    </div>
    <div>
        <span class="detail-label">Adresse :</span>
        @if($request->family->full_address)
            <x-admin.contact.address :address="$request->family->full_address" class="detail-inline" />
        @else
            <span class="detail-inline detail-value">-</span>
        @endif
    </div>
</div>

<div class="flex items-center gap-2 mb-4">
    <span class="detail-label">Statut famille :</span>
    <span class="{{ $request->status_badge_class }}">{{ $request->status_label }}</span>
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
