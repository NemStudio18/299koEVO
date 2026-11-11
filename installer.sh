#!/usr/bin/env bash
# ==============================================================================
# Script d'auto-configuration Nginx / YunoHost pour 299Ko EVO
#
# Exemples d'utilisation (tous supportés sans modifier le fichier) :
#   sudo ./installer.sh --299ko.flexcb.fr
#   sudo ./installer.sh --https://299ko.flexcb.fr
#   sudo ./installer.sh 299ko.flexcb.fr
#   sudo ./installer.sh --domain 299ko.flexcb.fr
#   sudo /var/www/my_webapp__19/www/installer.sh
#
# Détecte tout seul : le bon my_webapp__*, la version/socket PHP-FPM et les droits.
# ==============================================================================

set -e

if [ "$(id -u)" -ne 0 ]; then
    echo "Erreur : ce script doit être exécuté avec sudo (ex: sudo $0 --299ko.flexcb.fr)"
    exit 1
fi

SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CURRENT_PATH="$(pwd)"

DOMAIN=""
APP_ID=""

clean_domain() {
    local val="$1"
    val="${val#--domain=}"
    val="${val#--url=}"
    val="${val#--}"
    val="${val#-d}"
    val="${val#https://}"
    val="${val#http://}"
    val="${val%%/*}"
    echo "$val"
}

# 1. Analyse souple des arguments (--monurldomaine, --domain xxx, -d xxx, ou domaine direct)
while [ "$#" -gt 0 ]; do
    case "$1" in
        -h|--help)
            echo "Usage :"
            echo "  sudo $0 --<domaine>          (ex: sudo $0 --299ko.flexcb.fr)"
            echo "  sudo $0 <domaine>            (ex: sudo $0 299ko.flexcb.fr)"
            echo "  sudo $0                      (auto-détection depuis le dossier my_webapp)"
            exit 0
            ;;
        -d|--domain|--url)
            shift
            if [ -n "${1:-}" ]; then
                DOMAIN="$(clean_domain "$1")"
            fi
            ;;
        my_webapp*)
            APP_ID="$1"
            ;;
        *)
            CANDIDATE="$(clean_domain "$1")"
            if [[ "$CANDIDATE" == my_webapp* ]]; then
                APP_ID="$CANDIDATE"
            elif [ -n "$CANDIDATE" ]; then
                DOMAIN="$CANDIDATE"
            fi
            ;;
    esac
    shift
done

# 2. Si DOMAIN est fourni, trouver automatiquement le my_webapp* correspondant
if [ -n "$DOMAIN" ] && [ -z "$APP_ID" ]; then
    # 2a. Chercher dans /etc/nginx/conf.d/<DOMAIN>.d/my_webapp*.d
    for d in /etc/nginx/conf.d/"${DOMAIN}".d/my_webapp*.d; do
        if [ -d "$d" ]; then
            base="$(basename "$d")"
            APP_ID="${base%.d}"
            break
        fi
    done

    # 2b. Fallback : chercher dans /etc/yunohost/apps/my_webapp*/settings.yml
    if [ -z "$APP_ID" ]; then
        for settings in /etc/yunohost/apps/my_webapp*/settings.yml; do
            [ -f "$settings" ] || continue
            app_dom="$(grep -E '^domain:' "$settings" 2>/dev/null | head -n1 | awk '{print $2}' | tr -d "\"'")"
            if [ "$app_dom" = "$DOMAIN" ]; then
                APP_ID="$(basename "$(dirname "$settings")")"
                break
            fi
        done
    fi
fi

# 3. Si aucun argument n'a été passé, auto-détection depuis le dossier courant ou du script
if [ -z "$APP_ID" ]; then
    for CANDIDATE_PATH in "$CURRENT_PATH" "$SCRIPT_PATH"; do
        if [[ "$CANDIDATE_PATH" =~ /var/www/(my_webapp[^/]+) ]]; then
            APP_ID="${BASH_REMATCH[1]}"
            break
        fi
    done
fi

# 4. Si APP_ID est toujours inconnu, scanner les webapps YunoHost contenant 299Ko
if [ -z "$APP_ID" ]; then
    MATCHED_APPS=()
    for www_dir in /var/www/my_webapp*/www; do
        [ -d "$www_dir" ] || continue
        if [ -f "$www_dir/common/common.php" ] || [ -f "$www_dir/install.php" ]; then
            app_name="$(basename "$(dirname "$www_dir")")"
            MATCHED_APPS+=("$app_name")
        fi
    done

    if [ "${#MATCHED_APPS[@]}" -eq 1 ]; then
        APP_ID="${MATCHED_APPS[0]}"
    elif [ "${#MATCHED_APPS[@]}" -gt 1 ]; then
        echo "Plusieurs instances 299Ko détectées sur ce serveur :"
        idx=1
        for app_name in "${MATCHED_APPS[@]}"; do
            app_domain="$(grep -E '^domain:' "/etc/yunohost/apps/${app_name}/settings.yml" 2>/dev/null | awk '{print $2}' | tr -d "\"'" || true)"
            echo "  [$idx] $app_name (${app_domain:-domaine inconnu})"
            idx=$((idx + 1))
        done
        read -r -p "Choisissez le numéro de l'instance à configurer [1-${#MATCHED_APPS[@]}] : " choice
        if [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le "${#MATCHED_APPS[@]}" ]; then
            APP_ID="${MATCHED_APPS[$((choice - 1))]}"
        else
            echo "Choix invalide."
            exit 1
        fi
    fi
fi

