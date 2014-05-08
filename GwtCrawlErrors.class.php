<?php
ini_set('max_execution_time', 600);
ini_set('memory_limit', '64M');


/** GwtCrawlErrors
 *  ================================================================================
 *  PHP class to download crawl errors from Google webmaster tools as csv.
 *  ================================================================================
 *  @category
 *  @package     GwtCrawlErrors
 *  @copyright   2013 - present, Stephan Schmitz
 *  @license     http://eyecatchup.mit-license.org
 *  @version     CVS: $Id: GwtCrawlErrors.class.php, v1.0.1 Rev 11 2014/05/08 16:28:43 ssc Exp $
 *  @author      Stephan Schmitz <eyecatchup@gmail.com>
 *  @link        https://github.com/eyecatchup/GWT_CrawlErrors-php/
 *  ================================================================================
 *  LICENSE: Permission is hereby granted, free of charge, to any person obtaining
 *  a copy of this software and associated documentation files (the "Software'),
 *  to deal in the Software without restriction, including without limitation the
 *  rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *
 *    The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY
 *  WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 *  CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *  ================================================================================
 */

class GwtCrawlErrors
{
    const HOST = "https://www.google.com";
    const SERVICEURI = "/webmasters/tools/";

    public function __construct()
    {
        $this->_auth = $this->_loggedIn = $this->_domain = false;
        $this->_data = array();
    }

    public function getArray($domain)
    {
        if ($this->_validateDomain($domain)) {
            if ($this->_prepareData()) {
                return $this->_data;
            }
            else {
                throw new Exception('Error receiving crawl issues for ' . $domain);
            }
        }
        else {
            throw new Exception('The given domain is not connected to your Webmastertools account!');
            exit;
        }
    }

    public function getCsv($domain, $localPath = false)
    {
        if ($this->_validateDomain($domain)) {
            if ($this->_prepareData()) {
                if (!$localPath) {
                    $this->_HttpHeaderCSV();
                    $this->_outputCSV();
                }
                else {
                    $this->_outputCSV($localPath);
                }
            }
            else {
                throw new Exception('Error receiving crawl issues for ' . $domain);
            }
        }
        else {
            throw new Exception('The given domain is not connected to your Webmastertools account!');
            exit;
        }
    }

    public function getSites()
    {
        if($this->_loggedIn) {
            $feed = $this->_getData('feeds/sites/');
            if ($feed) {
                $doc = new DOMDocument();
                $doc->loadXML($feed);

                $sites = array();
                foreach ($doc->getElementsByTagName('entry') as $node) {
                    array_push($sites,
                      $node->getElementsByTagName('title')->item(0)->nodeValue);
                }

                return (0 < sizeof($sites)) ? $sites : false;
            }
            else {
                return false;
            }
        }
        else {
            return false;
        }
    }

