
# commonWP

[![Build Status](https://www.travis-ci.org/dimadin/commonWP.svg?branch=master)](https://www.travis-ci.org/dimadin/commonWP)
[![Latest Stable Version](https://poser.pugx.org/dimadin/commonwp/version)](https://wordpress.org/plugins/commonwp/)

commonWP is a plugin that enables usage of free CDN for open source JavaScript and CSS files. It aims to be both lightweight and very secure.

## Usage

There are three ways to install commonWP:

 * You can get stable version from [WordPress.org Plugins Repository](https://wordpress.org/plugins/commonwp/).
 * You can require [`dimadin/commonwp` package](https://packagist.org/packages/dimadin/commonwp) in your project.
 * You can clone this repository, then run `composer install`.

After activation, there are no any settings. It will fill up its cache in the background, and that may take some time depending on number of resources site uses.

You may change some default commonWP settings by using [WordPress hooks](https://developer.wordpress.org/plugins/hooks/) which are listed below.

## FAQ

- What files can be rewritten?
  - All files from WordPress core, unless you use development version of WordPress.
  - All files from plugins hosted by [WordPress.org Plugins Repository](https://wordpress.org/plugins/), unless author of specific plugin doesn't use [SVN tags for releasing](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/#tagging-new-versions).
  - All files from themes hosted by [WordPress.org Themes Repository](https://wordpress.org/themes/).
  - All files from plugins and themes hosted on [GitHub](https://github.com/) that support [GitHub Updater](https://github.com/afragen/github-updater).
  - All files marked as available on [npm](https://www.npmjs.com/) in any type of theme, plugin, or MU plugin.
- Why jsDelivr?
  - [jsDelivr](https://www.jsdelivr.com/) is a public, open source CDN, free to use for everyone, with no bandwidth limits, focused on performance, reliability, and security, built for production. It has multiple levels of failover, hundreds of points of presence around the world, valid ICP license issued by the Chinese government with locations directly in Mainland China.
  Product is mature, working from 2012.
  All files are delivered over HTTPS with HTTP/2, they use HTTP headers for long caching (including the immutable HTTP header), from some locations even delivered with Brotli compression.
- Is commonWP multisite compatible?
  - Yes, it is.
- Is commonWP [Bedrock](https://roots.io/bedrock/) compatible?
  - Yes, it is.
- How safe is this?
  - The approach used in this plugin is safer then in any other plugin that enables usage of any CDN. First, commonWP will only rewrite file to point to one on jsDelivr if that remote file is identical to local one. Second, during comparison, commonWP generates [subresource identity hash](https://developer.mozilla.org/en-US/docs/Web/Security/Subresource_Integrity) of remote jsDelivr file and includes that hash in page's source code so browser won't load remote file if it doesn't have exactly the same hash.
  In case that your site doesn't display pages as expected because of some problem with jsDelivr (or any problem that you suspect that might be caused by commonWP), you can simply disable commonWP just like any other plugin. It causes no permanent changes to either your database or files, except that it caches file paths that can be rewritten to the database, but smartly deletes that cache during plugin deactivation.
- Will my site be faster when I start using commonWP?
  - There are several factors that influence how much speed and for which visitors you might get:
    - How many of your files are available on jsDelivr and can be rewritten. The more files on jsDelivr, the more speed; the more files used by page are rewritten to jsDelivr, the more speed.
    - Further your site's visitors are from server your site is hosted on, the more speed they would get.
    - Slower your server is, the more speed they would get.
    - If your visitors already visited WordPress site(s) with commonWP activated, there is more chance that some of the files you use on your site are already cached by them so the more speed they would get.
- Are there any benefits other than speed?
  - There are indirect benefits of using public CDNs. For example, almost all WordPress sites on front end use `jquery.js`, `jquery-migrate.min.js`, `wp-embed.min.js`, `wp-emoji-release.min.js` files. Those files are the same on each site but they are distributed separately for each site. It means that you download the same files over and over again even though you already have them in your browser cache. Now imagine that instead of distributing separately, those files were distributed from the same location for each site. Now you would download files just once and whenever you go to another site you would use them from your browser cache. This means that those sites would load faster (you don't download same files again), use less data (less bandwidth to pay by site and less used data from your data cap), use less space in the browser cache (which could be used for caching other stuff), make less load on their servers (and all of this makes things more energy efficient).
  This is the idea behind commonWP. Since most of the files used by WordPress sites are open source (which means available on public CDNs), why not distributing them from the central location and having all benefits listed above?
  (This how name came: _*Common* *W*ord*P*ress_.)
- What if I already use CDN?
  - commonWP will try to rewrite files that exist on jsDelivr. For other files, you will fallback to CDN you use. This only works if plugin you use for CDN is not running before commonWP.
- What if I use Autoptimize or other plugin for automatic concatenation or minification?
  - If that plugin is not running before commonWP, you will automatically concatenate or minify only files not rewritten by commonWP.
- Are there any options that I can configure?
  - Not in the UI, though you can configure several things via WordPress filters if your setup needs it. This is only for developers that know what they are doing. See below for some examples and checkout full code reference at [http://api.milandinic.com/commonwp/](http://api.milandinic.com/commonwp/).
- What about privacy, GDPR?
  - By using commonWP, some files on your site's pages would be loaded from [jsDelivr](https://www.jsdelivr.com/). Privacy policy for using jsDelivr CDN is on https://www.jsdelivr.com/privacy-policy-jsdelivr-net. That page gives detailed information about their data processing.

## Marking files that exist in npm

Some external libraries that you use in your theme or plugin might be also available on npm. It is preferred to rewrite using `/npm` path on jsDelivr and marking those files as available on npm enables that. This works even for your custom or premium plugins/themes.

What you need to know is handle by which you registered that file in WordPress and you need to know name of npm package and path from package's root, including file name but without extension. Separate filters are used for JavaScript and CSS files. In both cases, you extend default array with key that is dependencies handle and value that is array of settings of that file on npm.

In this example, we would use Bootstrap files but also mark to commonWP that they are available on npm.

```php
add_action( 'wp_enqueue_scripts', function() {
	// Use the .min version if SCRIPT_DEBUG is turned off.
	$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	wp_enqueue_script( 'popper.js', get_template_directory_uri() . "/assets/js/popper{$min}.js", [], '1.14.0', true );
	wp_enqueue_script( 'bootstrap', get_template_directory_uri() . "/assets/js/bootstrap{$min}.js", [ 'jquery', 'popper.js' ], '4.1.0', true );

	wp_enqueue_style( 'bootstrap', get_template_directory_uri() . "/assets/css/bootstrap{$min}.css", [], '4.1.0' );
} );

add_filter( 'npm_packages_scripts', function( $scripts ) {
	// For file: https://cdn.jsdelivr.net/npm/bootstrap@4.1.0/dist/js/bootstrap.min.js
	$scripts['bootstrap'] = [ // This is the handle used when script is registered in WordPress.
		'package'  => 'bootstrap',  // Slug of npm package.
		'file'     => 'dist/js/bootstrap', // Path to file, excluding extension (it is always either .js or .css).
		'minified' => '.min', // If file has both minified and unminified version, and if simple suffix can be added to the base.
	];

	// For file: https://cdn.jsdelivr.net/npm/popper.js@1.14.0/dist/umd/popper.min.js
	$scripts['popper.js'] = [
		'package'  => 'popper.js',
		'file'     => 'dist/umd/popper',
		'minified' => '.min',
	]

	return $scripts;
} );

add_filter( 'npm_packages_styles', function( $styles ) {
	// For file: https://cdn.jsdelivr.net/npm/bootstrap@4.1.0/dist/css/bootstrap.min.css
	$styles['bootstrap'] = [
		'package'  => 'bootstrap',
		'file'     => 'dist/css/bootstrap',
		'minified' => '.min',
	];

	return $styles;
} );
```

Note that your local files and remote files must be identical, Otherwise, commonWP won't use remote files and will not rewrite. To make sure that files are identical, you can get files for local directory from npm package.

Also note that when using filter you shouldn't use file extension and you shouldn't enter package version (but you should enter correct file version when registering dependency).

You can skip `minified` key if you don't have development version locally and just append `.min` to the value of `file`.

Using these filters is safe since they are triggered only when commonWP is used.

## Using commonWP with object cache enabled

By default, commonWP stores its data in a standard option value. If you have object cache enabled, you can skip database and directly use object cache. Other than not having database calls, this also reduces size of autoloaded options cache when used on single site installations.

In this example, transients functions are used because transients do not use database when object cache is enabled. Network transients are used because they use global cache group on multisite installations.

```php
add_filter( 'commonwp_store_callback', function( $function, $base ) {
	if ( 'update' === $base ) {
		$base = 'set';
	}

	return $base . '_site_transient';
}, 10, 2 );
```

If you start using this approach, deactivate commonWP plugin, add this code and activate again, or delete option `commonwp_data` (on multisite installation that is network option) via other means to reduce size of autoloaded options cache.

## Deciding should path be rewritten

It is possible to avoid rewriting of some file paths by using filter. Those file paths won't be processed at all so there will be no cache in the database. This can be used, for example, for custom or premium plugins/themes that you know for sure that they don't have corresponding files on jsDelivr, even in `/npm` path.

```php
add_filter( 'commonwp_should_rewrite', function( $rewrite, $relative_path, $src, $handle, $type ) {
	if ( false !== strpos( $src, '/wp-content/plugins/my-custom-plugin' ) ) {
		return false;
	}

	return $rewrite;
}, 10, 5 );
```

## Returning path before storage lookup

You can return remote file path before touching commonWP storage or before regular processing. For example, some files used in MU Plugin might exist in a custom GitHub repository and you might want to use that.

```php
add_filter( 'commonwp_get_src', function( $pre, $relative_path, $src, $handle, $type ) {
	return \Some\Custom\Function( $relative_path );
}, 10, 5 );
```

## Cache Expiration

When commonWP sees new file, it processes that file in the background task to see if identical copy exists on jsDelivr or not. Result of that processing is cached so that next time commonWP immediately knows should it rewrite or not. Expiration time of cache of each file path (TTL) is different based on result of processing and of type of file, and can be changed using filters.

 - `commonwp_npm_path_ttl` - For files available on `/npm` path. Default one week.
 - `commonwp_emoji_npm_path_ttl` - For emoji directory available on `/npm` path. Default one week.
 - `commonwp_plugin_path_ttl` - For files available on `/wp/plugins` path. Default one day.
 - `commonwp_theme_path_ttl` - For files available on `/wp/themes` path. Default three days.
 - `commonwp_github_path_ttl` - For files available on `/gh` path. Default two days.
 - `commonwp_inactive_path_ttl` - For files that aren't on jsDelivr. Default one day.
 - `commonwp_inactive_path_ttl_for_recently_upgraded_core` - For WordPress core files that aren't on jsDelivr when WordPress core was recently upgraded. Default 15 minutes.

## Exposing private details

Because of the way jsDelivr works, if file is not loaded from `/npm` path, it is showing exact version of core, plugin, or theme in the full path to the file. This isn't problem as versions could already be discovered via other means. In case that core, plugin, or theme has an update available, you can tell commonWP not to cache paths of files of that core, plugin, or theme with their installed version and instead use their latest version.
Note that your site won't be secured by this, you just won't explicitly show installed versions in jsDelivr URLs, but that doesn't mean that you won't show versions elsewhere. You should keep core, plugins, and themes up to date anyway, and upgrade to newer version when they are available. Also note that since cache might last for up to two days, jsDelivr URLs might still be used and thus versions shown.

To use latest version in URL when caching paths when there is an update available, you can do that by type. Argument with `\dimadin\WP\Plugin\commonWP\Process` object is passed, and thus file name, theme or plugin. This will have no effect if file is marked as available on npm.

For core files, there are two filters: one is used when you have latest version in your branch but there is new branch available (major), second is used when you don't use latest version in a branch (minor).

```php
add_filter( 'commonwp_process_core_with_major_update', '__return_false' );
add_filter( 'commonwp_process_core_with_minor_update', '__return_false' );
add_filter( 'commonwp_process_plugin_with_update', '__return_false' );
add_filter( 'commonwp_process_theme_with_update', '__return_false' );
```

### Rewriting files whose remote copy on npm isn't identical

While it sounds non-logical to rewrite to remote file that are not identical as local one, there are valid cases where this could happen.

For example, some libraries used in WordPress core do not provide development version. This means that it won't rewrite to development version available on npm but to identical, minified version. Or, when minified version in npm package uses different tool for minification from tool in local version so files aren't identical.

You can rewrite to those remote files even if comparison fails by using filter. Note that this is only available for files marked as available in npm, and that it never happens by default, you must enable it via filter. Also note that remote file must exist before triggering this hook.

```php
add_filter( 'commonwp_npm_compare_with_local', function( $compare, $process ) {
	if ( ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) && 'script' == $process->type ) {
		switch ( $process->handle ) {
			case 'jquery-core' :
			case 'underscore':
			case 'backbone':
				$compare = false;

				break;
		}
	}

	return $compare;
}, 10, 2 );
```

## Disabling subresource integrity

If you are sure that jsDelivr won't be compromised and you want to reduce page size, you can disable subresource integrity, either globally or on file by file basis.

### Globally

You can remove it for either scripts, styles, or both.
```php
add_action( 'plugins_loaded', function() {
	remove_filter( 'script_loader_tag', [ 'dimadin\WP\Plugin\commonWP\SRI', 'script' ], 567, 3 );
	remove_filter( 'style_loader_tag',  [ 'dimadin\WP\Plugin\commonWP\SRI', 'style'  ], 567, 3 );
}, 15 );
```

### Per file

```php
add_filter( 'commonwp_add_subresource_integrity', function( $enable, $process ) {
	if ( 'jquery-core' == $process->handle ) {
		return false;
	}

	return $enable;
}, 10, 2 );
```
## WP-CLI

commonWP implements the following commands:

### wp commonwp clean

Delete from commonWP store.

#### wp commonwp clean all

Delete all data that commonWP is using.

~~~
wp commonwp clean all
~~~

**EXAMPLES**

    $ wp commonwp clean all
    commonWP data was deleted.


#### wp commonwp clean expired

Delete paths whose TTL has passed.

~~~
wp commonwp clean expired
~~~

**EXAMPLES**

    $ wp commonwp clean expired
    Expired paths were deleted.

#### wp commonwp clean starting-with <path>

Delete paths that start with requested path.

~~~
wp commonwp clean starting-with <path>
~~~

**OPTIONS**

	<path>
		Path that paths should start with.

**EXAMPLES**

    # Delete paths that are from /wp-admin folder.
    $ wp commonwp starting-with /wp-admin
    Paths that start with /wp-admin were deleted.

### wp commonwp paths

Get stored paths data.

#### wp commonwp paths list

List all type of paths.

~~~
wp commonwp paths list
~~~

**EXAMPLES**

    $ wp commonwp paths list

#### wp commonwp paths list active

List paths that can be rewritten.

~~~
wp commonwp paths list active
~~~

**EXAMPLES**

    $ wp commonwp paths list active

#### wp commonwp paths list inactive

List paths that are not rewritten.

~~~
wp commonwp paths list inactive
~~~

**EXAMPLES**

    $ wp commonwp paths list inactive

#### wp commonwp paths list queue

List paths that that should be processed.

~~~
wp commonwp paths list queue
~~~

**EXAMPLES**

    $ wp commonwp paths list queue

### wp commonwp queue

Process queued paths.

#### wp commonwp queue process

Process all waiting paths.

~~~
wp commonwp queue process
~~~

**EXAMPLES**

    $ wp commonwp queue process
    Queue was processed.
