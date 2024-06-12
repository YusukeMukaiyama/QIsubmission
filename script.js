// script.js
document.addEventListener('DOMContentLoaded', (event) => {
  const urlParams = new URLSearchParams(window.location.search);
  const uid = urlParams.get('uid');
  const pass = urlParams.get('pass');

  if (uid) {
      document.getElementById('uid').value = uid;
  }
  if (pass) {
      document.getElementById('pass').value = pass;
  }
});
