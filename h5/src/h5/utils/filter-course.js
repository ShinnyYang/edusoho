const getDisplayStyle = (data, listObj) => {
  const showStudentStr = listObj.showStudent
    ? `<span class="switch-box__state">
            <p style="color: #B0BDC9">${data.studentNum}人在学</p>
        </span>`
    : '';
  const price = data.price === '0.00'
    ? '<p style="color: #408FFB">免费</p>'
    : `<p style="color: #ff5353">¥ ${data.price}</p>`;

  if (listObj.typeList === 'classroom_list') {
    return {
      id: data.id,
      targetId: data.targetId,
      imgSrc: {
        url: data.cover.middle || '',
        className: 'e-course__img'
      },
      header: data.title,
      middle: {
        value: data.courseNum,
        html: `<div class="e-course__count">共 ${data.courseNum} 门课程</div>`
      },
      bottom: {
        value: data.price || data.studentNum,
        html: `<span class="switch-box__price">${price}</span>
                  <span class="switch-box__state"><p style="color: #B0BDC9">
                    ${data.studentNum}人在学</p>
                  </span>`
      }
    };
  }
  return {
    id: data.id,
    imgSrc: {
      url: data.courseSet.cover.middle || '',
      className: 'e-course__img'
    },
    header: data.courseSetTitle,
    middle: {
      value: data.title,
      html: `<div class="e-course__project text-overflow">
                  <span>${data.title}</span>
                </div>`
    },
    bottom: {
      value: data.price || data.studentNum,
      html: `<span class="switch-box__price">${price}</span>${showStudentStr}`
    }
  };
};
const getNewDisplayStyle = (data, listObj, platform) => {
  const dataPrice = Number(data.price2.amount);
  const primaryColor = {
    app: '#20B573',
    h5: '#408FFB'
  };
  let price;
  if (dataPrice > 0 && data.price2.currency === 'coin') {
    price = `<span style="color: #ff5353">${data.price2.coinAmount} ${data.price2.coinName}</span>`;
  } else if (dataPrice > 0 && data.price2.currency === 'RMB') {
    price = `<span style="color: #ff5353">¥ ${data.price2.amount}</span>`;
  } else {
    price = `<span style="color:${primaryColor[platform]}">免费</span>`;
  }

  if (listObj.typeList === 'classroom_list') {
    return {
      id: data.id,
      targetId: data.targetId,
      studentNum: listObj.classRoomShowStudent ? data.studentNum : null,
      imgSrc: {
        url: data.cover.middle || '',
        className: ''
      },
      header: data.title,
      middle: {
        value: data.courseNum,
        html: `<span>共 ${data.courseNum} 门课程</span>`
      },
      bottom: {
        value: data.price,
        html: `<span>${price}</span>`
      }
    };
  }
  return {
    id: data.id,
    studentNum: listObj.showStudent ? data.studentNum : null,
    imgSrc: {
      url: data.courseSet.cover.middle || '',
      className: ''
    },
    header: data.courseSetTitle,
    middle: {
      value: data.title,
      html: ` <span>${data.title}</span>`
    },
    bottom: {
      value: data.price,
      html: `<span>${price}</span>`
    }
  };
};
const courseListData = (data, listObj, uiStyle = 'old', platform = 'h5') => {
  // h5和app用了新版ui,小程序还是用旧版ui
  switch (listObj.type) {
    case 'price':
      if (uiStyle !== 'old') {
        return getNewDisplayStyle(data, listObj, platform);
      }
      return getDisplayStyle(data, listObj);

    case 'confirmOrder':
      return {
        imgSrc: {
          url: data.cover.middle || '',
          className: 'e-course__img'
        },
        header: data.title,
        middle: '',
        bottom: {
          value: data.coinPayAmount,
          html: `<span class="switch-box__price">
                  <p style="color: #ff5353">¥ ${data.coinPayAmount}</p>
                </span>`
        }
      };
    case 'rank':
      if (listObj.typeList === 'classroom_list') {
        return {
          id: data.id,
          targetId: data.targetId,
          imgSrc: {
            url: data.cover.middle || '',
            className: 'e-course__img'
          },
          header: data.title,
          middle: '',
          bottom: {
            value: data.courseNum,
            html: `<div class="e-course__count">共 ${data.courseNum} 门课程</div>`
          }
        };
      }
      return {
        id: data.id,
        imgSrc: {
          url: data.courseSet.cover.middle || '',
          className: 'e-course__img'
        },
        header: data.courseSetTitle,
        middle: {
          value: data.title,
          html: `<div class="e-course__project text-overflow">
                  <span>${data.title}</span>
                </div>`
        },
        bottom: {
          value: data.progress.percent,
          html: `<div class="rank-box">
                  <div class="progress round-conner">
                    <div class="curRate round-conner"
                      style="width:${data.progress.percent}%">
                    </div>
                  </div>
                  <span>${data.progress.percent}%</span>
                </div>`
        }
      };
    default:
      return 'empty data';
  }
};
export default courseListData;
