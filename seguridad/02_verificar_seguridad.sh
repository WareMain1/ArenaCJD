#!/bin/bash
set -u

echo "=== Firewall UFW ==="
sudo ufw status verbose

echo
echo "=== Fail2ban ArenaCJD ==="
sudo fail2ban-client status arenacjd

echo
echo "=== Últimos eventos ArenaCJD ==="
sudo tail -n 20 /var/log/arenacjd/security.log 2>/dev/null || true

echo
echo "=== Suricata ==="
sudo systemctl is-active suricata
sudo tail -n 10 /var/log/suricata/fast.log 2>/dev/null || true

echo
echo "=== Puertos escuchando ==="
sudo ss -lntp
