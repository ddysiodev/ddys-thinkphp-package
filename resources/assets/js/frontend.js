(function () {
  function text(value) {
    return value == null ? '' : String(value);
  }

  function handleSubmit(event) {
    var form = event.target;
    if (!form || !form.matches('[data-ddys-thinkphp-request-form]')) {
      return;
    }
    if (!window.fetch || !window.FormData) {
      return;
    }
    event.preventDefault();

    var status = form.querySelector('.ddys-thinkphp-status');
    var button = form.querySelector('button[type="submit"]');
    if (status) {
      status.textContent = '提交中...';
    }
    if (button) {
      button.disabled = true;
    }

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
      return response.json().catch(function () {
        return { success: false, message: '服务器返回了无效响应。' };
      });
    }).then(function (payload) {
      if (payload && payload.success !== false) {
        if (status) {
          status.textContent = '已提交，感谢反馈。';
        }
        form.reset();
        return;
      }
      if (status) {
        status.textContent = text(payload && payload.message ? payload.message : '提交失败，请稍后再试。');
      }
    }).catch(function () {
      if (status) {
        status.textContent = '网络请求失败，请稍后再试。';
      }
    }).then(function () {
      if (button) {
        button.disabled = false;
      }
    });
  }

  document.addEventListener('submit', handleSubmit);
}());
