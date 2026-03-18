<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$submissionId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user'];

$stmt = $conn->prepare("
    SELECT e.id as submission_id, s.control_number, p.id as personnel_id, p.full_name as personnel_name
    FROM ethics e
    JOIN students s ON e.student_id = s.id
    LEFT JOIN personnel p ON e.personnel_id = p.id
    WHERE e.id = ? AND s.user_id = ? AND e.status = 'Approved'
");
$stmt->bind_param("ii", $submissionId, $userId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    echo "<div class='p-6 text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg max-w-4xl mx-auto mt-8 border border-red-200 dark:border-red-900/30'>Invalid submission or unauthorized access.</div>";
    exit();
}

// 2. Fetch IDs of rubrics the student has ALREADY submitted for this document
$completedRubrics = [];
$serviceType = 'ethics'; 
$stmtCompleted = $conn->prepare("SELECT rubric_id FROM student_evaluations WHERE submission_id = ? AND service_type = ?"); 
$stmtCompleted->bind_param("is", $submissionId, $serviceType); 
$stmtCompleted->execute();
$resCompleted = $stmtCompleted->get_result();
while ($row = $resCompleted->fetch_assoc()) {
    $completedRubrics[] = $row['rubric_id'];
}
$stmtCompleted->close();

// 3. Fetch All Available Rubrics from the database
$rubrics = [];
$resR = $conn->query("SELECT * FROM rubrics ORDER BY id ASC");
while ($r = $resR->fetch_assoc()) {
    $rubric_id = $r['id'];

    // Check if this rubric is already done
    $isCompleted = in_array($rubric_id, $completedRubrics);

    $rubricData = [
        'id' => $rubric_id,
        'name' => $r['name'],
        'isCompleted' => $isCompleted, 
        'criteria' => []
    ];

    // Fetch Criteria
    $resC = $conn->query("SELECT * FROM rubric_criteria WHERE rubric_id = $rubric_id ORDER BY id ASC");
    while ($c = $resC->fetch_assoc()) {
        $c_id = $c['id'];
        $critData = [
            'id' => $c_id,
            'name' => $c['name'],
            'description' => $c['description'],
            'weight' => $c['weight'],
            'levels' => []
        ];

        // Fetch Levels
        $resL = $conn->query("SELECT * FROM rubric_levels WHERE criterion_id = $c_id ORDER BY score DESC");
        while ($l = $resL->fetch_assoc()) {
            $critData['levels'][] = ['id' => $l['id'], 'score' => $l['score'], 'desc' => $l['description']];
        }
        $rubricData['criteria'][] = $critData;
    }
    $rubrics[] = $rubricData;
}

$jsonRubrics = json_encode($rubrics);
?>

<div class="max-w-5xl mx-auto pb-12 pt-6 transition-colors duration-200" x-data="studentEvaluationForm(<?php echo htmlspecialchars($jsonRubrics, ENT_QUOTES, 'UTF-8'); ?>)">

    <div x-show="notification.show" 
         x-transition:enter="transition-all duration-500 transform"
         x-transition:enter-start="opacity-0 translate-x-full"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition-all duration-500 transform"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-full"
         style="display: none;"
         class="fixed top-6 right-6 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 z-50"
         :class="notification.type === 'success' ? 'bg-green-600 dark:bg-green-700' : 'bg-red-600 dark:bg-red-700'">
        
        <div class="rounded-full p-1" :class="notification.type === 'success' ? 'bg-green-500 dark:bg-green-600' : 'bg-red-500 dark:bg-red-600'">
            <svg x-show="notification.type === 'success'" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
            <svg x-show="notification.type === 'error'" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </div>
        <span class="font-medium text-sm" x-text="notification.message"></span>
    </div>

    <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 tracking-tight">Personnel Evaluation</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Provide feedback for your recent Ethics verification.</p>
        </div>
        <a href="student_dashboard.php?page=student_ethics_approved_result&id=<?php echo $submissionId; ?>"
            class="bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border text-gray-700 dark:text-gray-200 px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm hover:bg-gray-50 dark:hover:bg-warmdark-hover transition flex items-center gap-2">
            Back to Result
        </a>
    </div>

    <div class="bg-blue-900 dark:bg-warmdark-panel border border-transparent dark:border-warmdark-border rounded-t-2xl p-6 text-white dark:text-gray-100 shadow-md flex flex-col md:flex-row items-center justify-between gap-4 transition-colors">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-white/20 dark:bg-warmdark-bg rounded-full flex items-center justify-center shrink-0 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-100 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <p class="text-blue-200 dark:text-gray-400 text-xs font-bold uppercase tracking-wider mb-0.5">Evaluating Personnel</p>
                <h2 class="text-xl font-bold text-white dark:text-gray-100"><?php echo htmlspecialchars($submission['personnel_name'] ?? 'Unknown Personnel'); ?></h2>
                <p class="text-blue-100 dark:text-gray-300 text-sm">Control No: <?php echo htmlspecialchars($submission['control_number']); ?></p>
            </div>
        </div>

        <div class="w-full md:w-64 relative" x-data="{ dropdownOpen: false }">
            <label class="block text-blue-200 dark:text-gray-400 text-[10px] font-bold uppercase tracking-wider mb-1">Select Evaluation Instrument</label>
            
            <button @click="dropdownOpen = !dropdownOpen" @click.away="dropdownOpen = false" type="button" 
                    class="w-full bg-white dark:bg-warmdark-bg text-gray-800 dark:text-gray-200 text-sm rounded-lg px-3 py-2 border border-transparent dark:border-warmdark-border focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-500 shadow-sm flex justify-between items-center transition-all font-medium">
                
                <span class="truncate pr-3" x-text="activeRubric ? activeRubric.name : 'Select a Rubric...'"></span>
                
                <svg class="w-4 h-4 text-gray-400 dark:text-gray-500 transition-transform duration-200 shrink-0" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="dropdownOpen" 
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-1"
                 class="absolute mt-1 w-full md:w-72 right-0 bg-white dark:bg-warmdark-panel rounded-lg shadow-xl border border-gray-100 dark:border-warmdark-border z-50 overflow-hidden transition-colors"
                 style="display: none;">
                
                <ul class="max-h-56 overflow-y-auto p-1 space-y-0.5 custom-scrollbar">
                    <template x-for="rubric in rubrics" :key="rubric.id">
                        <li>
                            <button type="button" 
                                    @click="if(!rubric.isCompleted) { selectedRubricId = rubric.id; dropdownOpen = false; }"
                                    :class="{
                                        'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 font-semibold': selectedRubricId === rubric.id, 
                                        'opacity-50 bg-gray-50 dark:bg-warmdark-bg text-gray-500 dark:text-gray-400': rubric.isCompleted, 
                                        'hover:bg-gray-50 dark:hover:bg-warmdark-hover text-gray-700 dark:text-gray-200': !rubric.isCompleted && selectedRubricId !== rubric.id
                                    }"
                                    class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors flex items-center justify-between">
                                
                                <span class="truncate pr-2" x-text="rubric.name" :title="rubric.name"></span>
                                
                                <div class="shrink-0 flex items-center">
                                    <span x-show="rubric.isCompleted" class="text-[9px] font-bold uppercase tracking-wider bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 px-1.5 py-0.5 rounded shadow-inner">Done</span>
                                    
                                    <svg x-show="selectedRubricId === rubric.id && !rubric.isCompleted" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </button>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-warmdark-bg rounded-b-2xl shadow-md border border-gray-200 dark:border-warmdark-border border-t-0 overflow-hidden transition-colors" x-show="activeRubric && !activeRubric.isCompleted">

        <div class="p-6 md:p-8 space-y-8 bg-gray-50/50 dark:bg-warmdark-bg transition-colors">
            <template x-for="(criterion, cIndex) in activeRubric.criteria" :key="criterion.id">
                <div class="bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border rounded-xl shadow-sm overflow-hidden transition-colors">

                    <div class="px-6 py-4 border-b border-gray-100 dark:border-warmdark-border bg-gray-50 dark:bg-warmdark-bg flex items-start justify-between gap-4 transition-colors">
                        <div>
                            <h3 class="font-bold text-gray-800 dark:text-gray-100 text-base" x-text="criterion.name"></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="criterion.description"></p>
                        </div>
                        <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 text-xs font-bold px-2.5 py-1 rounded-md shrink-0 border border-blue-200 dark:border-blue-900/50 transition-colors" x-text="criterion.weight + '%'"></span>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <template x-for="level in criterion.levels" :key="level.id">
                                <div @click="selectRating(criterion.id, level.score)"
                                    class="cursor-pointer border-2 rounded-xl p-4 transition-all duration-200 flex flex-col h-full relative"
                                    :class="ratings[criterion.id] === level.score ? 'border-blue-600 dark:border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-md' : 'border-gray-200 dark:border-warmdark-border hover:border-blue-300 dark:hover:border-blue-500/50 hover:bg-gray-50 dark:hover:bg-warmdark-hover'">

                                    <div x-show="ratings[criterion.id] === level.score" class="absolute top-3 right-3 text-blue-600 dark:text-blue-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>

                                    <span class="text-2xl font-black mb-2 transition-colors" :class="ratings[criterion.id] === level.score ? 'text-blue-700 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500'" x-text="level.score"></span>
                                    <p class="text-sm font-medium leading-snug transition-colors" :class="ratings[criterion.id] === level.score ? 'text-blue-900 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400'" x-text="level.desc"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </template>
        </div>

        <div class="p-6 md:p-8 border-t border-gray-200 dark:border-warmdark-border transition-colors">
            <label class="block font-bold text-gray-800 dark:text-gray-200 mb-2">Additional Comments (Optional)</label>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Please share any specific experiences or suggestions to help this personnel improve their service.</p>
            <textarea x-model="commentText" rows="4" class="w-full p-4 border border-gray-300 dark:border-warmdark-border bg-white dark:bg-warmdark-panel text-gray-900 dark:text-gray-100 rounded-xl text-sm focus:ring-blue-900 dark:focus:ring-blue-500 focus:border-blue-900 dark:focus:border-blue-500 shadow-sm transition-colors" placeholder="Type your feedback here..."></textarea>
        </div>

        <div class="bg-gray-50 dark:bg-warmdark-bg px-6 py-4 border-t border-gray-200 dark:border-warmdark-border flex flex-col sm:flex-row items-center justify-between gap-4 transition-colors">
            <p class="text-sm text-gray-500 dark:text-gray-400 font-medium" x-show="!isFormComplete()">
                Please select a score for all criteria to submit.
            </p>
            <p class="text-sm text-green-600 dark:text-green-500 font-bold flex items-center gap-1" x-show="isFormComplete()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Evaluation Complete!
            </p>

            <button @click="submitFeedback"
                class="w-full sm:w-auto bg-blue-900 dark:bg-blue-800 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-800 dark:hover:bg-blue-700 transition-colors shadow-md disabled:opacity-50 flex justify-center items-center gap-2"
                :disabled="!isFormComplete()">
                Submit Evaluation
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>

    </div>

