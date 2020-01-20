<?php

$aliases = [/*--- ALIASES ---*/];

foreach ($aliases as $tighten => $illuminate) {
    if (! class_exists($illuminate) && ! interface_exists($illuminate) && ! trait_exists($illuminate)) {
        class_alias($tighten, $illuminate);
    }
}
