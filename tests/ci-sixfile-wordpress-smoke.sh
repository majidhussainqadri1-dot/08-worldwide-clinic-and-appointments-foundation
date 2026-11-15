#!/usr/bin/env bash
# Disposable File08 V12.3 GitHub CI. NOT Hostinger staging or production.
set -euo pipefail
test "$CI" = "true"
test "$GITHUB_ACTIONS" = "true"
test "$(git rev-parse HEAD)" = "$FILE08_SHA"
test "$(git -C .external/file00 rev-parse HEAD)" = "$FILE00_SHA"
test "$(git -C .external/file02 rev-parse HEAD)" = "$FILE02_SHA"
test "$(git -C .external/file03 rev-parse HEAD)" = "$FILE03_SHA"
test "$(git -C .external/file07 rev-parse HEAD)" = "$FILE07_SHA"
test "$(git -C .external/file09 rev-parse HEAD)" = "$FILE09_SHA"
root=/tmp/file08-wordpress
plugins="$root/wp-content/plugins"
curl --fail --location --retry 3 https://github.com/wp-cli/wp-cli/releases/download/v2.12.0/wp-cli-2.12.0.phar -o /tmp/wp-cli.phar
php /tmp/wp-cli.phar core download --version=7.0.1 --path="$root" --force
php /tmp/wp-cli.phar config create --path="$root" --dbname=wordpress --dbuser=wordpress --dbpass=wordpress --dbhost=127.0.0.1:3306 --skip-check
php /tmp/wp-cli.phar config set SMC_MASTER_KEY 'base64:MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY=' --type=constant --path="$root"
php /tmp/wp-cli.phar config set SMC_MASTER_KEY_ID 'file08-v123-ci' --type=constant --path="$root"
php /tmp/wp-cli.phar config set SA_MASTER_KEY 'file08-only-ephemeral-ci-test-master-key' --type=constant --path="$root"
php /tmp/wp-cli.phar config set GDO_KEYRING '{"active":"ci-v1","keys":{"ci-v1":"MDEyMzQ1Njc4OWFiY2RlZjAxMjM0NTY3ODlhYmNkZWY="}}' --type=constant --path="$root"
php /tmp/wp-cli.phar config set GDO_CLAIM_SIGNING_KEY 'file08-v123-ephemeral-ci-test-signing-key-2026' --type=constant --path="$root"
php /tmp/wp-cli.phar config set GDO_PRIVATE_STORAGE_DIR '/tmp/file08-v123-gdo-private' --type=constant --path="$root"
php /tmp/wp-cli.phar config set WP_ENVIRONMENT_TYPE 'staging' --type=constant --path="$root"
php /tmp/wp-cli.phar core install --path="$root" --url=http://file08-p05.local --title='File08 P05 Ephemeral CI' --admin_user=founder --admin_password='File08-Ephemeral-CI-Only!2026' --admin_email=founder@example.test --skip-email

mkdir -p "$plugins/sabri-membership-core"
rsync -a --exclude=.git .external/file00/source/sabri-membership-core/ "$plugins/sabri-membership-core/"
php /tmp/wp-cli.phar plugin activate sabri-membership-core --path="$root"
php /tmp/wp-cli.phar eval '
  $key=SMC_Security::ensure_key_ready();
  if(is_wp_error($key)){fwrite(STDERR,"File00 key unavailable\n");exit(51);}
  $audit=SMC_Installer::ensure_audit_infrastructure();
  if(is_wp_error($audit)){fwrite(STDERR,"File00 audit unavailable\n");exit(52);}
  SMC_Schema_Compat::reconcile_verification_queue_index();
  SMC_Installer::maybe_upgrade();
  if(SMC_DB_VERSION!==(string)get_option("smc_db_version","")){fwrite(STDERR,"File00 migration incomplete\n");exit(53);}
  if(SMC_Completion::safe_mode()){fwrite(STDERR,"File00 Safe Mode active\n");exit(54);}
  echo "PASS pinned File00 migration\n";
' --path="$root"

mkdir -p "$plugins/02-sabri-authentication" "$plugins/sabri-profiles-doctors" "$plugins/doctors-directory" "$plugins/worldwide-clinic"
rsync -a --exclude=.git .external/file02/ "$plugins/02-sabri-authentication/"
php /tmp/wp-cli.phar plugin activate 02-sabri-authentication --path="$root"
rsync -a --exclude=.git .external/file03/ "$plugins/sabri-profiles-doctors/"
php /tmp/wp-cli.phar plugin activate sabri-profiles-doctors --path="$root"
(
 cd .external/file09
 GITHUB_SHA="$FILE09_SHA" EXPECTED_SHA="$FILE09_SHA" python3 tools/build-release.py --output /tmp/file08-gdo-dist
)
unzip -q /tmp/file08-gdo-dist/global-doctor-onboarding-09-1.3.0-RC6.zip -d "$plugins/"
php /tmp/wp-cli.phar plugin activate global-doctor-onboarding-09 --path="$root"
rsync -a --exclude=.git .external/file07/doctors-directory/ "$plugins/doctors-directory/"
php /tmp/wp-cli.phar plugin activate doctors-directory --path="$root"
rsync -a --exclude=.git --exclude=.external --exclude=build ./ "$plugins/worldwide-clinic/"
php /tmp/wp-cli.phar plugin activate worldwide-clinic --path="$root"
php -l tests/v123-wordpress-file00-02-03-07-08-09-smoke.php
php /tmp/wp-cli.phar eval-file tests/v123-wordpress-file00-02-03-07-08-09-smoke.php --path="$root"
echo "PASS installed ephemeral six-module WordPress+MariaDB smoke; NOT Hostinger staging"
