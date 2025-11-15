=== VESSOT Secure Portable Data ===
Contributors: vessot
Tags: encryption, security, data storage, api
Requires at least: 5.8
Tested up to: 6.8.3
Requires PHP: 8.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

# VESSOT Secure Portable Data - WordPress Plugin

Zero visibility data storage with client-side encryption via the VESSOT platform.

## Description

VESSOT Secure Portable Data provides a secure way to store and retrieve encrypted data via the VESSOT API. All encryption happens client-side before data is transmitted, ensuring true zero-knowledge data storage.

You need to create an account at https://vessot.tech/ before you can use this plugin.

## Features

- Client-side AES-256-GCM encryption
- Store, retrieve, update, and destroy encrypted data
- Support for nested objects and arrays
- WordPress native HTTP API (no external dependencies)

## Requirements

- PHP 8.2 or higher
- OpenSSL PHP extension

## Installation

### Method 1: Install via WordPress Admin (Recommended)

1. Download the plugin as a ZIP file
2. In your WordPress admin, go to **Plugins > Add New > Upload Plugin**
3. Click **Choose File** and select the downloaded ZIP file
4. Click **Install Now**
5. After installation, click **Activate Plugin**
6. Configure your environment variables (see Configuration section)

### Method 2: Manual Installation

1. Download and extract the plugin ZIP file
2. Upload the entire plugin folder to `/wp-content/plugins/`
3. Go to **Plugins** in your WordPress admin
4. Find "VESSOT Secure Portable Data" and click **Activate**
5. Configure your environment variables (see Configuration section)

## Setup

Read about the simple account creation steps at https://vessot.tech/how-it-works.

Technical implementation is quick and easy - see the setup guide at https://vessot.tech/setup.

## Configuration

The plugin requires two environment variables to be set at the server level. This approach ensures credentials never touch your filesystem or version control.

### Required Environment Variables

#### VESSOT_INT_TOKEN
Your VESSOT API integration token for authentication.

#### VESSOT_CRYPT_KEY
A 32-byte encryption key for client-side encryption.

### Generating an Encryption Key

You can generate a secure encryption key using the plugin's helper function. Run this once in your WordPress environment:

```php
$vessotData = vessot_secure_portable_data();
$encryptionKey = $vessotData->cryptKeyGenerate(); // Copy this value to use as VESSOT_CRYPT_KEY
```

### Setting Environment Variables

Choose the method that matches your server setup:

#### Apache

Add to your virtual host configuration or `.htaccess`:

```apache
SetEnv VESSOT_INT_TOKEN "your-integration-token-here"
SetEnv VESSOT_CRYPT_KEY "your-encryption-key-here"
```

#### Nginx with PHP-FPM

Add to your PHP-FPM pool configuration (usually `/etc/php/8.2/fpm/pool.d/www.conf`):

```ini
env[VESSOT_INT_TOKEN] = your-integration-token-here
env[VESSOT_CRYPT_KEY] = your-encryption-key-here
```

Then add to your Nginx server block:

```nginx
location ~ \.php$ {
    fastcgi_param VESSOT_INT_TOKEN $VESSOT_INT_TOKEN;
    fastcgi_param VESSOT_CRYPT_KEY $VESSOT_CRYPT_KEY;
    # ... other fastcgi_param directives
}
```

#### Docker

Add to your `docker-compose.yml`:

```yaml
services:
  wordpress:
    environment:
      - VESSOT_INT_TOKEN=your-integration-token-here
      - VESSOT_CRYPT_KEY=your-encryption-key-here
```

Or use a `.env` file (excluded from version control):

```bash
VESSOT_INT_TOKEN=your-integration-token-here
VESSOT_CRYPT_KEY=your-encryption-key-here
```

#### Shared Hosting / cPanel

Many hosting providers offer environment variable management through their control panel. Check your hosting provider's documentation for "Environment Variables" or "PHP Configuration".

#### Local Development

For local development, you can use:

**Option 1: System environment variables**
```bash
export VESSOT_INT_TOKEN="your-dev-token"
export VESSOT_CRYPT_KEY="your-dev-key"
```

**Option 2: PHP-FPM configuration** (see Nginx section above)

### Security Best Practices

- Use different tokens and keys for each environment (development, staging, production)
- Never commit environment variables to version control
- Limit access to server configuration files

## Usage

### Basic Example

