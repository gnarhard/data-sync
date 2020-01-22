# Data Sync
Multi-site compatible WordPress data syndicator. Syndicates post data, post meta, custom post types, custom ACF fields and data, and Yoast data with multiple authenticated sites.

<b>Installation</b>
<ol>
<li>Understand that this plugin changes permalink structure to /%postname%/ or it won't work. This will break the option in the backend if you try to change it yourself.</li>
<li>Install Advanced Custom Fields Pro</li>
<li>Install Custom Post Types UI</li>
<li>Set source site in plugin settings</li>
<li>Add connected sites</li>
<li>Double check everything has an SSL (https)</li>
</ol>

<b>Syncing</b>
<ol>
<li>The sync start date on connected sites checks for posts that have a publish date after the connected site's sync start date.</li>
</ol>
