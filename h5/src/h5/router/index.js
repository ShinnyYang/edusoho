import Vue from 'vue';
import { Toast } from 'vant';
import store from '@/store';
import * as types from '@/store/mutation-types';
import Router from 'vue-router';
import find from './find';
import learning from './learning';
import my from './my';
/* eslint-disable no-new */
Vue.use(Router);
// 路由懒加载 实现代码分离
const routes = [
  {
    path: '/',
    meta: {
      title: '',
    },
    component: () =>
      import(/* webpackChunkName: "home" */ '@/containers/home.vue'),
    children: [
      ...find,
      ...learning,
      ...my,
      {
        path: '/prelogin',
        name: 'prelogin',
        meta: {
          title: '我的',
        },
        component: () =>
          import(
            /* webpackChunkName: "loginPrelogin" */ '@/containers/login/prelogin.vue'
          ),
      },
    ],
  },
  {
    path: '/login',
    name: 'login',
    meta: {
      title: '',
    },
    component: () =>
      import(/* webpackChunkName: "login" */ '@/containers/login/index.vue'),
  },
  {
    path: '/fastlogin',
    name: 'fastlogin',
    meta: {
      title: '登录',
    },
    component: () =>
      import(
        /* webpackChunkName: "fastlogin" */ '@/containers/login/fastlogin.vue'
      ),
  },
  {
    path: '/login/qrcode',
    name: 'login_qrcode',
    meta: {
      title: '',
    },
    component: () =>
      import(
        /* webpackChunkName: "login" */ '@/containers/login/face/index.vue'
      ),
  },
  {
    path: '/register',
    name: 'register',
    meta: {
      title: '',
    },
    component: () =>
      import(
        /* webpackChunkName: "register" */ '@/containers/register/index.vue'
      ),
  },
  {
    path: '/binding',
    name: 'binding',
    meta: {
      title: '',
    },
    component: () =>
      import(
        /* webpackChunkName: "binding" */ '@/containers/register/index.vue'
      ),
  },
  {
    path: '/protocol',
    name: 'protocol',
    meta: {
      title: '',
    },
    component: () =>
      import(
        /* webpackChunkName: "protocol" */ '@/containers/register/protocol/index.vue'
      ),
  },
  {
    path: '/settings',
    name: 'my_setting',
    meta: {
      title: '设置',
    },
    component: () =>
      import(
        /* webpackChunkName: "setting" */ '@/containers/my/setting/index.vue'
      ),
  },
  {
    path: '/setting/nickname',
    name: 'setting_nickname',
    meta: {
      title: '昵称设置',
    },
    component: () =>
      import(
        /* webpackChunkName: "nickname" */ '@/containers/my/setting/nickname.vue'
      ),
  },
  {
    path: '/course/explore',
    name: 'more_course',
    meta: {
      title: '所有课程',
    },
    component: () =>
      import(
        /* webpackChunkName: "more" */ '@/containers/more/course/index.vue'
      ),
  },
  {
    path: '/classroom/explore',
    name: 'more_class',
    meta: {
      title: '所有班级',
    },
    component: () =>
      import(
        /* webpackChunkName: "more" */ '@/containers/more/classroom/index.vue'
      ),
  },
  {
    path: '/itembank/explore',
    name: 'more_itembank',
    meta: {
      title: '所有题库',
    },
    component: () =>
      import(
        /* webpackChunkName: "more" */ '@/containers/more/itembank/index.vue'
      ),
  },
  {
    path: '/all/explore',
    name: 'all_explore',
    redirect: {
      name: 'more_course',
    },
  },
  {
    path: '/course/explore/vip',
    name: 'vip_course',
    meta: {
      title: '会员课程',
    },
    component: () =>
      import(
        /* webpackChunkName: "more" */ '@/containers/vip/more/course-list.vue'
      ),
  },
  {
    path: '/classroom/explore/vip',
    name: 'vip_classroom',
    meta: {
      title: '会员班级',
    },
    component: () =>
      import(
        /* webpackChunkName: "more" */ '@/containers/vip/more/classroom-list.vue'
      ),
  },
  {
    path: '/course/:id',
    name: 'course',
    meta: {
      title: '课程详情',
    },
    component: () =>
      import(/* webpackChunkName: "course" */ '@/containers/course/index.vue'),
  },
  {
    path: '/course/try',
    name: 'course_try',
    component: () =>
      import(/* webpackChunkName: "courseTry" */ '@/containers/course/try.vue'),
  },
  {
    path: '/course/web',
    name: 'course_web',
    component: () =>
      import(
        /* webpackChunkName: "webView" */ '@/containers/course/detail/web-view.vue'
      ),
  },
  {
    path: '/course/audioview',
    name: 'course_audioview',
    component: () =>
      import(
        /* webpackChunkName: "audioDoc" */ '@/containers/course/detail/audio-doc.vue'
      ),
  },
  {
    path: '/live',
    name: 'live',
    component: () =>
      import(
        /* webpackChunkName: "live" */ '@/containers/course/detail/live-view.vue'
      ),
  },
  {
    path: '/testpaper',
    name: 'testpaperIntro',
    meta: {
      title: '考试说明',
    },
    component: () =>
      import(
        /* webpackChunkName: "testpaperIntro" */ '@/containers/course/lessonTask/exam/testpaperIntro.vue'
      ),
  },
  {
    path: '/testpaperDo',
    name: 'testpaperDo',
    component: () =>
      import(
        /* webpackChunkName: "testpaperDo" */ '@/containers/course/lessonTask/exam/testpaperDo.vue'
      ),
  },
  {
    path: '/testpaperResult',
    name: 'testpaperResult',
    component: () =>
      import(
        /* webpackChunkName: "testpaperResult" */ '@/containers/course/lessonTask/exam/testpaperResult.vue'
      ),
  },
  {
    path: '/testpaperAnalysis',
    name: 'testpaperAnalysis',
    component: () =>
      import(
        /* webpackChunkName: "testpaperAnalysis" */ '@/containers/course/lessonTask/exam/testpaperAnalysis.vue'
      ),
  },
  {
    path: '/homeworkDo',
    name: 'homeworkDo',
    component: () =>
      import(
        /* webpackChunkName: "homeworkDo" */ '@/containers/course/lessonTask/homework/homeworkDo.vue'
      ),
  },
  {
    path: '/homeworkResult',
    name: 'homeworkResult',
    component: () =>
      import(
        /* webpackChunkName: "homeworkResult" */ '@/containers/course/lessonTask/homework/homeworkResult.vue'
      ),
  },
  {
    path: '/homeworkAnalysis',
    name: 'homeworkAnalysis',
    component: () =>
      import(
        /* webpackChunkName: "homeworkAnalysis" */ '@/containers/course/lessonTask/homework/homeworkAnalysis.vue'
      ),
  },
  {
    path: '/homeworkIntro',
    name: 'homeworkIntro',
    meta: {
      title: '作业说明',
    },
    component: () =>
      import(
        /* webpackChunkName: "homeworkIntro" */ '@/containers/course/lessonTask/homework/homeworkIntro.vue'
      ),
  },
  {
    path: '/exerciseDo',
    name: 'exerciseDo',
    component: () =>
      import(
        /* webpackChunkName: "exerciseDo" */ '@/containers/course/lessonTask/exercise/exerciseDo.vue'
      ),
  },
  {
    path: '/exerciseResult',
    name: 'exerciseResult',
    component: () =>
      import(
        /* webpackChunkName: "exerciseResult" */ '@/containers/course/lessonTask/exercise/exerciseResult.vue'
      ),
  },
  {
    path: '/exerciseAnalysis',
    name: 'exerciseAnalysis',
    component: () =>
      import(
        /* webpackChunkName: "exerciseAnalysis" */ '@/containers/course/lessonTask/exercise/exerciseAnalysis.vue'
      ),
  },
  {
    path: '/exerciseIntro',
    name: 'exerciseIntro',
    meta: {
      title: '练习说明',
    },
    component: () =>
      import(
        /* webpackChunkName: "exerciseIntro" */ '@/containers/course/lessonTask/exercise/exerciseIntro.vue'
      ),
  },
  {
    path: '/classroom/:id',
    name: 'classroom',
    meta: {
      title: '班级详情',
    },
    component: () =>
      import(
        /* webpackChunkName: "classroom" */ '@/containers/classroom/index.vue'
      ),
  },
  {
    path: '/comment/:id',
    name: 'comment',
    meta: {
      title: '学员评价',
    },
    component: () =>
      import(
        /* webpackChunkName: "comment" */ '@/containers/comment/index.vue'
      ),
  },
  {
    path: '/order/:id',
    name: 'order',
    meta: {
      title: '确认订单',
    },
    component: () =>
      import(/* webpackChunkName: "order" */ '@/containers/order/index.vue'),
  },
  {
    path: '/pay',
    name: 'pay',
    meta: {
      title: '订单支付',
    },
    component: () =>
      import(/* webpackChunkName: "pay" */ '@/containers/pay/index.vue'),
  },
  {
    path: '/weixin_pay',
    name: 'wxpay',
    meta: {
      title: '微信支付',
    },
    component: () =>
      import(/* webpackChunkName: "wxpay" */ '@/containers/wxpay/index.vue'),
  },
  {
    path: '/pay_success',
    name: 'paySuccess',
    meta: {
      title: '支付成功',
    },
    component: () =>
      import(/* webpackChunkName: "pay" */ '@/containers/pay/success.vue'),
  },
  {
    path: '/preview',
    name: 'preview',
    meta: {
      title: '预览',
    },
    component: () =>
      import(
        /* webpackChunkName: "preview" */ '@/containers/preview/index.vue'
      ),
  },
  {
    path: '/sts',
    name: 'sts',
    meta: {
      title: '人脸识别登录',
    },
    component: () =>
      import(
        /* webpackChunkName: "login" */ '@/containers/login/face/index.vue'
      ),
  },
  {
    path: '/face_verification',
    name: 'verification',
    meta: {
      title: '人脸认证',
    },
    component: () =>
      import(
        /* webpackChunkName: "verification" */ '@/containers/login/face/verification.vue'
      ),
  },
  {
    path: '/coupon/:token/receive',
    name: 'coupon_receive',
    meta: {
      title: '优惠券领取',
      hideTitle: true,
    },
    component: () =>
      import(
        /* webpackChunkName: "coupon_receive" */ '@/containers/coupon/index.vue'
      ),
  },
  {
    path: '/vip',
    name: 'vip',
    meta: {
      title: '会员专区',
    },
    component: () =>
      import(/* webpackChunkName: "vip" */ '@/containers/vip/index.vue'),
  },
  {
    path: '/setting/password/reset',
    name: 'password_reset',
    meta: {
      title: '',
    },
    component: () =>
      import(
        /* webpackChunkName: "password_reset" */ '@/containers/password-reset/index.vue'
      ),
  },
  {
    path: '/share/redirect', // 分享路由，微营销暂时不使用
    name: 'share_redirect',
    meta: {
      title: '',
    },
    component: () =>
      import(
        /* webpackChunkName: "share_redirect" */ '@/containers/share-redirect/index.vue'
      ),
  },
  {
    path: '/auth/social',
    name: 'auth_social',
    meta: {
      title: '',
    },
    component: () =>
      import(
        /* webpackChunkName: "auth_social" */ '@/containers/login/social/index.vue'
      ),
  },
  {
    path: '/coupon/covert',
    name: 'couponCovert',
    meta: {
      title: '兑换卡券',
    },
    component: () =>
      import(
        /* webpackChunkName: "auth_social" */ '@/containers/coupon/covert/index.vue'
      ),
  },
  {
    path: '/search',
    name: 'search',
    meta: {
      title: '搜索',
    },
    component: () =>
      import(/* webpackChunkName: "search" */ '@/containers/search/index.vue'),
  },
  {
    path: '/moneycard',
    name: 'study_card',
    meta: {
      title: '学习卡充值',
    },
    component: () =>
      import(
        /* webpackChunkName: "study_card" */ '@/containers/study-card/index.vue'
      ),
    redirect: '/moneycard/fixed_receive',
    children: [
      {
        path: '/moneycard/fixed_receive',
        name: 'fixed_receive',
        meta: {
          hideTitle: true,
          title: '学习卡充值',
          color: 'white',
        },
        component: () =>
          import(
            /* webpackChunkName: "fixed_receive" */ '@/containers/study-card/components/input-code'
          ),
      },
      {
        path: '/moneycard/:token/receive',
        name: 'receive',
        meta: {
          hideTitle: true,
          title: '学习卡充值',
          color: 'white',
        },
        component: () =>
          import(
            /* webpackChunkName: "receive" */ '@/containers/study-card/components/valid-card'
          ),
      },
      {
        path: '/moneycard/receive/:password',
        name: 'receive',
        meta: {
          hideTitle: true,
          title: '学习卡充值',
          color: 'white',
        },
        component: () =>
          import(
            /* webpackChunkName: "receive" */ '@/containers/study-card/components/valid-card'
          ),
      },
    ],
  },
  {
    path: '/my/history/learning',
    name: 'history-learn',
    meta: {
      title: '历史学习',
      hideTitle: true,
    },
    component: () =>
      import(
        /* webpackChunkName: "history-learning" */ '@/containers/webview/learning/historyLearn/index.vue'
      ),
  },
  {
    path: '/live-timetable',
    name: 'live-timetable',
    meta: {
      title: '直播课表',
      hideTitle: true,
    },
    component: () =>
      import(
        /* webpackChunkName: "live" */ '@/containers/webview/live-timetable/index.vue'
      ),
  },
  {
    path: '/more/live',
    name: 'moreLive',
    meta: {
      title: '今日直播',
      hideTitle: true,
    },
    component: () =>
      import(
        /* webpackChunkName: "live" */ '@/containers/webview/live-timetable/more/index.vue'
      ),
  },
  {
    path: '/course/explore/new',
    name: 'more_course_new',
    meta: {
      title: '所有课程',
      hideTitle: true,
    },
    component: () =>
      import(
        /* webpackChunkName: "more" */ '@/containers/webview/course/more.vue'
      ),
  },
  {
    path: '/classroom/explore/new',
    name: 'more_class_new',
    meta: {
      title: '所有班级',
      hideTitle: true,
    },
    component: () =>
      import(
        /* webpackChunkName: "more" */ '@/containers/webview/classroom/more.vue'
      ),
  },
  {
    path: '/itemBank/explore/new',
    name: 'more_item-Bank',
    meta: {
      title: '所有题库',
      hideTitle: true,
    },
    component: () =>
      import(
        /* webpackChunkName: "more" */ '@/containers/webview/itemBank/more.vue'
      ),
  },
  {
    path: '/openCourse/explore/new',
    name: 'more_openCourse',
    meta: {
      title: '免费直播课',
      hideTitle: true,
    },
    component: () =>
      import(
        /* webpackChunkName: "more" */ '@/containers/webview/openCourse/more.vue'
      ),
  },
  {
    path: '/my/certificate',
    name: 'my_certificate',
    meta: {
      title: '我的证书',
    },
    component: () =>
      import(
        /* webpackChunkName: "certificate" */ '@/containers/certificate/my-certificate/index.vue'
      ),
  },
  {
    path: '/certificate_records/:id',
    name: 'certificate_records',
    meta: {
      title: '高级产品经理证书',
    },
    component: () =>
      import(
        /* webpackChunkName: "certificate" */ '@/containers/certificate/my-certificate/certificate-detail/index.vue'
      ),
  },
  {
    path: '/certificate/list/:id',
    name: 'certificate_list',
    meta: {
      title: '可获得证书',
    },
    component: () =>
      import(
        /* webpackChunkName: "certificate" */ '@/containers/certificate/certificate/certificate-list.vue'
      ),
  },
  {
    path: '/certificate/detail/:id',
    name: 'certificate_detail',
    meta: {
      title: '高级产品经理证书',
    },
    component: () =>
      import(
        /* webpackChunkName: "certificate" */ '@/containers/certificate/certificate/certificate-detail.vue'
      ),
  },
  {
    path: '/webview/certificate/detail/:id',
    name: 'webview_certificate_detail',
    meta: {
      hideTitle: true,
    },
    component: () =>
      import(
        /* webpackChunkName: "certificate" */ '@/containers/webview/certificate/certificate-detail.vue'
      ),
  },
  {
    path: '/brushExercise',
    name: 'brush_exercise',
    meta: {
      title: '刷题',
    },
    component: () =>
      import(
        /* webpackChunkName: "brush" */ '@/containers/brush-exercise/index.vue'
      ),
  },
  {
    path: '/brushDo',
    name: 'brush_do',
    meta: {
      title: '刷题',
    },
    component: () =>
      import(
        /* webpackChunkName: "brush" */ '@/containers/brush-exercise/lessonTask/do.vue'
      ),
  },
  {
    path: '/brushReport/:answerRecordId',
    name: 'brush_report',
    meta: {
      title: '查看解析',
    },
    component: () =>
      import(
        /* webpackChunkName: "brush" */ '@/containers/brush-exercise/lessonTask/report.vue'
      ),
  },
  {
    path: '/brushResult/:answerRecordId',
    name: 'brush_result',
    meta: {
      title: '答题结果',
    },
    component: () =>
      import(
        /* webpackChunkName: "brush" */ '@/containers/brush-exercise/lessonTask/result.vue'
      ),
  },
  {
    path: '/brushReview/:answerRecordId',
    name: 'brush_review',
    meta: {
      title: '答题批阅',
    },
    component: () =>
      import(
        /* webpackChunkName: "brush" */ '@/containers/brush-exercise/lessonTask/review.vue'
      ),
  },
  {
    path: '/item_bank_exercise/:id',
    name: 'item_bank_exercise',
    meta: {
      title: '题库详情',
    },
    component: () =>
      import(
        /* webpackChunkName: "brush-exercise" */ '@/containers/brush-exercise/index.vue'
      ),
  },
  {
    path: '/info_collection',
    name: 'info_collection',
    meta: {
      title: '用户信息收集',
    },
    component: () =>
      import(
        /* webpackChunkName: "info-collection" */ '@/containers/info-collection/index.vue'
      ),
  },
];

