#!/bin/bash
set -euo pipefail

if [ "$EUID" -ne 0 ]; then
    echo "Ejecuta como root: sudo ./03_configurar_prueba_ids.sh"
    exit 1
fi

APP_DIR="${1:-/var/www/html/ArenaCJD}"
YAML="/etc/suricata/suricata.yaml"
RULES_DIR="/etc/suricata/rules"

if [ ! -f "$APP_DIR/seguridad/suricata/REGLAS_LOCALES.rules" ]; then
    echo "No se encontró la regla de ArenaCJD."
    exit 1
fi

if [ ! -f "$YAML" ]; then
    echo "No se encontró $YAML. Instala Suricata primero."
    exit 1
fi

mkdir -p "$RULES_DIR"
cp "$APP_DIR/seguridad/suricata/REGLAS_LOCALES.rules" "$RULES_DIR/local.rules"

if ! grep -Eq '^[[:space:]]*-[[:space:]]*local\.rules[[:space:]]*$' "$YAML"; then
    sed -i '/^rule-files:/a\  - local.rules' "$YAML"
fi

suricata -T -c "$YAML"
systemctl restart suricata

echo
echo "Regla IDS de prueba instalada."
echo "Desde OTRA máquina visita: http://IP_DEL_SERVIDOR/prueba-suricata"
echo "Luego revisa: sudo tail -n 20 /var/log/suricata/fast.log"
