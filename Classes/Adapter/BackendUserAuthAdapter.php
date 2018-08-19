<?php
declare(strict_types=1);

namespace Sto\LogoutInfo\Adapter;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "logout_info".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use ReflectionMethod;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Makes some protected fields in the default backend user authentication
 * accessible
 */
class BackendUserAuthAdapter
{
    /**
     * @var BackendUserAuthentication
     */
    private $backendUserAuthentication;

    public function __construct(BackendUserAuthentication $backendUserAuthentication)
    {
        $this->backendUserAuthentication = $backendUserAuthentication;
    }

    public function formatIpForLockCheck($parts)
    {
        $ipLockClauseMethod = new ReflectionMethod(BackendUserAuthentication::class, 'ipLockClause_remoteIPNumber');
        $ipLockClauseMethod->setAccessible(true);
        return $ipLockClauseMethod->invoke($this->backendUserAuthentication, $parts);
    }

    public function getAuthTimeoutField(): string
    {
        return (string)$this->backendUserAuthentication->auth_timeout_field;
    }

    public function getCookieName(): string
    {
        return (string)$this->backendUserAuthentication->name;
    }

    public function getIpLockSetting(): int
    {
        return (int)$this->backendUserAuthentication->lockIP;
    }

    public function getLoginFormData(): array
    {
        return $this->backendUserAuthentication->getLoginFormData();
    }

    public function getLoginType(): string
    {
        return (string)$this->backendUserAuthentication->loginType;
    }

    public function getRawUserByUid(int $userId): array
    {
        $user = $this->backendUserAuthentication->getRawUserByUid($userId);
        if (!empty($user)) {
            return $user;
        }

        return [];
    }

    public function getSessionId(): string
    {
        return (string)$this->backendUserAuthentication->id;
    }

    public function getSessionTimeout(): int
    {
        return (int)$this->backendUserAuthentication->sessionTimeout;
    }

    public function writelog(int $type, int $action, int $error, int $detailsNr, string $details, array $data): void
    {
        $this->backendUserAuthentication->writelog($type, $action, $error, $detailsNr, $details, $data);
    }
}