```php
// Get an instance
$vessotData = vessot_secure_portable_data();

// Store encrypted data
$result = $vessotData->store('your_unique_storage_key', [
    'theme' => 'dark',
    'notifications' => true,
    'email' => 'user@example.com'
]);

// Retrieve and decrypt data
$result = $vessotData->show('your_unique_storage_key');
if ($result['success']) {
    $settings = $result['value'];
    echo $settings['theme']; // 'dark'
}

// Update data
$result = $vessotData->update('your_unique_storage_key', [
    'theme' => 'light',
    'notifications' => false,
    'email' => 'user@example.com'
]);

// Partial update (update specific attributes)
$result = $vessotData->update('your_unique_storage_key', null, [
    'theme' => 'light'
]);

// Retrieve specific attribute
$result = $vessotData->show('your_unique_storage_key', 'theme');

// Delete data
$result = $vessotData->destroy('your_unique_storage_key');
```

### Using in WordPress Hooks

```php
// Store user preferences on profile update
add_action('profile_update', function($user_id) {
    $vessotData = vessot_secure_portable_data();
    $preferences = get_user_meta($user_id, 'preferences', true);

    $result = $vessotData->store("user_preferences_{$user_id}", $preferences);

    if (!$result['success']) {
        error_log('Failed to store preferences: ' . $result['error']);
    }
});

// Retrieve preferences on login
add_action('wp_login', function($user_login, $user) {
    $vessotData = vessot_secure_portable_data();
    $result = $vessotData->show("user_preferences_{$user->ID}");

    if ($result['success']) {
        update_user_meta($user->ID, 'preferences', $result['value']);
    }
}, 10, 2);
```

## API Reference

### store($key, $value)

Store encrypted data with a unique key.

**Parameters:**
- `$key` (string): Unique identifier for the data
- `$value` (mixed): Data to encrypt and store (can be string, or data array)

**Returns:** Array with `success`, `error`, `code`, and `value` keys

### show($key, $attribute = null)

Retrieve and decrypt stored data.

**Parameters:**
- `$key` (string): Unique identifier for the data
- `$attribute` (string|null): Optional specific attribute to retrieve

**Returns:** Array with `success`, `error`, `code`, and `value` keys

### update($key, $value = null, $attributes = null)

Update existing encrypted data.

**Parameters:**
- `$key` (string): Unique identifier for the data
- `$value` (mixed): New value (for full update)
- `$attributes` (array|null): Specific attributes to update (for partial update)

**Returns:** Array with `success`, `error`, `code`, and `value` keys

### destroy($key, $attributes = null)

Delete stored data.

**Parameters:**
- `$key` (string): Unique identifier for the data
- `$attributes` (mixed|null): Optional specific attributes to delete

**Returns:** Array with `success`, `error`, `code`, and `value` keys

### cryptKeyGenerate()

Generate a new encryption key. Returns empty string if key already exists in environment.

**Returns:** Encryption key or empty string

## External Services

This plugin connects to the VESSOT API (https://vessot.tech/api) to store and retrieve encrypted data.

### What is VESSOT?

VESSOT is a third-party encrypted data storage service that provides zero-knowledge data storage. The service is provided by VESSOT.

### What data is sent?

The plugin sends the following data to the VESSOT API:

- **Encrypted data values**: All data is encrypted client-side using AES-256-GCM encryption before transmission. The VESSOT API only receives encrypted ciphertext and never has access to your encryption keys or unencrypted data.
- **Data keys**: Unique identifiers you specify for each piece of data (e.g., "user_settings", "preferences").
- **Authentication token**: Your VESSOT_INT_TOKEN for API authentication.

### When is data sent?

Data is transmitted to the VESSOT API when you use any of these functions:

- `vessot_secure_portable_data()->store()` - Sends encrypted data to be stored
- `vessot_secure_portable_data()->show()` - Retrieves encrypted data
- `vessot_secure_portable_data()->update()` - Sends encrypted data updates
- `vessot_secure_portable_data()->destroy()` - Requests data deletion

### Service Information

- **Service Provider**: VESSOT
- **Service Website**: https://vessot.tech
- **Terms of Service**: https://vessot.tech/terms-conditions
- **Privacy Policy**: https://vessot.tech/privacy-policy
- **API Documentation**: https://vessot.tech/setup
- **WordPress Setup Guide**: https://vessot.tech/wordpress

### Account Requirement

You must create a VESSOT account at https://vessot.tech before using this plugin. See https://vessot.tech/how-it-works for account creation steps and https://vessot.tech/wordpress for WordPress-specific setup instructions.

## Security Notes

- All encryption happens client-side before transmission
- The VESSOT API never has access to unencrypted data or encryption keys
- Environment variables are set at the server level and never touch the filesystem
- Each environment should have its own unique tokens and keys
- Server configuration changes require appropriate restart (Apache reload, PHP-FPM restart, etc.)

## Support

For issues and support, visit: https://vessot.tech

## License

This plugin is licensed under the GNU General Public License v2 or later.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

## Author

VESSOT - https://vessot.tech
