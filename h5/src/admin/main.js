import Vue from 'vue';

import i18n from './lang';


import 'vant/lib/swipe/style';
import 'vant/lib/swipe-cell/style';
import 'vant/lib/swipe-item/style';
import utils from '@/utils';
import {
  Select,
  Button,
  Message,
  Upload,
  Input,
  Radio,
  Option,
  Cascader,
  Dialog,
  Tag,
  Autocomplete,
  Tooltip,
  Loading,
  Dropdown,
  DropdownItem,
  DropdownMenu,
  MessageBox,
  Col,
  Row,
  Menu,
  Submenu,
  MenuItem,
  MenuItemGroup,
  Carousel,
  CarouselItem,
} from 'element-ui';

import { Swipe, SwipeItem, Lazyload, Search } from 'vant';

import router from './router';
import store from './store';
import './styles/main.scss';
import App from './App';

// 按需引入组件
Vue.component('van-swipe', Swipe);
Vue.component('van-swipe-item', SwipeItem);
Vue.component('van-search', Search);

Vue.use(Loading);
Vue.use(Input);
Vue.use(Select);
Vue.use(Button);
Vue.use(Upload);
Vue.use(Radio);
Vue.use(Option);
Vue.use(Cascader);
Vue.use(Dialog);
Vue.use(Tag);
Vue.use(Autocomplete);
Vue.use(Tooltip);
Vue.use(utils);
Vue.use(Dropdown);
Vue.use(DropdownItem);
Vue.use(DropdownMenu);
Vue.use(Lazyload);
Vue.use(Col);
Vue.use(Row);
Vue.use(Menu);
Vue.use(Submenu);
Vue.use(MenuItem);
Vue.use(MenuItemGroup);
Vue.use(Carousel);
Vue.use(CarouselItem);

Vue.prototype.$message = Message;
Vue.prototype.$confirm = MessageBox.confirm;

Vue.config.productionTip = false;

/* eslint-disable no-new */
new Vue({
  router,
  store,
  i18n,
  render: h => h(App),
}).$mount('#app');
