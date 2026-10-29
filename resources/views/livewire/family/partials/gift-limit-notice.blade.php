@if($giftLimitMessage)
    <div class="notice-warning">
        <p class="notice-title">Nombre maximum de cadeaux atteint</p>
        <p class="notice-text">@linkify($giftLimitMessage)</p>
        <p class="notice-text">Vous pouvez modifier votre demande, mais vous ne pouvez plus ajouter d'enfant.</p>
        @if($this->hasNewChildren())
            <p class="notice-text">Retirez les enfants ajoutés pour pouvoir enregistrer votre demande.</p>
        @endif
    </div>
@endif
