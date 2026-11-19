# Deploy steps for the Hub WordPress on Laravel Forge. Forge's deploy script (Site > Settings > Deployments) runs
# this file from git: git show origin/$FORGE_SITE_BRANCH:wp-local/forge/deploy.sh | ... bash, as the forge user.
#
# The database is the committed SQLite file (wp-local/hub/wp-content/database/hub.sqlite), so git wins: every
# deploy replaces the server's database with the committed one. Changes made in the server's wp-admin are lost
# on the next deploy; make content changes locally and commit hub.sqlite. Uploads added on the server stay
# (they are untracked files), and so does the site's .env.
{
set -e
cd "$FORGE_SITE_PATH"

DB=wp-local/hub/wp-content/database/hub.sqlite

git fetch origin "$FORGE_SITE_BRANCH"
rm -f "$DB-wal" "$DB-shm"                      # SQLite's write-ahead log belongs to the old file
git reset --hard "origin/$FORGE_SITE_BRANCH"
rm -f "$DB-wal" "$DB-shm"

# Reload PHP so OPcache picks up the new code (FORGE_PHP_FPM is set by Forge)
if [ -n "${FORGE_PHP_FPM:-}" ]; then
  ( flock -w 10 9 || exit 1
    echo 'Reloading PHP FPM...'; sudo -S service "$FORGE_PHP_FPM" reload ) 9>/tmp/fpmlock
fi

echo "Deployed $(git rev-parse --short HEAD)"
exit 0
}
