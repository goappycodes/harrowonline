#!/bin/bash
# Auto-deploy and self-repair for the Hub WordPress on appy-dev, independent of Forge (Forge stopped running its
# site jobs for this server on 18 Nov 2026: repository installs and site removals never ran).
#
# Layout on the server:
#   /home/forge/harrow-hub-app            git checkout (forge user), its .env and .htpasswd-hub; outside every Forge
#                                         site folder, so Forge removing a site can't delete it
#   /etc/harrow-hub/vhosts                "domain forge-site-id" per line: Forge vhosts that should serve the app
#   /etc/harrow-hub/site.conf.tpl         nginx rules (wp-local/forge/nginx-hub.conf filled in; __SITE_ID__ = site id)
#   /usr/local/bin/harrow-hub-autodeploy  this file, root-owned, outside git (repository code never runs as root)
#   /etc/cron.d/harrow-hub-deploy         * * * * * root flock -n /run/harrow-hub-deploy.lock
#                                           /usr/local/bin/harrow-hub-autodeploy /home/forge/harrow-hub-app
#                                           >> /var/log/harrow-hub-autodeploy.log 2>&1
#
# Every minute:
#   1. Code: when the tracked branch on GitHub has a new commit, or the checkout is damaged, run
#      wp-local/forge/deploy.sh (taken from git) as the forge user, then reload PHP-FPM (OPcache does not check file
#      times here). Branch: DEPLOY_BRANCH in the app's .env, or main once that branch is gone from GitHub.
#   2. nginx: for each listed vhost that exists, point its root at the app and keep the Hub rules in its site.conf
#      (Forge rewrites both when it creates a site), then test and reload nginx.
{
set -uo pipefail
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin   # cron gives only /usr/bin:/bin
APP=${1:?usage: harrow-hub-autodeploy /home/forge/harrow-hub-app}
CONF=/etc/harrow-hub
PHP_FPM=${PHP_FPM:-php8.2-fpm}
log() { echo "$(date -u +%FT%TZ) $*"; }
as_forge() { sudo -u forge -H "$@"; }
cd "$APP" || { log "no app folder $APP"; exit 1; }

# 1. Code
BRANCH=$(grep -E '^DEPLOY_BRANCH=' .env 2>/dev/null | tail -1 | cut -d= -f2- | tr -d "\"' " || true)
BRANCH=${BRANCH:-main}
if ! as_forge git ls-remote --exit-code --heads origin "$BRANCH" >/dev/null 2>&1; then
  BRANCH=main
fi
if as_forge git fetch -q origin "$BRANCH"; then
  WHY=""
  [ "$(as_forge git rev-parse HEAD)" != "$(as_forge git rev-parse "origin/$BRANCH")" ] && WHY="new commit"
  [ -f wp-local/hub/wp-config.php ] && [ -f wp-local/hub/index.php ] && [ -f wp-local/hub/wp-content/database/hub.sqlite ] \
    || WHY=${WHY:-"checkout damaged"}
  if [ -n "$WHY" ]; then
    log "deploying $BRANCH $(as_forge git rev-parse --short "origin/$BRANCH") ($WHY)"
    if as_forge git show "origin/$BRANCH:wp-local/forge/deploy.sh" \
       | as_forge env FORGE_SITE_PATH="$APP" FORGE_SITE_BRANCH="$BRANCH" bash; then
      service "$PHP_FPM" reload
    else
      log "deploy failed"
    fi
  fi
else
  log "git fetch failed"
fi

# 2. nginx
CHANGED=""
while read -r DOMAIN SITE_ID _; do
  case "${DOMAIN:-#}" in \#*) continue ;; esac
  AV=/etc/nginx/sites-available/$DOMAIN
  SC=/etc/nginx/forge-conf/$SITE_ID/site.conf
  [ -f "$AV" ] && [ -d "/etc/nginx/forge-conf/$SITE_ID" ] || continue
  if ! grep -qE "^\s*root $APP/wp-local;" "$AV"; then
    sed -i -E "s#^(\s*)root .*;#\1root $APP/wp-local;#" "$AV"; CHANGED=1; log "nginx: root set for $DOMAIN"
  fi
  WANT=$(sed "s/__SITE_ID__/$SITE_ID/g" "$CONF/site.conf.tpl")
  if [ "$WANT" != "$(cat "$SC" 2>/dev/null)" ]; then
    printf '%s\n' "$WANT" > "$SC"; CHANGED=1; log "nginx: Hub rules set for $DOMAIN"
  fi
done < "$CONF/vhosts"
if [ -n "$CHANGED" ]; then
  if nginx -t >/dev/null 2>&1; then service nginx reload; log "nginx reloaded"; else log "nginx -t failed: $(nginx -t 2>&1 | grep emerg)"; fi
fi
exit 0
}
