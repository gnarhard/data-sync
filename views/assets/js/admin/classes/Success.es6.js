class Success {

  constructor() {

  }

  display_html( result, selector ) {
    let result_array = result.split('null');
    result_array.slice(-1)[0];
    let html = result_array.join();
    document.getElementById( selector + '_wrap').innerHTML = html;
    document.querySelector('#' + selector + ' .loading_spinner').classList.add('hidden');
  }
}

export default Success;