import 'app/common/widget/qrcode';
import { isMobileDevice } from 'common/utils';
import Api from 'common/api';
import 'store';
import Vue from 'common/vue';
import closedAlert from './closedAlert.vue';


const WECHAT_SUBSCRIBE_INTRO = 'WECHAT_SUBSCRIBE_INTRO';

let $unfavorite = $('.js-unfavorite-btn');
let $favorite = $('.js-favorite-btn');
let $loginModal = $('#login-modal');
discountCountdown();
ancelRefund();

function ancelRefund() {
  $('.cancel-refund').on('click', function () {
    if (!confirm(Translator.trans('course_set.refund_cancel_hint'))) {
      return false;
    }
    $.post($(this).data('url'), function (data) {
      window.location.reload();
    });
  });
}

function discountCountdown() {
  var remainTime = parseInt($('#discount-endtime-countdown').data('remaintime'));
  if (remainTime >= 0) {
    var endtime = new Date(new Date().valueOf() + remainTime * 1000);
    $('#discount-endtime-countdown').countdown(endtime, function (event) {
      var $this = $(this).html(event.strftime(Translator.trans('course_set.show.count_down_format_hint')));
    }).on('finish.countdown', function () {
      $(this).html(Translator.trans('course_set.show.time_finish_hint'));
      setTimeout(function () {
        $.post(app.crontab, function () {
          window.location.reload();
        });
      }, 2000);
    });
  }
}

if ($favorite.length) {
  $favorite.on('click', function () {
    Api.favorite.favorite({
      data: {
        'targetType': $(this).data('targetType'),
        'targetId': $(this).data('targetId'),
      }
    }).then((res) => {
      $unfavorite.removeClass('hidden');
      $favorite.addClass('hidden');
    });
  });
}

if ($unfavorite.length) {
  $unfavorite.on('click', function () {
    Api.favorite.unfavorite({
      data: {
        'targetType': $(this).data('targetType'),
        'targetId': $(this).data('targetId'),
      }
    }).then((res) => {
      $favorite.removeClass('hidden');
      $unfavorite.addClass('hidden');
    });
  });
}

const fixButtonPosition = () => {
  const $target = $('.js-course-detail-info');
  const height = $target.height();
  const $btn = $('.js-course-header-operation');
  if (height > 240) {
    $btn.removeClass('course-detail-info__btn');
  }
};

$(document).ready(() => {
  fixButtonPosition();
});

const wechatIntro = () => {
  introJs().setOptions({
    steps: [{
      element: '.js-es-course-qrcode',
      intro: Translator.trans('course.intro.wechat_subscribe'),
    }],
    doneLabel: '确认',
    showBullets: false,
    showStepNumbers: false,
    exitOnEsc: false,
    exitOnOverlayClick: false,
    tooltipClass: 'es-course-qrcode-intro',
  }).start();
}

var $notificationEnable = $('#wechat_notification_type').val();
if ($notificationEnable == 'messageSubscribe' && !store.get(WECHAT_SUBSCRIBE_INTRO) && !isMobileDevice()) {
  store.set(WECHAT_SUBSCRIBE_INTRO, true);
  wechatIntro();
}


jQuery.support.cors = true;

Vue.config.productionTip = false;
if (app.lang == 'en') {
  const locale = local.default;
  itemBank.default.install(Vue, {locale});
}

new Vue({
  render: createElement => createElement(closedAlert)
}).$mount('#closedAlert');