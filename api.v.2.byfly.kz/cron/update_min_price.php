<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');

try {
    $getMonitor = $db->query("SELECT * FROM monitor WHERE id='1'")->fetch_assoc();

    if ($getMonitor['update_min_price'] == 0) {
        $db->query("UPDATE monitor SET update_min_price='1' WHERE id='1'");
        $deportCtDB = $db->query("SELECT * FROM departure_citys GROUP BY countryid");
        while ($dp = $deportCtDB->fetch_assoc()) {
            $dp['country_info'] = $db->query("SELECT * FROM countries WHERE id='" . $dp['countryid'] . "'")->fetch_assoc();
            if ($dp['country_info'] !== null) {
                $dp['country_info']['min_price'] = getMinPriceToCountryAndRegion($dp['country_info']['visor_id']);

                $db->query("UPDATE countries SET min_price='" . $dp['country_info']['min_price'] . "' WHERE id='" . $dp['id'] . "'");

                $listCitysDB = $db->query("SELECT * FROM departure_citys WHERE countryid='" . $dp['countryid'] . "'");
                while ($listCitys = $listCitysDB->fetch_assoc()) {
                    $listCitys['min_price'] = getMinPriceToCountryAndRegion($dp['country_info']['visor_id'], $listCitys['id_visor']);
                    $db->query("UPDATE departure_citys SET min_price='" . $listCitys['min_price'] . "' WHERE id='" . $listCitys['id'] . "'");
                }
            }
        }



        $listCountriesDB = $db->query("SELECT * FROM countries");
        while ($listCountries = $listCountriesDB->fetch_assoc()) {
            $listCountries['min_price'] = 0;

            if ($listCountries['visor_id'] > 0) {
                $listCountries['min_price'] = getMinPriceToCountryAndRegion($listCountries['visor_id']);
                $db->query("UPDATE countries SET min_price='" . $listCountries['min_price'] . "' WHERE id='" . $listCountries['id'] . "'");
            }

            $listRegDB = $db->query("SELECT * FROM regions WHERE countryid='" . $listCountries['id'] . "'");
            while ($listReg = $listRegDB->fetch_assoc()) {
                $listReg['min_price'] = getMinPriceToCountryAndRegion($listCountries['visor_id'], $listReg['visor_id']);

                $db->query("UPDATE regions SET min_price='" . $listReg['min_price'] . "' WHERE id='" . $listReg['id'] . "'");

            }
        }
        $db->query("UPDATE monitor SET update_min_price='0' WHERE id='1'");
    }
} catch (\Throwable $th) {
    $db->query("UPDATE monitor SET update_min_price='0' WHERE id='1'");
}

$db->close();
$db2->close();
$db_docs->close();

?>