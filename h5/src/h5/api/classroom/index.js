export default [
  {
    // 课程详情页
    name: 'getClassroomDetail',
    url: '/pages/h5/classrooms/{classroomId}',
    method: 'GET',
  },
  {
    // 加入班级
    name: 'joinClass',
    url: '/classrooms/{classroomId}/members',
    method: 'POST',
  },
  {
    // 获取班级评论
    name: 'getClassroomReviews',
    url: '/classrooms/{id}/reviews',
    method: 'GET'
  },
  {
    // 获取题库评论
    name: 'getBankReviews',
    url: '/review',
    method: 'GET',
  },
  {
    // 获取课程列表数据
    name: 'getClassList',
    url: '/classrooms',
  },
  {
    // 退出班级
    name: 'deleteClassroom',
    url: '/me/classroom_members/{id}',
    method: 'DELETE',
    disableLoading: true,
  },
  {
    // 班级里搜索课程
    name: 'searchCourse',
    url: '/classrooms/{id}/courses',
    method: 'GET'
  },
];
