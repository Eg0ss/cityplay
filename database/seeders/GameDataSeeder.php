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
        // Nettoyer les tables dans le bon ordre (Version compatible PostgreSQL)
        DB::statement('TRUNCATE TABLE riddle_images CASCADE');
        DB::statement('TRUNCATE TABLE game_player_riddle_attempts CASCADE');
        DB::statement('TRUNCATE TABLE game_riddles CASCADE');
        DB::statement('TRUNCATE TABLE riddles CASCADE');
        DB::statement('TRUNCATE TABLE places CASCADE');
        DB::statement('TRUNCATE TABLE cities CASCADE');

        $citiesData = [
            [
                'name' => 'Cotonou',
                'departement' => 'Littoral',
                'lat' => 6.3650,
                'lng' => 2.4183,
                'places' => [
                    'Place de l\'Étoile Rouge', 'Stade de l\'Amitié', 'Marché Dantokpa', 'Fidjrossè Plage', 
                    'Cathédrale Notre-Dame', 'Mosquée Centrale', 'Centre Artisanal', 
                    'Haie Vive', 'Port de Cotonou', 'Palais des Congrès'
                ]
            ],
            [
                'name' => 'Porto-Novo',
                'departement' => 'Ouémé',
                'lat' => 6.4969,
                'lng' => 2.6288,
                'places' => [
                    'Musée Honmé', 'Place Jean Bayol', 'Cathédrale Notre-Dame de Porto-Novo', 'Grande Mosquée de Porto-Novo', 
                    'Jardin des Plantes', 'Assemblée Nationale', 'Marché Ouando', 'Pont de Porto-Novo', 
                    'Musée da Silva', 'Place Toffa 1er'
                ]
            ],
        ];

        foreach ($citiesData as $cityInfo) {
            $city = City::create([
                'name' => $cityInfo['name'],
                'departement' => $cityInfo['departement']
            ]);

            foreach ($cityInfo['places'] as $index => $placeName) {
                $placeLat = $cityInfo['lat'] + (rand(-20, 20) / 1000);
                $placeLng = $cityInfo['lng'] + (rand(-20, 20) / 1000);

                $place = Place::create([
                    'nom' => $placeName,
                    'city_id' => $city->id,
                    'ville' => $city->name,
                    'departement' => $city->departement,
                    'verified_description' => "Un lieu historique et culturel majeur de la ville de " . $city->name . ". Ce site est un pilier du patrimoine béninois.",
                    'lat' => $placeLat,
                    'lng' => $placeLng,
                    'rayon_marge' => 50,
                    'marge_validation_gps' => 50,
                    'is_active' => true,
                    'image' => 'https://images.unsplash.com/photo-1590001158193-42cc73bd1f91?auto=format&fit=crop&q=80&w=800'
                ]);

                // Pour chaque lieu, créer 10 énigmes par niveau (Total 30 énigmes par lieu)
                for ($level = 1; $level <= 3; $level++) {
                    $difficultyText = $level === 1 ? 'Facile' : ($level === 2 ? 'Intermédiaire' : 'Difficile');
                    
                    for ($i = 1; $i <= 10; $i++) {
                        $options = [
                            $place->nom,
                            "Lieu Mystère " . ($i + 1),
                            "Site Historique " . ($i + 2),
                            "Monument de " . $city->name
                        ];
                        shuffle($options);

                        $riddle = Riddle::create([
                            'place_id' => $place->id,
                            'niveau' => $level,
                            'description' => "[$difficultyText] Énigme #$i : Je suis un endroit célèbre à {$city->name}. Ma structure et mon histoire sont uniques. Qui suis-je ?",
                            'reponse' => $place->nom,
                            // Désormais, mcq_options est rempli pour TOUS les niveaux (même le niveau 3)
                            'mcq_options' => json_encode($options)
                        ]);

                        // Ajouter une image réelle (via Unsplash) pour chaque énigme
                        // On utilise des mots-clés spécifiques au lieu et à la ville pour plus de réalisme
                        $keywords = urlencode($city->name . " " . $place->nom);
                        \App\Models\RiddleImage::create([
                            'riddle_id' => $riddle->id,
                            'image_path' => "https://images.unsplash.com/photo-1590001158193-42cc73bd1f91?auto=format&fit=crop&q=80&w=800&sig=" . $riddle->id
                        ]);
                    }
                }
            }
        }
    }
}