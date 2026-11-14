{{-- Stacked contact lines of a family (email, phone, address); a missing phone or address is shown as "-" --}}
@props(['family'])
<div {{ $attributes->merge(['class' => 'contact-list']) }}>
    <div><x-admin.contact.email :email="$family->email" /></div>
    <div>
        @if($family->phone)
            <x-admin.contact.phone :tel="$family->tel_phone" :formatted="$family->formatted_phone" />
        @else
            -
        @endif
    </div>
    <div>
        @if($family->full_address)
            <x-admin.contact.address :address="$family->full_address" />
        @else
            -
        @endif
    </div>
</div>
