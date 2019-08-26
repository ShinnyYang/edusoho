import { formatTimeByNumber, formatCompleteTime } from '@/utils/date-toolkit';

const filters = [
  {
    name: 'toMoney',
    handler(value) {
      return isNaN(Number(value)) ? '0.00' : (Number(value) / 100).toFixed(2);
    }
  },
  {
    name: 'isFree',
    handler(value) {
      return value ? 'value' : '免费';
    }
  },
  {
    name: 'taskType',
    handler(task) {
      if (task.status !== 'published') {
        return '敬请期待';
      }
      const type = task.type;
      switch (type) {
        case 'video':
          return '视频';
        case 'audio':
          return '音频';
        case 'live':
          return '直播';
        case 'text':
          return '图文';
        case 'ppt':
        case 'doc':
          return '文档';
        case 'testpaper':
          return '考试';
        case 'homework':
          return '作业';
        default:
          return '暂不支持此类型';
      }
    }
  },
  {
    name: 'filterTask',
    handler(task) {
      if (task.status !== 'published') {
        return '';
      }
      switch (task.type) {
        case 'video':
        case 'audio':
          if (task.mediaSource !== 'self' && task.type !== 'audio') {
            return '';
          }
          return ` | 时长:  ${formatTimeByNumber(task.length)}`;
        case 'live':
          // var endDate = getDate(task.endTime * 1000);
          // var startDate = getDate(task.startTime * 1000);
          // var nowDate = getDate();
          // if (endDate < nowDate) {
          //   if (task.activity.replayStatus == 'ungenerated') {
          //     return '  |  暂无回放';
          //   }
          //   return '  |  回放';
          // }
          // if (nowDate > startDate & nowDate < endDate) {
          //   return '  |  正在直播';
          // }
          // return `  |  开始:  ${formatTime(startDate)}`;
          return '';
        case 'text':
        case 'doc':
          return '';
        default:
          return '';
      }
    }
  },
  {
    name: 'filterTaskTime',
    handler(task) {
      if (task.status !== 'published') {
        return '敬请期待';
      }
      switch (task.type) {
        case 'video':
        case 'audio':
          if (task.mediaSource !== 'self' && task.type !== 'audio') {
            return '';
          }
          return `${formatTimeByNumber(task.length)}`;
        case 'live':
          const now = new Date().getTime();
          const startTimeStamp = new Date(task.startTime * 1000);
          const endTimeStamp = new Date(task.endTime * 1000);
          // 直播未开始
          if (now <= startTimeStamp) {
            return `${formatCompleteTime(startTimeStamp)}开始`;
          }
          if (now > endTimeStamp) {
            if (task.activity.replayStatus === 'ungenerated') {
              return '已结束';
            }
            return '回放';
          }
          return '直播中';
        // case 'testpaper':
        //   const nowTime = new Date().getTime();
        //   const testStartTime = new Date(task.startTime * 1000);
        //   // 考试未开始
        //   if (nowTime <= testStartTime) {
        //     return `${formatCompleteTime(startTimeStamp)}开始`;
        //   }
        //   return '';
        case 'text':
        case 'doc':
        case 'ppt':
        case 'testpaper':
        case 'homework':
          return '';
        default:
          return '暂不支持';
      }
    }
  },
  {
    name: 'filterJoinStatus',
    handler(code, type = 'course', vipAccessToJoin) {
      if (vipAccessToJoin) {
        return '会员免费学';
      }
      const targetType = {
        course: '课程',
        classroom: '班级'
      };
      switch (code) {
        case 'success':
        case 'user.not_login':
          code = '加入学习';
          break;
        case 'user.locked':
          code = '用户被锁定';
          break;
        case 'member.member_exist':
          code = '课程学员已存在';
          break;
        case `${type}.reach_max_student_num`:
          code = '学员达到上限';
          break;
        case `${type}.not_found`:
          code = '计划不存在';
          break;
        case `${type}.unpublished`:
          code = `${targetType[type]}未发布`;
          break;
        case `${type}.closed`:
          code = `${targetType[type]}已关闭`;
          break;
        case `${type}.not_buyable`:
          code = `${targetType[type]}不可加入`;
          break;
        case `${type}.buy_expired`:
          code = '购买有效期已过';
          break;
        case `${type}.expired`:
          code = '学习有效期已过';
          break;
        case `${type}.only_vip_join_way`:
        case 'course.only_vip_join_way': // type 为班级时，code显示为 'course.only_vip_join_way', 此处临时处理
          code = '只能通过VIP加入';
          break;
        default:
      }
      return code;
    }
  },
  {
    name: 'filterOrderStatus',
    handler(status) {
      switch (status) {
        case 'created':
          status = '去支付';
          break;
        case 'paying':
          status = '去支付';
          break;
        case 'success':
          status = '已付款';
          break;
        case 'refunded':
          status = '已退款';
          break;
        case 'finished':
          status = '交易成功';
          break;
        case 'closed':
          status = '交易关闭';
          break;
        case 'paid':
          status = '已付款';
          break;
        case 'refunding':
          status = '已付款';
          break;
        case 'fail':
          status = '已付款';
          break;
        default:
      }
      return status;
    }
  }
];

export default {
  install(Vue) {
    filters.map(item => {
      Vue.filter(item.name, item.handler);

      return item;
    });
  }
};
