import Helpers from '../Helpers.es6.js'

document.addEventListener('DOMContentLoaded', function () {

  if (DataSync.options.source_site) {

    if (document.getElementsByClassName('edit-post-header__settings').length) {
      if (document.getElementsByClassName('edit-post-header__settings')[0].getElementsByTagName('button').length) {

        let button_list = document.getElementsByClassName('edit-post-header__settings')[0].getElementsByTagName('button')

        for (let i = 0; i < button_list.length; i++) {

          if (('Publish…' === button_list[i].innerText) || ('Update' === button_list[i].innerText) || ('Publish' === button_list[i].innerText)) {

            let publishButton = button_list[i]
            publishButton.onclick = function (e) {
              e.preventDefault()
              let canonical_setting_value = 0

              let radios = document.getElementsByName('canonical_site')

              for (let i = 0, length = radios.length; i < length; i++) {
                if (radios[i].checked) {
                  // do whatever you want with the checked radio
                  canonical_setting_value = parseInt(radios[i].value)
                  // only one radio can be logically checked, don't check the rest
                  break
                }
              }

              if (0 === canonical_setting_value) {
                alert('Please choose a canonical site before proceeding.')
                e.stopImmediatePropagation()

              } else {
                let excluded_sites = Helpers.getSelectValues(document.getElementById('excluded_sites'))
                excluded_sites.forEach((excluded_site) => {

                  // console.log(excluded_sites)
                  // console.log(canonical_setting_value)
                  if (canonical_setting_value === parseInt(excluded_site)) {
                    alert('The canonical site you set is also excluded. Please choose another site.')
                    e.stopImmediatePropagation()

                  }
                })
              }
            }

          }
        }
      }
    }

  }

})