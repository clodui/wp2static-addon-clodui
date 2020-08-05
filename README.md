# Clodui deployment add-on for WP2Static (WIP)

Add-on for WP2Static for deploying your WordPress website to [Clodui](https://www.clodui.com)

### Installation

1. Create plugin package from source code by running following command

   ```
   composer build wp2static-clodui COMPRESS_PHP_FILES
   ```

   This creates `wp2static-clodui.zip` under your `Downloads` directory.

2. Upload the ZIP to your WordPress plugins page within your dashboard.

3. Activate the plugin, then navigate to your WP2Static main plugin page to see
   the new deployment option available.
