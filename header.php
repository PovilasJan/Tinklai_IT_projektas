<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo SITE_TITLE; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> .thumb{width:140px;height:90px;object-fit:cover;} </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container-fluid">
  <a class="navbar-brand" href="<?php echo (BASE_PATH !== '' ? rtrim(BASE_PATH,'/') . '/index.php' : '/index.php'); ?>"><?php echo SITE_TITLE; ?></a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
  <li class="nav-item"><a class="nav-link" href="<?php echo (BASE_PATH !== '' ? rtrim(BASE_PATH,'/') . '/index.php' : '/index.php'); ?>">Paieška</a></li>
      </ul>
      <ul class="navbar-nav">
        <?php if(isLoggedIn()): ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo (BASE_PATH !== '' ? rtrim(BASE_PATH,'/') . '/profile.php' : '/profile.php'); ?>"><?php echo htmlspecialchars($_SESSION['user']['name']); ?> Profilis</a></li>
          <?php if(hasRole('admin') || hasRole('employee')): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo (BASE_PATH !== '' ? rtrim(BASE_PATH,'/') . '/admin/reservations.php' : '/admin/reservations.php'); ?>">Rezervacijos</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo (BASE_PATH !== '' ? rtrim(BASE_PATH,'/') . '/admin/calendar.php' : '/admin/calendar.php'); ?>">Kalendorius</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo (BASE_PATH !== '' ? rtrim(BASE_PATH,'/') . '/admin/discounts.php' : '/admin/discounts.php'); ?>">Nuolaidos</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo (BASE_PATH !== '' ? rtrim(BASE_PATH,'/') . '/admin/newsletter.php' : '/admin/newsletter.php'); ?>">Naujienlaiškis</a></li>
          <?php endif; ?>
          <?php if(hasRole('admin')): ?><li class="nav-item"><a class="nav-link" href="<?php echo (BASE_PATH !== '' ? rtrim(BASE_PATH,'/') . '/admin/index.php' : '/admin/index.php'); ?>">Administracija</a></li><?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo (BASE_PATH !== '' ? rtrim(BASE_PATH,'/') . '/logout.php' : '/logout.php'); ?>">Atsijungti</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo (BASE_PATH !== '' ? rtrim(BASE_PATH,'/') . '/login.php' : '/login.php'); ?>">Prisijungti</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo (BASE_PATH !== '' ? rtrim(BASE_PATH,'/') . '/register.php' : '/register.php'); ?>">Registruotis</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
 </nav>
<div class="container">
