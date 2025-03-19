# Word Frequency Counter (Vanilla PHP + SQLite)

A small API that:

1. Accepts **POST /text** calls to feed text into the system.
   *     curl -X POST -d 'text="Kindness lives in every heart."' http://localhost:8080/text
2. Accepts **GET /words** to retrieve **all** word counts (as JSON).
   *     curl http://localhost:8080/words
3. Accepts **GET /word/{word}** to retrieve the **count** of a specific word.
   *      curl "http://localhost:8080/word/kindness"
   
## Prerequisites

- PHP 8 (or higher)
- PDO and SQLite extensions enabled.

## Installation & Setup

1. **Clone or download** this project folder.
2. Ensure all four files are in the same api directory:
    - `Database.php`
    - `Word.php`
    - `router.php`
    - `README.md`
3. Make sure **PHP’s SQLite extension** is enabled:
    - Check by running `php -m | grep sqlite` or `php -i | grep SQLite`.
4. Testing 
   - `tests/WordTest.php`
   - Make it executable:
   
```bash
chmod +x run-tests.sh
```
   - Run the tests

```bash
./run-tests.sh
```
## Running

To start the server:

```bash
php -S localhost:8080 api/router.php
