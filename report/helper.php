<?php
// Reads and checks the "from" and "to" dates of a report (format 2026-10-31).
function report_dates()
{
    $from = trim((string)($_GET['from'] ?? ''));
    $to = trim((string)($_GET['to'] ?? ''));

    foreach ([$from, $to] as $value) {
        $date = DateTime::createFromFormat('Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Please choose a valid From and To date');
        }
    }
    if ($from > $to) {
        throw new InvalidArgumentException('From date cannot be after To date');
    }
    return [$from, $to];
}
