<div class="max-w-6xl mx-auto pb-12" x-data="academicRubricBuilder()">

    <div class="bg-white rounded-t-xl shadow-sm border border-gray-200 border-b-0 px-6 py-5 mt-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex-1 w-full">
                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Evaluation Instrument Title</label>
                <input type="text" x-model="rubricName" placeholder="Enter a rubric title..."
                    class="w-full text-lg font-bold text-gray-900 border-0 border-b border-gray-200 px-0 py-1 focus:ring-0 focus:border-blue-900 placeholder-gray-400 transition-colors bg-transparent">
            </div>

            <div class="shrink-0 bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 flex items-center gap-4">
                <div class="flex flex-col text-right">
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Total Weight</span>
                    <span class="text-[9px] font-medium mt-0.5" :class="totalWeight === 100 ? 'text-green-600' : 'text-red-500'" x-text="totalWeight === 100 ? 'Perfectly Balanced' : 'Must equal exactly 100%'"></span>
                </div>
                <div class="text-2xl font-black" :class="totalWeight === 100 ? 'text-green-600' : 'text-red-500'" x-text="totalWeight + '%'"></div>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 p-6 md:p-8 rounded-b-2xl shadow-inner border border-gray-200">

        <template x-for="(criterion, cIndex) in criteria" :key="cIndex">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden relative group">

                <div class="bg-blue-900/5 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3 flex-1 mr-4">
                        <span class="bg-blue-900 text-white text-xs font-bold px-2.5 py-1 rounded-md shrink-0">C<span x-text="cIndex + 1"></span></span>
                        <input type="text" x-model="criterion.name" class="font-bold text-gray-800 bg-transparent border-none focus:ring-0 p-0 text-lg w-full placeholder-gray-400" placeholder="Enter criterion name...">
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-gray-200 shadow-sm">
                            <label class="text-xs font-bold text-gray-500 uppercase">Weight</label>
                            <input type="number" x-model.number="criterion.weight" class="w-16 border-none p-0 text-right font-bold text-blue-900 focus:ring-0" placeholder="0">
                            <span class="text-gray-400 font-bold">%</span>
                        </div>

                        <button @click="removeCriteria(cIndex)" x-show="criteria.length > 1" class="text-gray-400 hover:text-red-600 transition p-1" title="Remove Criterion">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Detailed Description</label>
                    <textarea x-model="criterion.description" rows="2" class="w-full p-3 border-gray-200 rounded-lg text-sm focus:ring-blue-900 focus:border-blue-900 resize-y shadow-sm" placeholder="Enter a description..."></textarea>
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Scoring Scale</label>
                        <button @click="addLevel(cIndex)" class="text-xs font-bold text-blue-700 hover:text-blue-900 flex items-center gap-1 bg-blue-50 px-3 py-1.5 rounded-md transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Level
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <template x-for="(level, lIndex) in criterion.levels" :key="lIndex">
                            <div class="bg-white border border-gray-200 rounded-lg p-4 shadow-sm relative group/level hover:border-blue-300 transition-colors">

                                <button @click="removeLevel(cIndex, lIndex)" x-show="criterion.levels.length > 1" class="absolute -top-2 -right-2 bg-red-100 text-red-600 rounded-full p-1 opacity-0 group-hover/level:opacity-100 transition shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>

                                <div class="flex items-center border-b border-gray-100 pb-2 mb-2">
                                    <span class="text-xs font-bold text-gray-400 mr-2">Pts:</span>
                                    <input type="number" x-model.number="level.score" class="w-16 border-gray-200 rounded text-sm font-bold text-center py-1 px-2 focus:ring-blue-900 focus:border-blue-900 shadow-sm" placeholder="0">
                                </div>

                                <textarea x-model="level.desc" rows="2" class="w-full border-0 p-0 text-sm text-gray-600 resize-none focus:ring-0 placeholder-gray-300 bg-transparent" placeholder="Define the requirements for this score point..."></textarea>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </template>

        <button @click="addCriteria" class="w-full border-2 border-dashed border-gray-300 rounded-xl p-4 text-center text-gray-500 font-semibold hover:border-blue-500 hover:text-blue-600 hover:bg-blue-50 transition-all flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add New Evaluation Criterion
        </button>

    </div>

    <div class="mt-6 flex items-center justify-end gap-4">
        <a href="admin_dashboard.php?page=feedback_admin_view" class="px-6 py-2.5 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-100 transition">
            Cancel
        </a>
        <button @click="saveRubric" class="bg-blue-900 text-white px-8 py-2.5 rounded-lg text-sm font-bold hover:bg-blue-800 transition shadow-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2" :disabled="totalWeight !== 100 || !rubricName.trim()">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
            </svg>
            Publish Rubric
        </button>
    </div>

</div>

<script>
    function academicRubricBuilder() {
        return {
            rubricName: '',
            criteria: [{
                name: '',
                description: '',
                weight: 100,
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
            }],

            // Auto-calculates total percentage
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

            saveRubric() {
                if (this.totalWeight !== 100) {
                    alert("Total weight must be exactly 100%.");
                    return;
                }
                if (!this.rubricName.trim()) {
                    alert("Please provide a title for this evaluation instrument.");
                    return;
                }

                const payload = {
                    name: this.rubricName,
                    criteria: this.criteria
                };

                // Change the button state to show it's loading (optional but good practice)
                const btn = event.currentTarget;
                const originalText = btn.innerHTML;
                btn.innerHTML = 'Saving...';
                btn.disabled = true;

                // Send the data to our new PHP backend
                fetch('../../backend/ajax/save_rubric.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Redirect to the view page on success
                            window.location.href = 'admin_dashboard.php?page=feedback_admin_view';
                        } else {
                            alert("Error saving rubric: " + data.message);
                            btn.innerHTML = originalText;
                            btn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("An error occurred while saving.");
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
            }
        }
    }
</script>