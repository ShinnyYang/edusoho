import * as types from './mutation-types';

export default {
  [types.UPDATE_LOADING_STATUS](state, payload) {
    state.isLoading = payload;
  },
  [types.USER_LOGIN](state, payload) {
    state.token = payload.token;
    state.user = payload.user;
    localStorage.setItem('token', payload.token);
    localStorage.setItem('user', JSON.stringify(payload.user));
  },
  [types.USER_LOGOUT](state) {
    state.token = null;
    state.user = {};
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  },
  [types.USER_INFO](state, payload) {
    state.user = payload;
    localStorage.setItem('user', JSON.stringify(payload));
  },
  [types.ADD_USER](state, payload) {
    state.user = payload;
    localStorage.setItem('user', JSON.stringify(payload));
  },
  [types.SMS_CENTER](state, payload) {
    // 看起来像是没用的，后续需要验证
    state.smsToken = payload;
  },
  [types.SMS_SEND](state, payload) {
    // 看起来像是没用的，后续需要验证
    state.smsToken = payload;
  },
  [types.SET_NICKNAME](state, payload) {
    state.user = Object.assign({}, state.user, {
      nickname: payload.nickname,
    });
    localStorage.setItem('user', JSON.stringify(payload));
  },
  [types.SET_AVATAR](state, payload) {
    state.user = payload;
    localStorage.setItem('user', JSON.stringify(payload));
  },
  [types.BIND_MOBILE](state, payload) {
    state.user.verifiedMobile = payload.verifiedMobile;
    localStorage.setItem('user', JSON.stringify(state.user));
  },
  [types.GET_SETTINGS](state, { key, setting }) {
    state[key] = setting;
  },
  [types.SET_NAVBAR_TITLE](state, payload) {
    state.title = payload;
  },
  [types.SET_SOCIAL_STATUS](state, { key, status }) {
    state.socialBinded[key] = status;
  },
  [types.COUPON_SWITCH](state, payload) {
    state.couponSwitch = payload;
  },
  [types.SET_TASK_SATUS](state, payload) {
    state.course.taskStatus = payload;
  },

  SET_CLOUD_SDK_CDN(state, address) {
    state.cloudSdkCdn = address;
  },
  SET_CLOUD_PLAY_SERVER(state, address) {
    state.cloudPlayServer = address;
  },
  [types.SET_MOBILE_BIND](state, mobile_bind) {
    state.mobile_bind = mobile_bind;
  },
};
