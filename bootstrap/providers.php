<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\JetstreamServiceProvider::class,
...(class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class) ? [App\Providers\TelescopeServiceProvider::class] : []),
];
