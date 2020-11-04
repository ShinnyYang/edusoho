import * as messages from './rule-message';

export default {
  phone: {
    message: messages.PHONE_MESSAGE,
    validator(str) {
      const reg = /^1\d{10}$/;
      return reg.test(str);
    },
  },
  mobile: {
    message: messages.PHONE_MESSAGE,
    validator(str) {
      const reg = /^1\d{10}$/;
      return reg.test(str);
    },
  },
  encrypt_password: {
    message: messages.PASSWORD_REGISTER,
    validator(str) {
      const reg = /^[\S]{5,20}$/i;
      return reg.test(str);
    },
  },
  email: {
    message: messages.EMAIL_MESSAGE,
    validator(str) {
      const reg = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
      return reg.test(str);
    },
  },
};
