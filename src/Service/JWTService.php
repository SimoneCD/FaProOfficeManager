<?php

declare(strict_types=1);

namespace App\Service;

use Firebase\JWT\JWT;
use Google\Service\IAMCredentials;

class JWTService
{
    protected \Google_Client $client;
    protected const APPLICATION_ENCODING = 'RS256';
    protected const APPLICATION_CUSTOMER_NAME = 'SF-Elektro green-management';
    protected const APPLICATION_SERVICE_ACCOUNT_ID = 'green-management@sf-elektro-green-management-eu.iam.gserviceaccount.com';
    protected function getHeader(): array
    {
        return [
            'alg' => 'RS256',
            'typ' => 'JWT',
            'kid' => $this->getCredentialsAsJson()->private_key_id,
        ];
    }

    protected function getGmailScopes(): string
    {
        // return 'https://gmail.googleapis.com/ https://www.googleapis.com/auth/gmail.readonly';
        return 'https://gmail.googleapis.com/ https://www.googleapis.com/auth/gmail.modify https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.compose';
    }

    public function getSecred()
    {
        $j = $this->getCredentialsAsJson();

        return $j->private_key;
    }

    public function getCredentialsAsJson(): mixed
    {

        return json_decode($credentialsContent);
    }
}
