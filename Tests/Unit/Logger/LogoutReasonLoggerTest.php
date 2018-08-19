<?php
declare(strict_types=1);

namespace Sto\LogoutInfo\Tests\Unit\Logger;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "logout_info".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use PHPUnit\Framework\TestCase;
use Sto\LogoutInfo\Adapter\BackendUserAuthAdapter;
use Sto\LogoutInfo\Logger\LogoutReasonLogger;
use Sto\LogoutInfo\Session\BackendSessionData;
use Sto\LogoutInfo\Utility\GeneralUtilAdapter;

class LogoutReasonLoggerTest extends TestCase
{
    private $backendAuthAdapterProphecy;

    private $backendSessionData;

    private $generalUtilAdapterProphecy;

    private $logoutReasonLogger;

    protected function setUp()
    {
        $this->backendAuthAdapterProphecy = $this->prophesize(BackendUserAuthAdapter::class);
        $this->backendAuthAdapterProphecy->getLoginFormData()->willReturn([]);
        $this->backendAuthAdapterProphecy->getLoginType()->willReturn('BE');
        $this->backendAuthAdapterProphecy->getIpLockSetting()->willReturn(4);
        $this->backendAuthAdapterProphecy->formatIpForLockCheck(4)->willReturn('127.0.0.1');
        $this->backendAuthAdapterProphecy->getSessionTimeout()->willReturn(35);

        $this->generalUtilAdapterProphecy = $this->prophesize(GeneralUtilAdapter::class);
        $this->generalUtilAdapterProphecy->get('route')->willReturn('/someroute');
        $this->generalUtilAdapterProphecy->getExecTime()->willReturn(50);

        $this->backendSessionData = $this->prophesize(BackendSessionData::class);
        $this->backendSessionData->getSessionData()->willReturn([]);

        $this->logoutReasonLogger = new LogoutReasonLogger(
            $this->backendAuthAdapterProphecy->reveal(),
            $this->generalUtilAdapterProphecy->reveal(),
            $this->backendSessionData->reveal()
        );
        $this->logoutReasonLogger->logoutWasCalled();
    }

    /**
     * @test
     */
    public function logLogoutReasonLogsErrorIfIpLockIsInvalid()
    {
        $this->backendSessionData->getSessionId()->willReturn('somesession');
        $this->backendSessionData->getSessionData()->willReturn(['ses_iplock' => '127.0.0.2']);
        $this->expectLogCall(
            'Session %s IP lock %s did not match calculated IP lock %s',
            [
                'somesession',
                '127.0.0.2',
                '127.0.0.1',
            ]
        );
        $this->logoutReasonLogger->logLogoutReason();
    }

    /**
     * @test
     */
    public function logLogoutReasonLogsErrorIfSessionIdUnavailable()
    {
        $this->initializeSessionId('');
        $this->expectLogCall('Could not determine current session ID.', []);
        $this->logoutReasonLogger->logLogoutReason();
    }

    /**
     * @test
     */
    public function logLogoutReasonLogsErrorIfSessionRecordIsUnavailble()
    {
        $this->initializeSessionId('somesession');
        $this->expectLogCall('No session found for ID %s', ['somesession']);
        $this->logoutReasonLogger->logLogoutReason();
    }

    /**
     * @param string $authTimeoutField
     * @param int $timeoutTimestamp
     * @test
     * @dataProvider logLogoutReasonLogsErrorIfSessionTimedOutDataProvider
     */
    public function logLogoutReasonLogsErrorIfSessionTimedOut(string $authTimeoutField, int $timeoutTimestamp)
    {
        $this->initializeSessionId('somesession');
        $this->initializeValidIp();
        $this->initializeValidUser();

        $this->backendAuthAdapterProphecy->getAuthTimeoutField()->shouldBeCalled()->willReturn($authTimeoutField);

        $this->expectLogCall(
            'Session %s has timed out at %d',
            [
                'somesession',
                $timeoutTimestamp,
            ]
        );

        $this->logoutReasonLogger->logLogoutReason();
    }

    public function logLogoutReasonLogsErrorIfSessionTimedOutDataProvider()
    {
        return [
            'existing timeout field' => [
                'timeout_field',
                20,
            ],
            'non existing timeout field' => [
                '',
                45,
            ],
        ];
    }

    /**
     * @test
     */
    public function logLogoutReasonLogsErrorIfUserIsNotFound()
    {
        $this->initializeSessionId('somesession');
        $this->initializeValidIp();
        $this->backendAuthAdapterProphecy->getRawUserByUid(1)->willReturn([]);

        $this->expectLogCall(
            'Session %s user with UID %d was not found in the database',
            [
                'somesession',
                1,
            ]
        );

        $this->logoutReasonLogger->logLogoutReason();
    }

    /**
     * @test
     */
    public function logLogoutReasonLogsUnknownReasonIfNoReasonIsDetected()
    {
        $this->initializeSessionId('somesession');
        $this->initializeValidIp();
        $this->initializeValidUser();

        $this->backendAuthAdapterProphecy->getAuthTimeoutField()->shouldBeCalled()->willReturn('');
        $this->generalUtilAdapterProphecy->getExecTime()->willReturn(10);
        $this->backendAuthAdapterProphecy->getCookieName()->willReturn('thecookiename');

        $this->expectLogCall(
            'The session id %s in cookie %s was logged off for an unknown reason!',
            [
                'somesession',
                'thecookiename',
            ]
        );

        $this->logoutReasonLogger->logLogoutReason();
    }

    /**
     * @test
     */
    public function logLogoutReasonSkipsForLoginSubmit()
    {
        $this->backendAuthAdapterProphecy->getLoginFormData()->shouldBeCalled()->willReturn(['status' => 'login']);
        $this->doNotExpectLogCall();
        $this->logoutReasonLogger->logLogoutReason();
    }

    /**
     * @test
     */
    public function logLogoutReasonSkipsForLogoutRoute()
    {
        $this->generalUtilAdapterProphecy->get('route')->shouldBeCalled()->willReturn('/logout');
        $this->doNotExpectLogCall();
        $this->logoutReasonLogger->logLogoutReason();
    }

    /**
     * @test
     */
    public function logLogoutReasonSkipsForLogoutSubmit()
    {
        $this->backendAuthAdapterProphecy->getLoginFormData()->shouldBeCalled()->willReturn(['status' => 'logout']);
        $this->doNotExpectLogCall();
        $this->logoutReasonLogger->logLogoutReason();
    }

    private function doNotExpectLogCall(): void
    {
        $methodProphecy = $this->backendAuthAdapterProphecy->writelog(0, 0, 0, 0, '', []);
        $methodProphecy->shouldNotBeCalled();
    }

    private function expectLogCall(string $logMessage, array $logParameters): void
    {
        $methodProphecy = $this->backendAuthAdapterProphecy->writelog(255, 2, 0, 5, $logMessage, $logParameters);
        $methodProphecy->shouldBeCalled();
    }

    private function initializeSessionId(string $string)
    {
        $this->backendAuthAdapterProphecy->getSessionId()->shouldBeCalled()->willReturn($string);
    }

    private function initializeValidIp(): void
    {
        $this->backendSessionData->getSessionData()->willReturn(
            [
                'ses_iplock' => '127.0.0.1',
                'ses_userid' => 1,
            ]
        );
    }

    private function initializeValidUser(): void
    {
        $this->backendAuthAdapterProphecy->getRawUserByUid(1)->willReturn(
            [
                'ses_tstamp' => 10,
                'timeout_field' => 10,
            ]
        );
    }
}
