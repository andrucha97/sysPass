<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

use SP\UserUtil;

define('APP_ROOT', '..');

require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

SP\Request::checkReferer('POST');

$sk = SP\Request::analyze('sk', false);

if (!$sk || !SP\Common::checkSessionKey($sk)) {
    SP\Common::printJSON(_('CONSULTA INVÁLIDA'));
}

$userLogin = SP\Request::analyze('login');
$userEmail = SP\Request::analyze('email');
$userPass = SP\Request::analyze('pass');
$userPassV = SP\Request::analyze('passv');
$hash = SP\Request::analyze('hash');
$time = SP\Request::analyze('time');

$message['action'] = _('Recuperación de Clave');

if ($userLogin && $userEmail) {
    $log = new \SP\Log(_('Recuperación de Clave'));

    if (SP\Auth::mailPassRecover($userLogin, $userEmail)) {
        $log->addDescription(SP\Html::strongText(_('Solicitado para') . ': ') . ' ' . $userLogin . ' (' . $userEmail . ')');

        SP\Common::printJSON(_('Solicitud enviada') . ';;' . _('En breve recibirá un correo para completar la solicitud.'), 0, 'goLogin();');
    } else {
        $log->addDescription('ERROR');
        $log->addDescription(SP\Html::strongText(_('Solicitado para') . ': ') . ' ' . $userLogin . ' (' . $userEmail . ')');

        SP\Common::printJSON(_('No se ha podido realizar la solicitud. Consulte con el administrador.'));
    }

    $log->writeLog();
    SP\Email::sendEmail($log);
}

if ($userPass && $userPassV && $userPass === $userPassV) {
    $userId = UserUtil::checkHashPassRecover($hash);

    if ($userId) {
        if (UserUtil::updateUserPass($userId, $userPass) && UserUtil::updateHashPassRecover($hash)) {
            \SP\Log::writeNewLogAndEmail(_('Modificar Clave Usuario'), SP\Html::strongText(_('Login') . ': ') . UserUtil::getUserLoginById($userId));

            SP\Common::printJSON(_('Clave actualizada'), 0, 'goLogin();');
        }
    }

    SP\Common::printJSON(_('Error al modificar la clave'));
} else {
    SP\Common::printJSON(_('La clave es incorrecta o no coincide'));
}