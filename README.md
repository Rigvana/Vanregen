# Van Regen WordPress Site

Local WordPress site with a React and TypeScript frontend bundled into a custom theme.

## Local Setup

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open `http://localhost/VanRegen/wp-admin/setup-config.php` and connect WordPress to the local database:
   - Database: `vanregen_wp`
   - Username: `root`
   - Password: leave blank for the default local XAMPP database, unless your MySQL root account has a password
   - Database host: `localhost`
   - Table prefix: `wp_`
3. Complete the WordPress installer in the browser and create your own administrator account/password.
4. In WordPress Admin, activate **Van Regen Content** under Plugins, then activate the **Van Regen** theme under Appearance > Themes. The plugin seeds the ten page routes and four editable service entries on activation.
5. Set **Settings > Permalinks** to **Post name** and save once so nested service routes resolve.
6. Configure **Settings > Van Regen SMTP** with an email app password, enable SMTP, and use **Send test email** to verify delivery. Contact and quote inquiries are stored in **WordPress Admin > Website Inquiries** even when email delivery is unavailable.

## Frontend Build

From `wp-content/themes/vanregen`:

```powershell
npm.cmd install
npm.cmd run typecheck
npm.cmd run build
```

The theme loads the generated Vite manifest and assets from `assets/dist`. Run `npm.cmd run dev -- --host 127.0.0.1` for the standalone frontend preview; WordPress API-backed content and inquiry submission require the WordPress site to be installed and active.

## Content Notes

The supplied TSX is a visual and copy reference. Verify all regulatory, certification, carbon-impact, tree-planting, project-result, and contact claims with the company owner before publishing. The sustainability estimator is explicitly illustrative and is not an emissions or carbon-credit calculation.