    public function login($mail, $pass)
    {
        $postRequest = array(
            'accountType' => 'HOSTED_OR_GOOGLE',
            'Email'       => $mail,
            'Passwd'      => $pass,
            'service'     => "sitemaps",
            'source'      => "Google-WMTdownloadscript-0.11-php"
        );

        // Before PHP version 5.2.0 and when the first char of $pass is an @ symbol, 
        // send data in CURLOPT_POSTFIELDS as urlencoded string.
        if ('@' === (string)$pass[0] || version_compare(PHP_VERSION, '5.2.0') < 0) {
            $postRequest = http_build_query($postRequest);
        }

        $ch = curl_init(self::HOST . '/accounts/ClientLogin');
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $postRequest
        ));

        $output = curl_exec($ch);
        $info   = curl_getinfo($ch);
        curl_close($ch);

        if (200 != $info['http_code']) {
            throw new Exception('Login failed!');
            exit;
        }
        else {
            @preg_match('/Auth=(.*)/', $output, $match);
            if (isset($match[1])) {
                $this->_auth = $match[1];
                $this->_loggedIn = true;
                return true;
            }
            else {
                throw new Exception('Login failed!');
                exit;
            }
        }
    }

    private function _prepareData()
    {
        if ($this->_loggedIn) {
            $currentIndex = 1;
            $maxResults   = 100;

            $encUri = urlencode($this->_domain);

            /*
             * Get the total result count / result page count
             */
            $feed = $this->_getData("feeds/{$encUri}/crawlissues?start-index=1&max-results=1");
            if (!$feed) {
                return false;
            }

            $doc = new DOMDocument();
            $doc->loadXML($feed);

            $totalResults = (int)$doc->getElementsByTagNameNS('http://a9.com/-/spec/opensearch/1.1/', 'totalResults')->item(0)->nodeValue;
            $resultPages  = (0 != $totalResults) ? ceil($totalResults / $maxResults) : false;

            unset($feed, $doc);

            if (!$resultPages) {
                return false;
            }

            /*
             * Paginate over issue feeds
             */
            else {
                // Csv data headline
                $this->_data = Array(
                    Array('Issue Id', 'Crawl type', 'Issue type', 'Detail', 'URL', 'Date detected', 'Last detected')
                );

                while ($currentIndex <= $resultPages) {
                    $startIndex = ($maxResults * ($currentIndex - 1)) + 1;

                    $feed = $this->_getData("feeds/{$encUri}/crawlissues?start-index={$startIndex}&max-results={$maxResults}");
                    $doc  = new DOMDocument();
                    $doc->loadXML($feed);

                    foreach ($doc->getElementsByTagName('entry') as $node) {
                        $issueId = str_replace(
                            self::HOST . self::SERVICEURI . "feeds/{$encUri}/crawlissues/",
                            '',
                            $node->getElementsByTagName('id')->item(0)->nodeValue
                        );
                        $crawlType    = $node->getElementsByTagNameNS('http://schemas.google.com/webmasters/tools/2007', 'crawl-type')->item(0)->nodeValue;
                        $issueType    = $node->getElementsByTagNameNS('http://schemas.google.com/webmasters/tools/2007', 'issue-type')->item(0)->nodeValue;
                        $detail       = $node->getElementsByTagNameNS('http://schemas.google.com/webmasters/tools/2007', 'detail')->item(0)->nodeValue;
                        $url          = $node->getElementsByTagNameNS('http://schemas.google.com/webmasters/tools/2007', 'url')->item(0)->nodeValue;
                        $dateDetected = date('d/m/Y', strtotime($node->getElementsByTagNameNS('http://schemas.google.com/webmasters/tools/2007', 'date-detected')->item(0)->nodeValue));
                        $updated      = date('d/m/Y', strtotime($node->getElementsByTagName('updated')->item(0)->nodeValue));

                        // add issue data to results array
                        array_push($this->_data,
                            Array($issueId, $crawlType, $issueType, $detail, $url, $dateDetected, $updated)
                        );
                    }

                    unset($feed, $doc);
                    $currentIndex++;
                }
                return true;
            }
        }
        else {
            return false;
        }
    }

    private function _getData($url)
    {
        if ($this->_loggedIn) {
            $header = array(
                'Authorization: GoogleLogin auth=' . $this->_auth,
                'GData-Version: 2'
            );

            $ch = curl_init(self::HOST . self::SERVICEURI . $url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_ENCODING       => 1,
                CURLOPT_HTTPHEADER     => $header
            ));

            $result = curl_exec($ch);
            $info   = curl_getinfo($ch);
            curl_close($ch);

            return (200 != $info['http_code']) ? false : $result;
        }
        else {
            return false;
        }
    }

    private function _HttpHeaderCSV() {
        header('Content-type: text/csv; charset=utf-8');
        header('Content-disposition: attachment; filename=gwt-crawlerrors-' .
            $this->_getFilename());
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    private function _outputCSV($localPath = false) {
        $outstream = !$localPath ? 'php://output' : $localPath . DIRECTORY_SEPARATOR . $this->_getFilename();
        $outstream = fopen($outstream, "w");
        if (!function_exists('__outputCSV')) {
            function __outputCSV(&$vals, $key, $filehandler) {
                fputcsv($filehandler, $vals); // add parameters if you want
            }
        }
        array_walk($this->_data, "__outputCSV", $outstream);
        fclose($outstream);
    }

    private function _getFilename()
    {
        return 'gwt-crawlerrors-' .
            parse_url($this->_domain, PHP_URL_HOST) .'-'.
            date('Ymd-His') . '.csv';
    }

    private function _validateDomain($domain)
    {
        if (!filter_var($domain, FILTER_VALIDATE_URL)) {
            return false;
        }

        $sites = $this->getSites();
        if (!$sites) {
            return false;
        }

        foreach ($sites as $url) {
            if (parse_url($domain, PHP_URL_HOST) == parse_url($url, PHP_URL_HOST)) {
                $this->_domain = $domain;
                return true;
            }
        }

        return false;
    }
}
