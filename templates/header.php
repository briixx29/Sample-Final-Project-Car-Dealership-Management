<?php
/**
 * Header Template
 * CarDeal Dealership Management System
 */

use App\Services\AuthService;

if (!isset($pageTitle)) {
    $pageTitle = 'CarDeal - Management System';
}
$currentUser = AuthService::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | CarDeal</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body>
<div class="app-container">
