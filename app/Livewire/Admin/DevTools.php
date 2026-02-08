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
        $firstNames = ['Marie', 'Jean', 'Sophie', 'Pierre', 'Isabelle', 'François', 'Catherine', 'Michel', 'Anne', 'Philippe', 'Nathalie', 'Laurent', 'Céline', 'Thierry', 'Valérie', 'Christophe', 'Sandrine', 'Olivier', 'Stéphanie', 'Patrick', 'Emma', 'Louis', 'Léa', 'Hugo', 'Chloé', 'Lucas', 'Manon', 'Nathan', 'Inès', 'Théo', 'Jade', 'Raphaël', 'Alice', 'Arthur', 'Lina', 'Jules', 'Zoé', 'Adam', 'Eva', 'Gabriel', 'Luc', 'Marc', 'Anne-Marie', 'Christine', 'Véronique', 'Dominique', 'Jacqueline', 'Monique', 'Francine', 'Denise', 'Carlos', 'Antonio', 'Juan', 'Diego', 'Miguel', 'Fernando', 'Roberto', 'Francisco', 'Pablo', 'Alejandro', 'Iñigo', 'Ramón', 'Raúl', 'Luís', 'Gonzalo', 'Javier', 'Ignacio', 'Víctor', 'Ángel', 'Estela', 'Pilar', 'Amparo', 'Emilia', 'Rosario', 'Concepción', 'Dolores', 'Consuelo', 'Marisol', 'Graciela', 'Giovanna', 'Francesca', 'Maria', 'Chiara', 'Giulia', 'Lucia', 'Valeria', 'Alessia', 'Martina', 'Isabella', 'Anna', 'Sofia', 'Giordana', 'Marta', 'Serena', 'Benedetta', 'Paola', 'Gabriella', 'Roberta', 'Patrizia', 'Giovanni', 'Marco', 'Andrea', 'Paolo', 'Matteo', 'Alessandro', 'Davide', 'Leonardo', 'Stefano', 'Massimo', 'Klaus', 'Wolfgang', 'Hans', 'Jürgen', 'Helmut', 'Dieter', 'Herbert', 'Karl', 'Gerhard', 'Rolf', 'Günther', 'Christoph', 'Albrecht', 'Siegfried', 'Friedhelm', 'Angela', 'Petra', 'Gisela', 'Heidi', 'Doris', 'Gabriele', 'Renate', 'Ursula', 'Katharina', 'Margot', 'Gräfin', 'Elfriede', 'Gudrun', 'Edeltraud', 'Heidrun', 'Hiltraud', 'Irmtraud', 'Anders', 'Erik', 'Lars', 'Sven', 'Arne', 'Olof', 'Nils', 'Gunnar', 'Bengt', 'Per', 'Åke', 'Börje', 'Folke', 'Göran', 'Leif', 'Bertil', 'Torsten', 'Stellan', 'Anna-Karin', 'Ingrid', 'Birgitta', 'Solveig', 'Annika', 'Ulla', 'Karin', 'Marianne', 'Barbro', 'Kristina', 'Katrina', 'Börje', 'Maj-Britt', 'Ebba', 'Ragnhild', 'Gerd', 'Andrew', 'Michael', 'David', 'James', 'Robert', 'John', 'Charles', 'William', 'Thomas', 'Richard', 'Mary', 'Patricia', 'Jennifer', 'Margaret', 'Elizabeth', 'Susan', 'Jessica', 'Sarah', 'Linda', 'Barbara', 'Elena', 'Anastasia', 'Dmitri', 'Sergei', 'Igor', 'Vladimir', 'Alexei', 'Nikolai', 'Andrei', 'Yuri', 'Pavel', 'Boris', 'Fiódor', 'Grigori', 'Mijaíl', 'Vladímir', 'Natalia', 'Svetlana', 'Tatiana', 'Olga', 'Oksana', 'Lydia', 'Irina', 'Galina', 'Vera', 'Marina', 'Yekaterina', 'Evgeniya', 'Kenji', 'Takeshi', 'Hiroshi', 'Yuki', 'Akira', 'Masao', 'Isao', 'Toshiro', 'Noboru', 'Goro', 'Jirō', 'Tarō', 'Hanako', 'Sakura', 'Miyuki', 'Tomoe', 'Kaori', 'Aiko', 'Hiromi', 'Naomi', 'Fumiko', 'Kyoko', 'Emiko', 'Yōko', 'Wei', 'Ming', 'Li', 'Zhang', 'Wang', 'Liu', 'Chen', 'Yang', 'Huang', 'Wu', 'Hua', 'Jing', 'Mei', 'Xiu', 'Fang', 'Lei', 'Yan', 'Hong', 'Ping', 'Lin', 'Łucja', 'Zbigniew', 'Stanisław', 'Ryszard', 'Małgorzata', 'Krzysztof', 'Elżbieta', 'Mirosław', 'Bogumiła', 'Grzegorz', 'Jadwiga', 'Ignacy', 'Ahmed', 'Mohammed', 'Hassan', 'Ibrahim', 'Karim', 'Mehmet', 'Mübarek', 'Fatima', 'Aisha', 'Leila', 'Mariam', 'Zainab', 'Layla', 'Amira', 'Noor', 'Yasmine', 'Hana', 'Samira', 'Ayşe', 'Hüseyin', 'Priya', 'Amit', 'Rajesh', 'Vikram', 'Ashok', 'Deepak', 'Ravi', 'Sanjay', 'Arjun', 'Aryan', 'Akbar', 'Aishwarya', 'Ananya', 'Divya', 'Kavya', 'Shreya'];
        $lastNames = ['Dupont', 'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent', 'Rodriguez', 'Martinez', 'Gonzalez', 'Sanchez', 'Hernandez', 'Jiménez', 'Domínguez', 'Rodríguez', 'Vázquez', 'Ramírez', 'Pérez', 'López', 'Sáenz', 'Núñez', 'Ríos', 'Montoya', 'Córdoba', 'Rossi', 'Ferrari', 'Bianchi', 'Ricci', 'Russo', 'Gallo', 'Costa', 'Rosso', 'Conte', 'Puccini', 'Verdi', 'Neri', 'Conti', 'Müller', 'Schmidt', 'Schneider', 'Fischer', 'Weber', 'Meyer', 'Wagner', 'Becker', 'Schulz', 'Hoffmann', 'Schäfer', 'Krämer', 'Jäger', 'Böhm', 'Schröder', 'Köhler', 'Förster', 'Grüber', 'Andersson', 'Johnsson', 'Eriksson', 'Svensson', 'Carlsson', 'Bergman', 'Lindström', 'Nilsson', 'Gustafsson', 'Persson', 'Sundström', 'Ström', 'Norström', 'Söderström', 'Löfgren', 'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Wilson', 'Moore', 'Taylor', 'Anderson', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin', 'Thompson', 'Garcia', 'Martinez', 'Robinson', 'Kowalski', 'Lewandowski', 'Nowak', 'Szymanski', 'Wójcik', 'Chmielewski', 'Grabowski', 'Kucharski', 'Lewicki', 'Żak', 'Łukasik', 'Smoleński', 'Łopatka', 'Ali', 'Hassan', 'Karimi', 'Hosseini', 'Rahimi', 'Abbasi', 'Ardakani', 'Najafi', 'Rezaei', 'Vakili', 'Aysegül', 'Yilmaz', 'Kaya', 'Özdemir', 'Arslan', 'Balci', 'Coşkun', 'Dağ', 'Eroğlu', 'Günşen', 'Kaplan', 'Kumar', 'Sharma', 'Patel', 'Singh', 'Verma', 'Gupta', 'Rao', 'Nair', 'Reddy', 'Sinha'];

        $cities = ['Lausanne', 'Genève', 'Fribourg', 'Neuchâtel', 'Sion', 'Yverdon', 'Montreux', 'Vevey', 'Nyon', 'Morges'];
        $streets = ['Rue de la Gare', 'Avenue du Lac', 'Chemin des Prés', 'Route de Berne', 'Rue du Marché', 'Avenue de la Paix', 'Chemin du Soleil', 'Rue de Lausanne', 'Avenue des Alpes', 'Rue du Commerce'];
        $gifts = ['Poupée', 'Voiture télécommandée', 'Lego', 'Puzzle', 'Peluche', 'Jeu de société', 'Ballon de foot', 'Livre illustré', 'Kit de dessin', 'Trottinette', 'Vélo', 'Jeu vidéo', 'Pâte à modeler', 'Déguisement', 'Robot jouet', 'Scooter électrique', 'Skateboard', 'Rollers', 'Patins à glace', 'Ballon de basket', 'Ballon de volley', 'Raquette de tennis', 'Raquette de badminton', 'Corde à sauter', 'Trampoline', 'Piscine gonflable', 'Planches à roulettes', 'Trotteur de course', 'Cerceaux', 'Quilles', 'Palet de hockey', 'Boomerang', 'Cerf-volant', 'Frisbee', 'Chaussures de sport', 'Montre intelligente', 'Casque audio', 'Enceinte Bluetooth', 'Lampe LED tactile', 'Veilleuse projecteur', 'Globe terrestre', 'Telescope', 'Microscope', 'Binoculars', 'Appareil photo instantané', 'Caméra numérique', 'Drone', 'Kit robot programmable', 'Jeu d\'échecs magnétique', 'Jeu de dames', 'Cartes à jouer', 'Dominoes', 'Cubes Rubik', 'Casse-têtes 3D', 'Maquette à construire', 'Kit de science', 'Kit de chimie', 'Kit de cristallographie', 'Kit de géologie', 'Ensemble de peinture', 'Palette de couleurs', 'Bloc de dessin', 'Marqueurs de couleur', 'Crayons de couleur', 'Stylos à gel', 'Pinceau artistique', 'Chevalet de peinture', 'Argile polymère', 'Pâte à modeler naturelle', 'Kit de couture', 'Métier à tisser', 'Kit de broderie', 'Kit de bijoux', 'Perles à enfiler', 'Bracelets à faire soi-même', 'Collier à fabriquer', 'Kit de macramé', 'Autocollants', 'Décalques', 'Stickers luminescents', 'Stickers 3D', 'Tatouages temporaires', 'Cahier de coloriage', 'Cahier d\'activités', 'Livre de contes', 'Bande dessinée', 'Comics', 'Revue enfantine', 'Encyclopédie illustrée', 'Atlas mondial', 'Dictionnaire jeunesse', 'Livre de cuisine enfant', 'Livre de magie', 'Livre d\'aventure', 'Livre d\'énigmes'];
        $childFirstNames = $firstNames;
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
