<?php

namespace Solire\Front\Controller;
use Solire\Lib\Format\String;
use Solire\Lib\Registry;

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
        header("HTTP/1.0 404 Not Found");
        $this->seo->setTitle($this->tr('Page non trouvée'));
    }
}
