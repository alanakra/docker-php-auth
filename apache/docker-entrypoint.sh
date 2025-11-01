#!/bin/bash
set -e

# Check existing SSL certificates
if [ ! -f /etc/apache2/ssl/demo-register-server.local.crt ] || [ ! -f /etc/apache2/ssl/demo-register-server.local.key ]; then
    echo "ERROR: SSL certificates not found in /etc/apache2/ssl/"
    exit 1
fi

# Start SSL module
if [ ! -L /etc/apache2/mods-enabled/ssl.load ]; then
    a2enmod ssl
fi

# Start personalized SSL (if isn't enabled)
if [ ! -L /etc/apache2/sites-enabled/phpmyadmin-ssl.conf ]; then
    a2ensite phpmyadmin-ssl
fi

# Disable default site (HTTP only)
if [ -L /etc/apache2/sites-enabled/000-default.conf ]; then
    a2dissite 000-default || true
fi

# Start phpyMyAdmin entry script
# The original script is in /docker-entrypoint.sh of the l'image
# Si aucun argument n'est fourni, utiliser la commande par défaut
# If no arg, use default command
if [ $# -eq 0 ]; then
    set -- apache2-foreground
fi

if [ -f /docker-entrypoint.sh ]; then
    exec /docker-entrypoint.sh "$@"
else
    exec "$@"
fi

