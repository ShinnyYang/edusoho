import RewardPointNotify from 'app/common/reward-point-notify';
import { loginAgain } from './ajaxError'

let rpn = new RewardPointNotify();
let $document = $(document);

$document.ajaxSuccess(function (event, XMLHttpRequest, ajaxOptions) {
  rpn.push(XMLHttpRequest.getResponseHeader('Reward-Point-Notify'));
  rpn.display();
});

function handleAjaxStatus(status) {
  if (status === 401) {
    loginAgain()

    return false
  }

  return true
}

$document.ajaxError(function (event, jqxhr, settings, exception) {
  if (!handleAjaxStatus(jqxhr.status)) return

  let json = jQuery.parseJSON(jqxhr.responseText);
  let error = json.error;

  if (!error) return;

  let message = error.code ? error.message : Translator.trans('site.service_error_hint');

  switch (error.code) {
    case 4030102:
      window.location.href = '/login';
      break;
    case 11: //api 登陆失败状态码
    case 4040101: //普通请求异常状态码
      loginAgain();
      break;
    default:
      cd.message({
        type: 'danger',
        message: message
      });
  }
});

$document.ajaxSend(function (a, b, c) {
  // 加载loading效果
  let url = c.url;
  url = url.split('?')[0];
  let $dom = $(`[data-url="${url}"]`);
  if ($dom.data('loading')) {
    let loading;
    loading = cd.loading({
      isFixed: $dom.data('is-fixed')
    });

    let loadingBox = $($dom.data('target') || $dom);
    loadingBox.html(loading);
  }

  if (c.type === 'POST') {
    b.setRequestHeader('X-CSRF-Token', $('meta[name=csrf-token]').attr('content'));
    b.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  }
});

