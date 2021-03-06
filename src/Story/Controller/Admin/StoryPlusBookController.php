<?php
namespace Story\Controller\Admin;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Story\Model\StoryPlusBook;
use Story\Model\StoryPlusBookIntro;
use Twig_SimpleFunction;

class StoryPlusBookController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $admin = $app['controllers_factory'];

        $admin->get('add', array($this, 'addStoryPlusBook'));
        $admin->get('list', array($this, 'storyPlusBookList'));
        $admin->get('{id}', array($this, 'storyPlusBookDetail'));
        $admin->post('{id}/delete', array($this, 'deleteStoryPlusBook'));
        $admin->post('{id}/edit', array($this, 'editStoryPlusBook'));

        return $admin;
    }

    public function addStoryPlusBook(Request $req, Application $app)
    {
        $b_id = StoryPlusBook::create();
        $app['session']->getFlashBag()->add('alert', array('success' => '책이 추가되었습니다.'));
        return $app->redirect('/admin/storyplusbook/' . $b_id);
    }

    public function storyPlusBookList(Request $req, Application $app)
    {
        $books = StoryPlusBook::getWholeList();
        return $app['twig']->render('admin/storyplusbook_list.twig', array('books' => $books));
    }

    public function storyPlusBookDetail(Request $req, Application $app, $id)
    {
        $book = StoryPlusBook::get($id);
        $intros = StoryPlusBookIntro::getListByBid($id);
        $badge_names = array('NONE', 'BESTSELLER', 'FAMOUSAUTHOR', 'HOTISSUE', 'NEW');

        $app['twig']->addFunction(
            new Twig_SimpleFunction('today', function () {
                return date('Y-m-d');
            })
        );

        return $app['twig']->render(
            'admin/storyplusbook_detail.twig',
            array(
                'book' => $book,
                'intros' => $intros,
                'badge_names' => $badge_names
            )
        );
    }

    public function deleteStoryPlusBook(Request $req, Application $app, $id)
    {
        $intros = StoryPlusBookIntro::getListByBid($id);
        if (count($intros)) {
            return $app->json(array('error' => '소개 섹션이 있으면 책을 삭제할 수 없습니다.'));
        }
        StoryPlusBook::delete($id);
        $app['session']->getFlashBag()->add('alert', array('info' => '책이 삭제되었습니다.'));
        return $app->json(array('success' => true));
    }

    public function editStoryPlusBook(Request $req, Application $app, $id)
    {
        $inputs = $req->request->all();

        StoryPlusBook::update($id, $inputs);

        $app['session']->getFlashBag()->add('alert', array('info' => '책이 수정되었습니다.'));
        return $app->redirect('/admin/storyplusbook/' . $id);
    }
}
