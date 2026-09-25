<?php

namespace Database\Seeders;

use App\Models\FeaturedCity;
use App\Models\Region;
use App\Models\Township;
use Illuminate\Database\Seeder;

class MyanmarLocationsSeeder extends Seeder
{
    /**
     * This is a curated MVP list based on the locations supplied for the platform,
     * not a complete national township gazetteer.
     */
    public function run(): void
    {
        $regions = [
            ['name' => 'Yangon', 'type' => 'region'],
            ['name' => 'Mandalay', 'type' => 'region'],
            ['name' => 'Nay Pyi Taw', 'type' => 'union_territory'],
            ['name' => 'Ayeyarwady', 'type' => 'region'],
            ['name' => 'Bago', 'type' => 'region'],
            ['name' => 'Magway', 'type' => 'region'],
            ['name' => 'Sagaing', 'type' => 'region'],
            ['name' => 'Tanintharyi', 'type' => 'region'],
            ['name' => 'Mon', 'type' => 'state'],
            ['name' => 'Shan', 'type' => 'state'],
            ['name' => 'Kachin', 'type' => 'state'],
            ['name' => 'Kayin', 'type' => 'state'],
            ['name' => 'Kayah', 'type' => 'state'],
            ['name' => 'Rakhine', 'type' => 'state'],
            ['name' => 'Chin', 'type' => 'state'],
        ];

        foreach ($regions as $index => $attributes) {
            $regions[$index] = Region::updateOrCreate(['name' => $attributes['name']], [...$attributes, 'sort_order' => $index + 1]);
        }
        $byName = collect($regions)->keyBy('name');

        // Township names are stored without the repeated "Township" suffix.
        // "Central Business District" and similar labels were browse groupings,
        // not administrative units, so they are intentionally not stored as places.
        $townships = [
            'Yangon' => [
                'Kyauktada', 'Pabedan', 'Latha', 'Lanmadaw', 'Dagon', 'Botataung', 'Pazundaung',
                'Sanchaung', 'Ahlon', 'Kyimyindaing', 'Bahan', 'Yankin', 'Kamayut', 'Hlaing', 'Tamwe',
                'Thingangyun', 'Mingala Taungnyunt', 'Mayangon', 'Insein', 'North Okkalapa',
                'South Okkalapa', 'Dawbon', 'Thaketa', 'Hlaingthaya East', 'Hlaingthaya West',
                'Shwepyitha', 'Mingaladon', 'North Dagon', 'South Dagon', 'East Dagon', 'Dagon Seikkan',
                'Thanlyin', 'Kyauktan',
            ],
            'Mandalay' => [
                'Aungmyethazan', 'Chanayethazan', 'Maha Aungmye', 'Chanmyathazi', 'Pyigyidagun',
                'Amarapura', 'Patheingyi', 'Pyin Oo Lwin', 'Meiktila', 'Myingyan', 'Mogok', 'Nyaung-U', 'Kyaukse',
            ],
            'Nay Pyi Taw' => ['Zabuthiri', 'Dekkhinathiri', 'Ottarathiri', 'Pobbathiri', 'Zeyarthiri', 'Pyinmana', 'Lewe', 'Tatkon'],
            'Mon' => ['Mawlamyine', 'Thaton', 'Mudon', 'Ye'],
            'Shan' => ['Taunggyi', 'Lashio', 'Muse', 'Tachileik', 'Kengtung'],
            'Bago' => ['Bago', 'Pyay', 'Taungoo'],
            'Ayeyarwady' => ['Pathein', 'Hinthada', 'Myaungmya', 'Maubin'],
            'Sagaing' => ['Monywa', 'Sagaing', 'Shwebo', 'Kale'],
            'Tanintharyi' => ['Dawei', 'Myeik', 'Kawthaung'],
            'Kachin' => ['Myitkyina', 'Bhamo', 'Mohnyin', 'Hpakant'],
        ];

        foreach ($townships as $regionName => $names) {
            foreach ($names as $name) {
                Township::updateOrCreate(
                    ['region_id' => $byName[$regionName]->id, 'name' => $name],
                    ['is_featured' => true],
                );
            }
        }

        $cities = [
            ['Yangon', 'Yangon', 'Commercial capital and main job hub'],
            ['Mandalay', 'Mandalay', 'Northern commercial hub'],
            ['Naypyidaw', 'Nay Pyi Taw', 'Administrative capital'],
            ['Taunggyi', 'Shan', 'Capital of Shan State'],
            ['Mawlamyine', 'Mon', 'Southern economic hub'],
            ['Pathein', 'Ayeyarwady', 'Regional commercial center'],
            ['Monywa', 'Sagaing', 'Industrial and trade center'],
            ['Pyin Oo Lwin', 'Mandalay', 'Agriculture and tourism hub'],
            ['Myeik', 'Tanintharyi', 'Coastal trade and seafood center'],
            ['Sittwe', 'Rakhine', 'Regional administrative center'],
        ];

        foreach ($cities as $index => [$name, $regionName, $highlight]) {
            FeaturedCity::updateOrCreate(
                ['region_id' => $byName[$regionName]->id, 'name' => $name],
                ['highlight' => $highlight, 'sort_order' => $index + 1],
            );
        }
    }
}
