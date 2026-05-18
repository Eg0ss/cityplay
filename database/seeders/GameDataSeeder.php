<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\City;
use App\Models\Place;
use App\Models\Riddle;

class GameDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $citiesData = [
            [
                'name' => 'Cotonou',
                'departement' => 'Littoral',
                'lat' => 6.3650,
                'lng' => 2.4183,
                'places' => [
                    'Place de l\'Étoile Rouge', 'Stade de l\'Amitié', 'Marché Dantokpa', 'Fidjrossè Plage', 
                    'Aéroport de Cotonou', 'Cathédrale Notre-Dame', 'Mosquée Centrale', 'Centre Artisanal', 
                    'Haie Vive', 'Port de Cotonou'
                ]
            ],
            [
                'name' => 'Porto-Novo',
                'departement' => 'Ouémé',
                'lat' => 6.4969,
                'lng' => 2.6288,
                'places' => [
                    'Musée Honmé', 'Place Jean Bayol', 'Cathédrale Notre-Dame', 'Grande Mosquée', 
                    'Jardin des Plantes', 'Assemblée Nationale', 'Marché Ouando', 'Pont de Porto-Novo', 
                    'Musée da Silva', 'Place Toffa 1er'
                ]
            ],
            [
                'name' => 'Parakou',
                'departement' => 'Borgou',
                'lat' => 9.3372,
                'lng' => 2.6303,
                'places' => [
                    'Place Tabéra', 'Marché Arigbo', 'Musée en Plein Air', 'Cathédrale Saint Pierre et Saint Paul', 
                    'Université de Parakou', 'Gare Ferroviaire', 'Mosquée de Yéboubéri', 'Palais Royal', 
                    'Hôpital Boko', 'Aérodrome de Parakou'
                ]
            ],
            [
                'name' => 'Abomey',
                'departement' => 'Zou',
                'lat' => 7.1829,
                'lng' => 1.9912,
                'places' => [
                    'Palais Royaux d\'Abomey', 'Place Goho', 'Musée Historique', 'Marché Moundi', 
                    'Temple des Vodoun', 'Village Artisanal', 'Statue du Roi Béhanzin', 'Forêt Sacrée', 
                    'Palais de Djimè', 'Palais d\'Agonglo'
                ]
            ],
            [
                'name' => 'Ouidah',
                'departement' => 'Atlantique',
                'lat' => 6.3644,
                'lng' => 2.0833,
                'places' => [
                    'Porte du Non-Retour', 'Temple des Pythons', 'Musée d\'Histoire', 'Basilique', 
                    'Forêt Sacrée de Kpassè', 'Route des Esclaves', 'Marché de Zobè', 'Place Chacha', 
                    'Statue de de Souza', 'Villa Ajavon'
                ]
            ],
        ];

        foreach ($citiesData as $cityInfo) {
            $city = City::firstOrCreate(
                ['name' => $cityInfo['name']],
                ['departement' => $cityInfo['departement']]
            );

            foreach ($cityInfo['places'] as $index => $placeName) {
                // Generate a slight offset for each place to spread them around the city
                // 0.01 deg is roughly 1km
                $placeLat = $cityInfo['lat'] + (rand(-30, 30) / 1000);
                $placeLng = $cityInfo['lng'] + (rand(-30, 30) / 1000);

                $place = Place::firstOrCreate(
                    ['nom' => $placeName, 'city_id' => $city->id],
                    [
                        'ville' => $city->name,
                        'departement' => $city->departement,
                        'verified_description' => "Un lieu emblématique de " . $city->name . " à découvrir.",
                        'lat' => $placeLat,
                        'lng' => $placeLng,
                        'rayon_marge' => 50,
                        'marge_validation_gps' => 50,
                        'is_active' => true,
                        // Pseudo random image matching city vibe
                        'image' => 'https://source.unsplash.com/800x600/?' . urlencode('benin,' . $city->name . ',' . $placeName)
                    ]
                );

                // Create 15 Riddles for this Place (5 Facile, 5 Moyen, 5 Difficile)
                for ($level = 1; $level <= 3; $level++) {
                    for ($i = 1; $i <= 5; $i++) {
                        $difficultyText = $level === 1 ? 'Facile' : ($level === 2 ? 'Intermédiaire' : 'Difficile');
                        
                        Riddle::create([
                            'place_id' => $place->id,
                            'niveau' => $level,
                            'description' => "[$difficultyText] Énigme #$i : Qu'est-ce qui caractérise ce lieu historique à {$city->name} ?",
                            'reponse' => $place->nom,
                            'mcq_options' => $level < 3 ? json_encode([
                                $place->nom,
                                "Faux lieu de " . $city->name . " 1",
                                "Faux lieu de " . $city->name . " 2",
                                "Faux lieu de " . $city->name . " 3"
                            ]) : null
                        ]);
                    }
                }
            }
        }
    }
}
