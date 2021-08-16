export default [
  {
    // 课程详情页
    name: 'getCourseDetail',
    url: '/pages/h5/courses/{courseId}',
    method: 'GET',
    disableLoading: true,
  },
  {
    // 获取计划目录
    name: 'getCourseLessons',
    url: '/courses/{courseId}/item_with_lessons',
    method: 'GET',
    disableLoading: true,
  },
  {
    // 优化目录获取计划结构
    name: 'getOptimizationCourseLessons',
    url: '/courses/{courseId}/item_with_lessons?format=tree',
    method: 'GET',
    disableLoading: true,
  },
  {
    // 加入课程
    name: 'joinCourse',
    url: '/courses/{id}/members',
    method: 'POST',
  },
  {
    // 课时播放
    name: 'getMedia',
    url: '/courses/{courseId}/task_medias/{taskId}',
    method: 'GET',
  },
  {
    // 课时信息
    name: 'getCourseData',
    url: '/courses/{courseId}/task/{taskId}',
    method: 'GET',
    disableLoading: true,
  },
  {
    // 课时doing
    name: 'reportTaskDoing',
    url: '/courses/{courseId}/task/{taskId}/events/doing',
    method: 'PUT',
    disableLoading: true,
  },
  {
    // 课时上报事件
    name: 'reportTask',
    url: '/courses/{courseId}/task/{taskId}/events/{events}',
    method: 'PUT',
    disableLoading: true,
  },
  {
    // 课时finish
    name: 'reportTaskFinish',
    url: '/courses/{courseId}/task/{taskId}/events/finish',
    method: 'PUT',
    disableLoading: true,
  },
  {
    // 下次学习课时
    name: 'getNextStudy',
    url: '/me/course_learning_progress/{courseId}',
    method: 'GET',
  },
  {
    // 获取课程列表数据
    name: 'getCourseList',
    url: '/courses',
  },
  {
    // 获取课程搜索列表
    name: 'getCourseSets',
    url: '/course_sets',
  },
  {
    // 根据计划 id 查询计划详情
    name: 'getCourse',
    url: '/course_sets/{courseId}',
    method: 'GET',
  },
  {
    // 获取课程评论
    name: 'getCourseReviews',
    url: '/courseSet/{id}/reviews',
    method: 'GET',
  },
  {
    // 根据课程查询计划
    name: 'getCourseByCourseSet',
    url: '/course_sets/{id}/courses',
    method: 'GET',
    disableLoading: true,
  },
  {
    // 退出课程
    name: 'deleteCourse',
    url: '/me/course_members/{id}',
    method: 'DELETE',
    disableLoading: true,
  },
  {
    // 创建评价
    name: 'createReview',
    url: '/review',
    method: 'POST',
  },
  {
    // 数据上报
    name: 'reportTaskEvent',
    url: '/courses/{courseId}/task/{taskId}/event_v2/{eventName}',
    method: 'PATCH',
    disableLoading: true,
  },
  {
    // 获取本地视频资源
    // 由于是老的 api 接口, 不需要添加 headers.Accept = 'application/vnd.edusoho.v2+json', 于是在 name 里面添加 Live 过滤掉
    name: 'getLocalMediaLive',
    url: '/lessons/{taskId}',
    method: 'GET',
  },
  {
    // 搜索课程话题信息
    name: 'getCoursesThreads',
    url: '/courses/{courseId}/threads',
    method: 'GET',
    disableLoading: true
  },
  {
    // 搜索课程话题回复
    name: 'getCoursesThreadPost',
    url: '/courses/{courseId}/thread_post/{threadId}',
    method: 'GET',
    disableLoading: true
  },
  {
    // 添加课程话题
    name: 'createCoursesThread',
    url: '/courses/{courseId}/threads',
    method: 'POST',
    disableLoading: true
  },
];
