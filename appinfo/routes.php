<?php

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'api#week', 'url' => '/api/week', 'verb' => 'GET'],
        ['name' => 'api#create', 'url' => '/api/entries', 'verb' => 'POST'],
        ['name' => 'api#update', 'url' => '/api/entries/{id}', 'verb' => 'PUT'],
        ['name' => 'api#delete', 'url' => '/api/entries/{id}', 'verb' => 'DELETE'],
        ['name' => 'api#settings', 'url' => '/api/settings', 'verb' => 'GET'],
        ['name' => 'api#saveSettings', 'url' => '/api/settings', 'verb' => 'PUT'],
        ['name' => 'api#saveOrganizationSettings', 'url' => '/api/settings/organization', 'verb' => 'PUT'],
        ['name' => 'api#preferences', 'url' => '/api/preferences', 'verb' => 'GET'],
        ['name' => 'api#savePreferences', 'url' => '/api/preferences', 'verb' => 'PUT'],
        ['name' => 'api#saveShiftDefaults', 'url' => '/api/preferences/shifts', 'verb' => 'PUT'],
        ['name' => 'api#meetingGaps', 'url' => '/api/meeting-gaps', 'verb' => 'POST'],
    ],
];
