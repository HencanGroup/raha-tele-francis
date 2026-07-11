<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\Town;
use Illuminate\Database\Seeder;

class KenyaCountiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data for all 47 Kenyan counties with their major towns
        $counties = [
            [
                'name' => 'Mombasa',
                'code' => '001',
                'towns' => ['Mombasa', 'Nyali', 'Likoni', 'Kisauni', 'Changamwe', 'Mtongwe'],
            ],
            [
                'name' => 'Kwale',
                'code' => '002',
                'towns' => ['Kwale', 'Ukunda', 'Msambweni', 'Lungalunga', 'Kinango', 'Samburu'],
            ],
            [
                'name' => 'Kilifi',
                'code' => '003',
                'towns' => ['Kilifi', 'Malindi', 'Mtwapa', 'Mariakani', 'Watamu', 'Kaloleni', 'Rabai'],
            ],
            [
                'name' => 'Tana River',
                'code' => '004',
                'towns' => ['Hola', 'Garsen', 'Bura', 'Madogo', 'Bangale'],
            ],
            [
                'name' => 'Lamu',
                'code' => '005',
                'towns' => ['Lamu', 'Faza', 'Witu', 'Mpeketoni', 'Hindi'],
            ],
            [
                'name' => 'Taita Taveta',
                'code' => '006',
                'towns' => ['Voi', 'Wundanyi', 'Mwatate', 'Taveta', 'Mbololo'],
            ],
            [
                'name' => 'Garissa',
                'code' => '007',
                'towns' => ['Garissa', 'Dadaab', 'Fafi', 'Balambala', 'Ijara', 'Sankuri'],
            ],
            [
                'name' => 'Wajir',
                'code' => '008',
                'towns' => ['Wajir', 'Habaswein', 'Buna', 'Griftu', 'Arbajahan', 'Kotulo'],
            ],
            [
                'name' => 'Mandera',
                'code' => '009',
                'towns' => ['Mandera', 'Elwak', 'Takaba', 'Rhamu', 'Lafey', 'Banisa'],
            ],
            [
                'name' => 'Marsabit',
                'code' => '010',
                'towns' => ['Marsabit', 'Moyale', 'Laisamis', 'North Horr', 'Sololo', 'Loiyangalani'],
            ],
            [
                'name' => 'Isiolo',
                'code' => '011',
                'towns' => ['Isiolo', 'Garba Tulla', 'Merti', 'Kinna', 'Oldonyiro'],
            ],
            [
                'name' => 'Meru',
                'code' => '012',
                'towns' => ['Meru', 'Maua', 'Nkubu', 'Timau', 'Miathene', 'Kianjai', 'Muthara'],
            ],
            [
                'name' => 'Tharaka Nithi',
                'code' => '013',
                'towns' => ['Chuka', 'Kathwana', 'Marimanti', 'Magutuni', 'Mariani'],
            ],
            [
                'name' => 'Embu',
                'code' => '014',
                'towns' => ['Embu', 'Runyenjes', 'Siakago', 'Manyatta', 'Kiritiri', 'Ishiara'],
            ],
            [
                'name' => 'Kitui',
                'code' => '015',
                'towns' => ['Kitui', 'Mwingi', 'Mutomo', 'Kabati', 'Migwani', 'Tseikuru', 'Kwavonza'],
            ],
            [
                'name' => 'Machakos',
                'code' => '016',
                'towns' => ['Machakos', 'Athi River', 'Kathiani', 'Mavoko', 'Mwala', 'Kangundo', 'Masii'],
            ],
            [
                'name' => 'Makueni',
                'code' => '017',
                'towns' => ['Wote', 'Makindu', 'Sultan Hamud', 'Kibwezi', 'Mtito Andei', 'Nunguni'],
            ],
            [
                'name' => 'Nyandarua',
                'code' => '018',
                'towns' => ['Ol Kalou', 'Nyahururu', 'Ndaragwa', 'Njabini', 'Engineer', 'Mairo Inya'],
            ],
            [
                'name' => 'Nyeri',
                'code' => '019',
                'towns' => ['Nyeri', 'Othaya', 'Mweiga', 'Karatina', 'Kiganjo', 'Chaka', 'Endarasha'],
            ],
            [
                'name' => 'Kirinyaga',
                'code' => '020',
                'towns' => ['Kerugoya', 'Kutus', 'Sagana', 'Wanguru', 'Baricho', 'Kagio'],
            ],
            [
                'name' => 'Murang\'a',
                'code' => '021',
                'towns' => ['Murang\'a', 'Kangema', 'Kahuro', 'Kiharu', 'Gatanga', 'Kandara', 'Maragua'],
            ],
            [
                'name' => 'Kiambu',
                'code' => '022',
                'towns' => ['Kiambu', 'Thika', 'Ruiru', 'Gatundu', 'Kikuyu', 'Limuru', 'Kabete', 'Juja', 'Githunguri', 'Lari'],
            ],
            [
                'name' => 'Turkana',
                'code' => '023',
                'towns' => ['Lodwar', 'Kakuma', 'Lokichogio', 'Kalokol', 'Lokitaung', 'Kerio Valley'],
            ],
            [
                'name' => 'West Pokot',
                'code' => '024',
                'towns' => ['Kapenguria', 'Kacheliba', 'Sigor', 'Lelan', 'Chepareria'],
            ],
            [
                'name' => 'Samburu',
                'code' => '025',
                'towns' => ['Maralal', 'Baragoi', 'Wamba', 'Archers Post', 'South Horr'],
            ],
            [
                'name' => 'Trans Nzoia',
                'code' => '026',
                'towns' => ['Kitale', 'Kiminini', 'Endebess', 'Saboti', 'Kwanza'],
            ],
            [
                'name' => 'Uasin Gishu',
                'code' => '027',
                'towns' => ['Eldoret', 'Huruma', 'Langas', 'Maili Nne', 'Kapsaret', 'Kipkenyo'],
            ],
            [
                'name' => 'Elgeyo Marakwet',
                'code' => '028',
                'towns' => ['Iten', 'Tambach', 'Chesoi', 'Chepkorio', 'Kapyego', 'Tot'],
            ],
            [
                'name' => 'Nandi',
                'code' => '029',
                'towns' => ['Kapsabet', 'Nandi Hills', 'Mosoriot', 'Kabiyet', 'Kilibwoni', 'Lessos'],
            ],
            [
                'name' => 'Baringo',
                'code' => '030',
                'towns' => ['Kabarnet', 'Eldama Ravine', 'Mogotio', 'Marigat', 'Sacho', 'Kabartonjo'],
            ],
            [
                'name' => 'Laikipia',
                'code' => '031',
                'towns' => ['Rumuruti', 'Nanyuki', 'Nyahururu', 'Kinamba', 'Olmoran', 'Matanya'],
            ],
            [
                'name' => 'Nakuru',
                'code' => '032',
                'towns' => ['Nakuru', 'Naivasha', 'Molo', 'Gilgil', 'Njoro', 'Rongai', 'Bahati', 'Subukia', 'Mau Narok'],
            ],
            [
                'name' => 'Narok',
                'code' => '033',
                'towns' => ['Narok', 'Kilgoris', 'Aitong', 'Mulot', 'Ololulunga', 'Nairage Enkare'],
            ],
            [
                'name' => 'Kajiado',
                'code' => '034',
                'towns' => ['Kajiado', 'Ngong', 'Kitengela', 'Isinya', 'Oloosirkon', 'Namanga'],
            ],
            [
                'name' => 'Kericho',
                'code' => '035',
                'towns' => ['Kericho', 'Litein', 'Bureti', 'Belgut', 'Kipkelion', 'Sigor', 'Chepseon'],
            ],
            [
                'name' => 'Bomet',
                'code' => '036',
                'towns' => ['Bomet', 'Longisa', 'Sotik', 'Chepalungu', 'Ndanai', 'Mogogosiek'],
            ],
            [
                'name' => 'Kakamega',
                'code' => '037',
                'towns' => ['Kakamega', 'Mumias', 'Malava', 'Butere', 'Lugari', 'Likuyani', 'Navakholo', 'Shinyalu'],
            ],
            [
                'name' => 'Vihiga',
                'code' => '038',
                'towns' => ['Vihiga', 'Mbale', 'Luanda', 'Emuhaya', 'Serem', 'Maseno'],
            ],
            [
                'name' => 'Bungoma',
                'code' => '039',
                'towns' => ['Bungoma', 'Webuye', 'Kimilili', 'Sirisia', 'Tongaren', 'Chwele', 'Naitiri'],
            ],
            [
                'name' => 'Busia',
                'code' => '040',
                'towns' => ['Busia', 'Funyula', 'Nambale', 'Butula', 'Teso', 'Amukura'],
            ],
            [
                'name' => 'Siaya',
                'code' => '041',
                'towns' => ['Siaya', 'Bondo', 'Ugunja', 'Yala', 'Ukwala', 'Ngiya', 'Madiany'],
            ],
            [
                'name' => 'Kisumu',
                'code' => '042',
                'towns' => ['Kisumu', 'Maseno', 'Ahero', 'Katito', 'Muhoroni', 'Kondele', 'Manyatta', 'Kombewa'],
            ],
            [
                'name' => 'Homa Bay',
                'code' => '043',
                'towns' => ['Homa Bay', 'Kendu Bay', 'Rachuonyo', 'Rangwe', 'Mbita', 'Suba', 'Ndhiwa'],
            ],
            [
                'name' => 'Migori',
                'code' => '044',
                'towns' => ['Migori', 'Rongo', 'Awendo', 'Kehancha', 'Suna', 'Isebania', 'Nyatike'],
            ],
            [
                'name' => 'Kisii',
                'code' => '045',
                'towns' => ['Kisii', 'Ogembo', 'Nyamache', 'Keroka', 'Suneka', 'Tabaka', 'Nyamarambe'],
            ],
            [
                'name' => 'Nyamira',
                'code' => '046',
                'towns' => ['Nyamira', 'Manga', 'Kericho Border', 'Ekerenyo', 'Gesima', 'Rigoma'],
            ],
            [
                'name' => 'Nairobi',
                'code' => '047',
                'towns' => ['Nairobi CBD', 'Westlands', 'Parklands', 'Karen', 'Langata', 'Kasarani', 'Embakasi', 'Dagoretti', 'Makadara', 'Mathare', 'Starehe', 'Kamukunji', 'Roysambu', 'Ruaraka', 'Kibra'],
            ],
        ];

        $this->command->info('Seeding Kenyan counties and towns...');
        $this->command->getOutput()->progressStart(count($counties));

        foreach ($counties as $countyData) {
            // Create county
            $county = County::create([
                'name' => $countyData['name'],
                'code' => $countyData['code'],
            ]);

            // Create towns for this county
            foreach ($countyData['towns'] as $townName) {
                Town::create([
                    'name' => $townName,
                    'county_id' => $county->id,
                ]);
            }

            $this->command->getOutput()->progressAdvance();
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info('Successfully seeded '.count($counties).' counties with their towns.');
    }
}
