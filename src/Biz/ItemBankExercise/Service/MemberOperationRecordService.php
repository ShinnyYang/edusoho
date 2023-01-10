<?php

namespace Biz\ItemBankExercise\Service;

interface MemberOperationRecordService
{
    public function count($conditions);

    public function search($conditions, $orderBy, $start, $limit);

    public function create($record);

    public function updateRefundInfoByOrderId($orderId, $info);
}
