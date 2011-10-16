<?php

namespace Evolution\LHTML;
use Evolution\Kernel\Service;

/**
 * Add the site service
 */
Service::bind('Evolution\LHTML\Bundle::add_hook', 'lhtml:addhook');
Service::bind('Evolution\Router\Controller::route', 'portal:route:controller');
