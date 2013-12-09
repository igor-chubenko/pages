<?php

namespace src;

use src\pageModel;

class pageController
{
    private $twig;
    private $model;

    public function __construct(pageModel $model)
    {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/src/Twig/lib/Twig/Autoloader.php';
        \Twig_Autoloader::register();
        $loader = new \Twig_Loader_Filesystem($_SERVER['DOCUMENT_ROOT'] . '/src/view');
        $this->twig = new \Twig_Environment($loader, array(
            'cache' => 'compilation_cache',
            'auto_reload' => false//true for debug mode
        ));
        $this->model = $model;
    }

    public function showAction()
    {
        $messages = '';
        session_start();
        if (isset($_SESSION['messages'])) {
            $messages = $_SESSION['messages'];
            unset($_SESSION['messages']);
        }
        session_write_close();

        $pageID = -1;
        if (isset($_REQUEST['pageId'])) {
            $pageID = htmlentities($_REQUEST['pageId'], ENT_QUOTES, 'UTF-8');
        }

        $pageData = array('pages' => $this->model->getPagesList($pageID));
        $template = ($pageID == -1 ? 'pagesList.html.twig' : 'page.html.twig');
        $parameters = array(
            'pageData' => $pageData,
            'messages' => $messages
        );
        if (isset($_REQUEST['index'])) {
            $parameters['pageIndex'] = htmlentities($_REQUEST['index'], ENT_QUOTES, 'UTF-8');
        }
        echo $this->twig->render(
            $template,
            $parameters
        );
    }

    public function addAction()
    {
        $form = array();

        if (!isset($_REQUEST['pid'])) {
            $form['errors']['form'] = 'Internal error';
        } else {
            $form = array(
                'pid' => htmlentities($_REQUEST['pid'], ENT_QUOTES, 'UTF-8'),
                'title' => '',
                'body' => '',
                'changeParent' => false,
                'errors' => array(),
            );

            if (!empty($_POST)) {
                if (isset($_POST['title'])) {
                    $form['title'] = htmlentities($_POST['title'], ENT_QUOTES, 'UTF-8');
                    if ($form['title'] == '') {
                        $form['errors']['title'] = 'Incorrect value';
                    }
                }
                if (isset($_POST['body'])) {
                    $form['body'] = htmlentities($_POST['body'], ENT_QUOTES, 'UTF-8');
                    if ($form['body'] == '') {
                        $form['errors']['body'] = 'Incorrect value';
                    }
                }

                if (empty($form['errors'])) {
                    if ($this->model->addPage($form)) {
                        header('Location: /');
                    }
                }
            }
        }

        echo $this->twig->render(
            'formPage.html.twig',
            array(
                'form' => $form
            )
        );
    }

    public function editAction()
    {
        $form = array();

        if (!isset($_REQUEST['id'])) {
            $form['errors']['form'] = 'Internal error';
        } else {
            $pageId = htmlentities($_REQUEST['id'], ENT_QUOTES, 'UTF-8');
            $currentPage = $this->model->getCurrentPage($pageId);
            if(empty($currentPage)){
                $form['errors']['form'] = 'Internal error';
            }else{
                $parentsList = $this->model->getParentsList($currentPage['pid']);
                if(empty($parentsList)){
                    $form['errors']['form'] = 'Internal error';
                }else{
                    $form = array(
                        'id' => $pageId,
                        'pid' => $currentPage['pid'],
                        'old_pid' => $currentPage['pid'],
                        'title' => $currentPage['title'],
                        'body' => $currentPage['body'],
                        'changeParent' => true,
                        'parents' => $parentsList,
                        'errors' => array(),
                    );

                    if (!empty($_POST)) {
                        if (isset($_POST['title'])) {
                            $form['title'] = htmlentities($_POST['title'], ENT_QUOTES, 'UTF-8');
                            if ($form['title'] == '') {
                                $form['errors']['title'] = 'Incorrect value';
                            }
                        }
                        if (isset($_POST['body'])) {
                            $form['body'] = htmlentities($_POST['body'], ENT_QUOTES, 'UTF-8');
                            if ($form['body'] == '') {
                                $form['errors']['body'] = 'Incorrect value';
                            }
                        }
                        if (isset($_POST['pid'])) {
                            $form['pid'] = htmlentities($_POST['pid'], ENT_QUOTES, 'UTF-8');
                            if ($form['pid'] == '') {
                                $form['errors']['pid'] = 'Incorrect value';
                            }
                        }

                        if (empty($form['errors'])) {
                            if ($this->model->savePage($form)) {
                                header('Location: /');
                            }
                        }
                    }
                }
            }
        }

        echo $this->twig->render(
            'formPage.html.twig',
            array(
                'form' => $form
            )
        );
    }

    public function deleteAction()
    {
        $messages = array();
        if (!isset($_REQUEST['id'])) {
            $messages['error'] = 'Internal error';
        } else {
            $messages['success'] = $this->model->deletePage(htmlentities($_REQUEST['id'], ENT_QUOTES, 'UTF-8'));
        }

        session_start();
        $_SESSION['messages'] = $messages;
        session_write_close();

        header('Location: /');
    }
} 