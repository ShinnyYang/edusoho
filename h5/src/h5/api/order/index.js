export default [
  {
    // 我的订单
    name: 'getMyOrder',
    url: '/me/orders',
    method: 'GET',
    disableLoading: true,
  },
  {
    // 确认订单信息
    name: 'confirmOrder',
    url: '/order_infos',
    method: 'POST',
  },
  {
    // 创建订单信息
    name: 'createOrder',
    url: '/orders',
    method: 'POST',
  },
  {
    // 创建支付信息
    name: 'createTrade',
    url: '/trades',
    method: 'POST',
  },
  {
    // 获取订单信息
    name: 'getOrderDetail',
    url: '/orders/{sn}',
    method: 'GET',
  },
  {
    // 获取微信支付信息
    name: 'getTrade',
    url: '/trades/{tradesSn}',
    method: 'GET',
    disableLoading: true,
  },
  {
    // 获取购买协议配置
    name: 'getPurchaseAgreement',
    url: '/settings/course_purchase_agreement',
    method: 'GET'
  },
  // 营销商城订单支付
  {
    name: 'getMarketingMallPayConfig',
    url: '/unified_payment',
    method: 'POST'
  },
  {
    name: 'checkMarketingMallPayConfig',
    url: '/unified_payment'
  }
];
