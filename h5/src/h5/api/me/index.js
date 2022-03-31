export default [
  {
    // 登录
    name: 'login',
    url: '/tokens',
    method: 'POST',
  },
  {
    // 短信快捷登录/注册
    name: 'fastlogin',
    url: '/login',
    method: 'POST',
  },
  {
    // 短信快捷登录获取短信验证码
    name: 'getSmsSend',
    url: '/sms_send',
    method: 'POST',
    disableLoading: true,
  },
  {
    // 人脸识别 判断用户是否存在
    name: 'getUserIsExisted',
    url: '/users/{type}',
    method: 'GET',
  },
  {
    // 人脸识别 创建register的session接口
    name: 'getSessions',
    url: '/face_sessions',
    method: 'POST',
  },
  {
    // 人脸识别 获取register的session，确认人脸识别认证结果, 轮询
    name: 'faceSession',
    url: '/face_sessions/{sessionId}',
    method: 'GET',
  },
  {
    // 人脸识别 图片上传最终的结果
    name: 'finishUploadResult',
    url: '/face_sessions/{sessionId}/finish_upload_results',
    method: 'POST',
  },
  {
    // 新增用户短信验证码
    name: 'getSmsCenter',
    url: '/sms_center',
    method: 'POST',
    disableLoading: true,
  },
  {
    // 新增用户
    name: 'addUser',
    url: '/user',
    method: 'POST',
  },
  {
    name: 'mobileBindCheck',
    url: '/user/{userId}/mobile_bind_check',
    method: 'GET',
    // is_bind_mobile: 是否绑定手机号（false：未绑定，true：已绑定
    // mobile_bind_mode: 绑定手机号方式（constraint：强制绑定，option：非强制绑定，closed：不绑定）
  },
  {
    // 获取滑动验证参数
    name: 'dragCaptcha',
    url: '/drag_captcha',
    method: 'POST',
    disableLoading: true,
  },
  {
    // 滑动验证吗数据验证
    name: 'dragValidate',
    url: '/drag_captcha/{token}',
    method: 'GET',
    disableLoading: true,
  },
  {
    // 获取我的个人信息
    name: 'getUserInfo',
    url: '/me',
    method: 'GET',
  },
  {
    // 修改头像
    name: 'setAvatar',
    url: '/me',
    method: 'PATCH',
    disableLoading: true,
  },
  {
    // 修改昵称
    name: 'setNickname',
    url: '/me/nicknames/h5',
    method: 'PATCH',
    disableLoading: true,
  },
  {
    // 绑定手机
    name: 'setMobile',
    url: '/me/mobiles/{mobile}',
    method: 'PUT',
    disableLoading: true,
  },
  {
    // 我的学习
    name: 'myStudyCourses',
    url: '/me/courses',
    method: 'GET',
  },
  {
    // 我的学习
    name: 'myhistoryLearn',
    url: '/me/footprint',
    method: 'GET',
  },
  {
    // 我的学习
    name: 'myStudyClasses',
    url: '/me/classrooms',
    method: 'GET',
  },
  {
    // 我的学习
    name: 'myStudyBanks',
    url: '/me/item_bank_exercises',
    method: 'GET',
  },
  {
    // 邮箱重置密码
    /*
     * @params dragCaptchaToken
     * @return {
     *   "id":"25","nickname":"806338233", ...
     * }
     */
    // 4040104: 没有该用户
    // 4030106: discuz论坛用户，请到论坛重置密码
    // 4030301: 验证失败
    // 4030302: 验证过期
    // 4030303: 验证码失效
    name: 'resetPasswordByEmail',
    url: '/user/{email}/password/email',
    method: 'PUT',
    disableLoading: true,
  },
  {
    /*
     * @params dragCaptchaToken
     * @return {
     *   smsToken: token,
     * }
     */
    // 手机重置密码短信获取
    // 4030301: 验证失败
    // 4030302: 验证过期
    // 4030303: 验证码失效
    name: 'resetPasswordSMS',
    url: '/user/{mobile}/sms_reset_password',
    method: 'POST',
    disableLoading: true,
  },
  {
    // 手机重置密码短信校验
    /*
     * @return {
     *   'sms.code.success' / 'sms.code.invalid' / 'sms.code.expired',
     * }
     */
    // 5000305: 参数缺失
    name: 'resetPasswordSMSValidate',
    url: '/user/{mobile}/sms_reset_password/{smsCode}',
    method: 'GET',
    disableLoading: true,
  },
  {
    // 通过手机重置密码
    /*
     * @params smsToken smsCode encrypt_password
     * @return {
     *   "id":"25","nickname":"806338233", ...
     * }
     */
    // 5000305：参数缺失
    // 4040104：找不到用户
    // 5000306：参数错误
    // 4030103：注册失败，短信验证码没通过
    // 4030301: 验证失败
    // 4030302: 验证过期
    // 4030303: 验证码失效
    name: 'resetPasswordByMobile',
    url: '/user/{mobile}/password/mobile',
    method: 'PUT',
    disableLoading: true,
  },
  {
    name: 'weChatNotifyState',
    url: '/wechat_user_notify_state/official',
    method: 'GET',
    disableLoading: true,
  },
  {
    name: 'meCourseMember',
    url: '/me/course_members/{id}',
    method: 'GET',
  },
  {
    name: 'meClassroomMember',
    url: '/me/classroom_members/{id}',
    method: 'GET',
  },
  {
    name: 'getEmailServiceState',
    url: '/setting/mail',
    method: 'GET',
  },
];
