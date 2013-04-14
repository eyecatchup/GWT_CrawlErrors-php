<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'GwtCrawlErrors.class.php';

/**
 * Example 3:
 * Save a CSV for each domain connected to the GWT account
 * to a specific local path.
 */
try {
    $mail = 'eyecatchup@gmail.com';
    $pass = '********';

    $gwtCrawlErrors = new GwtCrawlErrors();

    if ($gwtCrawlErrors->login($mail, $pass)) {
        // iterate over all connected domains
        $sites = $gwtCrawlErrors->getSites();
        foreach ($sites as $domain) {
            // use an absolute path without trailing slash as
            // a second parameter, to write data to file system.
            $gwtCrawlErrors->getCsv($domain, __DIR__);
        }

    }
}
catch (Exception $e) {
    die($e->getMessage());
}