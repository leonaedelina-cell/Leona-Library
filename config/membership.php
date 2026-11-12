<?php

return [
    'plans' => [
        'basic' => [
            'name' => 'Basic',
            'prices' => [
                'monthly' => 29000,
                'yearly' => 290000,
            ],
            'loan_limit' => 3,
            'loan_days' => 14,
            'ebook_access' => false,
            'reservation_priority' => false,
        ],
        'premium' => [
            'name' => 'Premium',
            'prices' => [
                'monthly' => 49000,
                'yearly' => 490000,
            ],
            'loan_limit' => 7,
            'loan_days' => 21,
            'ebook_access' => true,
            'reservation_priority' => true,
        ],
    ],
];
