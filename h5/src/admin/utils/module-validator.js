import Vue from 'vue'

export default (module, startValidate) => {
  // 轮播图
  if (module.type === 'slide_show') {
    for (let i = 0; i < module.data.length; i += 1) {
      const imgUri = module.data[i].image.uri
      if (!imgUri) {
        if (!startValidate) return true
        Vue.prototype.$message({
          message: '请完善轮播图模块信息！',
          type: 'error'
        })
        return true
      }
    }
  }

  // 课程
  if (module.type === 'course_list') {
    const courseExist = module.data.items.length
    if (!module.data.title || (module.data.sourceType === 'custom' && !courseExist)) {
      if (!startValidate) return true
      Vue.prototype.$message({
        message: '请完善课程模块信息！',
        type: 'error'
      })
      return true
    }
  }

  // 班级
  if (module.type === 'classroom_list') {
    const classExist = module.data.items.length
    if (!module.data.title || (module.data.sourceType === 'custom' && !classExist)) {
      if (!startValidate) return true
      Vue.prototype.$message({
        message: '请完善班级模块信息！',
        type: 'error'
      })
      return true
    }
  }

  // 广告
  if (module.type === 'poster') {
    const imgUri = module.data.image.uri
    if (!imgUri) {
      if (!startValidate) return true
      Vue.prototype.$message({
        message: '请完善广告模块信息！',
        type: 'error'
      })
      return true
    }
  }

  // 营销活动——拼团
  if (['groupon', 'seckill', 'cut'].includes(module.type)) {
    const typeText = {
      seckill: '秒杀',
      cut: '砍价',
      groupon: '拼团'
    }
    const activityExist = module.data.activity.id
    if (!activityExist) {
      if (!startValidate) return true
      Vue.prototype.$message({
        message: `请完善${typeText[module.type]}模块信息！`,
        type: 'error'
      })
      return true
    }
  }

  // 优惠券
  if (module.type === 'coupon') {
    if (!module.data.items.length) {
      if (!startValidate) return true
      Vue.prototype.$message({
        message: '请完善优惠券模块信息！',
        type: 'error'
      })
      return true
    }
  }

  return false
};
