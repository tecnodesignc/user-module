<?php

view()->composer(
    [
        'user::admin.partials.permissions',
        'user::admin.partials.permissions-create',
        'permissions.partials.permissions',
        'permissions.partials.permissions-create',
    ],
    'Modules\User\Composers\PermissionsViewComposer'
);

view()->composer(
    [
        'partials.sidebar-nav',
        'partials.top-nav',
        'layouts.master',
        'partials.*',
    ],
    'Modules\User\Composers\UsernameViewComposer'
);
