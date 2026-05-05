<?php
// index.php

declare(strict_types=1);

$assignments = [
    [
        'id'          => 1,
        'title'       => 'HTML & CSS Portfolio',
        'due_date'    => '2025-02-15',
        'description' => 'Build a portfolio.',
        'files'       => [],
    ],
    [
        'id'          => 2,
        'title'       => 'JavaScript Interactivity',
        'due_date'    => '2025-03-01',
        'description' => 'Add JS functionality.',
        'files'       => [],
    ]
];

header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $assignments]);

// Handling different actions such as 'comment' would go here (for simplicity, it's not shown in this example).
?>