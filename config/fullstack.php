<?php

return [

   'cache_clear_commands'=>[
        'cache:clear',
     // 'cache:prune-stale-tags', // Only for Redis database
        'config:clear',
        'route:clear',
        'view:clear',
        'optimize:clear',
        'filament:clear-cached-components',
        'filament:optimize-clear',
        'modelCache:clear',
        'settings:clear-cache',
        'settings:clear-discovered',
        'schedule:clear-cache',
        'icons:clear',
   ],

];