<?php
namespace Story\Controller\Admin;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Story\Model\Buyer;
use Story\Model\RidibooksMigration;
use Symfony\Component\HttpFoundation\Request;

class RidibooksMigrationController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $admin = $app['controllers_factory'];

        $admin->get('all', array($this, 'ridibooksMigrationHistoryAll'));
        $admin->post('add', array($this, 'addRidibooksMigrationHistoryList'));
        $admin->get('request_list', array($this, 'ridibooksMigrationRequestList'));
        $admin->get('list', array($this, 'ridibooksMigrationHistoryList'));
        $admin->post('{u_id}/delete', array($this, 'deleteRidibooksMigrationHistory'));

        return $admin;
    }

    public function ridibooksMigrationHistoryAll(Request $req, Application $app)
    {
        $migration_uids = RidibooksMigration::getWholeUidList();
        return $app['twig']->render('/admin/buyer/buyer_ridibooks_migration_history_all.twig', array('migrations' => $migration_uids));
    }

    public function addRidibooksMigrationHistoryList(Request $req, Application $app)
    {
        $user_list = $req->get('user_list', null);

        $u_ids = explode(PHP_EOL, $user_list);
        foreach ($u_ids as $key => &$u_id) {
            $trimmed_uid = trim($u_id);
            if ($trimmed_uid) {
                $u_id = trim($u_id);
            } else {
                unset($u_ids[$key]);
            }
        }
        unset($u_id);

        if (!empty($u_ids)) {
            // 입력한 회원 중에, 유효하지 않은 회원이 있는지를 검사
            $invalid_u_ids = Buyer::verifyUids($u_ids);
            if (!empty($invalid_u_ids)) {
                $app['session']->getFlashBag()->add('alert', array('error' => '회원 계정 정보가 정확하지 않습니다. (유저 ID: ' . implode(' / ', $invalid_u_ids) . ')'));
                return $app->redirect('/admin/ridibooks_migration/list');
            }

            $app['db']->beginTransaction();
            try {
                foreach ($u_ids as $u_id) {
                    // 전환용 리디북스 계정이 등록되어있는지 검사
                    $buyer = Buyer::getByUid($u_id);
                    if (empty($buyer['ridibooks_id'])) {
                        throw new \Exception('전환용 리디북스 계정이 등록되어 있지 않습니다. (유저 ID: ' . $u_id . ')');
                    }

                    // 이미 마이그레이션된 유저 제외
                    if (RidibooksMigration::isMigrated($u_id)) {
                        throw new \Exception('이미 리디북스로 계정이 이전되었습니다. (유저 ID: ' . $u_id . ')');
                    }

                    $r = RidibooksMigration::add($u_id, $buyer['ridibooks_id']);
                    if (!$r) {
                        throw new \Exception('리디북스 계정이전 정보를 추가하지 못했습니다. (유저 ID: ' . $u_id . ')');
                    }
                }

                $app['db']->commit();
                $app['session']->getFlashBag()->add('alert', array('success' => '리디북스 계정이전 정보가 추가되었습니다. (총: ' . count($u_ids) . '명)'));
            } catch (\Exception $e) {
                $app['db']->rollback();
                $app['session']->getFlashBag()->add('alert', array('error' => $e->getMessage()));
            }
        }
        return $app->redirect('/admin/ridibooks_migration/list');
    }

    public function ridibooksMigrationRequestList(Request $req, Application $app)
    {
        $end_date = $temp_end_date = $req->get('end_date');
        if (!$temp_end_date) {
            $temp_end_date = date('Y-m-d');
        }
        $temp_end_date = date('Y-m-d', strtotime($temp_end_date . ' + 1 day'));

        $requests = array();
        if ($end_date) {
            $requests = RidibooksMigration::getMigrateRequestList($temp_end_date, false);
        }
        $request_count = count($requests);

        return $app['twig']->render(
            '/admin/buyer/buyer_ridibooks_migration_request_list.twig',
            array(
                'end_date' => $end_date,
                'request_count' => $request_count,
                'requests' => $requests
            )
        );
    }

    public function ridibooksMigrationHistoryList(Request $req, Application $app)
    {
        $search_type = $req->get('search_type', 'uid');
        $search_keyword = $req->get('search_keyword', null);
        $cur_page = $req->get('page', 0);

        $size = 50;
        $offset = $cur_page * $size;

        if ($search_keyword) {
            $migrations = RidibooksMigration::getListBySearchTypeAndKeyword($search_type, $search_keyword);
        } else {
            $migrations = RidibooksMigration::getListByOffsetAndSize($offset, $size);
        }

        $migrated_count = RidibooksMigration::getMigratedCount();

        return $app['twig']->render(
            'admin/buyer/buyer_ridibooks_migration_history_list.twig',
            array(
                'search_type' => $search_type,
                'search_keyword' => $search_keyword,
                'migrated_count' => $migrated_count,
                'migrations' => $migrations,
                'cur_page' => $cur_page
            )
        );
    }

    public function deleteRidibooksMigrationHistory(Request $req, Application $app, $u_id)
    {
        $r = RidibooksMigration::delete($u_id);
        if ($r) {
            $app['session']->getFlashBag()->add('alert', array('info' => '리디북스 계정이전 정보가 삭제되었습니다.'));
        } else {
            $app['session']->getFlashBag()->add('alert', array('error' => '리디북스 계정이전 정보를 삭제하지 못했습니다.'));
        }
        return $app->redirect('/admin/ridibooks_migration/list');
    }
}
