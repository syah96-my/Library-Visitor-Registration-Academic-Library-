<?php

function simpleEncode($token) {
    return base64_encode($token);
}

function simpleDecode($encodedToken) {
    return base64_decode($encodedToken, true);
}

?>
