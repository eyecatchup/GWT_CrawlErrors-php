<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'GwtCrawlErrors.class.php';

/**
 * Example 2:
 * Save a CSV for a specific domain to a specific local path.
 */
try {
    $mail = 'eyecatchup@gmail.com';
    $pass = '********';

    $domain = 'http://www.domain.tld/'; // must have trailing slash!

    $gwtCrawlErrors = new GwtCrawlErrors();

    if ($gwtCrawlErrors->login($mail, $pass)) {
        // save the crawl errors to a local path
        $gwtCrawlErrors->getCsv($domain, __DIR__);
    }
}
catch (Exception $e) {
    die($e->getMessage());
}