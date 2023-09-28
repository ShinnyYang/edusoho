import Vue from 'vue';
import router from '@/router';
import filters from '@/filters';
import utils from '@/utils';
import { GetUrlParam } from '@/utils/utils';
import store from '@/store';
import i18n from '@/lang';
import Cookies from 'js-cookie';
import plugins from '@/plugins';
import EdusohoUI from '@/components';
import whiteList from '@/router/config/white-list';
import '@/assets/styles/main.scss';
import '@/assets/styles/tailwind.css';
import App from '@/App';
import Api from '@/api';
import VueClipboard from 'vue-clipboard2';
import wapSdk from 'wap-sdk';
import moment from 'moment';
import {
  Row,
  Col,
  Button,
  NavBar,
  Tab,
  Tabs,
  Tabbar,
  Dialog,
  TabbarItem,
  Swipe,
  SwipeItem,
  List,
  Field,
  Uploader,
  Popup,
  Rate,
  Cell,
  Tag,
  Toast,
  Lazyload,
  Checkbox,
  CheckboxGroup,
  Radio,
  RadioGroup,
  Panel,
  ActionSheet,
  Switch,
  Loading,
  PullRefresh,
  Overlay,
  Search,
  CountDown,
  Form,
  Area,
  DatetimePicker,
  Picker,
  Icon,
  DropdownMenu,
  DropdownItem,
  Divider,
  Empty,
  CellGroup,
  Cascader,
  TreeSelect,
  Image,
  Progress
} from 'vant';
// 按需引入组件
Vue.component('van-nav-bar', NavBar);
Vue.component('van-tabbar', Tabbar);
Vue.component('van-tabbar-item', TabbarItem);
Vue.component('van-swipe', Swipe);
Vue.component('van-swipe-item', SwipeItem);
Vue.component('van-list', List);
Vue.component('van-button', Button);
Vue.component('van-dialog', Dialog);
Vue.component('van-tab', Tab);
Vue.component('van-tabs', Tabs);
Vue.component('van-field', Field);
Vue.component('van-uploader', Uploader);
Vue.component('van-rate', Rate);
Vue.component('van-cell', Cell);
Vue.component('van-checkbox', Checkbox);
Vue.component('van-checkbox-group', CheckboxGroup);
Vue.component('van-radio', Radio);
Vue.component('van-radio-group', RadioGroup);
Vue.component('van-panel', Panel);
Vue.component('van-pull-refresh', PullRefresh);
Vue.component('van-overlay', Overlay);
Vue.component('van-search', Search);
Vue.component('van-count-down', CountDown);
Vue.component('van-divider', Divider);
Vue.component('van-cell-group', CellGroup);
Vue.component('van-cascader', Cascader);
Vue.component('van-tree-select', TreeSelect);
Vue.component('van-image', Image);
Vue.component('van-progress', Progress);

Vue.use(ActionSheet);
Vue.use(filters);
Vue.use(Row);
Vue.use(Col);
Vue.use(Tag);
Vue.use(Popup);
Vue.use(plugins);
Vue.use(utils);
Vue.use(EdusohoUI);
Vue.use(Lazyload);
Vue.use(Toast);
Vue.use(Checkbox);
Vue.use(CheckboxGroup);
Vue.use(Radio);
Vue.use(RadioGroup);
Vue.use(Panel);
Vue.use(Tab)
  .use(Tabs)
  .use(Dialog)
  .use(Switch)
  .use(PullRefresh)
  .use(Loading)
  .use(Form)
  .use(Area)
  .use(DatetimePicker)
  .use(Picker);
Vue.use(VueClipboard);
Vue.use(Icon);
Vue.use(DropdownMenu);
Vue.use(DropdownItem);
Vue.use(wapSdk);
Vue.use(Empty);
Vue.use(Cascader);
Vue.use(TreeSelect);
Vue.config.productionTip = false;

Vue.prototype.$moment = moment;
Vue.prototype.$cookie = Cookies;
Vue.prototype.$version = require('../../package.json').version;
Vue.config.ignoredElements = ['wx-open-subscribe'];

Api.getSettings({
  query: {
    type: 'wap',
  },
})
  .then(res => {
    const hashStr = location.hash;
    const getPathNameByHash = hash => {
      const hasQuery = hash.indexOf('?');
      if (hasQuery === -1) return hash.slice(1);
      return hash.match(/#.*\?/g)[0].slice(1, -1);
    };

    const isWhiteList = whiteList.includes(getPathNameByHash(hashStr));

    const hashParamArray = getPathNameByHash(hashStr).split('/');
    const hashHasToken = hashParamArray.includes('loginToken');
    const courseId = hashParamArray[hashParamArray.indexOf('course') + 1];

    if (hashHasToken) {
      const tokenIndex = hashParamArray.indexOf('loginToken');
      const tokenFromUrl = hashParamArray[tokenIndex + 1];
      store.state.token = tokenFromUrl;
      localStorage.setItem('token', tokenFromUrl);
      if (courseId) {
        window.location.href = `${location.origin}/h5/index.html#/course/${courseId}?backUrl=%2F`;
      }
    }

    const hasToken = window.localStorage.getItem('token');

    if (hasToken && !store.state.user) {
      Api.getUserInfo({}).then(res => {
        store.state.user = res;
        localStorage.setItem('user', JSON.stringify(res));
      });
    }

    if (!hasToken && Number(GetUrlParam('needLogin'))) {
      window.location.href = `${
        location.origin
      }/h5/index.html#/login?redirect=/course/${courseId}&skipUrl=%2F&account=${GetUrlParam(
        'account',
      )}`;
    }

    // 已登录状态直接跳转详情页
    if (hasToken && Number(GetUrlParam('needLogin'))) {
      window.location.href = `${location.href}&backUrl=%2F`;
    }

    if (!isWhiteList) {
      if (parseInt(res.version, 10) !== 2) {
        // 如果没有开通微网校，则跳回老版本网校 TODO
        window.location.href = location.origin + getPathNameByHash(hashStr);
        return;
      }
    }
    new Vue({
      router,
      store,
      i18n,
      render: h => h(App),
    }).$mount('#app');
  })
  .catch(err => {
    console.log(err.message);
  });

Api.getSettings({
  query: {
    type: 'site',
  },
}).then(res => {
  if (!res.analytics || /document.write/.test(res.analytics)) return;
  let funStr = res.analytics.replace(/<\/?script[^>]*?>/gi, '');
  funStr = funStr.replace(/<noscript[^>]*?>.*?<\/noscript>/gis, '');
  const script = document.createElement('script');
  const scriptEle = document.getElementsByTagName('script')[0];
  script.type = 'text/javascript';
  script.innerHTML = funStr;
  scriptEle.parentNode.insertBefore(script, scriptEle);
});

Api.getSettings({
  query: {
    type: 'ugc',
  },
})
  .then(res => {
    store.state.goods.show_review = res.review.enable;
    store.state.goods.show_course_review = res.review.course_enable;
    store.state.goods.show_classroom_review = res.review.classroom_enable;
    store.state.goods.show_question_bank_review =
      res.review.question_bank_enable;
  })
  .catch(error => {
    console.error(error);
  });

if (!Cookies.get('language')) {
  Api.getSettings({
    query: {
      type: 'locale',
    },
  }).then(res => {
    const language = res.locale.toLowerCase().replace('_', '-');
    store.state.language = language;
    i18n.locale = language;
  });
}
