export default [
  {
    // 领取优惠券
    name: 'receiveCoupon',
    url: '/me/coupons',
    method: 'POST',
    disableLoading: true
  }, {
    // 根据渠道查询优惠券
    name: 'searchCoupon',
    url: '/plugins/coupon/channel/h5Mps/coupon_batches?limit=100',
    disableLoading: true
  }
];
