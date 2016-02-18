<?php

namespace Solire\Front\Controller;

use Solire\Lib\Path;

/**
 * Controleur qui gère l'affichage des pages gabaritPage.
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Page extends Main
{
    /**
     * Le gabaritpage de la page courante.
     *
     * @var \Solire\Lib\Model\gabaritPage
     */
    public $page = null;

    /**
     * Les gabaritpages parents de la page courante.
     *
     * @var \Solire\Lib\Model\gabaritPage[]
     */
    public $parents = null;

    /**
     * Accepte les rewritings.
     *
     * @var bool
     */
    public $acceptRew = true;

    /**
     * Action qui affiche la page gabpage courante.
     *
     * @return void
     */
    public function startAction()
    {
        /*
         * En cas de prévisualisation.
         */
        if ($this->utilisateurAdmin->isConnected()
            && isset($_POST['id_gabarit'])
        ) {
            $this->previsu();
        } else {
            $this->display();
            $this->page->setConnected($this->utilisateurAdmin->isConnected());
        }

        if (!$this->page->getGabarit()->getView()) {
            $this->pageNotFound();
        }

        if (isset($this->parents[1])) {
            $firstChild = $this->gabaritManager->getFirstChild(
                ID_VERSION,
                $this->parents[1]->getMeta('id')
            );
            $this->parents[1]->setFirstChild($firstChild);
        }

        /*
         * Balise META
         */
        $title = '';
        if ($this->page->getMeta('bal_title')) {
            $title = $this->page->getMeta('bal_title');
        } else {
            $title = $this->page->getMeta('titre');
        }
        $this->seo->setTitle($title);
        $this->seo->setDescription($this->page->getMeta('bal_descr'));
        $this->seo->addKeyword($this->page->getMeta('bal_key'));
        $this->seo->setUrlCanonical($this->page->getMeta('canonical'));
        if ($this->page->getMeta('author') > 0) {
            $authors = $this->view->mainPage['element_commun']->getBlocs('author_google')->getValues();
            foreach ($authors as $author) {
                if ($author['id'] == $this->page->getMeta('author')) {
                    $this->seo->setAuthor($author['compte_google']);
                    $this->seo->setAuthorName($author['nom_de_lauteur']);
                    break;
                }
            }
        }

        if ($this->page->getMeta('no_index')) {
            $this->seo->disableIndex();
        }

        $this->view->page = $this->page;
        $this->view->parents = $this->parents;

        $view = $this->page->getGabarit()->getName();
        $this->view->setViewPath('page' . Path::DS . $view);

        $hook = new \Solire\Lib\Hook();
        $hook->setSubdirName('Front');
        $hook->controller = $this;
        $hook->exec($view . 'Gabarit');
    }

    /**
     * Traitement propre à l'affichage de la page en mode prévisualisation.
     *
     * @return void
     */
    protected function previsu()
    {
        $this->page = $this->gabaritManager->previsu($_POST);

        $this->parents = array_reverse($this->page->getParents());
        foreach ($this->parents as $ii => $parent) {
            $this->parents[$ii] = $this->gabaritManager->getPage(
                $_POST['id_version'],
                $_POST['id_api'],
                $parent->getMeta('id'),
                0,
                false,
                false
            );

            $this->fullRewriting[] = $parent->getMeta('rewriting') . '/';

            $breadCrumbs = [
                'title' => $parent->getMeta('titre'),
            ];
            if ($parent->getGabarit()->getView()) {
                $breadCrumbs['url'] = implode('/', $this->fullRewriting) . '/';
            }
            $this->view->breadCrumbs[] = $breadCrumbs;
        }
    }

    /**
     * Traitement propre à l'affichage de la page en mode normal.
     *
     * @return void
     */
    protected function display()
    {
        $homepage = false;
        if (empty($this->rew)) {
            $homepage = true;
            $this->rew[] = 'accueil';
        }
        $this->parents = [];
        $this->fullRewriting = [];

        $id_parent = 0;

        foreach ($this->rew as $ii => $rewriting) {
            if (!$rewriting) {
                $this->pageNotFound();
            }

            $last = ($ii == count($this->rew) - 1);

            /*
             * Dans le cas de la homepage, on part du principe que sont id est
             * toujours 1
             */
            if ($homepage) {
                $id_gab_page = 1;
            } else {
                $id_gab_page = $this->gabaritManager->getIdByRewriting(
                    ID_VERSION,
                    \Solire\Lib\FrontController::$idApiRew,
                    $rewriting,
                    $id_parent
                );
            }

            if (!$id_gab_page) {
                $this->pageNotFound();
            }

            $page = $this->gabaritManager->getPage(
                ID_VERSION,
                \Solire\Lib\FrontController::$idApiRew,
                $id_gab_page,
                0,
                $last,
                true
            );

            if (!$page) {
                $this->pageNotFound();
            }

            $this->fullRewriting[] = $rewriting;

            if ($page->getGabarit()->getView()) {
                $url = implode('/', $this->fullRewriting)
                    . $page->getGabarit()->getExtension();
            } else {
                $url = '';
            }

            $this->view->breadCrumbs[] = [
                'title' => $page->getMeta('titre'),
                'url' => $url,
                'view' => $page->getGabarit()->getView(),
            ];

            if ($last) {
                $this->page = $page;
            } else {
                $this->parents[] = $page;
            }

            $id_parent = $id_gab_page;
        }
    }
}
