const config = [
  {
    // 获取全局设置
    name: 'getSettings',
    url: '/settings/{type}',
    method: 'GET',
  },
  {
    // 后台配置获取
    name: 'getDraft',
    url: '/pages/{portal}/settings/{type}',
  },
  {
    // 后台配置获取
    name: 'getTemplate',
    url: '/pages/{portal}/template/{template}',
  },
  {
    // 获取班级课程分类
    name: 'getCategoryType',
    url: '/category/{type}',
  },
  {
    // 后台配置保存
    name: 'saveDraft',
    headers: {
      'Content-Type': 'application/json',
    },
    url: '/pages/{portal}/settings',
    method: 'POST',
  },
  {
    // 删除后台配置
    name: 'deleteDraft',
    url: '/pages/{portal}/settings/{type}',
    method: 'DELETE',
  },
  {
    // 获取网校、插件版本
    name: 'getMPVersion',
    url: '/settings/miniprogram',
  },
  {
    // 上传文件
    name: 'uploadFile',
    url: '/files',
    method: 'POST',
  },
  {
    // 获取分类配置
    name: 'getCategories',
    url: '/categories/{groupCode}',
  },
  {
    // 获取课程列表数据
    name: 'getCourseList',
    url: '/courses',
  },
  {
    // 获取班级列表数据
    name: 'getClassList',
    url: '/classrooms',
  },
  {
    // 获取公开课列表数据
    name: 'getOpenCourseList',
    url: '/open_course',
  },
  {
    // 获取题库列表数据
    name: 'getItemBankList',
    url: '/item_bank_exercises',
  },
  {
    // 获取优惠券功能开关
    name: 'getCouponSetting',
    url: '/setting/coupon',
    method: 'GET',
  },
  {
    // 获取优惠券
    name: 'getCouponList',
    url: '/coupon_batch',
  },
  {
    // 微营销活动列表数据
    name: 'getMarketingList',
    url: '/marketing_activities',
  },
  {
    // 获得二维码
    name: 'getQrcode',
    url: '/qrcode/{route}',
    // noPrefix: true,
  },
  {
    name: 'getVipLevels',
    url: '/plugins/vip/vip_levels',
  },
  {
    name: 'vipPlugin',
    url: '/site_plugins/Vip',
  },
  {
    // 微营销创建活动地址
    name: 'getCreateMarketingUrl',
    url: '/settings/backstage',
    method: 'GET',
  },
];

export default config;
