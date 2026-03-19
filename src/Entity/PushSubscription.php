<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class PushSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string')]
    private $endpoint;

    #[ORM\Column(name: 'subscription_keys', type: 'json')]
    private $keys;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user;

    public function getId()
    {
        return $this->id;
    }

    public function getEndpoint()
    {
        return $this->endpoint;
    }

    public function setEndpoint($endpoint): void
    {
        $this->endpoint = $endpoint;
    }

    public function getKeys()
    {
        return $this->keys;
    }

    public function setKeys($keys): void
    {
        $this->keys = $keys;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user): void
    {
        $this->user = $user;
    }
}
