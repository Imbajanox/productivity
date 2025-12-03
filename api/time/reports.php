<?php
/**
 * API: Detailed Time Statistics and Charts Data
 * GET /api/time/reports.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

try {
    $userId = getCurrentUserId();
    
    $period = $_GET['period'] ?? 'week';
    $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
    $dateFrom = $_GET['date_from'] ?? null;
    $dateTo = $_GET['date_to'] ?? null;
    
    // Calculate date range based on period
    switch ($period) {
        case 'today':
            $dateFrom = date('Y-m-d');
            $dateTo = date('Y-m-d');
            break;
        case 'yesterday':
            $dateFrom = date('Y-m-d', strtotime('-1 day'));
            $dateTo = date('Y-m-d', strtotime('-1 day'));
            break;
        case 'week':
            $dateFrom = date('Y-m-d', strtotime('monday this week'));
            $dateTo = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'last_week':
            $dateFrom = date('Y-m-d', strtotime('monday last week'));
            $dateTo = date('Y-m-d', strtotime('sunday last week'));
            break;
        case 'month':
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-t');
            break;
        case 'last_month':
            $dateFrom = date('Y-m-01', strtotime('first day of last month'));
            $dateTo = date('Y-m-t', strtotime('last day of last month'));
            break;
        case 'year':
            $dateFrom = date('Y-01-01');
            $dateTo = date('Y-12-31');
            break;
        case 'custom':
            // Use provided dates
            if (!$dateFrom || !$dateTo) {
                $dateFrom = date('Y-m-d', strtotime('-30 days'));
                $dateTo = date('Y-m-d');
            }
            break;
    }
    
    // Build base query conditions
    $conditions = ['te.user_id = ?', 'DATE(te.start_time) >= ?', 'DATE(te.start_time) <= ?', 'te.duration_seconds IS NOT NULL'];
    $params = [$userId, $dateFrom, $dateTo];
    
    if ($projectId) {
        $conditions[] = 'te.project_id = ?';
        $params[] = $projectId;
    }
    
    $whereClause = implode(' AND ', $conditions);
    
    // Total time summary
    $summary = dbFetchOne(
        "SELECT 
            COUNT(*) as total_entries,
            SUM(duration_seconds) as total_seconds,
            SUM(CASE WHEN is_break = 1 THEN duration_seconds ELSE 0 END) as break_seconds,
            SUM(CASE WHEN is_break = 0 OR is_break IS NULL THEN duration_seconds ELSE 0 END) as work_seconds,
            AVG(duration_seconds) as avg_entry_seconds,
            COUNT(DISTINCT DATE(start_time)) as days_worked
         FROM time_entries te
         WHERE $whereClause",
        $params
    );
    
    // Daily breakdown for charts
    $dailyData = dbFetchAll(
        "SELECT 
            DATE(te.start_time) as date,
            SUM(CASE WHEN te.is_break = 0 OR te.is_break IS NULL THEN te.duration_seconds ELSE 0 END) as work_seconds,
            SUM(CASE WHEN te.is_break = 1 THEN te.duration_seconds ELSE 0 END) as break_seconds,
            COUNT(*) as entries
         FROM time_entries te
         WHERE $whereClause
         GROUP BY DATE(te.start_time)
         ORDER BY date ASC",
        $params
    );
    
    // Project breakdown
    $projectData = dbFetchAll(
        "SELECT 
            COALESCE(p.id, 0) as project_id,
            COALESCE(p.name, 'Kein Projekt') as project_name,
            COALESCE(p.color, '#6c757d') as project_color,
            SUM(te.duration_seconds) as total_seconds,
            COUNT(*) as entries
         FROM time_entries te
         LEFT JOIN projects p ON te.project_id = p.id
         WHERE $whereClause
         GROUP BY p.id, p.name, p.color
         ORDER BY total_seconds DESC",
        $params
    );
    
    // Hourly distribution (which hours do you work most)
    $hourlyData = dbFetchAll(
        "SELECT 
            HOUR(te.start_time) as hour,
            SUM(te.duration_seconds) as total_seconds,
            COUNT(*) as entries
         FROM time_entries te
         WHERE $whereClause
         GROUP BY HOUR(te.start_time)
         ORDER BY hour ASC",
        $params
    );
    
    // Weekly pattern (which days do you work most)
    $weekdayData = dbFetchAll(
        "SELECT 
            DAYOFWEEK(te.start_time) as day_num,
            DAYNAME(te.start_time) as day_name,
            SUM(te.duration_seconds) as total_seconds,
            COUNT(DISTINCT DATE(te.start_time)) as days_count,
            AVG(te.duration_seconds) as avg_seconds
         FROM time_entries te
         WHERE $whereClause
         GROUP BY DAYOFWEEK(te.start_time), DAYNAME(te.start_time)
         ORDER BY day_num ASC",
        $params
    );
    
    // Recent entries for the period
    $recentEntries = dbFetchAll(
        "SELECT 
            te.*,
            p.name as project_name,
            p.color as project_color
         FROM time_entries te
         LEFT JOIN projects p ON te.project_id = p.id
         WHERE $whereClause
         ORDER BY te.start_time DESC
         LIMIT 50",
        $params
    );
    
    // Format helper
    $formatDuration = function($seconds) {
        if (!$seconds) return '00:00';
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);
        return sprintf('%d:%02d', $hours, $mins);
    };
    
    $formatDurationLong = function($seconds) {
        if (!$seconds) return '0 Stunden';
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);
        if ($hours > 0) {
            return $hours . ' Std. ' . $mins . ' Min.';
        }
        return $mins . ' Min.';
    };
    
    jsonResponse([
        'success' => true,
        'data' => [
            'period' => [
                'type' => $period,
                'from' => $dateFrom,
                'to' => $dateTo
            ],
            'summary' => [
                'total_entries' => (int)$summary['total_entries'],
                'total_seconds' => (int)$summary['total_seconds'],
                'total_formatted' => $formatDuration($summary['total_seconds']),
                'total_long' => $formatDurationLong($summary['total_seconds']),
                'work_seconds' => (int)$summary['work_seconds'],
                'work_formatted' => $formatDuration($summary['work_seconds']),
                'break_seconds' => (int)$summary['break_seconds'],
                'break_formatted' => $formatDuration($summary['break_seconds']),
                'avg_entry_seconds' => (int)$summary['avg_entry_seconds'],
                'avg_entry_formatted' => $formatDuration($summary['avg_entry_seconds']),
                'days_worked' => (int)$summary['days_worked'],
                'avg_per_day' => $summary['days_worked'] > 0 
                    ? $formatDuration($summary['total_seconds'] / $summary['days_worked'])
                    : '00:00'
            ],
            'daily' => array_map(function($row) use ($formatDuration) {
                return [
                    'date' => $row['date'],
                    'work_seconds' => (int)$row['work_seconds'],
                    'work_formatted' => $formatDuration($row['work_seconds']),
                    'break_seconds' => (int)$row['break_seconds'],
                    'break_formatted' => $formatDuration($row['break_seconds']),
                    'entries' => (int)$row['entries']
                ];
            }, $dailyData),
            'by_project' => array_map(function($row) use ($formatDuration) {
                return [
                    'project_id' => (int)$row['project_id'],
                    'project_name' => $row['project_name'],
                    'project_color' => $row['project_color'],
                    'total_seconds' => (int)$row['total_seconds'],
                    'total_formatted' => $formatDuration($row['total_seconds']),
                    'entries' => (int)$row['entries']
                ];
            }, $projectData),
            'by_hour' => array_map(function($row) use ($formatDuration) {
                return [
                    'hour' => (int)$row['hour'],
                    'label' => sprintf('%02d:00', $row['hour']),
                    'total_seconds' => (int)$row['total_seconds'],
                    'total_formatted' => $formatDuration($row['total_seconds']),
                    'entries' => (int)$row['entries']
                ];
            }, $hourlyData),
            'by_weekday' => array_map(function($row) use ($formatDuration) {
                $dayNames = [
                    1 => 'Sonntag', 2 => 'Montag', 3 => 'Dienstag', 
                    4 => 'Mittwoch', 5 => 'Donnerstag', 6 => 'Freitag', 7 => 'Samstag'
                ];
                return [
                    'day_num' => (int)$row['day_num'],
                    'day_name' => $dayNames[$row['day_num']] ?? $row['day_name'],
                    'total_seconds' => (int)$row['total_seconds'],
                    'total_formatted' => $formatDuration($row['total_seconds']),
                    'days_count' => (int)$row['days_count']
                ];
            }, $weekdayData),
            'entries' => $recentEntries
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in time/reports.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Laden der Berichte'], 500);
}
