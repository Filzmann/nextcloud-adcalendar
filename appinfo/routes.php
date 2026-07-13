<?php

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'api#week', 'url' => '/api/week', 'verb' => 'GET'],
        ['name' => 'api#create', 'url' => '/api/entries', 'verb' => 'POST'],
        ['name' => 'api#update', 'url' => '/api/entries/{id}', 'verb' => 'PUT'],
        ['name' => 'api#delete', 'url' => '/api/entries/{id}', 'verb' => 'DELETE'],
        ['name' => 'api#preferences', 'url' => '/api/preferences', 'verb' => 'GET'],
        ['name' => 'api#savePreferences', 'url' => '/api/preferences', 'verb' => 'PUT'],
        ['name' => 'api#saveShiftDefaults', 'url' => '/api/preferences/shifts', 'verb' => 'PUT'],
        ['name' => 'meeting#gaps', 'url' => '/api/meeting-gaps', 'verb' => 'POST'],
        ['name' => 'meeting#block', 'url' => '/api/meetings', 'verb' => 'POST'],
        ['name' => 'meeting#update', 'url' => '/api/meetings/{meetingUid}', 'verb' => 'PUT'],
        ['name' => 'meeting#delete', 'url' => '/api/meetings/{meetingUid}', 'verb' => 'DELETE'],
    ],
];
