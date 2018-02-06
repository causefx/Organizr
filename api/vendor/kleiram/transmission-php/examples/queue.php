<?php
require_once __DIR__.'/../vendor/autoload.php';

$transmission = new Transmission\Transmission();
$queue = $transmission->all();

echo "Downloading to: {$transmission->getSession()->getDownloadDir()}\n";

foreach ($queue as $torrent) {
    echo "{$torrent->getName()}";

    if ($torrent->isFinished()) {
        echo ": done\n";
    } else {
        if ($torrent->isDownloading()) {
            echo ": {$torrent->getPercentDone()}% ";
            echo "(eta: ". gmdate("H:i:s", $torrent->getEta()) .")\n";
        } else{
            echo ": paused\n";
        }
    }
}

// Change download directories
// $session = $transmission->getSession();
// $session->setDownloadDir('/var/www/downloads/complete');
// $session->setIncompleteDir('/tmp/downloads');
// $session->save();
