<?php

namespace Solire\Front\Controller;

use Solire\Lib\Registry;

/**
 * Controleur qui gère le middle office
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Middleoffice extends \Solire\Lib\Controller
{
    /**
     * Le gabaritpage de la page courante
     *
     * @var \Solire\Lib\Model\gabaritPage
     */
    private $page = null;

    /**
     * La session de l'utilisateur admin connecté
     *
     * @var \Solire\Lib\Session
     */
    protected $utilisateurAdmin;

    /**
     * La manageur des gabarits
     *
     * @var \Solire\Lib\Model\gabaritManager
     */
    public $gabaritManager;

    /**
     * Accepte les rewritings
     *
     * @var boolean
     */
    public $acceptRew = true;

    /**
     * Toujours executé avant l'action.
     *
     * @return void
     */
    public function start()
    {
        parent::start();
        $this->utilisateurAdmin = new \Solire\Lib\Session('back', 'back');
        $this->gabaritManager = new \Solire\Lib\Model\GabaritManager();
    }

    /**
     * Action pour la barre du middle office
     *
     * @return void
     */
    public function toolbarbackAction()
    {
        $this->view->utilisateurAdmin = $this->utilisateurAdmin;
        if ($this->utilisateurAdmin->isConnected()) {
            $this->view->site = Registry::get('project-name');
            $this->view->modePrevisualisation = $_SESSION['mode_previsualisation'];
        }
        $this->view->unsetMain();
        $this->javascript->addLibrary('back/js/bootstrap/bootstrap.min.js');
        $this->javascript->addLibrary('back/js/main.js');
        $this->css->addLibrary(
            'back/css/bootstrap/bootstrap.min.css',
            [
                'media' => 'screen',
            ]
        );

        if (isset($_POST['id_gab_page'])
            && intval($_POST['id_gab_page']) > 0
            && isset($_POST['id_api'])
            && intval($_POST['id_api'])
        ) {
            $this->page = $this->gabaritManager->getPage(
                ID_VERSION,
                intval($_POST['id_api']),
                intval($_POST['id_gab_page'])
            );

            if ($this->utilisateurAdmin->isConnected()) {
                $this->page->makeVisible = true;
                $this->page->makeHidden  = true;

                $hook = new \Solire\Lib\Hook();
                $hook->setSubdirName('back');

                $hook->permission  = null;
                $hook->utilisateur = $this->utilisateurAdmin;
                $hook->visible     = $this->page->getMeta('visible') == 0 ? 1 : 0;
                $hook->ids         = $_POST['id_gab_page'];
                $hook->versionId   = ID_VERSION;

                $hook->exec('pagevisible');

                /*
                 * On récupère la permission du hook,
                 * on interdit uniquement si la variable a été modifié à false.
                 */
                if ($hook->permission === false) {
                    if ($hook->visible == 1) {
                        $this->page->makeVisible = false;
                    } else {
                        $this->page->makeHidden  = false;
                    }
                }
            }

            $this->view->page = $this->page;
        }

        $this->view->currentUrl = $_POST['currentUrl'];
    }

    /**
     * Dialog pour la configuration des images
     *
     * @return void
     */
    public function imageconfiguratorAction()
    {
        $this->view->unsetMain();
    }

    /**
     * Dialog pour la modification des zones HTML
     *
     * @return void
     */
    public function htmleditorAction()
    {
        $this->view->unsetMain();
    }
}
