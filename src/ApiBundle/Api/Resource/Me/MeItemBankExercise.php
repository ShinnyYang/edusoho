<?php

namespace ApiBundle\Api\Resource\Me;

use ApiBundle\Api\ApiRequest;
use ApiBundle\Api\Resource\AbstractResource;
use AppBundle\Common\ArrayToolkit;

class MeItemBankExercise extends AbstractResource
{
    public function search(ApiRequest $request)
    {
        $user = $this->getCurrentUser();
        $conditions = ['role' => 'student', 'userId' => $user['id']];
        $total = $this->getItemBankExerciseMemberService()->count($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);

        $members = $this->getItemBankExerciseMemberService()->search(
            $conditions,
            ['updatedTime' => 'DESC'],
            $offset,
            $limit
        );

        $itemBankExercises = $this->getItemBankExerciseService()->findByIds(ArrayToolkit::column($members, 'exerciseId'));
        foreach ($members as $key => &$member) {
            if (empty($itemBankExercises[$member['exerciseId']])) {
                unset($members[$key]);
            } else {
                $member['itemBankExercise'] = $itemBankExercises[$member['exerciseId']];
                $member['isExpired'] = $this->isExpired($members['deadline']);
            }
        }

        return $this->makePagingObject(array_values($members), $total, $offset, $limit);
    }

    private function isExpired($deadline)
    {
        return 0 != $deadline && $deadline < time();
    }

    protected function getUserService()
    {
        return $this->service('User:UserService');
    }

    /**
     * @return \Biz\ItemBankExercise\Service\ExerciseService
     */
    protected function getItemBankExerciseService()
    {
        return $this->service('ItemBankExercise:ExerciseService');
    }

    /**
     * @return \Biz\ItemBankExercise\Service\ExerciseMemberService
     */
    protected function getItemBankExerciseMemberService()
    {
        return $this->service('ItemBankExercise:ExerciseMemberService');
    }
}
