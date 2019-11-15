import * as types from './mutation-types'

export default {
  [types.UPDATE_LOADING_STATUS](state, payload) {
    state.isLoading = payload
  },
  [types.GET_COURSE_CATEGORIES](state, payload) {
    state.courseCategories = payload
  },
  [types.GET_CLASS_CATEGORIES](state, payload) {
    state.classCategories = payload
  },
  [types.GET_CSRF_TOKEN](state, payload) {
    state.csrfToken = payload
  },
  [types.UPDATE_DRAFT](state, payload) {
    state.draft = payload
  },
  [types.GET_SETTINGS](state, { key, setting }) {
    state[key] = setting
  }
}
