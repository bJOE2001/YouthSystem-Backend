<?php

return [
    'enabled' => env('SMS_ENABLED', true),
    'gateway_url' => env('SMS_GATEWAY_URL', 'http://192.168.100.52/cgi/WebCGI?1500101=account=apiuser&password=apipass&port=1'),
    'timeout' => (int) env('SMS_TIMEOUT', 10),
];