# 5. Si APP_ID est connu mais pas DOMAIN, le récupérer depuis YunoHost ou Nginx
if [ -n "$APP_ID" ] && [ -z "$DOMAIN" ]; then
    if [ -f "/etc/yunohost/apps/${APP_ID}/settings.yml" ]; then
        DOMAIN="$(grep -E '^domain:' "/etc/yunohost/apps/${APP_ID}/settings.yml" | head -n1 | awk '{print $2}' | tr -d "\"'")"
    fi
    if [ -z "$DOMAIN" ]; then
        for d in /etc/nginx/conf.d/*.d/"${APP_ID}".d; do
            if [ -d "$d" ]; then
                parent_dir="$(basename "$(dirname "$d")")"
                DOMAIN="${parent_dir%.d}"
                break
            fi
        done
    fi
fi

if [ -z "$DOMAIN" ] || [ -z "$APP_ID" ]; then
    echo "Erreur : impossible de trouver un my_webapp correspondant au domaine '${DOMAIN:-non spécifié}'."
    echo "Usage  : sudo $0 --<votre-domaine.fr>"
    exit 1
fi

NGINX_DIR="/etc/nginx/conf.d/${DOMAIN}.d/${APP_ID}.d"
MAIN_NGINX_CONF="/etc/nginx/conf.d/${DOMAIN}.d/${APP_ID}.conf"
APP_DIR="/var/www/${APP_ID}/www"

if [ ! -d "$NGINX_DIR" ]; then
    echo "Erreur : le dossier Nginx $NGINX_DIR n'existe pas."
    exit 1
fi

# 6. Auto-détection du socket PHP-FPM de cette instance my_webapp
PHP_SOCK=""
if [ -f "$MAIN_NGINX_CONF" ]; then
    PHP_SOCK="$(grep -Eo 'unix:/var/run/php/php[0-9.]+-fpm-[^;]+\.sock' "$MAIN_NGINX_CONF" | head -n1 || true)"
fi
if [ -z "$PHP_SOCK" ]; then
    for sock in /var/run/php/php*-fpm-"${APP_ID}".sock; do
        if [ -S "$sock" ]; then
            PHP_SOCK="unix:${sock}"
            break
        fi
    done
fi
if [ -z "$PHP_SOCK" ] && [ -f "/etc/yunohost/apps/${APP_ID}/settings.yml" ]; then
    PHP_VER="$(grep -E '^phpversion:' "/etc/yunohost/apps/${APP_ID}/settings.yml" | head -n1 | awk '{print $2}' | tr -d "\"'")"
    if [ -n "$PHP_VER" ]; then
        PHP_SOCK="unix:/var/run/php/php${PHP_VER}-fpm-${APP_ID}.sock"
    fi
fi
if [ -z "$PHP_SOCK" ]; then
    PHP_SOCK="unix:/var/run/php/php8.4-fpm-${APP_ID}.sock"
fi

echo "==> Configuration automatique Nginx / YunoHost pour 299Ko EVO"
echo "    Domaine    : $DOMAIN"
echo "    Webapp ID  : $APP_ID ($APP_DIR)"
echo "    Socket PHP : $PHP_SOCK"

# 7. Supprimer error-code.conf qui intercepte les routes virtuelles (ex: /admin, /blog)
if [ -f "$NGINX_DIR/error-code.conf" ]; then
    rm -f "$NGINX_DIR/error-code.conf"
    echo "[-] error-code.conf supprimé."
fi

# 8. Installer rewrite.conf
cat << EOF > "$NGINX_DIR/rewrite.conf"
location = /ads.txt {
    alias ${APP_DIR}/ads.txt;
    add_header Content-Type text/plain;
    access_log off;
    log_not_found off;
}

location / {
    root ${APP_DIR};
    try_files \$uri /index.php\$is_args\$args;
    autoindex off;
}

location ~* \.(json|tpl|ini|env|sql|log)$ {
    deny all;
}

location ~ ^/(.+/|)\.(?!well-known/) {
    deny all;
}

location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass ${PHP_SOCK};
    fastcgi_param SCRIPT_FILENAME \$request_filename;
}
EOF
echo "[+] rewrite.conf généré."

# 9. Installer 299ko.conf
cat << "EOF" > "$NGINX_DIR/299ko.conf"
# Fallback de réécriture propre pour 299Ko CMS
if (!-f $request_filename) {
    rewrite ^ /index.php last;
}
EOF
echo "[+] 299ko.conf généré."

# 10. Ajuster php.conf
cat << EOF > "$NGINX_DIR/php.conf"
location ~ [^/]\.php(/|$) {
    fastcgi_split_path_info ^(.+?\.php)(/.*)$;
    fastcgi_pass ${PHP_SOCK};
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param REMOTE_USER \$remote_user;
    fastcgi_param PATH_INFO \$fastcgi_path_info;
    fastcgi_param SCRIPT_FILENAME \$request_filename;
}
EOF
echo "[+] php.conf mis à jour."

# 11. Ajuster les permissions du CMS et créer le dossier miroir conf/
if [ -d "$APP_DIR" ]; then
    mkdir -p "$APP_DIR/data" "$APP_DIR/upload" "$APP_DIR/conf"
    cp "$NGINX_DIR"/*.conf "$APP_DIR/conf/" 2>/dev/null || true
    chown -R "${APP_ID}:www-data" "$APP_DIR" 2>/dev/null || true
    find "$APP_DIR/data" "$APP_DIR/upload" -type d -exec chmod 775 {} + 2>/dev/null || true
    find "$APP_DIR/data" "$APP_DIR/upload" -type f -exec chmod 664 {} + 2>/dev/null || true
    echo "[+] Permissions de $APP_DIR et miroir $APP_DIR/conf vérifiés."
fi

# 12. Test et reload Nginx
nginx -t && systemctl reload nginx
echo "==> Terminé avec succès ! Nginx est configuré et rechargé pour https://${DOMAIN}"
