<?php
declare(strict_types=1);

namespace Sto\LogoutInfo\Logger;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "logout_info".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Sto\LogoutInfo\Adapter\BackendUserAuthAdapter;
use Sto\LogoutInfo\Session\BackendSessionData;
use Sto\LogoutInfo\Utility\GeneralUtilAdapter;

/**
 * Hooks into the logoff process and writes the logoff reason to the
 * sys_log table
 */
class LogoutReasonLogger
{
    /**
     * @var BackendUserAuthAdapter
     */
    protected $userAuth;

    /**
     * @var GeneralUtilAdapter
     */
    private $generalUtilAdapter;

    /**
     * @var bool
     */
    private $logoutWasCalled = false;

    /**
     * @var BackendSessionData
     */
    private $previousSessionData;

    public function __construct(
        BackendUserAuthAdapter $backendUserAuthAdapter,
        GeneralUtilAdapter $generalUtilAdapter,
        BackendSessionData $previousSessionData
    ) {
        $this->userAuth = $backendUserAuthAdapter;
        $this->generalUtilAdapter = $generalUtilAdapter;
        $this->previousSessionData = $previousSessionData;
    }

    public function logLogoutReason()
    {
        if (!$this->shouldLogLogoutReason()) {
            return;
        }

        if (!$this->isSessionIpLockValid()) {
            return;
        }

        if (!$this->logoutWasCalled) {
            return;
        }

        $sessionId = $this->getSessionId();
        if ($sessionId === '') {
            $this->writeBackendLog('Could not determine current session ID.');
            return;
        }

        $sessionRecord = $this->fetchSessionRecord($sessionId);
        if (empty($sessionRecord)) {
            return;
        }

        if (!$this->isSessionTimeoutValid($sessionRecord)) {
            return;
        }

        $this->logUnknownLogoutReason();
    }

    public function logoutWasCalled()
    {
        $this->logoutWasCalled = true;
    }

    private function fetchSessionRecord(string $sessionId): array
    {
        $sessionRecord = $this->previousSessionData->getSessionData();

        if (empty($sessionRecord)) {
            $this->writeBackendLog('No session found for ID %s', [$sessionId]);
        }

        return $sessionRecord;
    }

    private function fetchUserRecord(int $userId): array
    {
        $userRecord = $this->userAuth->getRawUserByUid($userId);
        if (!empty($userRecord)) {
            return $userRecord;
        }

        $this->writeBackendLog(
            'Session %s user with UID %d was not found in the database',
            [
                $this->userAuth->getSessionId(),
                $userId,
            ]
        );

        return [];
    }

    private function getSessionId(): string
    {
        return (string)$this->userAuth->getSessionId();
    }

    private function getSessionTimeout(array $userRecord): int
    {
        if (empty($this->userAuth->getAuthTimeoutField())) {
            return (int)$this->userAuth->getSessionTimeout();
        }

        // Get timeout-time from usertable
        return (int)$userRecord[$this->userAuth->getAuthTimeoutField()];
    }

    private function isSessionIpLockValid(): bool
    {
        $sessionRecord = $this->previousSessionData->getSessionData();
        if (empty($sessionRecord)) {
            return true;
        }

        $ipLock = $this->userAuth->formatIpForLockCheck($this->userAuth->getIpLockSetting());

        if ($sessionRecord['ses_iplock'] === $ipLock) {
            return true;
        }

        if ($sessionRecord['ses_iplock'] === '[DISABLED]') {
            return true;
        }

        $this->writeBackendLog(
            'Session %s IP lock %s did not match calculated IP lock %s',
            [
                $this->previousSessionData->getSessionId(),
                $sessionRecord['ses_iplock'],
                $ipLock,
            ]
        );

        return false;
    }

    private function isSessionTimeoutValid($sessionRecord): bool
    {
        $userId = (int)$sessionRecord['ses_userid'];
        $userRecord = $this->fetchUserRecord($userId);
        if (empty($userRecord)) {
            return false;
        }

        $userRecord = array_merge($sessionRecord, $userRecord);
        $sessionTimeout = $this->getSessionTimeout($userRecord);
        if ($sessionTimeout <= 0) {
            $this->writeBackendLog(
                'Session %s had an invalid timeout value %d',
                [
                    $this->getSessionId(),
                    $sessionTimeout,
                ]
            );
            return false;
        }

        $sessionTimestamp = (int)$userRecord['ses_tstamp'];
        $timeoutTimestamp = $sessionTimestamp + $sessionTimeout;
        if ($this->generalUtilAdapter->getExecTime() >= $timeoutTimestamp) {
            $this->writeBackendLog(
                'Session %s has timed out at %d',
                [
                    $this->getSessionId(),
                    $timeoutTimestamp,
                ]
            );
            return false;
        }

        return true;
    }

    private function logUnknownLogoutReason(): void
    {
        $this->writeBackendLog(
            'The session id %s in cookie %s was logged off for an unknown reason!',
            [
                $this->userAuth->getSessionId(),
                $this->userAuth->getCookieName(),
            ]
        );
    }

    private function shouldLogLogoutReason(): bool
    {
        $route = $this->generalUtilAdapter->get('route');
        if ($route === '/logout') {
            return false;
        }

        $loginData = $this->userAuth->getLoginFormData();
        if (empty($loginData['status'])) {
            return true;
        }

        // The user requested to be logged off so we do not care
        if ($loginData['status'] == 'logout') {
            return false;
        }

        // If the user tried to log in but failed we do not care
        if ($loginData['status'] == 'login') {
            return false;
        }

        return true;
    }

    /**
     * Writes a sys_log entry of type "logout"
     *
     * @param string $details
     * @param array $data
     */
    private function writeBackendLog(string $details, array $data = []): void
    {
        // 255=login / out action
        $type = 255;

        // 1=login, 2=logout, 3=attempt
        $action = 2;

        $error = 0;
        $detailsNr = 5;

        $this->userAuth->writelog($type, $action, $error, $detailsNr, $details, $data);
    }
}
