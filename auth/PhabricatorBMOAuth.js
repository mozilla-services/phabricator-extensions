/**
 * @provides bmo-auth-js
 */

(function() {
  // Display the admin login form if "?admin"
  if((new URLSearchParams(window.location.search)).has('admin')) {
    var adminForm = document.querySelector('form[action="/auth/login/password:self/"]');
    if(adminForm) {
      adminForm.classList.add('bmo-show');
    }
  }
})();
