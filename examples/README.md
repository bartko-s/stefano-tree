# Examples

## Running the demo

The demo (`ex1.php`) is a small web application that exposes the most common
nested-set operations (create, move, update, delete, descendants/ancestors
queries) through a simple UI.

It expects a MariaDB database and reads the connection settings from
`examples/config.php` (gitignored — copy and adjust it from
`config.php.dist`; use the database credentials as defined in
`docker-compose.yml`).

One-time setup (create the database and load the schema):

```bash
docker compose up -d mariadb
docker compose exec mariadb mariadb -u root -e "CREATE DATABASE IF NOT EXISTS stefano_tree_demo"
docker compose exec -T mariadb mariadb -u root stefano_tree_demo < examples/mariadb.sql
```

Start the demo server (PHP built-in server, `ex1.php` acts as the router):

```bash
docker compose run --rm -p 9100:9100 php8.5 sh -c "cd examples && php -S 0.0.0.0:9100 ex1.php"
```

Then open <http://localhost:9100> in your browser.

> The built-in server with `ex1.php` as the router script is required, so the
> links in the demo (`/?action=...`) work.