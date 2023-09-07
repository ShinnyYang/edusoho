export default [
  {
    // 我的错题列表信息
    name: 'getWrongBooks',
    url: '/me/wrong_books',
    method: 'GET',
  },
  {
    // 我的错题题目分类
    name: 'getWrongBooksCertainTypes',
    url: '/me/wrong_books/{targetType}/certain_types',
    method: 'GET',
  },
  {
    // 课程、班级、题库练习错题展示
    name: 'getWrongBooksQuestionShow',
    url: '/wrong_books/{poolId}/question_show',
    method: 'GET',
  },
  {
    // 错题课程分类级联查询条件
    name: 'getWrongQuestionCondition',
    url: '/wrong_books/{poolId}/condition',
    method: 'GET',
  },
  {
    // 题库练习-章节、试卷练习数量详情
    name: 'getWrongQuestionExercise',
    url: '/wrong_books/{poolId}/bank_exercise',
    method: 'GET',
  },
  {
    // 刷题
    name: 'getWrongQuestionStartAnswer',
    url: '/wrong_books/{poolId}/start_answer',
    method: 'POST',
  },
  {
    // 做题提交
    name: 'submitWrongQuestionAnswer',
    url: '/wrong_books/{poolId}/submit_answer/{recordId}',
    method: 'PUT',
  },
  {
    // 获取错题总数
    name: 'getWrongNumCount',
    url: '/wrong_book/{poolId}',
    method: 'GET',
  },

];