</div>

<script>
    function studentEvaluationForm(rubricsData) {
        return {
            rubrics: rubricsData,
            selectedRubricId: rubricsData.length > 0 ? rubricsData[0].id : null,
            ratings: {}, // Stores criterion_id -> selected score
            commentText: '',
            
            // Notification State
            notification: {
                show: false,
                message: '',
                type: 'success'
            },

            get activeRubric() {
                // Clear ratings when switching rubrics
                if (this.selectedRubricId) {
                    return this.rubrics.find(r => r.id == this.selectedRubricId);
                }
                return null;
            },

            // Watch for rubric changes to clear previous ratings
            init() {
                this.$watch('selectedRubricId', value => {
                    this.ratings = {};
                });
            },

            selectRating(criterionId, score) {
                this.ratings[criterionId] = score;
            },

            isFormComplete() {
                if (!this.activeRubric) return false;
                // Check if every criterion in the active rubric has a rating in the ratings object
                return this.activeRubric.criteria.every(c => this.ratings[c.id] !== undefined);
            },

            showNotification(message, type) {
                this.notification.message = message;
                this.notification.type = type;
                this.notification.show = true;
                
                if(type === 'error'){
                    setTimeout(() => {
                        this.notification.show = false;
                    }, 3000);
                }
            },

            submitFeedback(event) {
                if (!this.isFormComplete()) return;

                const payload = {
                    submission_id: <?php echo $submissionId; ?>,
                    personnel_id: <?php echo $submission['personnel_id'] ?? 'null'; ?>,
                    service_type: 'ethics', 
                    rubric_id: this.selectedRubricId,
                    ratings: this.ratings,
                    comments: this.commentText
                };

                const btn = event.currentTarget;
                const originalHTML = btn.innerHTML;
                btn.innerHTML = 'Submitting...';
                btn.disabled = true;

                // Hit the real backend!
                fetch('../../backend/ajax/save_student_feedback.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showNotification("Evaluation submitted successfully!", 'success');
                            
                            setTimeout(() => {
                                window.location.href = 'student_dashboard.php?page=student_ethics_approved_result&id=<?php echo $submissionId; ?>';
                            }, 3000);
                        } else {
                            this.showNotification("Error: " + data.message, 'error');
                            btn.innerHTML = originalHTML;
                            btn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        this.showNotification("An error occurred while saving.", 'error');
                        btn.innerHTML = originalHTML;
                        btn.disabled = false;
                    });
            }
        }
    }
</script>