<?php
declare(strict_types=1);

namespace Sto\LogoutInfo\Session;

class BackendSessionData
{
    /**
     * @var array
     */
    private $sessionData;

    /**
     * @var string
     */
    private $sessionId;

    public function __construct(string $sessionId, array $sessionData)
    {
        $this->sessionId = $sessionId;
        $this->sessionData = $sessionData;
    }

    public function getSessionData(): array
    {
        return $this->sessionData;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }
}
