class AJAX {

  constructor() {

  }

  static async post(data) {
    console.log('hi');
      const rawResponse = await fetch(DataSync.api.url, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-WP-Nonce': DataSync.api.nonce
        },
        body: JSON.stringify(data)
      });
      const content = await rawResponse;

      jQuery( '#feedback' ).html( '<p>' + DataSync.strings.saved + '</p>' );
      console.log(content);
  }

  static async get(url) {
      const rawResponse = await fetch(url, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-WP-Nonce': DataSync.api.nonce
        }
      });
      const content = await rawResponse;

      jQuery( '#feedback' ).html( '<p>' + DataSync.strings.saved + '</p>' );
      console.log(content);
  }


}

export default AJAX;