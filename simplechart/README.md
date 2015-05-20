# Simplechart for WordPress

Simplechart lets anyone quickly create interactive data visualizations that are easy to embed in any webpage.

### Technical overview

The plugin sets up a custom post type for Charts and launches the Simplechart app inside an iframe. After the user creates a chart through the JS app in the iframe, all the info needed to recreate it (data and settings/options) is sent via postMessage back to the parent page. Then it gets saved in postmeta when the WordPress post is saved.

When the post is rendered on the front end, this same data is used to bootstrap redrawing the same chart.

### Installation for WordPress.com VIP themes

1. Install and activate [Media Explorer](https://github.com/Automattic/media-explorer) in your development environment. It is loaded automatically as part of the platform on WordPress.com.
1. Make sure that your VIP plugins repo is up to date. You can probably do this like `$ svn up` from `broadway/themes/vip/plugins`
1. Add this line in your theme's `functions.php`:

````
wpcom_vip_load_plugin( 'simplechart' );
````

### Installation for non-VIP themes

1. Install and activate [Media Explorer](https://github.com/Automattic/media-explorer)
1. Install and activate the Simplechart plugin

### Usage

1. Your WP Admin area should now have a custom post type for Charts.
1. Click the "Launch Simplechart App" button to create a new chart.
1. When you're happy with your new chart, click "Send to WordPress" button
1. Save the post in WordPress
1. You can now embed the Chart in any post by selecting it from the Charts section in the Media Manager, which will drop a shortcode into the post content.

### Update script for Simplechart web app

In the unlikely event that you need to update the version of the app that lives in the plugin, do this from the root of the `wordpress-simplechart` plugin directory, either in your theme or in `wp-plugins`:

````
$ npm install
$ node simplechart-update.js
````

The command accepts two arguments:

`--token=<your GitHub token>` A [GitHub access token](https://github.com/settings/tokens) is **required**. You can provide it with the `--token` argument or by placing it in a text file `github_token.txt` in this directory.

`--deploy-mode` deletes Git files, Node modules, and other stuff not necessary for deploying _and updating_ the plugin. **Use with caution!** Note that `--deploy-mode` does not require a value, but you can specify `--deploy-mode=vip`. This will skip the check for the Media Explorer plugin, which is part of the platform on WordPress.com VIP.

### Available WordPress filters

##### simplechart_web_app_url

URL of the Simplechart web app. This is used to locate the `assets/widget/loader.js` script (unless overridden by the `'simplechart_loader_js_url'` filter) and then by `loader.js` to find `assets/widget/js/app.js`.
````
http://www.mysite.com/wp-content/plugins/wordpress-simplechart/app
````

##### simplechart_loader_js_url

URL of the JS file used to render charts on the front-end. Override the default location of `loader.js` by providing the full URL of the script.
````
http://www.mysite.com/wp-content/plugins/wordpress-simplechart/app/assets/widget/loader.js
````

##### simplechart_web_app_iframe_src

Set the `src` attribute of the iframe for creating/editing charts in wp-admin. Defaults to root-relative for postMessage security reasons, e.g.
````
/wp-content/plugins/wordpress-simplechart/app/#/simplechart
````