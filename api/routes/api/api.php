<?php


Route::name('webhooks.')->group(function () {
    require __DIR__ . '/api/Webhooks/index.php';
});
