<?php

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'api#week', 'url' => '/api/week', 'verb' => 'GET'],
        ['name' => 'api#create', 'url' => '/api/entries', 'verb' => 'POST'],
        ['name' => 'api#update', 'url' => '/api/entries/{id}', 'verb' => 'PUT'],
        ['name' => 'api#delete', 'url' => '/api/entries/{id}', 'verb' => 'DELETE'],
    ],
];
