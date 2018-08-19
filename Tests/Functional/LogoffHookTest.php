<?php
declare(strict_types=1);

namespace Sto\LogoutInfo\Tests\Functional;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PHPUnit\Util\PHP\DefaultPhpProcess;

class LogoffHookTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = ['typo3conf/ext/logout_info'];

    /**
     * @param array $sessionUpdate
     * @param string $expectedMessage
     * @test
     * @dataProvider logoutReasonIsLoggedIfSessionExpiredDataProvider
     */
    public function logoutReasonIsLoggedIfSessionExpired(array $sessionUpdate, string $expectedMessage): void
    {
        $backendUser = $this->setUpBackendUserFromFixture(1);
        $sessionId = $backendUser->id;

        $this->importDataSet('ntf://Database/pages.xml');
        $this->setUpFrontendRootPage(1, ['ntf://TypoScript/JsonRenderer.ts']);

        $this->getDatabaseConnection()->updateArray(
            'be_sessions',
            ['ses_id' => $sessionId],
            $sessionUpdate
        );

        $this->makeBackendRequest($backendUser->id);

        $logEntry = $this->getDatabaseConnection()->selectSingleRow(
            '*',
            'sys_log',
            '1=1'
        );

        $this->assertEquals($expectedMessage, $logEntry['details']);
    }

    public function logoutReasonIsLoggedIfSessionExpiredDataProvider(): array
    {
        return [
            'session timeout' => [
                ['ses_tstamp' => 100],
                'Session %s has timed out at %d',
            ],
            'session IP lock changed' => [
                ['ses_iplock' => '192.168.178.26'],
                'Session %s IP lock %s did not match calculated IP lock %s',
            ],
        ];
    }

    private function makeBackendRequest($backendSessionId): void
    {
        $arguments = [
            'documentRoot' => $this->getInstancePath(),
            'requestUrl' => 'http://localhost/typo3/index.php',
            'backendSessionId' => $backendSessionId,
        ];

        $template = new \Text_Template(__DIR__ . '/Request.tpl');
        $template->setVar(
            [
                'arguments' => var_export($arguments, true),
                'originalRoot' => ORIGINAL_ROOT,
                'ntfRoot' => __DIR__ . '/../../',
                'backendSessionId' => $backendSessionId,
            ]
        );

        $php = DefaultPhpProcess::factory();
        $php->runJob($template->render());
    }
}