// 页面刷新，store数据会被清掉，需对token、user重新赋值
if (localStorage.getItem('token')) {
  store.commit(types.USER_LOGIN, {
    token: localStorage.getItem('token'),
    user: JSON.parse(localStorage.getItem('user')),
  });
}

const router = new Router({
  routes,
});

const isWeixinBrowser = /micromessenger/.test(
  navigator.userAgent.toLowerCase(),
);

// 检查会员开关配置（会员页面需要有限判断，其他页面异步滞后判断减少页面等待时间）
const setVipSwitch = () =>
  new Promise((resolve, reject) => {
    if (!Object.keys(store.state.vipSettings).length) {
      return store
        .dispatch('getGlobalSettings', { type: 'vip', key: 'vipSettings' })
        .then(vipRes => {
          // vip 前端元素判断（vip 插件已安装(升级) && vip 插件已开启 && vip 等级已设置）
          if (vipRes && vipRes.h5Enabled && vipRes.enabled) {
            return store.dispatch('setVipSwitch', true).then(() => resolve());
          }
          return resolve(vipRes);
        })
        .catch(err => {
          Toast.fail(err.message);
          return reject(err);
        });
    }
    return resolve();
  });

// 检查微信公众号开关配置
const setWeChatSwitch = () =>
  new Promise((resolve, reject) => {
    if (!Object.keys(store.state.wechatSwitch).length && isWeixinBrowser) {
      return store
        .dispatch('getGlobalSettings', {
          type: 'wechat',
          key: 'wechatSettings',
        })
        .then(res => {
          if (res.enabled) {
            return store
              .dispatch('setWeChatSwitch', true)
              .then(() => resolve());
          }
          return resolve(res);
        })
        .catch(err => {
          console.log(err.message);
          return reject(err);
        });
    }
    return resolve();
  });

