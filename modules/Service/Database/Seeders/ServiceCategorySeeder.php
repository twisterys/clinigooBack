<?php

namespace Modules\Service\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Categories with their nested services.
     */
    private const array CATEGORIES = [
        [
            'name' => 'Soins à domicile',
            'slug' => 'soins-a-domicile',
            'icon' => 'home.png',
            'services' => [
                [
                    'name' => 'Soins infirmiers',
                    'slug' => 'soins-infirmiers',
                    'icon' => 'nurse.png',
                    'short_description' => 'Injection, pansement, surveillance et soins infirmiers à domicile.',
                    'base_price' => 250,
                    'duration_minutes' => 60,
                    'is_active' => true,
                ],
                [
                    'name' => 'Pansement',
                    'slug' => 'pansement',
                    'icon' => 'bandage.png',
                    'short_description' => 'Réfection de pansement simple ou complexe à domicile.',
                    'base_price' => 150,
                    'duration_minutes' => 30,
                    'is_active' => true,
                ],
                [
                    'name' => 'Injection',
                    'slug' => 'injection',
                    'icon' => 'syringe.png',
                    'short_description' => 'Injection intramusculaire, sous-cutanée ou intraveineuse à domicile.',
                    'base_price' => 100,
                    'duration_minutes' => 20,
                    'is_active' => true,
                ],
                [
                    'name' => 'Prise de tension',
                    'slug' => 'prise-de-tension',
                    'icon' => 'heart-pulse.png',
                    'short_description' => 'Mesure et surveillance de la tension artérielle à domicile.',
                    'base_price' => 80,
                    'duration_minutes' => 15,
                    'is_active' => true,
                ],
                [
                    'name' => 'Perfusion',
                    'slug' => 'perfusion',
                    'icon' => 'iv-drip.png',
                    'short_description' => 'Pose et surveillance de perfusion à domicile.',
                    'base_price' => 300,
                    'duration_minutes' => 90,
                    'is_active' => true,
                ],
                [
                    'name' => 'Sondage urinaire',
                    'slug' => 'sondage-urinaire',
                    'icon' => 'catheter.png',
                    'short_description' => 'Pose et surveillance de sonde urinaire à domicile.',
                    'base_price' => 350,
                    'duration_minutes' => 45,
                    'is_active' => true,
                ],
                [
                    'name' => 'Soins post-opératoires',
                    'slug' => 'soins-post-operatoires',
                    'icon' => 'stethoscope.png',
                    'short_description' => 'Suivi et soins après une intervention chirurgicale à domicile.',
                    'base_price' => 400,
                    'duration_minutes' => 60,
                    'is_active' => true,
                ],
            ],
        ],
        [
            'name' => 'Consultation',
            'slug' => 'consultation',
            'icon' => 'stethoscope.png',
            'services' => [
                [
                    'name' => 'Consultation médecin généraliste',
                    'slug' => 'consultation-medecin-generaliste',
                    'icon' => 'doctor.png',
                    'short_description' => 'Consultation avec un médecin généraliste à domicile.',
                    'base_price' => 300,
                    'duration_minutes' => 30,
                    'is_active' => true,
                ],
                [
                    'name' => 'Consultation pédiatre',
                    'slug' => 'consultation-pediatre',
                    'icon' => 'child.png',
                    'short_description' => "Consultation d'un pédiatre pour enfants à domicile.",
                    'base_price' => 400,
                    'duration_minutes' => 40,
                    'is_active' => true,
                ],
                [
                    'name' => 'Consultation gériatre',
                    'slug' => 'consultation-geriatre',
                    'icon' => 'elderly.png',
                    'short_description' => 'Consultation spécialisée pour personnes âgées à domicile.',
                    'base_price' => 450,
                    'duration_minutes' => 45,
                    'is_active' => true,
                ],
                [
                    'name' => 'Consultation psychologue',
                    'slug' => 'consultation-psychologue',
                    'icon' => 'brain.png',
                    'short_description' => 'Séance de soutien psychologique à domicile.',
                    'base_price' => 500,
                    'duration_minutes' => 60,
                    'is_active' => true,
                ],
                [
                    'name' => 'Consultation diététicien',
                    'slug' => 'consultation-dieteticien',
                    'icon' => 'apple.png',
                    'short_description' => 'Consultation diététique et conseils nutritionnels à domicile.',
                    'base_price' => 350,
                    'duration_minutes' => 45,
                    'is_active' => true,
                ],
            ],
        ],
        [
            'name' => 'Prélèvements',
            'slug' => 'prelevements',
            'icon' => 'blood-sample.png',
            'services' => [
                [
                    'name' => 'Prise de sang',
                    'slug' => 'prise-de-sang',
                    'icon' => 'blood-drop.png',
                    'short_description' => 'Prélèvement sanguin à domicile pour analyses.',
                    'base_price' => 150,
                    'duration_minutes' => 15,
                    'is_active' => true,
                ],
                [
                    'name' => 'Prélèvement urinaire',
                    'slug' => 'prelevement-urinaire',
                    'icon' => 'urine.png',
                    'short_description' => "Recueil d'urine à domicile pour analyses.",
                    'base_price' => 100,
                    'duration_minutes' => 10,
                    'is_active' => true,
                ],
                [
                    'name' => 'Prélèvement de selles',
                    'slug' => 'prelevement-de-selles',
                    'icon' => 'stool.png',
                    'short_description' => 'Recueil de selles à domicile pour analyses.',
                    'base_price' => 100,
                    'duration_minutes' => 10,
                    'is_active' => true,
                ],
                [
                    'name' => 'Test PCR / COVID',
                    'slug' => 'test-pcr-covid',
                    'icon' => 'virus.png',
                    'short_description' => 'Test PCR ou antigénique à domicile.',
                    'base_price' => 200,
                    'duration_minutes' => 15,
                    'is_active' => true,
                ],
                [
                    'name' => 'Prélèvement pour glycémie',
                    'slug' => 'prelevement-glycemie',
                    'icon' => 'glucose.png',
                    'short_description' => 'Contrôle de la glycémie capillaire à domicile.',
                    'base_price' => 80,
                    'duration_minutes' => 10,
                    'is_active' => true,
                ],
            ],
        ],
        [
            'name' => 'Rééducation',
            'slug' => 'reeducation',
            'icon' => 'rehabilitation.png',
            'services' => [
                [
                    'name' => 'Kinésithérapie',
                    'slug' => 'kinesitherapie',
                    'icon' => 'physiotherapy.png',
                    'short_description' => 'Séances de kinésithérapie à domicile pour rééducation motrice.',
                    'base_price' => 350,
                    'duration_minutes' => 45,
                    'is_active' => true,
                ],
                [
                    'name' => 'Rééducation neurologique',
                    'slug' => 'reeducation-neurologique',
                    'icon' => 'brain.png',
                    'short_description' => 'Rééducation pour patients atteints de troubles neurologiques.',
                    'base_price' => 500,
                    'duration_minutes' => 60,
                    'is_active' => true,
                ],
                [
                    'name' => 'Rééducation orthopédique',
                    'slug' => 'reeducation-orthopedique',
                    'icon' => 'bone.png',
                    'short_description' => 'Rééducation après fracture, opération ou traumatisme.',
                    'base_price' => 450,
                    'duration_minutes' => 45,
                    'is_active' => true,
                ],
                [
                    'name' => 'Rééducation respiratoire',
                    'slug' => 'reeducation-respiratoire',
                    'icon' => 'lungs.png',
                    'short_description' => 'Rééducation respiratoire pour patients insuffisants respiratoires.',
                    'base_price' => 400,
                    'duration_minutes' => 45,
                    'is_active' => true,
                ],
                [
                    'name' => 'Orthophonie',
                    'slug' => 'orthophonie',
                    'icon' => 'speech.png',
                    'short_description' => "Séances d'orthophonie à domicile pour troubles du langage.",
                    'base_price' => 400,
                    'duration_minutes' => 45,
                    'is_active' => true,
                ],
                [
                    'name' => 'Psychomotricité',
                    'slug' => 'psychomotricite',
                    'icon' => 'movement.png',
                    'short_description' => 'Séances de psychomotricité à domicile.',
                    'base_price' => 400,
                    'duration_minutes' => 45,
                    'is_active' => true,
                ],
            ],
        ],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::CATEGORIES as $categoryData) {
                $services = $categoryData['services'] ?? [];
                unset($categoryData['services']);

                $category = ServiceCategory::updateOrCreate(
                    ['slug' => $categoryData['slug']],
                    $categoryData
                );

                foreach ($services as $serviceData) {
                    Service::updateOrCreate(
                        ['slug' => $serviceData['slug']],
                        array_merge($serviceData, [
                            'service_category_id' => $category->id,
                        ])
                    );
                }
            }
        });
    }
}
