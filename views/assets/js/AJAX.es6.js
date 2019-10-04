class AJAX {

  static async post (url, data) {

    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-WP-Nonce': DataSync.api.nonce
      },
      body: JSON.stringify(data)
    })
    return await response.json()
  }

  static async get (url) {

    let response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-WP-Nonce': DataSync.api.nonce,
      },
    })

    return await response.json()
  }

  static async get_html (url) {

    let response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json', // FIXES HEADERS ALREADY SENT ISSUE.
        'Content-Type': 'text/html; charset=utf-8',
        'X-WP-Nonce': DataSync.api.nonce,
      },
    })

    return await response.text()

  }

  static async delete (url) {
    let response = await fetch(url, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-WP-Nonce': DataSync.api.nonce
      },
    })
    return await response.json()
  }

}

export default AJAX