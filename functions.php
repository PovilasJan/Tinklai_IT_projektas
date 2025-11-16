<?php
require_once __DIR__ . '/db.php';

function isLoggedIn(){
    return !empty($_SESSION['user']);
}

function currentUser(){
    return $_SESSION['user'] ?? null;
}

function requireLogin(){
    if (!isLoggedIn()){
        header('Location: login.php'); exit;
    }
}

function hasRole($role){
    $u = currentUser();
    return $u && $u['role'] === $role;
}

function hasConfirmedReservation($userId){
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status = "confirmed"');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn() > 0;
}

function applyDiscount($userId, $amount, $discountCode = null){
    $pdo = getPDO();
    
    $discount = 0;
    $codeId = null;

    // Check if discount code is provided and valid
    if ($discountCode){
        $codeStmt = $pdo->prepare('SELECT id, discount_percent, usage_limit, times_used FROM discount_codes WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())');
        $codeStmt->execute([$discountCode]);
        $codeData = $codeStmt->fetch();
        if ($codeData && ($codeData['usage_limit'] === null || $codeData['times_used'] < $codeData['usage_limit'])){
            $discount = (int)$codeData['discount_percent'];
            $codeId = $codeData['id'];
        }
    }

    $final = $amount - ($amount * $discount / 100.0);
    return [
        'final' => round($final,2), 
        'discount' => $discount, 
        'code_id' => $codeId,
        'discount_type' => $discount > 0 ? 'code' : 'none'
    ];
}
