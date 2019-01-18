// 秒转化为时间
const formatTimeByNumber = time => {
  time = parseInt(time, 10);
  if (time < 0) {
    return time;
  }
  const hour = parseInt(time / 3600, 10);
  time %= 3600;
  const minute = parseInt(time / 60, 10);
  time %= 60;
  const second = time;
  if (hour <= 0) {
    return [minute, second].map(n => {
      n = n.toString();
      return n[1] ? n : `0${n}`;
    }).join(':');
  }
  return [hour, minute, second].map(n => {
    n = n.toString();
    return n[1] ? n : `0${n}`;
  }).join(':');
};

// 11-16
const formatSimpleTime = date => {
  const month = date.getMonth() + 1;
  const day = date.getDate();

  return [month, day].map(n => {
    n = n.toString();
    return n[1] ? n : `0${n}`;
  }).join('-');
};

// 2018-12-06
const formatFullTime = date => {
  const year = date.getFullYear();
  const month = date.getMonth() + 1;
  const day = date.getDate();

  return [year, month, day].map(n => {
    n = n.toString();
    return n[1] ? n : `0${n}`;
  }).join('-');
};

// 2018/12/06 12:03
const formatTime = date => {
  const year = date.getFullYear();
  const month = date.getMonth() + 1;
  const day = date.getDate();
  const hour = date.getHours();
  const minute = date.getMinutes();
  const second = date.getSeconds();
  return `${[year, month, day].map(n => {
    n = n.toString();
    return n[1] ? n : `0${n}`;
  }).join('/')} ${[hour, minute, second].map(n => {
    n = n.toString();
    return n[1] ? n : `0${n}`;
  }).join(':')}`;
};

// 2018-12-06 12:03
const formatCompleteTime = date => {
  const reg = new RegExp('/', 'g');
  const time = formatTime(date).replace(reg, '-');
  return time.slice(0, -3);
};

const dateTimeDown = date => {
  const now = new Date().getTime();
  if (now > date) {
    return '已到期';
  }
  const diff = parseInt((date - now) / 1000, 10);
  let day = parseInt(diff / 24 / 60 / 60, 10);
  let hour = parseInt((diff / 60 / 60) % 24, 10);
  let minute = parseInt((diff / 60) % 60, 10);
  let second = parseInt(diff % 60, 10);
  day = day ? `${day}天` : '';
  hour = hour ? `${hour}小时` : '';
  minute = minute ? `${minute}分` : '';
  second = second ? `${second}秒` : '';
  const time = day + hour + minute + second;
  return time;
};

// days（传时间戳）
const getOffsetDays = (time1, time2) => {
  const offsetTime = Math.abs(time1 - time2);
  return Math.floor(offsetTime / (3600 * 24 * 1e3));
};

export {
  formatTime,
  formatFullTime,
  formatSimpleTime,
  formatTimeByNumber,
  formatCompleteTime,
  dateTimeDown,
  getOffsetDays
};
