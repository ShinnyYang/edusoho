import { Dialog } from 'vant';
/**
 * 异常离开或者页面刷新考试监控，超时自动提交，未超时继续做题
 * promise函数，reject代码要交卷，resolve代表继续做题
 */

export default {
  methods: {
    // 继续答题或交卷
    canDoing(result, userId) {
      return new Promise((resolve, reject) => {
        if (result && result.status === 'doing') {
          // 获取localstorge数据
          const answerName = `${userId}-${result.id}`;
          // const timeName = `${userId}-${result.id}-time`;
          let answer = JSON.parse(localStorage.getItem(answerName));
          // const time = Number(localStorage.getItem(timeName));

          // 本地是否有answer缓存，没有则为一个空数组
          if (answer) {
            answer = Object.keys(answer).forEach(key => {
              answer[key] = answer[key].filter(t => t !== '');
            });
          } else {
            answer = {};
          }

          Dialog.confirm({
            title: '提示',
            cancelButtonText: '放弃考试',
            confirmButtonText: '继续考试',
            message: '您有未完成的考试，是否继续？',
          })
            .then(() => {
              // 如果有时间限制 且超出时间限制，自动交卷
              if (Number(result.limitedTime) > 0) {
                const alUsed = Math.ceil(
                  (new Date().getTime() - result.beginTime * 1000) / 1000 / 60,
                );
                if (Number(alUsed) > Number(result.limitedTime)) {
                  // endTime： 如果已经超时，结束时间=开始时间+限制时间，否则结束时间是当前时间
                  const endTime =
                    Number(result.beginTime * 1000) +
                    Number(result.limitedTime * 60 * 1000);
                  reject({ answer, endTime });
                  return;
                }
              }

              resolve();
            })
            .catch(() => {
              const endTime = null;
              reject({ answer, endTime });
            });
        }
      });
    },
  },
};
