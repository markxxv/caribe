<?php

namespace App\Support\Events;

use App\Models\Event;

final class EventDetails
{
    public const STATUSES = [
        'EventScheduled' => 'Запланировано',
        'EventCancelled' => 'Отменено',
        'EventPostponed' => 'Отложено',
        'EventRescheduled' => 'Перенесено',
    ];

    public const AVAILABILITIES = [
        'InStock' => 'Есть билеты',
        'SoldOut' => 'Билеты закончились',
        'PreOrder' => 'Предварительная продажа',
    ];

    public static function data(Event $record, array $item): array
    {
        $date = $item['date'];
        $startTime = self::time($item['start_time'] ?? null);
        $endDate = self::date($item['end_date'] ?? null);
        $endTime = self::time($item['end_time'] ?? null);
        if ($endDate !== null && $endDate < $date) {
            $endDate = null;
        }
        if ($endTime !== null && ($endDate ?? $date) === $date && $startTime !== null && $endTime < $startTime) {
            $endTime = null; // Overnight events need an explicit end date.
        }
        if ($endTime !== null && $endDate === null) {
            $endDate = $date;
        }

        $status = self::string($item['event_status'] ?? null);
        $availability = self::string($item['availability'] ?? null);
        $price = $item['price'] ?? null;
        $currency = strtoupper(self::string($item['currency'] ?? null) ?? '');
        $validPrice = is_numeric($price) && is_finite((float) $price) && (float) $price >= 0
            && preg_match('/^[A-Z]{3}$/D', $currency);

        return [
            'start_date' => $date . ($startTime ? 'T' . $startTime . ':00' : ''),
            'end_date' => $endDate ? $endDate . ($endTime ? 'T' . $endTime . ':00' : '') : null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'city' => self::string($item['city'] ?? null),
            'street_address' => self::string($item['street_address'] ?? null),
            'country' => self::string($record->country?->title) ?? self::string($item['country'] ?? null),
            'event_status' => array_key_exists($status ?? '', self::STATUSES) ? $status : 'EventScheduled',
            'performer' => self::string($item['performer'] ?? null),
            'performer_type' => ($item['performer_type'] ?? null) === 'PerformingGroup' ? 'PerformingGroup' : 'Person',
            'ticket_url' => self::url($item['ticket_url'] ?? null),
            'price' => $validPrice ? (float) $price : null,
            'currency' => $validPrice ? $currency : null,
            'availability' => array_key_exists($availability ?? '', self::AVAILABILITIES) ? $availability : null,
        ];
    }

    public static function schema(Event $record, array $item): ?array
    {
        $details = self::data($record, $item);
        // A country alone is not an event address. Incomplete records remain
        // readable in the agenda without claiming eligibility for rich results.
        if (! $details['street_address'] && ! $details['city']) {
            return null;
        }

        $address = ['@type' => 'PostalAddress'];
        foreach (['streetAddress' => 'street_address', 'addressLocality' => 'city', 'addressCountry' => 'country'] as $property => $field) {
            if ($details[$field]) {
                $address[$property] = $details[$field];
            }
        }

        $place = ['@type' => 'Place', 'address' => $address];
        if ($name = self::string($item['location'] ?? null)) {
            // For older imports the street is also part of the venue label.
            if ($details['street_address'] && str_contains($name, ', ' . $details['street_address'])) {
                $name = strstr($name, ', ' . $details['street_address'], true);
            }
            $place['name'] = $name;
        }
        if ($item['map_url']) {
            $place['hasMap'] = $item['map_url'];
        }

        $node = [
            '@type' => 'Event',
            '@id' => $item['detail_url'] . '#event',
            'url' => $item['detail_url'],
            'name' => $item['title'],
            'startDate' => $details['start_date'],
            'eventStatus' => 'https://schema.org/' . $details['event_status'],
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $place,
        ];
        if ($description = self::string($item['announcement'] ?? null)) {
            $node['description'] = $description;
        }
        if ($details['end_date']) {
            $node['endDate'] = $details['end_date'];
        }
        if ($details['performer']) {
            $node['performer'] = [
                '@type' => $details['performer_type'],
                'name' => $details['performer'],
            ];
        }
        if ($details['ticket_url'] || $details['price'] !== null) {
            $offer = ['@type' => 'Offer'];
            if ($details['ticket_url']) {
                $offer['url'] = $details['ticket_url'];
            }
            if ($details['price'] !== null) {
                $offer['price'] = $details['price'];
                $offer['priceCurrency'] = $details['currency'];
                $node['isAccessibleForFree'] = $details['price'] === 0.0;
            }
            if ($details['availability']) {
                $offer['availability'] = 'https://schema.org/' . $details['availability'];
            }
            $node['offers'] = $offer;
        }

        return $node;
    }

    private static function string(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    private static function date(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $value, $parts)
            && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) ? $value : null;
    }

    private static function time(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $value) ? $value : null;
    }

    private static function url(mixed $value): ?string
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL)
            && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true) ? $value : null;
    }
}
