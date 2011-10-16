<?php

namespace Evolution\LHTML;
use Evolution\Kernel\Service;

/**
 * Add the site service
 */
Service::bind('Evolution\LHTML\Bundle::sahook', 'lhtml:addhook');
Service::bind('Evolution\LHTML\Bundle::sbuild', 'lhtml:parse');
