<?php

return [
    'products' => [
        'name_max' => 120,
        'price_min' => 1000,
        'price_max' => 99999999,
        'description_min' => 20,
        'description_max' => 2000,
        'max_photos' => 5,
        'photo_max_kb' => 2048,
        'video_max_kb' => 20480,
        'conditions' => [
            'Baru',
            'Seperti baru',
            'Baik',
            'Cukup',
            'Ada kekurangan',
        ],
    ],
    'negotiations' => [
        'minimum_price' => 1000,
        'minimum_price_percent' => 30,
    ],
];
