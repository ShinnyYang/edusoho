export default [
  {
    // 获取题库分类
    name: 'getItemBankCategoriesNew',
    url: '/item_bank_category',
  },
  {
    // 获取题库课程列表数据
    name: 'getItemBankList',
    url: '/item_bank_exercises',
  },
  {
    // 获取题库课程信息
    name: 'getItemBankExercise',
    url: '/item_bank_exercises/{id}',
  },
  {
    // 获取题库目录信息
    name: 'getItemBankModules',
    url: '/item_bank_exercises/{id}/modules',
  },
  {
    // 获取个人题库试卷信息
    name: 'getMyItemBankAssessments',
    url: '/me/item_bank_exercises/{exerciseId}/modules/{moduleId}/assessments',
    disableLoading: true,
  },
  {
    // 获取个人题库章节信息
    name: 'getMyItemBankCategories',
    url: '/me/item_bank_exercises/{exerciseId}/modules/{moduleId}/categories',
    disableLoading: true,
  },
  {
    // 获取题库试卷信息
    name: 'getItemBankAssessments',
    url: '/item_bank_exercises/{exerciseId}/modules/{moduleId}/assessments',
    disableLoading: true,
  },
  {
    // 获取题库章节信息
    name: 'getItemBankCategories',
    url: '/item_bank_exercises/{exerciseId}/modules/{moduleId}/categories',
    disableLoading: true,
  },
  {
    // 模拟卷开始/再次答题
    name: 'getAssessmentExerciseRecord',
    url: '/item_bank_exercises/{exerciseId}/assessment_exercise_record',
    method: 'POST',
  },
  {
    // 章节练习开始/再次答题
    name: 'getChapterExerciseRecord',
    url: '/item_bank_exercises/{exerciseId}/chapter_exercise_record',
    method: 'POST',
  },
  {
    // 章节练习自判
    name: 'pushItemBankReviewReport',
    url: '/item_bank_exercises/{exerciseId}/review_report',
    method: 'POST',
  },
  {
    // 加入
    name: 'joinItemBank',
    url: '/item_bank_exercises/{exerciseId}/members',
    method: 'POST',
    disableLoading: true,
  },
];
