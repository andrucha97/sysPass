<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Exceptions\SPException;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;

/**
 * Class ConfigWikiController
 *
 * @package SP\Modules\Web\Controllers
 */
class ConfigWikiController extends SimpleControllerBase
{
    use ConfigTrait;

    /**
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    public function saveAction()
    {
        $messages = [];
        $configData = clone $this->config->getConfigData();

        // Wiki
        $wikiEnabled = Request::analyze('wiki_enabled', false, false, true);
        $wikiSearchUrl = Request::analyze('wiki_searchurl');
        $wikiPageUrl = Request::analyze('wiki_pageurl');
        $wikiFilter = Request::analyze('wiki_filter');

        // Valores para la conexión a la Wiki
        if ($wikiEnabled && (!$wikiSearchUrl || !$wikiPageUrl || !$wikiFilter)) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de Wiki'));
        }

        if ($wikiEnabled) {
            $configData->setWikiEnabled(true);
            $configData->setWikiSearchurl($wikiSearchUrl);
            $configData->setWikiPageurl($wikiPageUrl);
            $configData->setWikiFilter(explode(',', $wikiFilter));

            $messages[] = __u('Wiki habiltada');
        } elseif ($configData->isWikiEnabled()) {
            $configData->setWikiEnabled(false);

            $messages[] = __u('Wiki deshabilitada');
        }

        // DokuWiki
        $dokuWikiEnabled = Request::analyze('dokuwiki_enabled', false, false, true);
        $dokuWikiUrl = Request::analyze('dokuwiki_url');
        $dokuWikiUrlBase = Request::analyze('dokuwiki_urlbase');
        $dokuWikiUser = Request::analyze('dokuwiki_user');
        $dokuWikiPass = Request::analyzeEncrypted('dokuwiki_pass');
        $dokuWikiNamespace = Request::analyze('dokuwiki_namespace');

        // Valores para la conexión a la API de DokuWiki
        if ($dokuWikiEnabled && (!$dokuWikiUrl || !$dokuWikiUrlBase)) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de DokuWiki'));
        }

        if ($dokuWikiEnabled) {
            $configData->setDokuwikiEnabled(true);
            $configData->setDokuwikiUrl($dokuWikiUrl);
            $configData->setDokuwikiUrlBase(trim($dokuWikiUrlBase, '/'));
            $configData->setDokuwikiUser($dokuWikiUser);
            $configData->setDokuwikiPass($dokuWikiPass);
            $configData->setDokuwikiNamespace($dokuWikiNamespace);

            $messages[] = __u('DokuWiki habilitada');
        } elseif ($configData->isDokuwikiEnabled()) {
            $configData->setDokuwikiEnabled(false);

            $messages[] = __u('DokuWiki deshabilitada');
        }

        $this->eventDispatcher->notifyEvent('save.config.wiki', new Event($this, $messages));

        $this->saveConfig($configData, $this->config);
    }

    protected function initialize()
    {
        try {
            if (!$this->checkAccess(ActionsInterface::WIKI_CONFIG)) {
                throw new UnauthorizedPageException(SPException::INFO);
            }
        } catch (UnauthorizedPageException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage(), [$e->getHint()]);
        }
    }
}