Moneys
====

Very simple money management
----

1. Pull in data from your bank (only ING CSV import supported currently).
2. Add categories.
3. Add 'parties' to auto-categorize.
4. Categorize and tag all your transactions.
5. Voila. Statistics! By far the best of the -istics!

Statistics?
----

* In/out per category per year or per month.
* In/out per tag per year or per month.
* Search through all transactions: category, tag, amount, year, text.
* The sky is the limit, once the data is in.

Requirements
----

* PHP `>= 5.3`, obviously.
* The database is SQLite3 with [db_generic](https://github.com/rudiedirkx/db_generic) as DBAL, so you need `db_generic`
  and a writable folder for the db file and sqlite cache.

Get started
----

1. Download, clone, whatever this repo & [db_generic](https://github.com/rudiedirkx/db_generic).
2. Create a folder `db` in the root and make it webserver-writable.
3. Copy `env.php.orig` to `env.php` and change `WHERE_DB_GENERIC_AT` to the correct path to include `db_generic`.
4. Good to go: import CSV etc
