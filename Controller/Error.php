<?php
/**
 * Controleur qui gère les pages d'erreurs
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Front\Controller;

/**
 * Controleur qui gère les pages d'erreurs
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Error extends Main
{
    /**
     * Action pour l'erreur 404
     *
     * @return void
     */
    public function error404Action()
    {
        $page = $this->gabaritManager->getPage(ID_VERSION, ID_API, 1, 0, 0, true);

        $this->seo->setTitle($page->getMeta('titre'));
        $this->seo->setDescription($page->getMeta('bal_descr'));

        $requestUrl = str_replace(
            \Solire\Lib\Registry::get('url'),
            '',
            'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']
        );

        $requestUrl = urldecode($requestUrl);
        $requestUrl = strtolower($requestUrl);
        $requestUrl = \Solire\Lib\String::replaceAccent($requestUrl);
        $tab        = preg_split('`[^a-z]+`', $requestUrl);

        $trash = array(
            'html',
            'htm',
            'php',
        );

        $tab = array_diff($tab, $trash);

        foreach ($tab as $ii => $t) {
            if (mb_strlen($t) < 3) {
                unset($tab[$ii]);
            }
        }

        $this->view->search = implode(' ', $tab);
    }
}
