<?php
$site = $content['site'];
?><!DOCTYPE html>
<html lang="<?= e($site['language'] ?? 'en') ?>">
<head>
<?php include __DIR__ . '/meta.php'; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css?family=Montserrat:900" rel="stylesheet">
<link rel="stylesheet" href="assets/styles.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vanta@latest/dist/vanta.waves.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/baffle@0.3.6/dist/baffle.min.js"></script>
</head>
<body>
<div id="vanta-bg"></div>
<div class="main-content-z">
  <section class="nav" id="home">
    <div class="intro">
