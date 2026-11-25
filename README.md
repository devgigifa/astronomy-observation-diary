# Astronomy Observation Diary

A full-stack web application for logging personal astronomical observations, built as a software engineering coursework project to practice HTML5, CSS3, JavaScript, and PHP with MySQL.

## Features

- **Log observations** — date, location, object name, sky conditions, notes, and an optional photo upload
- **Filter & search** — filter the observation list by month or object name via AJAX (no page reload)
- **Export to CSV** — download the current filtered view as a `.csv` file
- **Dashboard** — summary stats: total observations, unique objects, and most active month
- **Photo viewer** — click any thumbnail to open a full-size modal

## Tech Stack

| Layer | Technology |
|---|---|
| Markup | HTML5 (semantic tags, forms, tables) |
| Styles | CSS3 (custom properties, Flexbox, Grid, responsive) |
| Client logic | Vanilla JavaScript (DOM, Fetch API, AJAX, JSON) |
| Server logic | PHP 8 (`$_POST`, `$_GET`, `$_FILES`, PDO) |
| Database | MySQL (PDO with prepared statements) |

## Project Structure

```
astronomy-observation-diary/
├── index.php              # Dashboard
├── new-observation.php    # Observation form
├── observations.php       # Filterable list with AJAX table
├── insert.php             # Form handler — validates & saves to DB
├── delete.php             # Deletes a record and its photo file
├── export.php             # Streams filtered results as CSV
├── api-observations.php   # JSON endpoint consumed by fetch()
├── db.php                 # PDO connection (single source of truth)
├── style.css              # All styles — dark astronomical theme
├── database.sql           # Schema + sample data
└── uploads/               # Uploaded observation photos
```

## Getting Started

**Requirements:** XAMPP (or any Apache + PHP 8 + MySQL stack)

1. Clone or extract the project into `htdocs/astronomy-observation-diary/`
2. Start **Apache** and **MySQL** in the XAMPP Control Panel
3. Open `http://localhost/phpmyadmin`, create a database named `astronomy_diary`
4. Import `database.sql` — this creates the table and loads 3 sample observations
5. Open `http://localhost/astronomy-observation-diary/`

> If your MySQL credentials differ from the XAMPP defaults, update `DB_USER` and `DB_PASS` in `db.php`.

## Key Concepts Demonstrated

- Client–server model and HTTP request methods (GET / POST)
- HTML5 form validation and file upload (`enctype="multipart/form-data"`)
- CSS custom properties (design tokens), Box Model, pseudo-classes
- DOM manipulation and asynchronous requests with the Fetch API
- PHP superglobals, control structures, arrays, and functions
- PDO connection, `prepare()` / `execute()`, and SQL Injection prevention
- XSS prevention with `htmlspecialchars()`
- Dynamic CSV generation with HTTP headers and `fputcsv()`
