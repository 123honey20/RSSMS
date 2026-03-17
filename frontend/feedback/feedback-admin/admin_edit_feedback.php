<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$rubric_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Fetch the Rubric
$stmt = $conn->prepare("SELECT * FROM rubrics WHERE id = ?");
$stmt->bind_param("i", $rubric_id);
$stmt->execute();
$rubricRes = $stmt->get_result();
$rubric = $rubricRes->fetch_assoc();

if (!$rubric) {
    echo "<div class='p-6 text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400 rounded-lg max-w-4xl mx-auto mt-8 border border-red-200 dark:border-red-900/30'>Rubric not found or has been deleted.</div>";
    exit;
}

// 2. Build the array structure
$rubricData = [
    'id' => $rubric['id'],
    'name' => $rubric['name'],
    'criteria' => []
];

// 3. Fetch Criteria
$stmtC = $conn->prepare("SELECT * FROM rubric_criteria WHERE rubric_id = ? ORDER BY id ASC");
$stmtC->bind_param("i", $rubric_id);
$stmtC->execute();
$criteriaRes = $stmtC->get_result();

$stmtL = $conn->prepare("SELECT * FROM rubric_levels WHERE criterion_id = ? ORDER BY score DESC");

while ($criterion = $criteriaRes->fetch_assoc()) {
    $c_data = [
        'id' => $criterion['id'],
        'name' => $criterion['name'],
        'description' => $criterion['description'],
        'weight' => intval($criterion['weight']),
        'levels' => []
    ];

    // 4. Fetch Levels for this Criterion
    $stmtL->bind_param("i", $criterion['id']);
    $stmtL->execute();
    $levelsRes = $stmtL->get_result();

    while ($level = $levelsRes->fetch_assoc()) {
        $c_data['levels'][] = [
            'id' => $level['id'],
            'score' => intval($level['score']),
            'desc' => $level['description']
        ];
    }
    $rubricData['criteria'][] = $c_data;
}

// Convert to JSON so Alpine.js can use it
$jsonRubricData = json_encode($rubricData);
?>

