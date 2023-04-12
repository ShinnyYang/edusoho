import Api from '@/api';
import * as types from '../mutation-types';

const state = {
  selectedPlanId: 0,
  joinStatus: false, // 当前计划是否已加入学习
  sourceType: 'img', //
  details: {},
  taskId: 0, // 任务id
  courseLessons: [], // 课程中所有任务
  nextStudy: {}, // 下一次学习
  OptimizationCourseLessons: [], // 优化后的课程中所有任务
  allTask: {},
  taskStatus: '', // 当前task任务完成情况
  searchCourseList: {
    selectedData: {},
    courseList: [],
    paging: {},
  },
  currentJoin: false, // 课程加入后是否采集用户信息
};

const hasJoinedCourse = course => course.member;

const mutations = {
  [types.GET_COURSE_LESSONS](currentState, payload) {
    currentState.courseLessons = payload;
  },
  [types.GET_OPTIMIZATION_COURSE_LESSONS](currentState, payload) {
    currentState.OptimizationCourseLessons = payload;
  },
  [types.GET_NEXT_STUDY](currentState, payload) {
    currentState.nextStudy = payload;
  },
  [types.GET_COURSE_DETAIL](currentState, payload) {
    currentState.selectedPlanId = payload.id;
    currentState.details = payload;
    currentState.joinStatus = hasJoinedCourse(payload);
    currentState.sourceType = 'img';
  },
  [types.JOIN_COURSE](currentState) {
    currentState.joinStatus = true;
  },
  [types.SET_SOURCETYPE](currentState, payload) {
    currentState.sourceType = payload.sourceType || 'img';
    currentState.taskId = payload.taskId;
  },
  [types.UPDATE_PROGRESS](currentState, payload) {
    currentState.details.progress.percent = payload;
  },
  [types.SET_ALL_TASK](currentState, payload) {
    currentState.allTask = payload;
  },
  [types.SET_COURSELIST](currentState, data) {
    currentState.searchCourseList = data || {};
  },
  [types.SET_CURRENT_JOIN_COURSE](currentState, payload) {
    currentState.currentJoin = payload;
  },
};

