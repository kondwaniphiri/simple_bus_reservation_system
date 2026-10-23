<?php
// Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025
require_once __DIR__.'/Environment.php';
define('DB_NAME', Environment::value('BUS_DB_NAME', 'bus_ticket'));
define('DB_HOST', Environment::value('BUS_DB_HOST', '127.0.0.1'));
define('DB_USER', Environment::value('BUS_DB_USER', 'root'));
define('DB_PASSWORD', Environment::value('BUS_DB_PASSWORD', ''));
define('DB_PORT', Environment::integer('BUS_DB_PORT', 3306, 1, 65535));
define('CONTACT_RATE_LIMIT', Environment::integer('CONTACT_RATE_LIMIT', 5, 1, 100));
define('CONTACT_MIN_LENGTH', Environment::integer('CONTACT_MIN_LENGTH', 20, 1, 1000));
define('CONTACT_MAX_LENGTH', Environment::integer('CONTACT_MAX_LENGTH', 4000, CONTACT_MIN_LENGTH, 16000));
