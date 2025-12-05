<?php
include('/var/www/www-root/data/www/api.v.2.byfly.kz/config.php');


try {
    $getMonitor = $db->query("SELECT * FROM monitor WHERE id='1'")->fetch_assoc();

    if ($getMonitor['clear_searched'] == 0) {
        $db->query("UPDATE monitor SET clear_searched='1' WHERE id='1'");
        $currentDate = new DateTime();
        $currentDate->modify('-2 days');

        $listSearchedDB = $db->query("SELECT * FROM tours_searched WHERE date_create < '" . $currentDate->format('Y-m-d H:i:s') . "'");
        while ($listSearched = $listSearchedDB->fetch_assoc()) {
            $db->query("DELETE FROM tours_searched WHERE id = '" . $listSearched['id'] . "'");
            $db->query("DELETE FROM tours_searched_details WHERE parrent_id = '" . $listSearched['id'] . "'");
        }

        $db->query("DELETE FROM tours_searched_details WHERE flydate < '" . date('Y-m-d H:i:s') . "'");
        $db->query("DELETE FROM hot_tours_searched WHERE flydate < '" . date('Y-m-d H:i:s') . "'");
        $db->query("DELETE FROM hot_tours_searched WHERE date_create < '" . $currentDate->format('Y-m-d H:i:s') . "'");



        $dateString = '01.01.2015';
        $date = DateTime::createFromFormat('d.m.Y', $dateString);
        $formattedDate = $date->format('Y-m-d H:i:s');
        $db->query("UPDATE monitor SET clear_searched='0' WHERE id='1'");

        echo $formattedDate;
    }
} catch (\Throwable $th) {
    $db->query("UPDATE monitor SET clear_searched='0' WHERE id='1'");
}

$db->close();
$db2->close();
$db_docs->close();

?>