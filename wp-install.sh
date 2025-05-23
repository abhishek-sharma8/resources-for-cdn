#!/bin/bash

# Prompt for input
read -p "Enter domain name (e.g., fundsetup.net): " DOMAIN
read -p "Enter web root directory (e.g., /www/wwwroot/$DOMAIN): " WEBROOT
read -p "Enter MySQL database name: " DB_NAME
read -p "Enter MySQL user name: " DB_USER
read -s -p "Enter MySQL user password: " DB_PASS
echo

# 1. Create web root
sudo mkdir -p "$WEBROOT"
sudo chown -R www-data:www-data "$WEBROOT"
sudo chmod -R 755 "$WEBROOT"

# 2. Create Apache VirtualHost
VHOST_FILE="/etc/apache2/sites-available/$DOMAIN.conf"

sudo bash -c "cat > $VHOST_FILE" <<EOF
<VirtualHost *:80>
    ServerAdmin webmaster@$DOMAIN
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN

    DocumentRoot $WEBROOT

    <Directory $WEBROOT>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN}_error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}_access.log combined
</VirtualHost>
EOF

# Enable site and reload Apache
sudo a2ensite "$DOMAIN.conf"
sudo systemctl reload apache2

# 3. Issue SSL certificate
sudo certbot --apache -d "$DOMAIN" -d "www.$DOMAIN" --non-interactive --agree-tos -m "admin@$DOMAIN"

# 4. Create MySQL DB and user
sudo mysql -e "CREATE DATABASE $DB_NAME;"
sudo mysql -e "CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# 5. Download and install WordPress
cd /tmp
curl -sO https://wordpress.org/latest.tar.gz
tar -xzf latest.tar.gz
sudo cp -a wordpress/. "$WEBROOT/"
sudo chown -R www-data:www-data "$WEBROOT"
sudo find "$WEBROOT" -type d -exec chmod 755 {} \;
sudo find "$WEBROOT" -type f -exec chmod 644 {} \;

echo
echo "WordPress setup complete!"
echo "Visit https://$DOMAIN to finish installation in your browser."
