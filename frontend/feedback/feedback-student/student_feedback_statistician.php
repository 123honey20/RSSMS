<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$submissionId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$userId = $_SESSION['user'];

$stmt = $conn->prepare("
    SELECT st.id as submission_id, s.control_number, p.id as personnel_id, p.full_name as personnel_name
    FROM statistician st
    JOIN students s ON st.student_id = s.id
    LEFT JOIN personnel p ON st.personnel_id = p.id
    WHERE st.id = ? AND s.user_id = ? AND st.status = 'Approved'
");
$stmt->bind_param("ii", $submissionId, $userId);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();

if (!$submission) {
    echo "<div class='p-6 text-red-600 bg-red-50 rounded-lg max-w-4xl mx-auto mt-8'>Invalid submission or unauthorized access.</div>";
    exit();
}

$completedRubrics = [];
$serviceType = 'statistician';
$stmtCompleted = $conn->prepare("SELECT rubric_id FROM student_evaluations WHERE submission_id = ? AND service_type = ?");
$stmtCompleted->bind_param("is", $submissionId, $serviceType);
$stmtCompleted->execute();
$resCompleted = $stmtCompleted->get_result();
while ($row = $resCompleted->fetch_assoc()) {
    $completedRubrics[] = $row['rubric_id'];
}
$stmtCompleted->close();

$rubrics = [];
$resR = $conn->query("SELECT * FROM rubrics ORDER BY id ASC");
while ($r = $resR->fetch_assoc()) {
    $rubric_id = $r['id'];
    $isCompleted = in_array($rubric_id, $completedRubrics);

    $rubricData = [
        'id' => $rubric_id,
        'name' => $r['name'],
        'isCompleted' => $isCompleted,
        'criteria' => []
    ];

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

<div class="max-w-5xl mx-auto pb-12 pt-6" x-data="studentEvaluationForm(<?php echo htmlspecialchars($jsonRubrics, ENT_QUOTES, 'UTF-8'); ?>)">

    <div x-show="notification.show"
        x-transition:enter="transition-all duration-500 transform"
        x-transition:enter-start="opacity-0 translate-x-full"
        x-transition:enter-end="opacity-100 translate-x-0"
        x-transition:leave="transition-all duration-500 transform"
        x-transition:leave-start="opacity-100 translate-x-0"
        x-transition:leave-end="opacity-0 translate-x-full"
        style="display: none;"
        class="fixed top-6 right-6 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 z-50"
        :class="notification.type === 'success' ? 'bg-green-600' : 'bg-red-600'">

        <div class="rounded-full p-1" :class="notification.type === 'success' ? 'bg-green-500' : 'bg-red-500'">
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
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Personnel Evaluation</h1>
            <p class="text-sm text-gray-500 mt-1">Provide feedback for your recent Statistician review.</p>
        </div>
        <a href="student_dashboard.php?page=student_statistician_approved_result&id=<?php echo $submissionId; ?>"
            class="bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-lg text-sm font-medium shadow-sm hover:bg-gray-50 transition flex items-center gap-2">
            Back to Result
        </a>
    </div>

    <div class="bg-blue-900 rounded-t-2xl p-6 text-white shadow-md flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-white/20 rounded-full flex items-center justify-center shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-100" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <p class="text-blue-200 text-xs font-bold uppercase tracking-wider mb-0.5">Evaluating Personnel</p>
                <h2 class="text-xl font-bold text-white"><?php echo htmlspecialchars($submission['personnel_name'] ?? 'Unknown Personnel'); ?></h2>
                <p class="text-blue-100 text-sm">Control No: <?php echo htmlspecialchars($submission['control_number']); ?></p>
            </div>
        </div>

        <div class="w-full md:w-64 relative" x-data="{ dropdownOpen: false }">
            <label class="block text-blue-200 text-[10px] font-bold uppercase tracking-wider mb-1">Select Evaluation Instrument</label>

            <button @click="dropdownOpen = !dropdownOpen" @click.away="dropdownOpen = false" type="button"
                class="w-full bg-white text-gray-800 text-sm rounded-lg px-3 py-2 border border-transparent focus:ring-2 focus:ring-blue-300 shadow-sm flex justify-between items-center transition-all font-medium">

                <span class="truncate pr-3" x-text="activeRubric ? activeRubric.name : 'Select a Rubric...'"></span>

                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0" :class="dropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                class="absolute mt-1 w-full md:w-72 right-0 bg-white rounded-lg shadow-xl border border-gray-100 z-50 overflow-hidden"
                style="display: none;">

                <ul class="max-h-56 overflow-y-auto p-1 space-y-0.5 custom-scrollbar">
                    <template x-for="rubric in rubrics" :key="rubric.id">
                        <li>
                            <button type="button"
                                @click="if(!rubric.isCompleted) { selectedRubricId = rubric.id; dropdownOpen = false; }"
                                :class="{
                                        'bg-blue-50 text-blue-700 font-semibold': selectedRubricId === rubric.id, 
                                        'opacity-50 cursor-not-allowed bg-gray-50 text-gray-500': rubric.isCompleted, 
                                        'hover:bg-gray-50 text-gray-700': !rubric.isCompleted && selectedRubricId !== rubric.id
                                    }"
                                class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors flex items-center justify-between">

                                <span class="truncate pr-2" x-text="rubric.name" :title="rubric.name"></span>

                                <div class="shrink-0 flex items-center">
                                    <span x-show="rubric.isCompleted" class="text-[9px] font-bold uppercase tracking-wider bg-gray-200 text-gray-500 px-1.5 py-0.5 rounded shadow-inner">Done</span>

                                    <svg x-show="selectedRubricId === rubric.id && !rubric.isCompleted" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

    <div class="bg-white rounded-b-2xl shadow-md border border-gray-200 border-t-0 overflow-hidden" x-show="activeRubric && !activeRubric.isCompleted">

        <div class="p-6 md:p-8 space-y-8 bg-gray-50/50">
            <template x-for="(criterion, cIndex) in activeRubric.criteria" :key="criterion.id">
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">

                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-bold text-gray-800 text-base" x-text="criterion.name"></h3>
                            <p class="text-sm text-gray-500 mt-1" x-text="criterion.description"></p>
                        </div>
                        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2.5 py-1 rounded-md shrink-0 border border-blue-200" x-text="criterion.weight + '%'"></span>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <template x-for="level in criterion.levels" :key="level.id">
                                <div @click="selectRating(criterion.id, level.score)"
                                    class="cursor-pointer border-2 rounded-xl p-4 transition-all duration-200 flex flex-col h-full relative"
                                    :class="ratings[criterion.id] === level.score ? 'border-blue-600 bg-blue-50 shadow-md' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'">

                                    <div x-show="ratings[criterion.id] === level.score" class="absolute top-3 right-3 text-blue-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>

                                    <span class="text-2xl font-black mb-2" :class="ratings[criterion.id] === level.score ? 'text-blue-700' : 'text-gray-400'" x-text="level.score"></span>
                                    <p class="text-sm font-medium leading-snug" :class="ratings[criterion.id] === level.score ? 'text-blue-900' : 'text-gray-600'" x-text="level.desc"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                </div>
            </template>
        </div>

        <div class="p-6 md:p-8 border-t border-gray-200">
            <label class="block font-bold text-gray-800 mb-2">Additional Comments (Optional)</label>
            <p class="text-xs text-gray-500 mb-3">Please share any specific experiences or suggestions to help this personnel improve their service.</p>
            <textarea x-model="commentText" rows="4" class="w-full p-4 border border-gray-300 rounded-xl text-sm focus:ring-blue-900 focus:border-blue-900 shadow-sm" placeholder="Type your feedback here..."></textarea>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-sm text-gray-500 font-medium" x-show="!isFormComplete()">
                Please select a score for all criteria to submit.
            </p>
            <p class="text-sm text-green-600 font-bold flex items-center gap-1" x-show="isFormComplete()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Evaluation Complete!
            </p>

            <button @click="submitFeedback"
                class="w-full sm:w-auto bg-blue-900 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-800 transition shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex justify-center items-center gap-2"
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
            ratings: {},
            commentText: '',

            // Notification State
            notification: {
                show: false,
                message: '',
                type: 'success'
            },

            get activeRubric() {
                if (this.selectedRubricId) {
                    return this.rubrics.find(r => r.id == this.selectedRubricId);
                }
                return null;
            },

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
                return this.activeRubric.criteria.every(c => this.ratings[c.id] !== undefined);
            },

            showNotification(message, type) {
                this.notification.message = message;
                this.notification.type = type;
                this.notification.show = true;

                // Hide automatically after 3 seconds if it's an error
                if (type === 'error') {
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
                    service_type: 'statistician',
                    rubric_id: this.selectedRubricId,
                    ratings: this.ratings,
                    comments: this.commentText
                };

                const btn = event.currentTarget;
                const originalHTML = btn.innerHTML;
                btn.innerHTML = 'Submitting...';
                btn.disabled = true;

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
                                window.location.href = 'student_dashboard.php?page=student_statistician_approved_result&id=<?php echo $submissionId; ?>';
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