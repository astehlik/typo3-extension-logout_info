<?php
declare(strict_types=1);

namespace Sto\LogoutInfo\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sto\LogoutInfo\LogoutInfoFactory;
use TYPO3\CMS\Core\Session\Backend\Exception\SessionNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class BackendSessionDataCollectorMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $logoutInfoFactory = GeneralUtility::makeInstance(LogoutInfoFactory::class);
        $backendUserAuthAdapter = $logoutInfoFactory->createBackendUserAuthAdapterDummy();

        $cookieName = $backendUserAuthAdapter->getCookieName();
        $sessionId = $request->getCookieParams()[$cookieName] ?? '';

        $sessionData = [];
        if ($sessionId !== '') {
            try {
                $sessionData = $logoutInfoFactory->getBackendSessionBackend()->get($sessionId);
            } catch (SessionNotFoundException $e) {
                // Nothing to do here, session data stays empty.
            }
        }

        $logoutInfoFactory->initializeBackendSessionData($sessionId, $sessionData);

        return $handler->handle($request);
    }
}
