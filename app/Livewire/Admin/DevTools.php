<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Family;
use App\Models\GiftRequest;
use App\Models\Season;
use Illuminate\Support\Str;
use Livewire\Component;

class DevTools extends Component
{
    public ?Season $activeSeason = null;
    public int $familyCount = 5;
    public string $flashMessage = '';
    public string $flashType = '';

    public function mount(): void
    {
        $this->activeSeason = Season::getActive();
    }

    public function seedFamilies(): void
    {
        if (! $this->activeSeason) {
            $this->flash('Aucune saison active.', 'error');

            return;
        }

        $this->validate([
            'familyCount' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $created = 0;
        $firstNames = ['Marie', 'Jean', 'Sophie', 'Pierre', 'Isabelle', 'François', 'Catherine', 'Michel', 'Anne', 'Philippe', 'Nathalie', 'Laurent', 'Céline', 'Thierry', 'Valérie', 'Christophe', 'Sandrine', 'Olivier', 'Stéphanie', 'Patrick'];
        $lastNames = ['Dupont', 'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent'];
        $cities = ['Lausanne', 'Genève', 'Fribourg', 'Neuchâtel', 'Sion', 'Yverdon', 'Montreux', 'Vevey', 'Nyon', 'Morges'];
        $streets = ['Rue de la Gare', 'Avenue du Lac', 'Chemin des Prés', 'Route de Berne', 'Rue du Marché', 'Avenue de la Paix', 'Chemin du Soleil', 'Rue de Lausanne', 'Avenue des Alpes', 'Rue du Commerce'];
        $gifts = ['Poupée', 'Voiture télécommandée', 'Lego', 'Puzzle', 'Peluche', 'Jeu de société', 'Ballon de foot', 'Livre illustré', 'Kit de dessin', 'Trottinette', 'Vélo', 'Jeu vidéo', 'Pâte à modeler', 'Déguisement', 'Robot jouet'];
        $childFirstNames = ['Emma', 'Louis', 'Léa', 'Hugo', 'Chloé', 'Lucas', 'Manon', 'Nathan', 'Inès', 'Théo', 'Jade', 'Raphaël', 'Alice', 'Arthur', 'Lina', 'Jules', 'Zoé', 'Adam', 'Eva', 'Gabriel'];
        $genders = [Child::GENDER_BOY, Child::GENDER_GIRL, Child::GENDER_UNSPECIFIED];

        for ($i = 0; $i < $this->familyCount; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $email = Str::slug($firstName).'.'.Str::slug($lastName).'.'.Str::random(4).'@example.com';

            // 30% chance of being an existing family not yet in this season
            $family = null;
            if (rand(1, 100) <= 30) {
                $family = Family::whereDoesntHave('giftRequests', function ($q) {
                    $q->where('season_id', $this->activeSeason->id);
                })->inRandomOrder()->first();
            }

            if (! $family) {
                $family = Family::create([
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'address' => $streets[array_rand($streets)].' '.rand(1, 50),
                    'postal_code' => strval(rand(1000, 1999)),
                    'city' => $cities[array_rand($cities)],
                    'phone' => '07'.str_pad(strval(rand(0, 99999999)), 8, '0', STR_PAD_LEFT),
                ]);
            }

            // Skip if family already has a request for this season
            if ($family->getRequestForSeason($this->activeSeason)) {
                continue;
            }

            $giftRequest = GiftRequest::create([
                'family_id' => $family->id,
                'season_id' => $this->activeSeason->id,
                'status' => GiftRequest::STATUS_PENDING,
            ]);

            $childCount = rand(1, 5);
            for ($j = 0; $j < $childCount; $j++) {
                $gender = $genders[array_rand($genders)];
                Child::create([
                    'gift_request_id' => $giftRequest->id,
                    'first_name' => $childFirstNames[array_rand($childFirstNames)],
                    'gender' => $gender,
                    'anonymous' => (bool) rand(0, 1),
                    'birth_year' => (int) now()->format('Y') - rand(1, 14),
                    'height' => rand(70, 170),
                    'gift' => $gifts[array_rand($gifts)],
                    'shoe_size' => strval(rand(20, 42)),
                    'status' => Child::STATUS_PENDING,
                ]);
            }

            $created++;
        }

        $this->flash("{$created} famille(s) créée(s) avec leurs enfants.", 'success');
    }

    public function batchValidate(): void
    {
        if (! $this->activeSeason) {
            $this->flash('Aucune saison active.', 'error');

            return;
        }

        $familiesValidated = GiftRequest::where('season_id', $this->activeSeason->id)
            ->where('status', GiftRequest::STATUS_PENDING)
            ->count();

        GiftRequest::where('season_id', $this->activeSeason->id)
            ->where('status', GiftRequest::STATUS_PENDING)
            ->update([
                'status' => GiftRequest::STATUS_VALIDATED,
                'status_changed_at' => now(),
            ]);

        $childrenValidated = Child::whereHas('giftRequest', function ($q) {
            $q->where('season_id', $this->activeSeason->id);
        })->where('status', Child::STATUS_PENDING)->count();

        Child::whereHas('giftRequest', function ($q) {
            $q->where('season_id', $this->activeSeason->id);
        })->where('status', Child::STATUS_PENDING)->update([
            'status' => Child::STATUS_VALIDATED,
            'status_changed_at' => now(),
            'validated_at' => now(),
        ]);

        $this->flash("{$familiesValidated} famille(s) et {$childrenValidated} enfant(s) validé(s).", 'success');
    }

    protected function flash(string $message, string $type): void
    {
        $this->flashMessage = $message;
        $this->flashType = $type;
    }

    public function render()
    {
        $stats = [];
        if ($this->activeSeason) {
            $stats = [
                'totalFamilies' => GiftRequest::where('season_id', $this->activeSeason->id)->count(),
                'pendingFamilies' => GiftRequest::where('season_id', $this->activeSeason->id)
                    ->where('status', GiftRequest::STATUS_PENDING)->count(),
                'totalChildren' => Child::whereHas('giftRequest', fn ($q) => $q->where('season_id', $this->activeSeason->id))->count(),
                'pendingChildren' => Child::whereHas('giftRequest', fn ($q) => $q->where('season_id', $this->activeSeason->id))
                    ->where('status', Child::STATUS_PENDING)->count(),
            ];
        }

        return view('livewire.admin.dev-tools', compact('stats'));
    }
}
