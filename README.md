# GwtCrawlErrors: Download website crawl errors from Google Webmaster Tools as CSV.

## Introduction

This project provides an easy way to automate downloading of crawl errors from Google Webmaster Tools.

## Usage

This document explains how to automate the file download process from Google Webmaster Tools by showing examples for using the php class `GwtCrawlErrors`.

### Get started

To get started, the steps are as follows:

 - Download the php file <a target="_blank" href="https://raw.github.com/eyecatchup/GWT_CrawlErrors-php/master/GwtCrawlErrors.class.php.php">`GwtCrawlErrors.class.php`</a>.
 - Create a folder and add the <a target="_blank" href="https://raw.github.com/eyecatchup/GWT_CrawlErrors-php/master/GwtCrawlErrors.class.php.php">`GwtCrawlErrors.class.php`</a> script to it.

### Note

This class will download <strong>all</strong> crawl errors that are currently listed for a domain in Webmaster Tools. Depending on your domain, this can be a lot of data. The csv file size for 25k crawl errors, for example, is somewhere between 5 and 8 Mb. So, please consider that processes may take some time!
 
### Example 1 - Download via browser

To download CSV data for a single domain name via a web browser, the steps are as follows:

 - In the same folder where you added the `GwtCrawlErrors.class.php`, create and run the following PHP script.<br>_You'll need to replace the example values for "mail" and "pass" with valid login details for your Google Account and for "domain" with a valid URL for a site registered in your GWT account._

```php
<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'GwtCrawlErrors.class.php';

/**
 * Example 1:
 * Download a CSV for a specific domain from the browser.
 */
try {
    $mail = 'eyecatchup@gmail.com';
    $pass = '********';

    $domain = 'http://www.domain.tld/'; // must have trailing slash!

    $gwtCrawlErrors = new GwtCrawlErrors();

    if ($gwtCrawlErrors->login($mail, $pass)) {
        // force download in browser (using http headers)
        $gwtCrawlErrors->getCsv($domain);
    }
}
catch (Exception $e) {
    die($e->getMessage());
}
```

This will force a download in your browser (using HTTP headers) to download one CSV file named `gwt-crawlerrors-www.domain.com-YYYYmmdd-H:i:s.csv`.


### Example 2 - Download to filesystem

To download CSV data for a single domain name directly to the local file system (eg. when executing from command line), the steps are as follows:

 - In the same folder where you added the `GwtCrawlErrors.class.php`, create and run the following PHP script.<br>_You'll need to replace the example values for "mail" and "pass" with valid login details for your Google Account and for "domain" with a valid URL for a site registered in your GWT account._

```php
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
```

This will create one CSV file named `gwt-crawlerrors-www.domain.com-YYYYmmdd-H:i:s.csv` in the specified path.


### Example 3 - Bulk downloads

To download CSV data for each domain connected to the Google WMT account to the local file system (eg. when executing from command line), the steps are as follows:

 - In the same folder where you added the `GwtCrawlErrors.class.php`, create and run the following PHP script.<br>_You'll need to replace the example values for "mail" and "pass" with valid login details for your Google Account and for "domain" with a valid URL for a site registered in your GWT account._

```php
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
        foreach($sites as $domain) {
            $gwtCrawlErrors->getCsv($domain, __DIR__);
        }

    }
}
catch (Exception $e) {
    die($e->getMessage());
}
```

This will create a CSV file named `gwt-crawlerrors-www.domain.com-YYYYmmdd-H:i:s.csv`, for each domain connected to the Google WMT account, in the specified path.

## License

(c) 2013 - now, Stephan Schmitz eyecatchup@gmail.com   
License: MIT, http://eyecatchup.mit-license.org   
URL: https://github.com/eyecatchup/GWT_CrawlErrors-php   