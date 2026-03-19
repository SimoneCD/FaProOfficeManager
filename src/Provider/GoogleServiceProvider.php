<?php

declare(strict_types=1);

namespace App\Provider;

class GoogleServiceProvider
{
    public function registerMail()
    {
        $client = new \Google_Client();
        $client->setSubject($_ENV['GOOGLE_SERVICE_ACCOUNT_SUBJECT']);

        // set the authorization configuration using the 2.0 style
        $client->setAuthConfig([
            'type' => 'service_account',
            'client_email' => $_ENV['GOOGLE_SERVICE_ACCOUNT_PRIVATE_EMAIL'],
            'client_id' => $_ENV['GOOGLE_SERVICE_ACCOUNT_CLIENT_ID'],
            'private_key' => $_ENV['GOOGLE_SERVICE_ACCOUNT_PRIVATE_KEY'],
        ]);
    }
}
