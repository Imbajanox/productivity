<?php
/**
 * API: Export Time Entries
 * GET /api/time/export.php
 */

require_once __DIR__ . '/../../includes/init.php';

requireAuth();

try {
    $userId = getCurrentUserId();
    
    $format = $_GET['format'] ?? 'csv';
    $period = $_GET['period'] ?? 'month';
    $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
    $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
    $dateTo = $_GET['date_to'] ?? date('Y-m-d');
    
    // Calculate date range based on period
    switch ($period) {
        case 'week':
            $dateFrom = date('Y-m-d', strtotime('monday this week'));
            $dateTo = date('Y-m-d', strtotime('sunday this week'));
            break;
        case 'month':
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-t');
            break;
        case 'year':
            $dateFrom = date('Y-01-01');
            $dateTo = date('Y-12-31');
            break;
        case 'custom':
            // Use provided dates
            break;
    }
    
    // Build query
    $conditions = ['te.user_id = ?', 'DATE(te.start_time) >= ?', 'DATE(te.start_time) <= ?'];
    $params = [$userId, $dateFrom, $dateTo];
    
    if ($projectId) {
        $conditions[] = 'te.project_id = ?';
        $params[] = $projectId;
    }
    
    $whereClause = implode(' AND ', $conditions);
    
    $entries = dbFetchAll(
        "SELECT 
            te.*,
            p.name as project_name,
            t.title as todo_title
         FROM time_entries te
         LEFT JOIN projects p ON te.project_id = p.id
         LEFT JOIN todos t ON te.todo_id = t.id
         WHERE $whereClause
         ORDER BY te.start_time ASC",
        $params
    );
    
    // Format duration
    $formatDuration = function($seconds) {
        if (!$seconds) return '00:00:00';
        $hours = floor($seconds / 3600);
        $mins = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
    };
    
    $formatDurationDecimal = function($seconds) {
        if (!$seconds) return '0.00';
        return number_format($seconds / 3600, 2);
    };
    
    if ($format === 'csv') {
        // Generate CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="zeiterfassung_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // BOM for Excel UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header
        fputcsv($output, [
            'Datum',
            'Startzeit',
            'Endzeit',
            'Dauer (hh:mm:ss)',
            'Dauer (Dezimal)',
            'Projekt',
            'Aufgabe',
            'Beschreibung',
            'Pause'
        ], ';');
        
        foreach ($entries as $entry) {
            fputcsv($output, [
                date('d.m.Y', strtotime($entry['start_time'])),
                date('H:i', strtotime($entry['start_time'])),
                $entry['end_time'] ? date('H:i', strtotime($entry['end_time'])) : '-',
                $formatDuration($entry['duration_seconds']),
                $formatDurationDecimal($entry['duration_seconds']),
                $entry['project_name'] ?? '-',
                $entry['todo_title'] ?? '-',
                $entry['description'] ?? '-',
                $entry['is_break'] ? 'Ja' : 'Nein'
            ], ';');
        }
        
        // Summary row
        $totalSeconds = array_sum(array_column($entries, 'duration_seconds'));
        fputcsv($output, [], ';');
        fputcsv($output, [
            'Gesamt:',
            '',
            '',
            $formatDuration($totalSeconds),
            $formatDurationDecimal($totalSeconds),
            '',
            '',
            '',
            ''
        ], ';');
        
        fclose($output);
        exit;
        
    } elseif ($format === 'pdf') {
        // For PDF, we return data that will be rendered client-side
        // This is simpler and doesn't require server-side PDF libraries
        
        $user = getCurrentUser();
        
        // Group entries by date
        $grouped = [];
        foreach ($entries as $entry) {
            $date = date('Y-m-d', strtotime($entry['start_time']));
            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'entries' => [],
                    'total_seconds' => 0
                ];
            }
            $grouped[$date]['entries'][] = $entry;
            $grouped[$date]['total_seconds'] += $entry['duration_seconds'] ?? 0;
        }
        
        // Project summary
        $projectSummary = [];
        foreach ($entries as $entry) {
            $projectName = $entry['project_name'] ?? 'Kein Projekt';
            if (!isset($projectSummary[$projectName])) {
                $projectSummary[$projectName] = 0;
            }
            $projectSummary[$projectName] += $entry['duration_seconds'] ?? 0;
        }
        arsort($projectSummary);
        
        $totalSeconds = array_sum(array_column($entries, 'duration_seconds'));
        
        jsonResponse([
            'success' => true,
            'data' => [
                'title' => 'Zeiterfassung',
                'subtitle' => 'Bericht vom ' . date('d.m.Y', strtotime($dateFrom)) . ' bis ' . date('d.m.Y', strtotime($dateTo)),
                'user' => $user['username'],
                'generated' => date('d.m.Y H:i'),
                'summary' => [
                    'total_entries' => count($entries),
                    'total_duration' => $formatDuration($totalSeconds),
                    'total_hours' => $formatDurationDecimal($totalSeconds),
                    'days_worked' => count($grouped)
                ],
                'by_project' => array_map(function($name, $seconds) use ($formatDuration, $formatDurationDecimal) {
                    return [
                        'name' => $name,
                        'duration' => $formatDuration($seconds),
                        'hours' => $formatDurationDecimal($seconds)
                    ];
                }, array_keys($projectSummary), array_values($projectSummary)),
                'by_date' => array_map(function($date, $data) use ($formatDuration) {
                    return [
                        'date' => date('d.m.Y', strtotime($date)),
                        'weekday' => strftime('%A', strtotime($date)),
                        'entries' => count($data['entries']),
                        'duration' => $formatDuration($data['total_seconds'])
                    ];
                }, array_keys($grouped), array_values($grouped)),
                'entries' => array_map(function($entry) use ($formatDuration) {
                    return [
                        'date' => date('d.m.Y', strtotime($entry['start_time'])),
                        'start' => date('H:i', strtotime($entry['start_time'])),
                        'end' => $entry['end_time'] ? date('H:i', strtotime($entry['end_time'])) : '-',
                        'duration' => $formatDuration($entry['duration_seconds']),
                        'project' => $entry['project_name'] ?? '-',
                        'description' => $entry['description'] ?? '-',
                        'is_break' => (bool)$entry['is_break']
                    ];
                }, $entries)
            ]
        ]);
        
    } else {
        jsonResponse(['success' => false, 'error' => 'Ungültiges Format. Verwende csv oder pdf.'], 400);
    }
    
} catch (Exception $e) {
    error_log("Error in time/export.php: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Fehler beim Export'], 500);
}