const actions = {
  async getCourseLessons({ dispatch }, { courseId }) {
    let s;
    try {
      await dispatch('getNextStudy', { courseId });
      s = await dispatch('getCourse', { courseId });
    } catch (e) {
      s = await dispatch('getCourse', { courseId });
    }
    return s[2];
  },
  getCourse({ commit }, { courseId }) {
    const query = { courseId };
    commit('UPDATE_LOADING_STATUS', true, { root: true }); // -> 'someMutation'
    return Promise.all([
      Api.getCourseLessons({ query }),
      Api.getOptimizationCourseLessons({ query }),
      Api.getCourseDetail({ query }),
    ]).then(([coursePlan, OptimizationCoursePlan, courseDetail]) => {
      commit(types.GET_COURSE_LESSONS, coursePlan);
      commit(types.GET_OPTIMIZATION_COURSE_LESSONS, OptimizationCoursePlan);
      commit(types.GET_COURSE_DETAIL, courseDetail);
      commit('UPDATE_LOADING_STATUS', false, { root: true }); // -> 'someMutation'
      return [coursePlan, OptimizationCoursePlan, courseDetail];
    });
  },
  getBeforeCourse({ commit }, { courseId }) {
    const query = { courseId };
    return Promise.all([Api.getCourseLessons({ query })]).then(
      ([coursePlan]) => {
        commit(types.GET_COURSE_LESSONS, coursePlan);
        return [coursePlan];
      },
    );
  },
  getAfterCourse({ commit }, { courseId }) {
    const query = { courseId };
    return Promise.all([Api.getOptimizationCourseLessons({ query })]).then(
      ([OptimizationCoursePlan]) => {
        commit(types.GET_OPTIMIZATION_COURSE_LESSONS, OptimizationCoursePlan);
        // dispatch('getNextStudy', { courseId });
        return [OptimizationCoursePlan];
      },
    );
  },
  getCourseDetail({ commit }, { courseId }) {
    const query = { courseId };
    return Promise.all([Api.getCourseDetail({ query })]).then(
      ([courseDetail]) => {
        commit(types.GET_COURSE_DETAIL, courseDetail);
        return courseDetail;
      },
    );
  },
  getNextStudy({ commit }, { courseId }) {
    const query = { courseId };
    return Promise.all([Api.getNextStudy({ query })]).then(([nextStudy]) => {
      commit(types.GET_NEXT_STUDY, nextStudy);
      return [nextStudy];
    });
  },
  joinCourse({ commit }, { id }) {
    return Api.joinCourse({
      query: {
        id,
      },
    }).then(res => {
      // 返回空对象，表示加入失败，需要去创建订单购买
      if (!(Object.keys(res).length === 0)) {
        commit(types.JOIN_COURSE, res);
        commit(types.SET_CURRENT_JOIN_COURSE, true);
      }
      return res;
    });
  },
  // eslint-disable-next-line no-unused-vars
  handExamdo({ commit }, datas) {
    // eslint-disable-next-line prefer-const
    let { answer, resultId, beginTime, endTime, userId } = { ...datas };
    beginTime *= 1000;
    let used_time = Math.ceil((endTime - beginTime) / 1000);

    // 如果是不限时间限制，使用时间在本地有记录，如果有时间限制，使用时间在本地无记录
    const localuseTime = `${userId}-${resultId}-usedTime`;
    if (localStorage.getItem(localuseTime)) {
      used_time = localStorage.getItem(localuseTime);
    }

    return new Promise((resolve, reject) => {
      Api.handExam({
        data: {
          data: answer,
          resultId,
          used_time,
        },
      })
        .then(res => {
          localStorage.removeItem(`${userId}-${resultId}`);
          localStorage.removeItem(`${userId}-${resultId}-time`);
          resolve(res);
        })
        .catch(err => {
          reject(err);
        });
    });
  },
 // eslint-disable-next-line no-unused-vars
 saveAnswerdo({ commit }, data) {
  return new Promise((resolve, reject) => {
    Api.saveAnswerdo({
      query: {
        resultId: data.resultId
      },
      hideLoading: true,
      data
    }).then(res => {
      resolve(res)
    }).catch(err => {
      reject(err)
    })
  })
},
  // eslint-disable-next-line no-unused-vars
  handHomeworkdo({ commit }, datas) {
    // eslint-disable-next-line prefer-const
    let { answer, homeworkResultId, homeworkId, userId } = { ...datas };

    // 时间取localstorge存储时间，默认值为0
    const localuseTime = `homework-${userId}-${homeworkResultId}-usedTime`;
    const usedTime = Number(localStorage.getItem(localuseTime)) || 0;

    return new Promise((resolve, reject) => {
      Api.handHomework({
        query: {
          homeworkId,
          homeworkResultId,
        },
        data: {
          data: answer,
          usedTime,
        },
      })
        .then(res => {
          localStorage.removeItem(`homework-${userId}-${homeworkResultId}`);
          localStorage.removeItem(localuseTime);
          resolve(res);
        })
        .catch(err => {
          reject(err);
        });
    });
  },
  // eslint-disable-next-line no-unused-vars
  handExercisedo({ commit }, datas) {
    // eslint-disable-next-line prefer-const
    let { answer, exerciseResultId, exerciseId, userId } = { ...datas };

    // 时间取localstorge存储时间，默认值为0
    const localuseTime = `exercise-${userId}-${exerciseResultId}-usedTime`;
    const usedTime = Number(localStorage.getItem(localuseTime)) || 0;

    return new Promise((resolve, reject) => {
      Api.handExercise({
        query: {
          exerciseId,
          exerciseResultId,
        },
        data: {
          data: answer,
          usedTime,
        },
      })
        .then(res => {
          localStorage.removeItem(`exercise-${userId}-${exerciseResultId}`);
          localStorage.removeItem(localuseTime);
          resolve(res);
        })
        .catch(err => {
          reject(err);
        });
    });
  },
  setCourseList({ commit }, data) {
    commit(types.SET_COURSELIST, data);
  },
};

export default {
  namespaced: true,
  state,
  actions,
  mutations,
};
