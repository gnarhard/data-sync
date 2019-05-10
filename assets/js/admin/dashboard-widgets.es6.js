import AJAX from './AJAX.es6.js';

document.addEventListener("DOMContentLoaded", function() {
  document.getElementById('save_push_enabled_post_types').onclick = function(e) {
    e.preventDefault();

    let data = {};
    let input_name = document.getElementById('push_enabled_post_types').getAttribute('name').replace(/[^a-z0-9_]/gi,'');
    // let input_name = document.getElementById('enabled_post_types').getAttribute('name');
    data[input_name] = getSelectValues(document.getElementById('push_enabled_post_types'));

    AJAX.post(data);
  }
});

function getSelectValues(select) {
  var result = [];
  var options = select && select.options;
  var opt;

  for (var i=0, iLen=options.length; i<iLen; i++) {
    opt = options[i];

    if (opt.selected) {
      result.push(opt.value || opt.text);
    }
  }
  return result;
}