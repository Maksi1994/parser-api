<?php

namespace App\Models;

use App\Tools\DataBaseConnection;

require_once 'C:\OSPanel\domains\example-parser\libs\phpQuery.php';

class WorldLocationParserModel extends DataBaseConnection
{
    private $pdo;
    private $domain = 'http://planetolog.ru';

    public function __construct()
    {
        $this->pdo = $this->connectDataBase();
    }

    public function runParser()
    {
        $this->clearAll();

        $continentsLinks = $this->parseContinens();
        $citiesLinks = $this->parseContinentCountries($continentsLinks);
    }

    public function clearAll()
    {
        try {
            $this->pdo->query('DELETE FROM continents WHERE 1=1; DELETE FROM countries WHERE 1=1; DELETE FROM cities WHERE 1=1');
        } catch (\PDOException $err) {
            throw $err;
        }
    }

    public function parseContinens()
    {
        $continents = [];
        $mainPage = $this->requestToPage("$this->domain/city-world-list.php");
        \phpQuery::newDocument($mainPage);
        $anhors = pq(".regionbox p:first-child a");
        $sql = 'INSERT INTO continents (name) VALUES';
        $continetsCount = count($anhors) - 1;

        foreach ($anhors as $index => $a) {
            $link = pq($a);

            $href = $link->attr('href');

            if ($index < $continetsCount) {
                $sql .= "(:name$index),";
            } else if ($index == $continetsCount) {
                $sql .= "(:name$index)";
            }

            $continents[] = $href;
        }

        $stmt = $this->pdo->prepare($sql);

        foreach ($anhors as $index => $a) {
            $link = pq($a);

            $name = $link->text();
            $stmt->bindValue(":name$index", $name);
        }

        try {
            $stmt->execute();
        } catch (\PDOException $err) {
            throw $err;
        }

        return $continents;
    }

    public function parseContinentCountries($continentsLinks)
    {
        $continentsCitiesPages = $this->requestToPage($continentsLinks);

        $countriesReviewLinks = [];
        $countriesHrefs = [];
        $citiesHrefs = [];
        $capitalsHrefs = [];

        foreach ($continentsCitiesPages as $continentPage) {
            \phpQuery::newDocument($continentPage);
            $countiesAnhors = pq(".textplane:nth-child(2) .CountryList:last-child a");

            foreach ($countiesAnhors as $anhor) {
                $countryLink = pq($anhor);
                $countriesReviewLinks[] = $countryLink->attr('href');
            }
        }

        $countriesReviewPages = $this->requestToPage($countriesReviewLinks);

        foreach ($countriesReviewPages as $countryRewiewPage) {
            \phpQuery::newDocument($countryRewiewPage);
            $countyHref = pq(".textplane:nth-child(2) > a:nth-of-type(1)")->attr('href');
            $capitalHref = pq(".textplane:nth-child(2) .AncillaryBox a")->attr('href');
            $countryCitiesAnhors = pq(".'.textplane:nth-child(2) .CountryList a");

            $countriesHrefs[] = $countyHref;
            $capitalsHrefs[] = $capitalHref;

            foreach ($countryCitiesAnhors as $anhor) {
                $cityHref = pq($anhor)->attr('href');

                if ($capitalHref !== $cityHref) {
                    $citiesHrefs[] = $cityHref;
                }
            }
        }

        $countriesPages = $this->requestToPage($countriesHrefs);
        $capitalsPages = $this->requestToPage($capitalsHrefs);

        $countriesInsertQuery = "INSERT INTO countries 
        (name, description, continental_id, flag_url, short_name, population, area, population_density) VALUES";


            foreach ($countriesPages as $index => $countryPage) {
                try {
                \phpQuery::newDocument($countryPage);
                $continentName = pq('.textplane:nth-child(2) > a:nth-child(5)')->text();

                $short_name = pq('.textplane:nth-child(2) h1')->text();
                $flag = "{$this->domain}/".pq('.textplane:nth-child(2) table tr:first-of-type td:first-of-type img')->attr('src');
                $name = pq('.CountryInfoBox p:nth-child(1)')->text();
                $desciption = pq('.CountryInfoBox p:nth-child(2)')->text();

                $infoDescription = explode('<br>', pq('.textplane:nth-child(2) p:nth-child(4)')->html());
                $area = preg_replace('/\D/', '', strip_tags($infoDescription[0]));
                $population = preg_replace('/\D/', '', strip_tags($infoDescription[1]));
                $population_density = preg_replace('/\D/', '', strip_tags($infoDescription[2]));

                $continental_id = "(SELECT id FROM continents WHERE name = $continentName )";

                if ((count($countriesPages) - 1) > $index) {
                    $countriesInsertQuery .= "($name, $desciption, $continental_id, $flag, $short_name, $population, $area, $population_density),";
                } else {
                    $countriesInsertQuery .= "($name, $desciption, $continental_id, $flag, $short_name, $population, $area, $population_density)";
                }

                } catch (\Exception $e) {
                    var_dump($short_name);
                }
            }


       // var_dump($countriesInsertQuery);
        exit;

        return $citiesHrefs;
    }

    public function parseCities($citiesLinks)
    {

    }

    private function requestToPage($param)
    {
        if (is_array($param)) {
            $curls = [];
            $results = [];
            $mh = curl_multi_init();

            foreach ($param as $url) {
                $curls[$url] = curl_init();

                curl_setopt($curls[$url], CURLOPT_URL, "$this->domain/$url");
                curl_setopt($curls[$url], CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curls[$url], CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($curls[$url], CURLOPT_HEADER, 0); // no headers in the output

                curl_multi_add_handle($mh, $curls[$url]);
            }

            $running = null;

            do {
                curl_multi_exec($mh, $running);
                curl_multi_select($mh);
            } while ($running > 0);

            foreach ($curls as $curl) {
                $results[] = curl_multi_getcontent($curl);
                curl_multi_remove_handle($mh, $curl);
            }

            curl_multi_close($mh);

            return $results;

        } else {
            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $param);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

            return curl_exec($curl);
        }
    }


}