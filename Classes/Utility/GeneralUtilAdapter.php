<?php
declare(strict_types=1);

namespace Sto\LogoutInfo\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Extension "logout_info".              *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class GeneralUtilAdapter implements SingletonInterface
{
    public function get(string $name): string
    {
        return (string)GeneralUtility::_GET($name);
    }

    public function getExecTime(): int
    {
        return $GLOBALS['EXEC_TIME'];
    }
}
