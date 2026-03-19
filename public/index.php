<?php

declare(strict_types=1);
ini_set('memory_limit', '256M');
use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    $env = $context['APP_ENV'];
    $parts = explode('.', $context['HTTP_HOST']);
    if (count($parts) > 1) {
        $domain = array_shift($parts);
        $customers = explode(',', $context['APP_CUSTOMER_ENV']);
        if (in_array($domain, $customers)) {
            $env = $domain;
            $context['APP_DEBUG'] = true;
        } elseif ('extra' === $env) {
            $env = 'dev';
            $context['APP_DEBUG'] = true;
        } elseif ('dev' !== $env) {
            $env = 'prod';
        }
    }

    return new Kernel($env, (bool) $context['APP_DEBUG']);
};
