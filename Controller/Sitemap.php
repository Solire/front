<?php
/**
 * Controleur qui gère l'affichage du sitemap
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Front\Controller;

/**
 * Controleur qui gère l'affichage du sitemap
 *
 * @author  smonnot <smonnot@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Sitemap extends Main
{
    /**
     * Action qui va s'occuper de l'affichage du sitemap
     * 
     * @global array $pagesResult ??
     * 
     * @return void
     */
    public function startAction()
    {
        global $pagesResult;
        $this->view->unsetMain();

        $visible = true;
        if (isset($_GET['visible']) && $_GET['visible'] == 0) {
            $visible = false;
        }

        $format = 'xml';
        if (isset($_GET['json']) && $_GET['json'] == 1) {
            $format = 'json';
            $title = false;
        }

        $this->pages = array();

        $accueil = $this->gabaritManager->getPage(ID_VERSION, ID_API, 1);
        $this->pages[] = array(
            'title' => $accueil->getMeta('titre'),
            'visible' => $accueil->getMeta('visible'),
            'path' => '',
            'importance' => $accueil->getMeta('importance'),
            'lastmod' => substr($accueil->getMeta('date_modif'), 0, 10)
        );

        //Si ids = *, on recupere tous les gabarits de niveau 0
        if ($this->appConfig->get('sitemap', 'ids') == '*') {
            $query = ' SELECT *'
                   . ' FROM `gab_gabarit`'
                   . ' WHERE id <> 1 AND id <> 2'
                   . ' AND (id_parent = 0 OR id_parent = id)';
            $categoryIds = $this->db->query($query)->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $categoryIds = explode(',', $this->appConfig->get('sitemap', 'ids'));
        }

        //On recupere les gabarits
        $query = 'SELECT `gab_gabarit`.id, `gab_gabarit`.*'
               . ' FROM `gab_gabarit`'
               . ' WHERE id <> 1 AND id <> 2';
        $gabarits = $this->db->query($query)->fetchAll(\PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC);

        if (count($categoryIds) == 0) {
            exit();
        }

        $this->rubriques = $this->gabaritManager->getList(ID_VERSION, ID_API, 0, $categoryIds, $visible);

        //GABARIT NIVEAU 0
        foreach ($this->rubriques as $ii => $rubrique) {
            if ($gabarits[$rubrique->getMeta('id_gabarit')]['view']
                && $rubrique->getMeta('no_index') == 0
            ) {
                $path = $rubrique->getMeta('rewriting')
                      . $gabarits[$rubrique->getMeta('id_gabarit')]['extension'];
                $this->pages[] = array(
                    'title'      => $rubrique->getMeta('titre'),
                    'visible'    => $rubrique->getMeta('visible'),
                    'path'       => $path,
                    'importance' => $rubrique->getMeta('importance'),
                    'lastmod'    => substr($rubrique->getMeta('date_modif'), 0, 10)
                );
            }

            //Récupération des enfants
            $pages = $this->gabaritManager->getList(ID_VERSION, ID_API, $rubrique->getMeta('id'), false, $visible);
            $rubrique->setChildren($pages);

            //GABARIT NIVEAU 1
            foreach ($pages as $page) {
                if ($gabarits[$page->getMeta('id_gabarit')]['view']
                    && $page->getMeta('no_index') == 0
                ) {
                    $path = $rubrique->getMeta('rewriting') . '/'
                          . $page->getMeta('rewriting')
                          . $gabarits[$page->getMeta('id_gabarit')]['extension'];
                    $this->pages[] = array(
                        'title'      => $page->getMeta('titre'),
                        'visible'    => $page->getMeta('visible'),
                        'path'       => $path,
                        'importance' => $page->getMeta('importance'),
                        'lastmod'    => substr($page->getMeta('date_modif'), 0, 10)
                    );
                }

                //Récupération des enfants
                $sspages = $this->gabaritManager->getList(ID_VERSION, ID_API, $page->getMeta('id'), false, $visible);
                $page->setChildren($sspages);

                //GABARIT NIVEAU 2
                foreach ($sspages as $sspage) {
                    if ($gabarits[$sspage->getMeta('id_gabarit')]['view']
                        && $sspage->getMeta('no_index') == 0
                    ) {
                        $path = $rubrique->getMeta('rewriting') . '/'
                              . $page->getMeta('rewriting') . '/'
                              . $sspage->getMeta('rewriting')
                              . $gabarits[$sspage->getMeta('id_gabarit')]['extension'];
                        $this->pages[] = array(
                            'title'      => $sspage->getMeta('titre'),
                            'visible'    => $sspage->getMeta('visible'),
                            'path'       => $path,
                            'importance' => $sspage->getMeta('importance'),
                            'lastmod'    => substr($sspage->getMeta('date_modif'), 0, 10)
                        );
                    }
                }
            }
        }

        $this->view->pages = $this->pages;

        if ($format == 'xml') {
            header('Content-Type: application/xml');
        }

        if ($format == 'json') {
            $this->view->enable(false);

            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Content-type: application/json');

            $pages = $this->pages;

            if (isset($_GET['term']) && $_GET['term'] != '') {
                $term = $_GET['term'];
                $pagesResult = array();
                array_walk($pages, array($this, 'filter'), $term);
                $pages = $pagesResult;
            }

            foreach ($pages as $page) {
                if (isset($_GET['tinymce']) && $_GET['tinymce'] == 1) {
                    $page['title'] = ($page['visible'] ? '✓' : '✕') . ' ' . $page['title'];

                    $pagesClone[] = array(
                        'title' => $page['title'],
                        'value' => $page['path'],
                    );
                } elseif (isset($_GET['onlylink']) && $_GET['onlylink'] == 1) {
                    $page['title'] = ($page['visible'] ? '&#10003;' : '&#10005;') . ' ' . $page['title'];

                    $pagesClone[] = array(
                        $page['title'],
                        $page['path'],
                    );
                } else {
                    $pagesClone[] = $page;
                }
            }
            $pages = $pagesClone;

            echo json_encode($pages);
        }
    }

    /**
     * Permet de filter la liste des pages selon le titre
     *
     * @param array  $page         Tableau des infos de pages
     * @param type   $index        ??
     * @param string $searchString chaine recherchée
     * 
     * @global array $pagesResult
     * 
     * @return void
     */
    public function filter($page, $index, $searchString)
    {
        global $pagesResult;
        if (stripos($page['title'], $searchString) !== false) {
            $pagesResult[] = $page;
        }
    }
}
