<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;
use App\Models\Place;
use App\Models\Riddle;
use App\Models\Hint;
use App\Models\RiddleImage;

class GameDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // On ne nettoie pas ici, migrate:fresh s'en charge. 
        // On crée les données directement via Eloquent pour assurer la compatibilité PG.

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
                // Variation légère des coordonnées pour chaque lieu
                $placeLat = $cityInfo['lat'] + (rand(-20, 20) / 1000);
                $placeLng = $cityInfo['lng'] + (rand(-20, 20) / 1000);

                $place = Place::create([
                    'nom' => $placeName,
                    'city_id' => $city->id,
                    'ville' => $city->name,
                    'departement' => $city->departement,
                    'verified_description' => "Un lieu emblématique de " . $city->name . ". Découvrez son architecture unique et son importance historique pour le Bénin.",
                    'lat' => $placeLat,
                    'lng' => $placeLng,
                    'rayon_marge' => 50,
                    'marge_validation_gps' => 50,
                    'is_active' => true,
                    'image' => 'https://images.unsplash.com/photo-1548013146-72479768bada?auto=format&fit=crop&q=80&w=800'
                ]);

                // Création d'énigmes pour chaque niveau
                for ($level = 1; $level <= 3; $level++) {
                    $difficultyText = $level === 1 ? 'Facile' : ($level === 2 ? 'Intermédiaire' : 'Difficile');
                    
                    // On réduit à 3 énigmes par niveau pour être plus léger (Total 180 énigmes)
                    for ($i = 1; $i <= 3; $i++) {
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
                            'mcq_options' => $options
                        ]);

                        // L'indice est la réponse elle-même
                        Hint::create([
                            'riddle_id' => $riddle->id,
                            'type' => 'text',
                            'content' => $place->nom,
                            'difficulty_level' => 'medium',
                            'order' => 1
                        ]);

                        // Images Unsplash de qualité
                        $imageUrls = [
                            'https://images.unsplash.com/photo-1548013146-72479768bada?auto=format&fit=crop&q=80&w=800',
                            'https://images.unsplash.com/photo-1564507592333-c60657451dad?auto=format&fit=crop&q=80&w=800',
                            'https://images.unsplash.com/photo-1503177119275-0aa32b3a9368?auto=format&fit=crop&q=80&w=800',
                            'https://images.unsplash.com/photo-1514222139-1bc96e232ed1?auto=format&fit=crop&q=80&w=800',
                            'https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?auto=format&fit=crop&q=80&w=800',
                            'https://images.unsplash.com/photo-1526392060635-9d6019884377?auto=format&fit=crop&q=80&w=800',
                        ];
                        $randomImage = $imageUrls[array_rand($imageUrls)];

                        RiddleImage::create([
                            'riddle_id' => $riddle->id,
                            'image_path' => $randomImage . "?sig=" . $riddle->id
                        ]);
                    }
                }
            }
        }
    }
}
