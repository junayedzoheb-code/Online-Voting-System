<?php
// Shared <head>. Set $page_title before including. $asset_prefix is ''
// for top-level pages and '../' for pages inside /admin.
$asset_prefix = $asset_prefix ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($page_title ?? 'Online Voting System') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700;800&display=swap">
  <link rel="stylesheet" href="<?= $asset_prefix ?>assets/style.css">
</head>
