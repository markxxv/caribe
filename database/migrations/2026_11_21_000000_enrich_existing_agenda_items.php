<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only the two published Buenos Aires agendas are enriched. All values
        // come from their existing venue labels and editorial announcements.
        $slugs = [
            'kuda-sxodit-v-buenos-airese-v-noiabre-2026',
            'kuda-sxodit-v-buenos-airese-v-dekabre-2026',
        ];

        $countryId = DB::table('countries')->where('slug', 'argentina')->value('id');
        $ranges = [
            'La Alasita — ярмарка желаний' => '2026-11-30',
            'Buenos Aires Rojo Sangre' => '2026-11-29',
            'República Folklore' => '2026-11-22',
            'FEJA — фестиваль аргентинского джаза' => '2026-11-29',
            'Festival de Canes — кинофестиваль для людей и собак' => '2026-11-29',
            'Primavera Sound Buenos Aires' => '2026-11-29',
            'Argentina Comic Con' => '2026-12-06',
            'Feria MIGRA — фестиваль печатного искусства' => '2026-12-06',
            'Generación — фестиваль цифровой культуры' => '2026-12-12',
        ];
        $performers = [
            'Концерт классической гитары в Iglesia San Agustín' => ['Рафаэль Вердойя', 'Person'],
            'Órgano al Mediodía — органный концерт в базилике' => ['Луис Капарра', 'Person'],
            'Малер — симфония № 2 «Воскресение»' => ['Филармонический оркестр Буэнос-Айреса', 'PerformingGroup'],
            'Sexteto Mayor — вечер аргентинского танго' => ['Sexteto Mayor', 'PerformingGroup'],
            'Асмик Григорян — оперный гала-концерт' => ['Асмик Григорян', 'Person'],
            'Brazilian Lovers — вечер бразильского джаза' => ['Квартет Josi Dias', 'PerformingGroup'],
            'Рождественский хоровой концерт Cantoría del Socorro' => ['Cantoría del Socorro', 'PerformingGroup'],
        ];

        foreach (DB::table('events')->whereIn('slug', $slugs)->get() as $record) {
            $items = json_decode($record->items ?? '[]', true);
            if (! is_array($items)) {
                continue;
            }

            foreach ($items as &$item) {
                if (! is_array($item) || ! is_string($item['title'] ?? null)
                    || ! is_string($item['date'] ?? null) || ! is_string($item['location'] ?? null)) {
                    continue;
                }
                $location = $item['location'];
                $announcement = (string) ($item['announcement'] ?? '');
                $city = match (true) {
                    str_contains($location, 'San Isidro') => 'San Isidro',
                    str_contains($location, 'Morón') => 'Morón',
                    default => 'Buenos Aires',
                };
                $fields = ['city' => $city, 'country' => 'Argentina'];

                // An explicit street and house number already present in the
                // venue text can be used as-is; do not treat a district as a street.
                $parts = explode(', ', $location);
                if (isset($parts[1]) && preg_match('/\d+$/D', $parts[1])) {
                    $fields['street_address'] = $parts[1];
                }
                if (isset($ranges[$item['title']]) && $ranges[$item['title']] >= $item['date']) {
                    $fields['end_date'] = $ranges[$item['title']];
                }
                if (preg_match('/(?:Начало|Старт) в ([0-2]\d:[0-5]\d)/u', $announcement, $time)) {
                    $fields['start_time'] = $time[1];
                } elseif (preg_match('/(?:^|[.!?]\s+)С ([0-2]\d:[0-5]\d) до ([0-2]\d:[0-5]\d)/u', $announcement, $time)
                    && $time[1] <= $time[2]) {
                    $fields['start_time'] = $time[1];
                    $fields['end_time'] = $time[2];
                    $fields['end_date'] = $item['date'];
                }
                if (preg_match('/(?:вход свободный|бесплатный концерт|бесплатно по предварительной записи)/iu', $announcement)) {
                    // Zero is reserved for events explicitly described as free.
                    if (! isset($item['price']) || $item['price'] === '') {
                        $fields['price'] = 0;
                        $fields['currency'] = 'ARS';
                    }
                }
                if (isset($performers[$item['title']]) && empty($item['performer'])) {
                    [$fields['performer'], $fields['performer_type']] = $performers[$item['title']];
                }

                foreach ($fields as $key => $value) {
                    if (! isset($item[$key]) || $item[$key] === '') {
                        $item[$key] = $value;
                    }
                }
            }
            unset($item);

            $values = ['items' => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)];
            if ($countryId && $record->country_id === null) {
                $values['country_id'] = $countryId;
            }
            DB::table('events')->where('id', $record->id)->update($values);
        }
    }

    public function down(): void
    {
        // Data-only enrichment: keep facts and any subsequent editorial edits.
    }
};
