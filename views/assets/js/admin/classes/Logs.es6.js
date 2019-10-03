import AJAX from '../../AJAX.es6'
import Success from './Success.es6'

class Logs {
  constructor () {
    this.init()
  }

  init () {
    let self = this
    document.querySelector('#debug_log .loading_spinner').classList.add('hidden')

    if (document.getElementById('error_log')) {

      document.getElementById('refresh_error_log').onclick = function (e) {
        self.refresh_log()
      }
    }
  }

  refresh_log () {
    AJAX.get(DataSync.api.url + '/log/get').then(function (result) {
      if (JSON.parse(result.html) !== document.getElementById('error_log').innerHTML) {
        document.getElementById('error_log').innerHTML = JSON.parse(result.html)
        new Logs()
      }
    })
  }

}

export default Logs