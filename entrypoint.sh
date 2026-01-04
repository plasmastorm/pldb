#!/bin/bash
set -e

if [ ! -f "/var/www/html/wp-config.php" ]; then
    cd /var/www/html
    wp core download --allow-root

    until mysql --skip-ssl -h wpdb -u wpdb -pchangeme -e "SELECT 1"; do
        echo "Waiting for database container to be up and healthy..."
        sleep 2
    done

    wp config create \
        --dbname=wpdb \
        --dbuser=wpdb \
        --dbpass=changeme \
        --dbhost=wpdb \
        --allow-root

    wp core install \
        --url=http://localhost:8082 \
        --title="PlaylistDB Dev" \
        --admin_user=admin \
        --admin_password=admin \
        --admin_email=admin@example.com \
        --allow-root
    
    chown -R www-data:www-data /var/www/html

    echo "Enabling #ThePlaylist DB plugin..."
    wp plugin activate pldb --allow-root

    wp option add pldb_host db --allow-root
    wp option add pldb_name pldb --allow-root
    wp option add pldb_user pldb --allow-root
    wp option add pldb_password changeme --allow-root

    if [ -f "/tmp/import.xml" ]; then
        wp plugin install wordpress-importer --activate --allow-root
        wp import /tmp/import.xml --authors=create --allow-root
    fi
    echo "####################################################################################"
    echo "Successful install - visit http://localhost:8082/wp-admin to log in with admin/admin"
    echo "To match the prod theme, go to Appearance > Editor > Styles > Colours > Edit Palette"
    echo "   and choose Evening (the 2nd palette), then Preview... > Save"
    echo "####################################################################################"
fi

exec apache2-foreground
