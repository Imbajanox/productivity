<?php
/**
 * API: Time Statistics
 * GET /api/time/stats.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

try {
    $userId = getCurrentUserId();
    
    // Today
    $today = date('Y-m-d');
    $todayResult = dbFetchOne(
        "SELECT SUM(duration_seconds) as total FROM time_entries 
         WHERE user_id = ? AND DATE(start_time) = ? AND duration_seconds IS NOT NULL",
        [$userId, $today]
    );
    
    // This week
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));
    $weekResult = dbFetchOne(
        "SELECT SUM(duration_seconds) as total FROM time_entries 
         WHERE user_id = ? AND DATE(start_time) >= ? AND DATE(start_time) <= ? AND duration_seconds IS NOT NULL",
        [$userId, $weekStart, $weekEnd]
    );
    
    // This month
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    $monthResult = dbFetchOne(
        "SELECT SUM(duration_seconds) as total FROM time_entries 
         WHERE user_id = ? AND DATE(start_time) >= ? AND DATE(start_time) <= ? AND duration_seconds IS NOT NULL",
        [$userId, $monthStart, $monthEnd]
    );
    
    // Format durations
    $formatDuration = function($seconds) {
        if (!$seconds) return '00:00';
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $mins);
    };
    
    jsonResponse([
        'success' => true,
        'data' => [
            'today' => $formatDuration($todayResult['total'] ?? 0),
            'week' => $formatDuration($weekResult['total'] ?? 0),
            'month' => $formatDuration($monthResult['total'] ?? 0),
            'today_seconds' => (int)($todayResult['total'] ?? 0),
            'week_seconds' => (int)($weekResult['total'] ?? 0),
            'month_seconds' => (int)($monthResult['total'] ?? 0)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in time/stats.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Laden der Statistiken'], 500);
}
