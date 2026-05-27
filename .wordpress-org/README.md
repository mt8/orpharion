# `.wordpress-org/`

Assets in this directory are uploaded to the WordPress.org SVN `/assets/` path by `10up/action-wordpress-plugin-deploy` when the **Deploy to WordPress.org** workflow is dispatched. They are **not** included in the plugin zip itself (see `.distignore`).

## Expected files

| File | Purpose |
| --- | --- |
| `banner-772x250.png` | Standard banner shown on the plugin's wp.org page. |
| `banner-1544x500.png` | High-DPI banner (2x) used on Retina-class displays. |

Optional future additions: `icon-128x128.png`, `icon-256x256.png`, `screenshot-1.png`, ….

Refer to the [WordPress.org plugin assets documentation](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/) for sizing and format requirements.
