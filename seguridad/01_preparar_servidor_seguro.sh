#!/bin/bash
set -euo pipefail

if [ "$EUID" -ne 0 ]; then
    echo "Ejecuta este script como root: sudo ./01_preparar_servidor_seguro.sh"
    exit 1
fi

APP_DIR="${1:-/var/www/html/ArenaCJD}"

if [ ! -d "$APP_DIR" ]; then
    echo "No existe el proyecto en: $APP_DIR"
    echo "Uso: sudo ./01_preparar_servidor_seguro.sh /ruta/ArenaCJD"
    exit 1
fi

apt update
apt install -y ufw fail2ban suricata

ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp comment 'SSH administracion'
ufw allow 80/tcp comment 'HTTP ArenaCJD'
ufw allow 443/tcp comment 'HTTPS ArenaCJD'
ufw --force enable

mkdir -p /var/log/arenacjd
touch /var/log/arenacjd/security.log
chown -R www-data:adm /var/log/arenacjd
chmod 750 /var/log/arenacjd
chmod 640 /var/log/arenacjd/security.log

if command -v a2enconf >/dev/null 2>&1; then
    cat > /etc/apache2/conf-available/arenacjd-security.conf <<'EOF'
SetEnv ARENA_SECURITY_LOG "/var/log/arenacjd/security.log"
EOF
    a2enconf arenacjd-security >/dev/null
    systemctl restart apache2
fi

cp "$APP_DIR/seguridad/fail2ban/arenacjd.conf" /etc/fail2ban/filter.d/arenacjd.conf
cp "$APP_DIR/seguridad/fail2ban/arenacjd.local" /etc/fail2ban/jail.d/arenacjd.local

systemctl enable --now fail2ban
systemctl restart fail2ban

if command -v suricata-update >/dev/null 2>&1; then
    suricata-update || true
fi
systemctl enable --now suricata
systemctl restart suricata

echo
echo "=== UFW ==="
ufw status verbose

echo
echo "=== FAIL2BAN ==="
fail2ban-client status arenacjd || true

echo
echo "=== SURICATA ==="
systemctl --no-pager --full status suricata | head -20 || true

echo
echo "Servidor preparado en modo IDS. Lee seguridad/LEEME_CIBERSEGURIDAD.md antes de activar IPS."
