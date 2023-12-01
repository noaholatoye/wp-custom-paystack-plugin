# Adding Dynamic Paystack Public and Secreet Keys.

plugins/custom-paystack-plugin

The plugin allows user to create dynamic pk and sk from the admin dashboard. You would see Paysack Settings menu option once the plugin is activated.

The plugin also updated the checkout form by making sure user selects a store.

Removed script and css tag from checkout page (they are no longer needed there).

Secodly, changed billing_stores field from paragraph to textarea.

## Wrote an End point to update keys session for paystack

```
/plugins/custom-paystack-plugin/update-session-keys.php
```

Activated session and update paystack_public_key and paystack_secret_keys.

## Security and Permissions:

WordPress might not allow direct access to files for security reasons. In your .htaccess file, make sure you are not blocking access to this file.

![htaccess placement](/assets/img/htaccess.jpeg)

```
<Files "update-session-keys.php">
    Order Allow,Deny
    Allow from all
    Require all granted
</Files>
```

## Updated paystack official plugin file.

```
/plugins/woo-paystack/includes/class-wc-geteway-paystack.php
```

Just immeditiately after the declaration of global `$this->public_key=` and `$this->serete_key=`, add the following lines.

![class-wc-geteway-paystack placement](/assets/img/class-wc-geteway-paystack.jpeg)

```php
// START: Added custom multiple store key switches
$paystack_public_key = isset($_SESSION['paystack_public_key']) ? $_SESSION['paystack_public_key'] : $this->public_key;
$paystack_secret_key = isset($_SESSION['paystack_secret_key']) ? $_SESSION['paystack_secret_key'] : $this->secret_key;

$this->public_key = $paystack_public_key;
$this->secret_key = $paystack_secret_key;
// END: Custom
```
