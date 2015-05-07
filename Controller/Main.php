<?php

namespace Solire\Front\Controller;

use Solire\Lib\FrontController;
use Solire\Lib\Registry;
use Solire\Lib\Model\GabaritManager;
use Solire\Lib\Session;
use Solire\Lib\Hook;

/**
 * Controleur principal
 *
 * @author  smonnot  <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Main extends \Solire\Lib\Controller
{
    /**
     * Utilisateur de l'admin
     * @var Session
     */
    public $utilisateurAdmin;

    /**
     * Manager des gabarits
     * @var GabaritManager
     */
    public $gabaritManager;

    /**
     * Fonction éxécutée avant l'execution de la fonction relative à la page en cours
     *
     * @return void
     * @hook front/ shutdown Avant l'inclusion de la vue
     */
    public function start()
    {
        parent::start();

        $this->seo->setTitle($this->mainConfig->get('project', 'name'));

        $this->view->googleAnalytics = Registry::get('analytics');

        $this->view->filAriane = null;

        $className = FrontController::searchClass('Model\GabaritManager');
        if ($className !== false) {
            $this->gabaritManager = new $className();
        } else {
            $this->gabaritManager = new GabaritManager();
        }

        /*
         * Mode prévisualisation,
         * On teste si utilisateur de l'admin est connecté et donc si il a la
         * possibilité de voir le site sans tenir compte de la visibilité
         */
        $this->utilisateurAdmin = new Session('back', 'back');
        $this->view->utilisateurAdmin = $this->utilisateurAdmin;

        $this->view->modePrevisuPage = false;

        if ($this->utilisateurAdmin->isConnected()
            && !$this->ajax
        ) {
            if (!isset($_POST['id_gabarit'])) {
                if (isset($_GET['mode_previsualisation'])) {
                    $_SESSION['mode_previsualisation'] = (bool) $_GET['mode_previsualisation'];
                }

                if (!isset($_SESSION['mode_previsualisation'])) {
                    $_SESSION['mode_previsualisation'] = 0;
                }

                $this->gabaritManager->setModePrevisualisation($_SESSION['mode_previsualisation']);

                $this->view->site = Registry::get('project-name');
                $this->view->modePrevisualisation = $_SESSION['mode_previsualisation'];
            } else {
                $this->view->modePrevisuPage = true;
            }
        }

        /*
         * Recupération des gabarits main
         */
        $this->view->mainPage = $this->gabaritManager->getMain(ID_VERSION, ID_API);

        /*
         * On recupere la page elements communs qui sera disponible sur toutes
         * les pages
         */
        $this->view->mainPage['element_commun'] = $this->gabaritManager->getPage(
            ID_VERSION,
            ID_API,
            $this->view->mainPage['element_commun'][0]->getMeta('id'),
            0,
            false,
            true
        );

        $this->view->breadCrumbs = array();
        $this->view->breadCrumbs[] = array(
            'label' => $this->tr('Accueil'),
            'url'   => './',
        );

        $hook = new Hook();
        $hook->setSubdirName('front');

        $hook->controller = $this;

        $hook->exec('start');
    }

    /**
     * Fonction éxécutée après l'execution de la fonction relative à la page en cours
     *
     * @return void
     * @hook front/ shutdown Avant l'inclusion de la vue
     */
    public function shutdown()
    {
        parent::shutdown();

        $hook = new Hook();
        $hook->setSubdirName('front');

        $hook->controller = $this;

        $hook->exec('shutdown');
    }
}
