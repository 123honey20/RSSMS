<?php
// Get Active School Year
$sy_query = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_school_year'");
$active_sy = $sy_query->fetch_assoc()['setting_value'] ?? '2025-2026';
$safe_sy = $conn->real_escape_string($active_sy);

// 1. Fetch Document Submission Data
$doc_status = ['Pending' => 0, 'Approved' => 0, 'Needs Revision' => 0];
$total_docs = 0;
$doc_query = $conn->query("
    SELECT l.status, COUNT(*) as count 
    FROM librarian l 
    JOIN students s ON l.student_id = s.id 
    WHERE s.school_year = '$safe_sy' 
    GROUP BY l.status
");
if ($doc_query) {
    while ($row = $doc_query->fetch_assoc()) {
        if (isset($doc_status[$row['status']])) {
            $doc_status[$row['status']] = (int)$row['count'];
        }
        $total_docs += (int)$row['count'];
    }
}

// 2. Fetch Document Rounds Data (Up to 7 Rounds)
$rounds_data = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0];
$round_query = $conn->query("
    SELECT l.round, COUNT(*) as count 
    FROM librarian l 
    JOIN students s ON l.student_id = s.id 
    WHERE s.school_year = '$safe_sy' 
    GROUP BY l.round
    ");
if ($round_query) {
    while ($row = $round_query->fetch_assoc()) {
        $r = (int)$row['round'];
        if ($r >= 7) {
            $rounds_data[7] += (int)$row['count'];
        } elseif ($r >= 1) {
            $rounds_data[$r] = (int)$row['count'];
        }
    }
}

// 3. Fetch Feedback Totals
$total_ratings = 0;
$total_comments = 0;
$fb_stats_query = $conn->query("
    SELECT 
        COUNT(DISTINCT ev.id) as total_ratings,
        SUM(CASE WHEN ev.comments IS NOT NULL AND TRIM(ev.comments) != '' THEN 1 ELSE 0 END) as total_comments
    FROM student_evaluations ev
    JOIN students s ON ev.student_id = s.id
    WHERE ev.service_type LIKE '%librarian%' AND s.school_year = '$safe_sy'
");
if ($fb_stats_query) {
    $fb_stats = $fb_stats_query->fetch_assoc();
    $total_ratings = (int)$fb_stats['total_ratings'];
    $total_comments = (int)$fb_stats['total_comments'];
}

// 3b. Fetch Feedback Ratings Distribution
$fb_ratings = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$fb_sum = 0;
$actual_ratings_counted = 0;

$fb_query = $conn->query("
    SELECT rating, COUNT(*) as count 
    FROM (
        SELECT ev.id, ROUND(AVG(r.score)) as rating
        FROM student_evaluations ev
        JOIN student_evaluation_ratings r ON ev.id = r.evaluation_id
        JOIN students s ON ev.student_id = s.id
        WHERE ev.service_type LIKE '%librarian%' AND s.school_year = '$safe_sy'
        GROUP BY ev.id
    ) as subquery
    GROUP BY rating
");
if ($fb_query) {
    while ($row = $fb_query->fetch_assoc()) {
        $rtg = (int)$row['rating'];
        if (isset($fb_ratings[$rtg])) {
            $fb_ratings[$rtg] = (int)$row['count'];
            $fb_sum += $rtg * (int)$row['count'];
            $actual_ratings_counted += (int)$row['count'];
        }
    }
}
$avg_rating = $actual_ratings_counted > 0 ? number_format($fb_sum / $actual_ratings_counted, 1) : "0.0";

// 4. Fetch Recent Submissions
$recent_query = $conn->query("
    SELECT s.control_number, s.thesis_title, l.status, l.round, l.uploaded_at
    FROM librarian l 
    JOIN students s ON l.student_id = s.id 
    WHERE s.school_year = '$safe_sy'
    ORDER BY l.id DESC LIMIT 5
");
?>

<div class="space-y-6 transition-colors duration-200">
    
    <!-- HEADER SECTION -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-gray-200 dark:border-warmdark-border">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Librarian Analytics</h2>
                <span class="bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800/50 text-[10px] font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">SY <?= htmlspecialchars($active_sy) ?></span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium mt-1">Comprehensive overview of submissions and feedback for the current school year.</p>
        </div>
        
        <a href="../dashboards/admin_dashboard.php?page=librarian_personnel_workload" 
           class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-md shadow-blue-500/20 transition-all flex items-center justify-center gap-2 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            View Personnel Workload
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white dark:bg-warmdark-panel p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-warmdark-border hover:shadow-md hover:border-blue-100 dark:hover:border-blue-900/50 transition-all duration-300 flex items-center justify-between group">
            <div>
                <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Total Submissions</p>
                <p class="text-3xl font-extrabold text-gray-900 dark:text-gray-100 tracking-tight"><?= $total_docs ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
        </div>

        <div class="bg-white dark:bg-warmdark-panel p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-warmdark-border hover:shadow-md hover:border-yellow-200 dark:hover:border-yellow-900/50 transition-all duration-300 flex items-center justify-between group">
            <div>
                <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Pending Review</p>
                <p class="text-3xl font-extrabold text-gray-900 dark:text-gray-100 tracking-tight"><?= $doc_status['Pending'] ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-yellow-50 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-500 flex items-center justify-center group-hover:bg-yellow-500 group-hover:text-white transition-colors duration-300 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white dark:bg-warmdark-panel p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-warmdark-border hover:shadow-md hover:border-green-200 dark:hover:border-green-900/50 transition-all duration-300 flex items-center justify-between group">
            <div>
                <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Approved</p>
                <p class="text-3xl font-extrabold text-gray-900 dark:text-gray-100 tracking-tight"><?= $doc_status['Approved'] ?></p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-500 flex items-center justify-center group-hover:bg-green-600 group-hover:text-white transition-colors duration-300 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
        </div>

        <div class="bg-white dark:bg-warmdark-panel p-6 rounded-2xl shadow-sm border border-gray-100 dark:border-warmdark-border hover:shadow-md hover:border-purple-200 dark:hover:border-purple-900/50 transition-all duration-300 flex items-center justify-between group">
            <div class="flex-1">
                <p class="text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Feedback Stats</p>
                <div class="flex items-center gap-4">
                    <div>
                        <p class="text-2xl font-extrabold text-gray-900 dark:text-gray-100 leading-none tracking-tight"><?= $total_ratings ?></p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold mt-1">Total Ratings</p>
                    </div>
                    <div class="w-px h-8 bg-gray-200 dark:bg-warmdark-border"></div>
                    <div>
                        <p class="text-2xl font-extrabold text-gray-900 dark:text-gray-100 leading-none tracking-tight"><?= $total_comments ?></p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 uppercase font-bold mt-1">Written Feedback</p>
                    </div>
                </div>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 flex items-center justify-center group-hover:bg-purple-600 group-hover:text-white transition-colors duration-300 shrink-0 ml-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8-1.125 0-2.197-.183-3.21-.516L3 21l1.516-5.79C3.183 14.197 3 13.125 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm border border-gray-100 dark:border-warmdark-border flex flex-col items-center lg:col-span-1 transition-colors">
            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider self-start mb-4">Submission Status</h3>
            <div class="relative w-full max-w-[220px] aspect-square">
                <canvas id="libDocStatusChart"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm border border-gray-100 dark:border-warmdark-border flex flex-col lg:col-span-2 transition-colors">
            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Feedback Distribution</h3>
            <p class="text-[11px] text-gray-400 dark:text-gray-500 leading-snug mb-4 mt-1">Shows how many students gave an overall average rating of 1 to 5 stars for this service.</p>
            <div class="relative w-full flex-1 min-h-[180px]">
                <canvas id="libFeedbackChart"></canvas>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-warmdark-panel p-6 rounded-xl shadow-sm border border-gray-100 dark:border-warmdark-border transition-colors">
        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-6">Submissions Per Round (Limit: 7)</h3>
        <div class="relative w-full h-[280px]">
            <canvas id="libRoundBuildingChart"></canvas>
        </div>
    </div>

    <div class="bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-100 dark:border-warmdark-border overflow-hidden transition-colors">
        <div class="p-5 border-b border-gray-100 dark:border-warmdark-border transition-colors">
            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Recent Librarian Submissions</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-warmdark-bg text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-transparent dark:border-warmdark-border transition-colors">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Control No.</th>
                        <th class="px-6 py-3 font-semibold">Thesis Title</th>
                        <th class="px-6 py-3 font-semibold text-center">Round</th>
                        <th class="px-6 py-3 font-semibold text-center">Date Uploaded</th>
                        <th class="px-6 py-3 font-semibold text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-warmdark-border">
                    <?php if ($recent_query && $recent_query->num_rows > 0): ?>
                        <?php while ($row = $recent_query->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-warmdark-hover transition-colors">
                                <td class="px-6 py-3 font-bold text-gray-800 dark:text-gray-200"><?= htmlspecialchars($row['control_number']) ?></td>
                                <td class="px-6 py-3 truncate max-w-xs" title="<?= htmlspecialchars($row['thesis_title']) ?>"><?= htmlspecialchars($row['thesis_title']) ?></td>
                                <td class="px-6 py-3 text-center font-medium text-gray-600 dark:text-gray-300">R<?= $row['round'] ?></td>
                                <td class="px-6 py-3 text-center text-xs text-gray-500 dark:text-gray-400"><?= date('M d, Y', strtotime($row['uploaded_at'])) ?></td>
                                <td class="px-6 py-3 text-center">
                                    <?php
                                    $badge = 'bg-gray-100 dark:bg-warmdark-bg text-gray-600 dark:text-gray-400 border border-transparent dark:border-warmdark-border';
                                    if ($row['status'] == 'Approved') $badge = 'bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 border border-transparent dark:border-green-500/30';
                                    if ($row['status'] == 'Pending') $badge = 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 border border-transparent dark:border-yellow-900/50';
                                    if ($row['status'] == 'Needs Revision') $badge = 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-transparent dark:border-red-900/50';
                                    ?>
                                    <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase <?= $badge ?>"><?= $row['status'] ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400 dark:text-gray-500">No recent document submissions for this school year.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {

        const gridColor = 'rgba(156, 163, 175, 0.2)'; 
        const textColor = '#9ca3af';

        new Chart(document.getElementById('libDocStatusChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Needs Revision'],
                datasets: [{
                    data: [<?= $doc_status['Approved'] ?>, <?= $doc_status['Pending'] ?>, <?= $doc_status['Needs Revision'] ?>],
                    backgroundColor: ['#22c55e', '#facc15', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            usePointStyle: true,
                            padding: 15,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });

        new Chart(document.getElementById('libFeedbackChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
                datasets: [{
                    label: 'Ratings',
                    data: [<?= $fb_ratings[5] ?>, <?= $fb_ratings[4] ?>, <?= $fb_ratings[3] ?>, <?= $fb_ratings[2] ?>, <?= $fb_ratings[1] ?>],
                    backgroundColor: '#facc15',
                    borderRadius: 4,
                    barThickness: 20
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [4, 4],
                            color: gridColor
                        },
                        ticks: {
                            color: textColor,
                            precision: 0
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: textColor
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        new Chart(document.getElementById('libRoundBuildingChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Round 1', 'Round 2', 'Round 3', 'Round 4', 'Round 5', 'Round 6', 'Round 7'],
                datasets: [{
                    label: 'Submissions',
                    data: [<?= $rounds_data[1] ?>, <?= $rounds_data[2] ?>, <?= $rounds_data[3] ?>, <?= $rounds_data[4] ?>, <?= $rounds_data[5] ?>, <?= $rounds_data[6] ?>, <?= $rounds_data[7] ?>],
                    backgroundColor: '#1e3a8a',
                    borderRadius: {
                        topLeft: 8,
                        topRight: 8
                    },
                    borderSkipped: false,
                    barThickness: 45
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [4, 4],
                            color: gridColor
                        },
                        ticks: {
                            color: textColor,
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: textColor
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>