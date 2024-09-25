/**
 * 使用说明
 *
 * import Api from 'common/api';
 *
 * Api.course.create({
 *    params: {},
 *    data: {}
 * }).then((res) => {
 *    // 请求成功
 *    console.log('res', res);
 * }).catch((res) => {
 *   // 异常捕获
 *   console.log('catch', res.responseJSON.error.message);
 * })
 */

import courseModule from './modules/course';
import classroomModule from './modules/classroom';
import tradeModule from './modules/trade';
import captchaModule from './modules/captcha';
import dragCaptchaModule from './modules/dragCaptcha';
import smsModule from './modules/sms';
import teacherLiveCourseModule from './modules/teacherLiveCourse';
import studentLiveCourseModule from './modules/studentLiveCourse';
import conversationModule from './modules/conversation';
import newNotificationModule from './modules/newNotification';
import resetPasswordEmail from './modules/reset-password/email.js';
import resetPasswordMobile from './modules/reset-password/mobile.js';
import resetPasswordSms from './modules/reset-password/sms.js';
import informationCollect from './modules/informationCollect.js';
import favoriteModule from './modules/favorite';
import reviewModule from './modules/review';
import courseTaskEventModule from './modules/courseTaskEvent';
import courseTaskResultModule from './modules/courseTaskResult';
import fastLoginSmsModule from './modules/fastLoginSms';
import watermark from './modules/watermark';
import setting from './modules/setting';

const API_URL_PREFIX = '/api';

const Api = {
  // 课程模块
  course: courseModule(API_URL_PREFIX),
  // 班级模块
  classroom: classroomModule(API_URL_PREFIX),
  //交易
  trade: tradeModule(API_URL_PREFIX),
  //验证码
  captcha: captchaModule(API_URL_PREFIX),
  //拖动验证码
  dragCaptcha: dragCaptchaModule(API_URL_PREFIX),
  //短信
  sms: smsModule(API_URL_PREFIX),
  fastLoginSms: fastLoginSmsModule(API_URL_PREFIX),
  teacherLiveCourse: teacherLiveCourseModule(API_URL_PREFIX),
  studentLiveCourse: studentLiveCourseModule(API_URL_PREFIX),
  conversation: conversationModule(API_URL_PREFIX),
  newNotification: newNotificationModule(API_URL_PREFIX),
  resetPasswordEmail: resetPasswordEmail(API_URL_PREFIX),
  resetPasswordMobile: resetPasswordMobile(API_URL_PREFIX),
  resetPasswordSms: resetPasswordSms(API_URL_PREFIX),
  informationCollect: informationCollect(API_URL_PREFIX),
  //收藏
  favorite: favoriteModule(API_URL_PREFIX),
  review: reviewModule(API_URL_PREFIX),
  //task event数据上报
  courseTaskEvent: courseTaskEventModule(API_URL_PREFIX),
  //courseTaskResult接口
  courseTaskResult: courseTaskResultModule(API_URL_PREFIX),
  watermark: watermark(API_URL_PREFIX),
  setting: setting(API_URL_PREFIX),
};

export default Api;
