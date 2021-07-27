import Vue from 'vue';
import store from 'admin/store';
import * as types from 'admin/store/mutation-types';
import Router from 'vue-router';

Vue.use(Router);
// 路由懒加载 实现代码分离
const routes = [
  {
    path: '/',
    name: 'h5Setting',
    meta: {
      title: 'h5后台配置',
    },
    component: () =>
      import(
        /* webpackChunkName: "setting" */ 'admin/containers/setting/h5-home.vue'
      ),
  },
  {
    path: '/preview',
    name: 'preview',
    meta: {
      title: '发现页预览',
    },
    component: () =>
      import(
        /* webpackChunkName: "preview" */ 'admin/containers/preview/index.vue'
      ),
  },
  {
    path: '/miniprogram',
    name: 'miniprogramSetting',
    meta: {
      title: '小程序后台配置',
    },
    component: () =>
      import(
        /* webpackChunkName: "miniprogramSetting" */ 'admin/containers/setting/index.vue'
      ),
  },
  {
    path: '/app',
    name: 'appSetting',
    meta: {
      title: 'app后台配置',
    },
    component: () =>
      import(
        /* webpackChunkName: "appSetting" */ 'admin/containers/setting/index.vue'
      ),
  },
];

const env = process.env.NODE_ENV;
console.log('process.env', env);
// csrfToken 赋值
if (!store.state.csrfToken && env === 'production') {
  const csrfTag = window.parent.document.getElementsByTagName('meta')[
    'csrf-token'
  ];
  if (csrfTag && csrfTag.content) {
    store.commit(types.GET_CSRF_TOKEN, csrfTag.content);
  } else {
    throw new Error('csrfToken 不存在');
  }
}

const router = new Router({
  routes,
});

router.beforeEach((to, from, next) => {
  // 获取会员后台配置
  if (!Object.keys(store.state.courseSettings).length) {
    Promise.all([
      store.dispatch('setVipSetupStatus'),
      store.dispatch('getGlobalSettings', { type: 'vip', key: 'vipSettings' }),
      store.dispatch('getGlobalSettings', {
        type: 'course',
        key: 'courseSettings',
      }),
      store.dispatch('getGlobalSettings', { type: 'site', key: 'settings' }),
      store.dispatch('getGlobalSettings', {
        type: 'classroom',
        key: 'classroomSettings',
      }),
      store.dispatch('getLanguage')
    ])
      .then(([vipPlugin, vipRes]) => {
        console.log(vipPlugin, 8888);
        return vipRes;
      })
      .then(vipRes => {
        if (vipRes && vipRes.h5Enabled && vipRes.enabled) {
          store.dispatch('setVipLevels').then(() => next());
        } else {
          next();
        }
      })
      .catch(err => {
        Vue.prototype.$message({
          message: err.message,
          type: 'error',
        });
        next();
      });
  } else {
    next();
  }
});

export default router;
