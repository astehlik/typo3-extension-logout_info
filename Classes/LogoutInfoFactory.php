<?php
declare(strict_types=1);

namespace Sto\LogoutInfo;

use Sto\LogoutInfo\Adapter\BackendUserAuthAdapter;
use Sto\LogoutInfo\Logger\LogoutReasonLogger;
use Sto\LogoutInfo\Session\BackendSessionData;
use Sto\LogoutInfo\Utility\GeneralUtilAdapter;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LogoutInfoFactory implements SingletonInterface
{
    private $backendSessionData;

    private $logoutReasonLogger;

    public function initializeBackendSessionData(string $sessionId, array $sessionData): void
    {
        $this->backendSessionData = GeneralUtility::makeInstance(BackendSessionData::class, $sessionId, $sessionData);
    }

    public function createBackendUserAuthAdapter(BackendUserAuthentication $backendUserAuth): BackendUserAuthAdapter
    {
        return GeneralUtility::makeInstance(BackendUserAuthAdapter::class, $backendUserAuth);
    }

    public function createBackendUserAuthAdapterDummy(): BackendUserAuthAdapter
    {
        $backendUserAuth = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        return $this->createBackendUserAuthAdapter($backendUserAuth);
    }

    public function getBackendSessionBackend(): SessionBackendInterface
    {
        return $this->getSessionManager()->getSessionBackend('BE');
    }

    public function getBackendSessionData(): BackendSessionData
    {
        if (!$this->isPreviousBackendSessionDataAvailable()) {
            throw new \RuntimeException('Backend session data is not available.');
        }

        return $this->backendSessionData;
    }

    public function getLogoutReasonLogger(BackendUserAuthentication $backendUserAuthentication): LogoutReasonLogger
    {
        if ($this->logoutReasonLogger) {
            return $this->logoutReasonLogger;
        }

        $this->logoutReasonLogger = GeneralUtility::makeInstance(
            LogoutReasonLogger::class,
            $this->createBackendUserAuthAdapter($backendUserAuthentication),
            $this->getGeneralUtilAdapter(),
            $this->getBackendSessionData()
        );

        return $this->logoutReasonLogger;
    }

    public function isPreviousBackendSessionDataAvailable(): bool
    {
        return isset($this->backendSessionData);
    }

    private function getGeneralUtilAdapter(): GeneralUtilAdapter
    {
        return GeneralUtility::makeInstance(GeneralUtilAdapter::class);
    }

    private function getSessionManager(): SessionManager
    {
        return GeneralUtility::makeInstance(SessionManager::class);
    }
}
