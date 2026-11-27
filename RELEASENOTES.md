## ℹ️ What is this?

This is the connector component for [Akeeba Panopticon](https://github.com/getpanopticon/panopticon), our self-hosted site monitoring software. You need to install it on your site to be able to monitor it with Akeeba Panopticon.

ℹ️ If you have a Joomla! 3 site please [look at the Joomla 3 connector's repository](https://github.com/getpanopticon/panopticon_connector_j3/releases/latest) instead.

ℹ️ If you have a WordPress site please [look at the WordPress connector's repository](https://github.com/getpanopticon/panopticon-connector-wordpress/releases/latest) instead.

## 🔎 Release highlights

* **✏️ Super User permissions are now required**. Panopticon manages your whole site, so every request to the connector must now use the API token of a Super User. Requests made with anyone else's token are refused with “403 Forbidden”. The connector's backend page and the `panopticon:token:get` command only give a connection token to Super Users. **If Panopticon reports 403 errors after you update, reconnect the site using a Super User's token.**

* **🐞 Core updates downloaded in several steps**. Two problems are fixed. When the download took more than one request, the package ended up one byte short and could not be extracted. With some HTTP/1.1 update servers, the download failed with “Could not find a file to download” when the package size had to be looked up.

* **✨ Component permissions**. The connector's Options page now has working Permissions settings. Super Users can use them to hide the connector from other user groups.

## 🖥️ System Requirements

* Joomla! 4.0 to 6.2, inclusive.
* PHP versions 7.2 to 8.6, inclusive.

**Important notes on system requirements**

PHP version compatibility refers to our connector, not Joomla! itself. PHP 8.1 or later required for Joomla! 5, PHP 8.3 or later required for Joomla! 6.

The connector will refuse to install on a PHP or Joomla! version outside the range above, telling you which version it found. If you need to run a newer version than we list, wait for the connector release which supports it.

Future versions of the connector will drop support for Joomla 5.3 and earlier versions. We strongly advise you to upgrade to Joomla! 5.4 or 6.x as soon as possible.

## 🧑🏽‍💻 Important note about the Joomla! API

Akeeba Panopticon Connector uses the Joomla! API application (the `/api` folder on your site). Around February 2023 there was a lot of unnecessary panic, leading some people to disable access to this folder through their `.htaccess` file. If you did that, you need to undo that change to re-enable access to the `/api` folder.

When Akeeba Panopticon connects to sites running on Joomla! 4 and later it makes use of code provided not only by its own connector, but also core Joomla! plugins in the `webservices` and `api-authentication` folders. Please make sure that the following plugins are enabled on your site:

* `Web Services - Panopticon` (provided by this connector).
* `Web Services - Installer` (provided by Joomla!).
* `API Authentication - Web Services Joomla Token` (required for secure, token-based authentication to the Joomla! API).

If any of these plugins are disabled, _or if its Access is set to anything other than Public_, you will run into connection problems with your site. The connector itself will try to detect and report these issues, but there are cases it might fail to identify the problem.

## 📋 CHANGELOG

* ✨ Component permissions: Super Users can now hide the connector from other user groups
* ✏️ The connector now requires Super User permissions for all its features [#31]
* 🐞 Core updates downloaded in several requests produced a package one byte short, failing extraction [#32]
* 🐞 Chunked core update downloads reported no file to download with some HTTP/1.1 update servers
* 🐞 A legacy configuration fallback used when clearing caches discarded its value (no visible effect)

Legend:
* 🚨 Security update
* ‼️ Important change
* ✨ New feature
* ✂️ Removed feature
* ✏️ Miscellaneous change
* 🐞 Bug fix
