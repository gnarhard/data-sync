# data-sync
Synchronizes all post data, custom ACF fields, and Yoast data across multiple, authenticated sites.

<b>Installation:</b>
<ol>
<li>Add user <code>data_sync</code> with password <code>x&J8vQxxrI9@mnGUWaDpQtsO</code> to both source and receiver sites.</li>
<li>Make sure HTTP_AUTHORIZATION is enabled for headers by adding this to .htaccess:<br><code>RewriteEngine on<br>
      RewriteCond %{HTTP:Authorization} ^(.*)
      RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
      </code>
      </li>
<li>Install JWT Authentication for WP-API</li>
<li>Install Advanced Custom Fields Pro</li>
<li>Install Custom Post Types UI</li>
<li>Set source site in plugin settings</li>
<li>Add connected sites</li>
</ol>
