# How Salesboard works

Read this before an interview. It explains the project in plain words.

## The idea

Reports are read far more often than the data changes, and they are expensive: they scan many rows and group them. The skills this project shows are: let the database do the aggregating, avoid redoing the same work, and export big data without running out of memory.

## Where things live

| File | Job |
|---|---|
| `app/Reports/ReportService.php` | All the report queries, plus caching. The most important file. |
| `app/Models/Order.php` | The `filter` scope shared by the page and the CSV, and the hook that clears the cache on changes. |
| `app/Http/Requests/ReportRequest.php` | Validates filters and fills in defaults. |
| `app/Http/Controllers/ReportController.php` | `index` (page), `csv`, `pdf`. |
| `resources/views/reports/` | The page (`index`) and the PDF (`pdf`). |
| `database/seeders/DatabaseSeeder.php` | 20,000 orders in batches of 1000. |

## Money

Amounts are stored as whole paise in an integer column (`amount`). Sums are exact, and the page divides by 100 only when showing them.

## The queries

`ReportService::build` does a handful of queries, each grouped in SQL, never by looping over orders in PHP:

1. **Totals:** one query with `count`, `sum(case when status = 'completed' ...)` and a count of cancelled orders. Conditional sums mean one scan gives several numbers.
2. **By month:** `group by` year-month. SQLite and MySQL have different functions for that (`strftime` and `date_format`), so a small `if` on the driver name picks the right one. Months with no orders don't come back from SQL, so PHP walks every month in the range and fills in zeros. Otherwise the chart would hide quiet months.
3. **By category** and **by region:** `group by`, ordered by revenue.

Revenue ignores cancelled orders. The average order is revenue divided by the number of completed orders (`orders - cancelled`), and an empty range returns 0 instead of dividing by zero.

The date range is `from 00:00:00` to `to 23:59:59`, so both end days are included.

Indexes: `ordered_at`, `(region, ordered_at)` and `(category, ordered_at)`, matching the way the filters are used.

## The cache and how it is cleared

The cache key looks like `reports:<version>:<hash of the filters>`.

- **Hash of the filters:** each filter combination gets its own entry.
- **Version:** a single number in the cache. When any order is saved or deleted (model events), the number goes up. All old keys now carry the old version and are never read again, and they expire by themselves after 10 minutes.

Why a version number and not cache tags? Tags aren't supported by the file or database cache drivers. A version key works with every driver and is simple to explain. The cost is that a change clears *all* reports, not only the affected ones, and that bulk inserts using `DB::table()->insert()` bypass model events (the seeder does this, which is fine on a fresh database). A real import job should call `ReportService::forgetCached()` when it finishes.

## The CSV export

`response()->streamDownload` writes to the output while it reads. `chunkById(1000)` loads 1000 orders at a time, so memory use stays flat whether the range holds 1,000 orders or 1,000,000. `fputcsv` handles commas and quotes in names (a test has `Asha, K.`). Use the same `filter` scope as the page, so the CSV always matches what you see.

## The PDF

`dompdf` turns a Blade view into a PDF. It only gets the cached summary numbers, so it is quick. It uses `Rs` instead of the rupee sign, because the default PDF font has no ₹ glyph.

## The chart

A plain `<svg>` in the Blade view. Each month is a `<rect>` whose height is its revenue divided by the largest month, with a `<title>` for the hover tooltip. No JavaScript libraries, nothing to load, and it prints well.

## Questions you may get

**Why not calculate the report in PHP from `Order::all()`?** It would load every row into memory. The database is built for grouping and sums, and returns a few rows.

**How do you keep the cache correct?** A version number bumped from model events, plus a 10 minute expiry as a safety net. A test changes, adds and deletes orders and checks the numbers update.

**What is the weakness of that approach?** It clears every report on any change, so on a very busy system the cache hit rate drops. Options: tag-based caching on Redis, a short TTL only, or pre-aggregated summary tables updated by a job.

**What if the table has 100 million rows?** Add a summary table (one row per day, region and category) filled by a scheduled job, and report from that. Partition the orders table by month.

**Why paise as integers?** Exact arithmetic. Floats give totals that are off by a paisa.

**How would you add Excel export?** A package such as `openspout/openspout` streams .xlsx the same way the CSV streams. CSV opens in Excel anyway, so I left it out.

**How do you know the SQL is right on both databases?** The tests run on SQLite. I also ran the report against MySQL 8.4 with the full seed.

## Try it yourself

Run the seeder and open the dashboard. Change the region filter and watch the "Calculated" time at the bottom: the same filters reuse the cached result and a new combination recalculates. Edit an order in `php artisan tinker` (`App\Models\Order::first()->update(['amount' => 1])`) and reload to see the cache clear. Then read `tests/Feature/ReportTest.php`.
