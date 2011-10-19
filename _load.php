<?php

namespace Evolution\LHTML;
use Evolution\Kernel\Configure;
use Evolution\Kernel\Service;

/**
 * Add the site service
 */
Service::bind('Evolution\LHTML\Scope::addHook', 'lhtml:addhook');
Service::bind('Evolution\LHTML\Router::route', 'router:route:lhtml', 'portal:route:lhtml');

/**
 * Add lhtml to default router and portal routing
 */
Configure::add('portal.defaults.run_with', 'lhtml');
Configure::add('router.defaults.run_with', 'lhtml');