router.beforeEach((to, from, next) => {
  const shouldUpdateMetaTitle = [
    'binding',
    'password_reset',
    'register',
    'login',
    'protocol',
    'find',
  ].includes(to.name);

  // 已登录用户不进入 prelogin/login/register 路由
  // 已登录用户进入 auth_social 路由，返回到首页，解决反复进入微信授权页面的问题
  if (['prelogin', 'register'].includes(to.name) && store.state.token) {
    next(to.query.redirect || '/');
    return;
  }

  // 未登录用户 信息设置页 跳转到首页
  if (['settings', 'couponCovert'].includes(to.name) && !store.state.token) {
    next('/');
    return;
  }

  // 站点后台设置、会员后台配置
  if (!Object.keys(store.state.settings).length) {
    store
      .dispatch('getGlobalSettings', { type: 'site', key: 'settings' })
      .then(siteRes => {
        // 动态更新 navbar title
        if (shouldUpdateMetaTitle) {
          to.meta.title = siteRes.name;
        }
        store.dispatch('setDrpSwitch');
        if (to.name === 'vip') {
          setVipSwitch().then(() => next());
        } else {
          next();
        }
      })
      .catch(err => {
        Toast.fail(err.message);
      });
  } else if (shouldUpdateMetaTitle) {
    to.meta.title = store.state.settings.name;
    next();
  } else {
    next();
  }

  if (store.state.token) {
    setWeChatSwitch();
  }

  if (store.state.couponSwitch === null) {
    store.dispatch('setCouponSwitch').then(res => {
      console.log(res);
    });
  }
});

// 异步加载配置
router.afterEach(to => {
  // 课程后台配置数据
  if (!Object.keys(store.state.courseSettings).length) {
    store
      .dispatch('getGlobalSettings', {
        type: 'course',
        key: 'courseSettings',
      })
      .catch(err => {
        Toast.fail(err.message);
      });
  }
  if (to.name !== 'vip') {
    setVipSwitch();
  }
});
export default router;
