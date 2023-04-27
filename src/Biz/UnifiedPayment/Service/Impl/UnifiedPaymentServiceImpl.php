<?php

namespace Biz\UnifiedPayment\Service\Impl;

use AppBundle\Common\ArrayToolkit;
use Biz\BaseService;
use Biz\System\Service\SettingService;
use Biz\UnifiedPayment\Dao\TradeDao;
use Biz\UnifiedPayment\Service\UnifiedPaymentService;
use Codeages\Biz\Framework\Service\Exception\InvalidArgumentException;
use Codeages\Biz\Framework\Targetlog\Service\TargetlogService;
use Codeages\Biz\Pay\Payment\AbstractGateway;
use Codeages\Biz\Pay\Status\PayingStatus;
use Exception;

class UnifiedPaymentServiceImpl extends BaseService implements UnifiedPaymentService
{
    public function isEnabledPlatform($platform)
    {
        $platformSetting = $this->getSettingService()->get('platform', []);
        if ('wechat' == $platform) {
            if (!empty($platformSetting['wxpay_enabled'])) {
                return true;
            }
        }

        return false;
    }

    protected function checkPlatform($platform)
    {
        if (!$this->isEnabledPlatform($platform)) {
            throw new \InvalidArgumentException('支付方式未配置，请联系机构处理。');
        }
    }

    public function getTradeByTradeSn(string $sn)
    {
        return $this->getTradeDao()->getByTradeSn($sn);
    }

    public function createTrade($fields, $createPlatformTrade = true)
    {
        $tradeFields = ['title', 'orderSn', 'amount', 'platform', 'platformType', 'userId', 'source', 'redirectUrl'];
        $platformFields = ['description', 'notifyUrl', 'openId', 'createIp'];
        if (!ArrayToolkit::requireds($fields, array_merge($tradeFields, $platformFields))) {
            throw new InvalidArgumentException('trade args is invalid.');
        }

        if ($fields['amount'] > 0 && $createPlatformTrade) {
            $this->checkPlatform($fields['platform']);
        }

        $trade = [
            'title' => $fields['title'],
            'tradeSn' => $this->generateSn(),
            'orderSn' => $fields['orderSn'],
            'amount' => $fields['amount'],
            'platform' => $fields['platform'],
            'platformType' => $fields['platformType'],
            'userId' => $fields['userId'],
            'source' => $fields['source'],
            'redirectUrl' => $fields['redirectUrl'],
            'sellerId' => $fields['sellerId'] ?? '',
            'status' => 'paying',
        ];

        $trade = $this->getTradeDao()->create($trade);
        if ($fields['amount'] > 0 && $createPlatformTrade) {
            $trade = $this->createPlatformTrade($fields, $trade);
        }

        $this->getTargetlogService()->log(TargetlogService::INFO, 'up_trade.create', $trade['tradeSn'], '创建订单', ['trade' => $trade, 'fields' => $fields]);

        return $trade;
    }

    protected function createPlatformTrade($data, $trade)
    {
        $params = [
            'goods_detail' => $data['description'],
            'attach' => $data['attach'] ?? [],
            'goods_title' => $data['title'],
            'notify_url' => $data['notifyUrl'],
            'open_id' => $data['openId'],
            'trade_sn' => $trade['tradeSn'],
            'amount' => $trade['amount'],
            'platform_type' => $trade['platformType'],
            'platform' => $trade['platform'],
            'create_ip' => $data['createIp'],
        ];

        $result = $this->getPayment($params['platform'])->createTrade($params);

        return $this->getTradeDao()->update($trade['id'], [
            'platformCreatedParams' => $params,
            'platformCreatedResult' => $result,
        ]);
    }

    public function createPlatformTradeByTradeSn($tradeSn)
    {
        $trade = $this->getTradeDao()->getByTradeSn($tradeSn);
        $this->checkPlatform($trade['platform']);

        $result = $this->getPayment($trade['platform'])->createTrade($trade['platformCreatedParams']);

        $this->getTradeDao()->update($trade['id'], [
            'platformCreatedResult' => $result,
        ]);

        return $result;
    }

    public function notifyPaid($payment, $data)
    {
        list($data, $result) = $this->getPayment($payment)->converterNotify($data);
        $this->getTargetlogService()->log(TargetlogService::INFO, 'up_trade.paid_notify', $data['trade_sn'], "收到第三方支付平台{$payment}的通知，交易号{$data['trade_sn']}，支付状态{$data['status']}", (array) $data);

        $trade = $this->updateTradeToPaidAndTransferAmount($data);

        $this->dispatch('unified_payment.trade.receive_notified', $trade, $data);

        return $result;
    }

    protected function updateTradeToPaidAndTransferAmount($data)
    {
        $tradeSn = $data['trade_sn'];
        if ('paid' !== $data['status']) {
            return $this->getTradeDao()->getByTradeSn($tradeSn);
        }

        $trade = $this->getTradeDao()->getByTradeSn($tradeSn);
        if (empty($trade)) {
            $this->getTargetlogService()->log(TargetlogService::WARNING, 'up_trade.not_found', $tradeSn, "交易号{$tradeSn}不存在", $data);

            return $trade;
        }

        try {
            $trade = $this->getTradeDao()->get($trade['id'], ['lock' => true]);

            if (PayingStatus::NAME != $trade['status']) {
                $this->getTargetlogService()->log(TargetlogService::INFO, 'up_trade.is_not_paying', $tradeSn, "交易号{$tradeSn}状态不正确，状态为：{$trade['status']}");

                return $trade;
            }
            if ($trade['amount'] != $data['pay_amount']) {
                $this->getTargetlogService()->log(TargetlogService::WARNING, 'up_trade.pay_amount.mismatch', $tradeSn, '实际支付的价格与交易记录价格不匹配', ['trade' => $trade, 'data' => $data]);
            }

            $trade = $this->updateTradeToPaid($trade['id'], $data);

            $this->getTargetlogService()->log(TargetlogService::INFO, 'up_trade.paid', $tradeSn, "交易号{$tradeSn}，账目流水处理成功", $data);
        } catch (Exception $e) {
            $this->getTargetlogService()->log(TargetlogService::ERROR, 'up_trade.error', $tradeSn, "交易号{$tradeSn}处理失败, {$e->getMessage()}", $data);
            throw $e;
        }

        $this->dispatch('unified_payment.trade.paid', $trade, $data);

        return $trade;
    }

    protected function updateTradeToPaid($tradeId, $data)
    {
        $updatedFields = [
            'status' => $data['status'],
            'payTime' => $data['paid_time'],
            'platformSn' => $data['cash_flow'],
            'notifyData' => $data,
            'currency' => $data['cash_type'],
        ];

        return $this->getTradeDao()->update($tradeId, $updatedFields);
    }

    protected function generateSn($prefix = ''): string
    {
        return $prefix.date('YmdHis', time()).mt_rand(10000, 99999);
    }

    /**
     * @param $payment
     *
     * @return AbstractGateway
     */
    protected function getPayment($payment)
    {
        return $this->biz['payment.'.$payment];
    }

    /**
     * @return TradeDao
     */
    protected function getTradeDao()
    {
        return $this->biz->dao('UnifiedPayment:TradeDao');
    }

    /**
     * @return TargetlogService
     */
    protected function getTargetlogService()
    {
        return $this->biz->service('Targetlog:TargetlogService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->biz->service('System:SettingService');
    }
}
