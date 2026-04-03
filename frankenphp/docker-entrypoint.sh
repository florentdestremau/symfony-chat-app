#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then

	if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
		composer install --prefer-dist --no-progress --no-interaction
	fi

	# Display information about the current project
	php bin/console -V

	# Ensure storage directory exists for SQLite
	if [ -n "$DATABASE_URL" ] && echo "$DATABASE_URL" | grep -q "sqlite"; then
		STORAGE_DIR=$(echo "$DATABASE_URL" | sed 's|sqlite:///||' | xargs dirname)
		mkdir -p "$STORAGE_DIR"
		chown -R 1000:1000 "$STORAGE_DIR" 2>/dev/null || true
		
		echo 'SQLite database configured at:' $(echo "$DATABASE_URL" | sed 's|sqlite:///||')
		
		# Check if database is accessible
		if php bin/console dbal:run-sql -q "SELECT 1" 2>/dev/null; then
			echo 'Database is ready'
		else
			echo 'Database needs initialization...'
		fi
		
		# Run migrations if they exist
		if [ -d "./migrations" ] && [ "$(find ./migrations -iname '*.php' -print -quit)" ]; then
			php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing || true
		fi
		
		# Load fixtures only if database is empty (optional, for fresh installs)
		if [ "${LOAD_FIXTURES:-false}" = "true" ]; then
			php bin/console doctrine:fixtures:load --no-interaction || true
		fi
	fi

	echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"
