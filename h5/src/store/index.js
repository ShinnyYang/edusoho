import Vuex from 'vuex';
import Vue from 'vue';

import * as getters from './getters';
import * as actions from './actions';
import mutations from './mutations';
import course from './modules/course';

Vue.use(Vuex);

const state = {
  isLoading: false,
  token: null,
  user: {},
  smsToken: '',
  settings: {},
  courseSettings: {},
  title: '',
  vipSwitch: true
};


export default new Vuex.Store({
  state,
  getters,
  actions,
  mutations,
  modules: {
    course
  }
});
