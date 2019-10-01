
class Settings {

  constructor () {
    this.init();
  }

  init() {
    document.querySelector('#settings .loading_spinner').classList.add('hidden');
    document.querySelector('#settings').classList.remove('hidden');
  }
}

export default Settings;