<?php
declare(strict_types=1);

namespace Sto\LogoutInfo;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "logout_info".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LoggerHooks
{
    /**
     * This method is called by t3lib_userAuth at the beginning of the
     * logoff process and writes the reason why a user was logged off
     * to the log.
     *
     * @param array $params The params array is empty
     * @param \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication $userAuth The parent user auth object
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function logoffPreProcessing(
        /** @noinspection PhpUnusedParameterInspection */
        $params,
        $userAuth
    ) {
        if (!$userAuth instanceof BackendUserAuthentication) {
            return;
        }

        $logoutInfoFactory = GeneralUtility::makeInstance(LogoutInfoFactory::class);
        $logoutReasonLogger = $logoutInfoFactory->getLogoutReasonLogger($userAuth);
        $logoutReasonLogger->logoutWasCalled();
    }

    public function postUserLookUp(
        /** @noinspection PhpUnusedParameterInspection */
        array $params,
        $userAuth
    ) {
        if (!$userAuth instanceof BackendUserAuthentication) {
            return;
        }

        $logoutInfoFactory = GeneralUtility::makeInstance(LogoutInfoFactory::class);
        if (!$logoutInfoFactory->isPreviousBackendSessionDataAvailable()) {
            return;
        }

        $logoutReasonLogger = $logoutInfoFactory->getLogoutReasonLogger($userAuth);
        $logoutReasonLogger->logLogoutReason();
    }
}
