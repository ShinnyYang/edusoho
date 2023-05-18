<?php

namespace AppBundle\Controller\AdminV2\System;

use AppBundle\Common\ArrayToolkit;
use AppBundle\Common\Paginator;
use AppBundle\Controller\AdminV2\BaseController;
use Biz\Sensitive\Service\SensitiveService;
use Symfony\Component\HttpFoundation\Request;

class SensitiveController extends BaseController
{
    public function indexAction(Request $request)
    {
        $fields = $request->query->all();
        unset($fields['page']);
        $conditions = [
            'keyword' => '',
            'searchKeyWord' => '',
            'state' => '',
        ];

        if (empty($fields)) {
            $fields = [];
        }

        $conditions = array_merge($conditions, $fields);
        $conditions = array_filter($conditions);
        $paginator = new Paginator($this->get('request'), $this->getSensitiveService()->searchkeywordsCount($conditions), 20);
        $keywords = $this->getSensitiveService()->searchKeywords($conditions, ['id' => 'DESC'], $paginator->getOffsetCount(), $paginator->getPerPageCount());
        foreach ($keywords as &$keyword) {
            $keyword['name'] = stripslashes($keyword['name']);
        }

        return $this->render('admin-v2/system/user-content-control/sensitive/index.html.twig', [
            'keywords' => $keywords,
            'paginator' => $paginator,
        ]);
    }

    public function createAction(Request $request)
    {
        if ('POST' == $request->getMethod()) {
            $name = $request->request->get('name');
            $keywords = preg_split('/\r/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
            $state = $request->request->get('state');
            $existedKeyWords = [];
            foreach ($keywords as $value) {
                $value = trim($value);

                if (!empty($value)) {
                    $keyword = $this->getSensitiveService()->getKeywordByName($value);

                    if (empty($keyword)) {
                        $this->getSensitiveService()->addKeyword($value, $state);
                        continue;
                    }
                    $existedKeyWords[] = $value;
                }
            }
            if (!empty($existedKeyWords)) {
                $this->setFlashMessage(
                    'warning',
                    $this->get('translator')->trans(
                        'admin.sensitive_manage.existed',
                        ['%keywords%' => implode(',', array_unique($existedKeyWords))]
                    )
                );
            }

            return $this->redirect($this->generateUrl('admin_v2_keyword'));
        }

        return $this->render('admin-v2/system/user-content-control/sensitive/keyword-add.html.twig');
    }

    public function deleteAction(Request $request, $id)
    {
        $this->getSensitiveService()->deleteKeyword($id);

        return $this->redirect($this->generateUrl('admin_v2_keyword'));
    }

    public function changeAction(Request $request, $id)
    {
        $state = $request->query->get('state');

        if ('banned' == $state) {
            $conditions['state'] = 'replaced';
        } else {
            $conditions['state'] = 'banned';
        }

        $this->getSensitiveService()->updateKeyword($id, $conditions);

        return $this->redirect($this->generateUrl('admin_v2_keyword'));
    }

    public function banlogsAction(Request $request)
    {
        $fields = $request->query->all();
        unset($fields['page']);
        $conditions = [
            'keyword' => '',
            'searchBanlog' => '',
            'state' => '',
        ];

        if (empty($fields)) {
            $fields = [];
        }

        $conditions = array_merge($conditions, $fields);
        $conditions = array_filter($conditions);

        if (empty($banlogs)) {
            $banlogs = [];
        }

        if ('userName' == $conditions['searchBanlog']) {
            $userName = $conditions['keyword'];
            $userTemp = $this->getUserService()->searchUsers(
                ['nickname' => $userName],
                ['createdTime' => 'DESC'],
                0,
                1000
            );
            $userIds = ArrayToolkit::column($userTemp, 'id');

            if (!empty($userTemp)) {
                $conditions['userId'] = $userIds;
            } else {
                if (!empty($conditions['keyword'])) {
                    $conditions['userId'] = 0;
                }
            }
            if (empty($count)) {
                $count = 0;
            }
            foreach ($userIds as $value) {
                $conditions['userId'] = $value;
                $countTemp = $this->getSensitiveService()->searchBanlogsCount($conditions);
                $count += $countTemp;
            }
            $paginator = new Paginator($this->get('request'), $count, 20);
            $banlogs = $this->getSensitiveService()->searchBanlogsByUserIds($userIds, [
                'id' => 'DESC',
            ], $paginator->getOffsetCount(), $paginator->getPerPageCount());
        } else {
            $count = $this->getSensitiveService()->searchBanlogsCount($conditions);
            $paginator = new Paginator($this->get('request'), $count, 20);

            $banlogs = $this->getSensitiveService()->searchBanlogs($conditions, [
                'id' => 'DESC',
            ], $paginator->getOffsetCount(), $paginator->getPerPageCount());
        }

        foreach ($banlogs as &$value) {
            $value['text'] = $this->replaceSensitive($value['keywordName'], $value['text']);
            $value['text'] = preg_replace("/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i", '', $value['text']);
        }

        $users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($banlogs, 'userId'));

        return $this->render('admin-v2/system/user-content-control/sensitive/banlogs.html.twig', [
            'banlogs' => $banlogs,
            'users' => $users,
            'paginator' => $paginator,
        ]);
    }

    protected function replaceSensitive(String $keyword, String $text){
        $index = stripos($text, $keyword);
        while ($index !== false){
            $replacement = substr($text, $index, strlen($keyword));
            $text = substr_replace($text, "<span style='color:#FF0000'>".$replacement.'</span>', $index, strlen($replacement));
            $index = $index + strlen("<span style='color:#FF0000'>".$replacement.'</span>');
            $index = stripos($text, $keyword, $index);
        }
        return $text;
    }

    /**
     * @return SensitiveService
     */
    protected function getSensitiveService()
    {
        return $this->createService('Sensitive:SensitiveService');
    }
}
