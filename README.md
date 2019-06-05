# Data Sync
Synchronizes all post data, custom ACF fields, and Yoast data across multiple, authenticated sites.

<b>Installation:</b>
<ol>
<li>Add user <code>data_sync</code> with password <code>x&J8vQxxrI9@mnGUWaDpQtsO</code> to both source and receiver sites.</li>
<li>Make sure HTTP_AUTHORIZATION is enabled for headers by adding this to .htaccess:<br>
<code>RewriteEngine on<br>
      RewriteCond %{HTTP:Authorization} ^(.*)<br>
      RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]</code>
      </li>
<li>Install JWT Authentication for WP-API
<ol>
<li>The JWT needs a secret key to sign the token. This secret key must be unique and never revealed.
    To add the secret key, edit your wp-config.php file and add a new constant called JWT_AUTH_SECRET_KEY.
    <code>define('JWT_AUTH_SECRET_KEY', 'your-top-secret-key');</code>
    You can use a string from here https://api.wordpress.org/secret-key/1.1/salt/</li>
    <li>To enable the CORs Support edit your wp-config.php file and add a new constant called JWT_AUTH_CORS_ENABLE.
    <code>define('JWT_AUTH_CORS_ENABLE', true);</code></li></li>
</ol>
</li>
<li>Install Advanced Custom Fields Pro</li>
<li>Install Custom Post Types UI</li>
<li>Set source site in plugin settings</li>
<li>Add connected sites</li>
</ol>
