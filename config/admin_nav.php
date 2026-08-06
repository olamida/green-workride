<?php

/*
|--------------------------------------------------------------------------
| Admin Control Tower — Navigation Groups
|--------------------------------------------------------------------------
|
| The 30+ flat sidebar links are grouped into five collapsible packages
| (navigation-first sprint 1). Each item maps to a real route; `active`
| holds a comma-separated routeIs() pattern list. `badge` keys refer to a
| badge computed once per request in layouts/admin.blade.php.
|
| Later sprints may add items here (e.g. admin.trips.live, admin.sos) — no
| layout change required.
|
*/

return [

    'groups' => [
        'operations' => [
            'label' => 'Operations',
            'icon' => 'truck',
            'items' => [
                ['label' => 'Demand Research', 'route' => 'admin.ops.demand', 'active' => 'admin.ops.demand'],
                ['label' => 'Fleet', 'route' => 'admin.fleet.index', 'active' => 'admin.fleet.*,admin.faults.*,admin.maintenance.*'],
                ['label' => 'Verifications', 'route' => 'admin.verifications.index', 'active' => 'admin.verifications.*', 'badge' => 'verifications'],
                ['label' => 'Driver Scores', 'route' => 'admin.scoreboard.index', 'active' => 'admin.scoreboard.*'],
            ],
        ],

        'people' => [
            'label' => 'People',
            'icon' => 'users',
            'items' => [
                ['label' => 'Users', 'route' => 'admin.users.index', 'active' => 'admin.users.*'],
                ['label' => 'Workplaces', 'route' => 'admin.workplaces.index', 'active' => 'admin.workplaces.*'],
                ['label' => 'Employers', 'route' => 'admin.employers.index', 'active' => 'admin.employers.*', 'badge' => 'employers'],
                ['label' => 'Ratings', 'route' => 'admin.ratings.index', 'active' => 'admin.ratings.*'],
            ],
        ],

        'intelligence' => [
            'label' => 'Intelligence',
            'icon' => 'map',
            'items' => [
                ['label' => 'Road Intelligence', 'route' => 'admin.road.index', 'active' => 'admin.road.*'],
                ['label' => 'GTFS Publisher', 'route' => 'admin.gtfs.index', 'active' => 'admin.gtfs.*'],
                ['label' => 'Demand Calendar', 'route' => 'admin.forecasts.index', 'active' => 'admin.forecasts.*'],
                ['label' => 'Rewards', 'route' => 'admin.rewards.index', 'active' => 'admin.rewards.*'],
                ['label' => 'Missions', 'route' => 'admin.missions.index', 'active' => 'admin.missions.*'],
            ],
        ],

        'business' => [
            'label' => 'Business',
            'icon' => 'wallet',
            'items' => [
                ['label' => 'Subsidies', 'route' => 'admin.subsidies.index', 'active' => 'admin.subsidies.*'],
                ['label' => 'Business', 'route' => 'admin.business.index', 'active' => 'admin.business.*'],
                ['label' => 'Stakeholders', 'route' => 'admin.stakeholders.index', 'active' => 'admin.stakeholders.*'],
                ['label' => 'Community Trust', 'route' => 'admin.trust.index', 'active' => 'admin.trust.*'],
            ],
        ],

        'system' => [
            'label' => 'System',
            'icon' => 'settings',
            'items' => [
                ['label' => 'Settings', 'route' => 'admin.settings.index', 'active' => 'admin.settings.*'],
            ],
        ],
    ],

];
