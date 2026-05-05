<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

require 'vendor/autoload.php';

$app = new \Slim\App();

$assignments = [
    [
        'id'          => 1,
        'title'       => 'HTML & CSS Portfolio',
        'description' => 'Build a portfolio.',
        'due_date'    => '2025-02-15',
        'files'       => [],
        'created_at'  => '2025-01-01',
    ],
];

$app->get('/index.php', function (ServerRequestInterface $request, ResponseInterface $response) use ($assignments) {
    $data = [
        'success' => true,
        'data'    => $assignments
    ];
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/index.php', function (ServerRequestInterface $request, ResponseInterface $response) use ($assignments) {
    $id = $request->getQueryParams()['id'] ?? null;
    if ($id) {
        $assignment = array_filter($assignments, fn($assignment) => $assignment['id'] == $id);
        if (empty($assignment)) {
            return $response->withStatus(404)->write(json_encode(['success' => false, 'message' => 'Assignment not found']));
        }
        $response->getBody()->write(json_encode(['success' => true, 'data' => array_values($assignment)[0]]));
    } else {
        return $response->withStatus(400)->write(json_encode(['success' => false, 'message' => 'ID is required']));
    }
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/index.php', function (ServerRequestInterface $request, ResponseInterface $response) {
    $data = json_decode($request->getBody(), true);
    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        return $response->withStatus(400)->write(json_encode(['success' => false, 'message' => 'Missing required fields']));
    }

    $newAssignment = [
        'id'          => count($assignments) + 1,
        'title'       => $data['title'],
        'description' => $data['description'],
        'due_date'    => $data['due_date'],
        'files'       => $data['files'] ?? [],
        'created_at'  => date('Y-m-d H:i:s'),
    ];
    $assignments[] = $newAssignment;

    return $response->withStatus(201)->write(json_encode(['success' => true, 'id' => $newAssignment['id']]));
});

$app->put('/index.php', function (ServerRequestInterface $request, ResponseInterface $response) use ($assignments) {
    $data = json_decode($request->getBody(), true);
    $id   = $data['id'] ?? null;

    if (!$id || !isset($assignments[$id - 1])) {
        return $response->withStatus(404)->write(json_encode(['success' => false, 'message' => 'Assignment not found']));
    }

    $assignments[$id - 1] = array_merge($assignments[$id - 1], $data);

    return $response->write(json_encode(['success' => true]));
});

$app->delete('/index.php', function (ServerRequestInterface $request, ResponseInterface $response) use (&$assignments) {
    $id = $request->getQueryParams()['id'] ?? null;
    if ($id && isset($assignments[$id - 1])) {
        unset($assignments[$id - 1]);
        $assignments = array_values($assignments);
        return $response->write(json_encode(['success' => true]));
    }
    return $response->withStatus(404)->write(json_encode(['success' => false, 'message' => 'Assignment not found']));
});

$app->post('/index.php', function (ServerRequestInterface $request, ResponseInterface $response) {
    $data = json_decode($request->getBody(), true);
    $action = $data['action'] ?? '';
    if ($action == 'comment') {
        $commentData = [
            'assignment_id' => $data['assignment_id'],
            'author'        => $data['author'],
            'text'          => $data['text'],
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        return $response->write(json_encode(['success' => true, 'data' => $commentData]));
    }
    return $response->withStatus(400)->write(json_encode(['success' => false, 'message' => 'Action is required']));
});

$app->run();