import Vuex from 'vuex';
import Vue from 'vue';

import * as getters from './getters';
import * as actions from './actions';
import mutations from './mutations';
import course from './modules/course';
import classroom from './modules/classroom';

Vue.use(Vuex);

const state = {
  isLoading: false,
  token: null,
  user: {},
  smsToken: '',
  settings: {},
  courseSettings: {},
  title: '',
  vipSettings: {},
  wechatSwitch: false,
  vipSwitch: false,
  couponSwitch: 0,
  socialBinded: {
    wx: true,
  },
  DrpSwitch: false, // 分销插件
};

export default new Vuex.Store({
  state,
  getters,
  actions,
  mutations,
  modules: {
    course,
    classroom,
  },
});
