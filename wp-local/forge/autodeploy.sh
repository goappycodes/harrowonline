#!/bin/bash
# Auto-deploy for the Hub WordPress on appy-dev without Forge's push-to-deploy (Forge never finished installing the
# site's repository). Installed by root, outside git, so repository code never runs as root:
#
#   install -m 755 -o root wp-local/forge/autodeploy.sh /usr/local/bin/harrow-hub-autodeploy
#   echo '* * * * * root flock -n /run/harrow-hub-deploy.lock /usr/local/bin/harrow-hub-autodeploy /home/forge/SITE >> /home/forge/.forge/harrow-hub-autodeploy.log 2>&1' > /etc/cron.d/harrow-hub-deploy
#
# Every minute: if the tracked branch on GitHub has a new commit, run wp-local/forge/deploy.sh as the forge user,
# then reload PHP-FPM (OPcache does not check file times on this server). The branch is DEPLOY_BRANCH in the site's
# .env; when that branch no longer exists on GitHub (e.g. deleted after its PR merged) it deploys main.
{
set -euo pipefail
SITE=${1:?usage: harrow-hub-autodeploy /home/forge/SITE}
PHP_FPM=${PHP_FPM:-php8.2-fpm}
cd "$SITE"
as_forge() { sudo -u forge -H "$@"; }

BRANCH=$(grep -E '^DEPLOY_BRANCH=' .env 2>/dev/null | tail -1 | cut -d= -f2- | tr -d "\"' " || true)
BRANCH=${BRANCH:-main}
if ! as_forge git ls-remote --exit-code --heads origin "$BRANCH" >/dev/null 2>&1; then
  BRANCH=main
fi

as_forge git fetch -q origin "$BRANCH"
[ "$(as_forge git rev-parse HEAD)" = "$(as_forge git rev-parse "origin/$BRANCH")" ] && exit 0

echo "$(date -u +%FT%TZ) deploying $BRANCH $(as_forge git rev-parse --short "origin/$BRANCH")"
as_forge env FORGE_SITE_PATH="$SITE" FORGE_SITE_BRANCH="$BRANCH" bash -c "cat wp-local/forge/deploy.sh | bash"
service "$PHP_FPM" reload
exit 0
}
