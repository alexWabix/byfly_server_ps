<?php
// Конфигурация аукциона
return [
    'auction_active' => true, // true - аукцион активен, false - закрыт
    'start_price' => 10000,
    'max_price' => 340000,
    'step_price' => 10000,
    'max_bids_per_user' => 3,
    'auction_end_time' => '2025-09-19 23:59:59', // Дата окончания аукциона
    'tour_info' => [
        'country' => 'Таиланд',
        'city' => 'Паттайя',
        'hotel' => 'Sawasdee Siam 2*',
        'location' => 'Central Pattaya',
        'checkin' => '20.09.2025',
        'checkout' => '11.10.2025',
        'nights' => 21,
        'meal' => 'Room Only',
        'airline' => 'Air Astana',
        'aircraft' => 'A-321 LR',
        'departure_flight' => '20.09.2025 Алматы (00:15) → Бангкок (08:55)',
        'return_flight' => '11.10.2025 Бангкок (10:15) → Алматы (14:55)',
        'baggage' => '23 кг + ручная кладь 8 кг',
        'transfer' => 'Групповой трансфер: аэропорт ↔️ отель',
        'insurance' => 'Страхование: покрытие 10 000 $',
        'description' => 'Тур для самых смелых — впереди незабываемое приключение!'
    ]
];
?>