<div class="max-w-6xl mx-auto pb-12 transition-colors duration-200" x-data="academicRubricBuilder(<?php echo htmlspecialchars($jsonRubricData, ENT_QUOTES, 'UTF-8'); ?>)">

    <div class="bg-white dark:bg-warmdark-panel rounded-t-xl shadow-sm border border-gray-200 dark:border-warmdark-border border-b-0 px-6 py-5 mt-4 transition-colors">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex-1 w-full">
                <div class="flex items-center gap-3 mb-1">
                    <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">Edit Evaluation Instrument</label>
                    <span class="bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider border border-transparent dark:border-yellow-900/50">Editing Mode</span>
                </div>
                <input type="text" x-model="rubricName" placeholder="Enter a rubric title..."
                    class="w-full text-lg font-bold text-gray-900 dark:text-gray-100 border-0 border-b border-gray-200 dark:border-warmdark-border px-0 py-1 focus:ring-0 focus:border-blue-900 dark:focus:border-blue-500 placeholder-gray-400 dark:placeholder-gray-500 transition-colors bg-transparent">
            </div>

            <div class="shrink-0 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-lg px-4 py-2 flex items-center gap-4 transition-colors">
                <div class="flex flex-col text-right">
                    <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Weight</span>
                    <span class="text-[9px] font-medium mt-0.5" :class="totalWeight === 100 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'" x-text="totalWeight === 100 ? 'Perfectly Balanced' : 'Must equal exactly 100%'"></span>
                </div>
                <div class="text-2xl font-black" :class="totalWeight === 100 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400'" x-text="totalWeight + '%'"></div>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 dark:bg-warmdark-bg p-6 md:p-8 rounded-b-2xl shadow-inner border border-gray-200 dark:border-warmdark-border space-y-8 transition-colors">

        <template x-for="(criterion, cIndex) in criteria" :key="cIndex">
            <div class="bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden relative group transition-colors">

                <div class="bg-blue-900/5 dark:bg-blue-900/10 px-6 py-4 border-b border-gray-100 dark:border-warmdark-border flex items-center justify-between transition-colors">
                    <div class="flex items-center gap-3 flex-1 mr-4">
                        <span class="bg-blue-900 dark:bg-blue-800 text-white text-xs font-bold px-2.5 py-1 rounded-md shrink-0">C<span x-text="cIndex + 1"></span></span>
                        <input type="text" x-model="criterion.name" class="font-bold text-gray-800 dark:text-gray-200 bg-transparent border-none focus:ring-0 p-0 text-lg w-full placeholder-gray-400 dark:placeholder-gray-500" placeholder="Enter criterion name...">
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 bg-white dark:bg-warmdark-bg px-3 py-1.5 rounded-lg border border-gray-200 dark:border-warmdark-border shadow-sm transition-colors">
                            <label class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Weight</label>
                            <input type="number" x-model.number="criterion.weight" class="w-16 border-none p-0 text-right font-bold text-blue-900 dark:text-blue-400 focus:ring-0 bg-transparent" placeholder="0">
                            <span class="text-gray-400 dark:text-gray-500 font-bold">%</span>
                        </div>

                        <button @click="removeCriteria(cIndex)" x-show="criteria.length > 1" class="text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 transition p-1" title="Remove Criterion">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 border-b border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg transition-colors">
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Detailed Description</label>
                    <textarea x-model="criterion.description" rows="2" class="w-full p-3 border-gray-200 dark:border-warmdark-border bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-200 rounded-lg text-sm focus:ring-blue-900 dark:focus:ring-blue-500 focus:border-blue-900 dark:focus:border-blue-500 resize-y shadow-sm transition-colors placeholder-gray-400 dark:placeholder-gray-500" placeholder="Enter a description..."></textarea>
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scoring Scale</label>
                        <button @click="addLevel(cIndex)" class="text-xs font-bold text-blue-700 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 flex items-center gap-1 bg-blue-50 dark:bg-blue-900/30 border border-transparent dark:border-blue-900/50 px-3 py-1.5 rounded-md transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Level
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <template x-for="(level, lIndex) in criterion.levels" :key="lIndex">
                            <div class="bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-lg p-4 shadow-sm relative group/level hover:border-blue-300 dark:hover:border-blue-500/50 transition-colors">

                                <button @click="removeLevel(cIndex, lIndex)" x-show="criterion.levels.length > 1" class="absolute -top-2 -right-2 bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400 border border-transparent dark:border-red-500/30 rounded-full p-1 opacity-0 group-hover/level:opacity-100 transition shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>

                                <div class="flex items-center border-b border-gray-100 dark:border-warmdark-border pb-2 mb-2 transition-colors">
                                    <span class="text-xs font-bold text-gray-400 dark:text-gray-500 mr-2">Pts:</span>
                                    <input type="number" x-model.number="level.score" class="w-16 border-gray-200 dark:border-warmdark-border bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-200 rounded text-sm font-bold text-center py-1 px-2 focus:ring-blue-900 dark:focus:ring-blue-500 focus:border-blue-900 dark:focus:border-blue-500 shadow-sm transition-colors" placeholder="0">
                                </div>

                                <textarea x-model="level.desc" rows="2" class="w-full border-0 p-0 text-sm text-gray-600 dark:text-gray-300 resize-none focus:ring-0 placeholder-gray-300 dark:placeholder-gray-600 bg-transparent transition-colors" placeholder="Define the requirements for this score point..."></textarea>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </template>

        <button @click="addCriteria" class="w-full border-2 border-dashed border-gray-300 dark:border-warmdark-border rounded-xl p-4 text-center text-gray-500 dark:text-gray-400 font-semibold hover:border-blue-500 dark:hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-all flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add New Evaluation Criterion
        </button>

    </div>

    <div class="mt-6 flex items-center justify-end gap-4">
        <a href="admin_dashboard.php?page=feedback_admin_view" class="px-6 py-2.5 rounded-lg text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-warmdark-hover transition">
            Cancel
        </a>
        <button @click="updateRubric(event)" class="bg-blue-900 dark:bg-blue-800 text-white px-8 py-2.5 rounded-lg text-sm font-bold hover:bg-blue-800 dark:hover:bg-blue-700 transition shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2" :disabled="totalWeight !== 100 || !rubricName.trim()">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Save Updates
        </button>
    </div>

</div>

<script>
    function academicRubricBuilder(initialData) {
        return {
            rubricId: initialData.id,
            rubricName: initialData.name,
            criteria: initialData.criteria,

            get totalWeight() {
                return this.criteria.reduce((sum, c) => sum + (Number(c.weight) || 0), 0);
            },

            addCriteria() {
                this.criteria.push({
                    name: '',
                    description: '',
                    weight: 0,
                    levels: [{
                            score: 4,
                            desc: 'Exceeds Expectations'
                        },
                        {
                            score: 3,
                            desc: 'Meets Expectations'
                        },
                        {
                            score: 2,
                            desc: 'Needs Improvement'
                        },
                        {
                            score: 1,
                            desc: 'Unacceptable'
                        }
                    ]
                });
            },

            removeCriteria(index) {
                this.criteria.splice(index, 1);
            },

            addLevel(cIndex) {
                this.criteria[cIndex].levels.push({
                    score: 0,
                    desc: ''
                });
            },

            removeLevel(cIndex, lIndex) {
                this.criteria[cIndex].levels.splice(lIndex, 1);
            },

            updateRubric(event) {
                if (this.totalWeight !== 100) {
                    alert("Total weight must be exactly 100%.");
                    return;
                }
                if (!this.rubricName.trim()) {
                    alert("Please provide a title for this evaluation instrument.");
                    return;
                }

                const payload = {
                    id: this.rubricId,
                    name: this.rubricName,
                    criteria: this.criteria
                };

                const btn = event.currentTarget;
                const originalHTML = btn.innerHTML;
                btn.innerHTML = 'Updating...';
                btn.disabled = true;

                fetch('../../backend/ajax/update_rubric.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = 'admin_dashboard.php?page=feedback_admin_view';
                        } else {
                            alert("Error: " + data.message);
                            btn.innerHTML = originalHTML;
                            btn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("An error occurred while updating.");
                        btn.innerHTML = originalHTML;
                        btn.disabled = false;
                    });
            }
        }
    }
</script>