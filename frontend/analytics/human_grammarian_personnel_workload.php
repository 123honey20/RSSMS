<?php
require_once "../../backend/config/database.php";

$dept_query = $conn->query("SELECT id, name FROM departments ORDER BY name ASC");
?>

<div class="space-y-6 transition-colors duration-200" x-data="personnelWorkloadApp()">
    
    <!-- HEADER SECTION -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-5 border-b border-gray-200 dark:border-warmdark-border">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="admin_dashboard.php?page=human_grammarian_analytics" class="text-gray-400 hover:text-blue-600 dark:text-gray-500 dark:hover:text-blue-400 transition-colors p-1 rounded-md hover:bg-gray-100 dark:hover:bg-warmdark-hover">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <h2 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">Personnel Workload Report</h2>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 ml-9 font-medium">Monitor evaluation metrics and active caseloads for Human Grammarian reviewers.</p>
        </div>

        <div class="w-full sm:w-72">
            <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1.5">Filter by Department</label>
            <div class="relative">
                <select x-model="selectedDept" @change="fetchPersonnelStats" class="w-full border border-gray-300 dark:border-warmdark-border rounded-xl px-4 py-2.5 text-sm bg-white dark:bg-warmdark-bg text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold transition-shadow shadow-sm appearance-none cursor-pointer">
                    <option value="">-- Select Department --</option>
                    <?php while ($d = $dept_query->fetch_assoc()): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN GRID -->
    <div x-show="selectedDept && !isLoading" x-transition.opacity x-cloak class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        
        <template x-for="person in personnelList" :key="person.id">
            <div class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col group">
                
                <!-- Card Header -->
                <div class="p-5 border-b border-gray-100 dark:border-warmdark-border flex items-center justify-between bg-gray-50/50 dark:bg-warmdark-bg/50">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-700 dark:text-slate-300 font-bold shadow-sm shrink-0">
                            <span x-text="person.full_name.charAt(0)"></span>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate" :title="person.full_name" x-text="person.full_name"></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium font-mono tracking-tight" x-text="person.school_id"></p>
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-5 flex-1">
                    <!-- Progress Bar (Completion Rate) -->
                    <div class="mb-5">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completion Rate</span>
                            <span class="text-sm font-extrabold text-gray-900 dark:text-gray-100" x-text="getCompletionRate(person) + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden border border-gray-200 dark:border-gray-700">
                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="'width: ' + getCompletionRate(person) + '%'"></div>
                        </div>
                    </div>

                    <!-- Mini Metrics Grid -->
                    <div class="grid grid-cols-2 gap-3 mb-2">
                        <div class="border border-gray-100 dark:border-warmdark-border rounded-lg p-3 bg-white dark:bg-warmdark-panel">
                            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-0.5">Assigned</p>
                            <p class="text-lg font-bold text-gray-900 dark:text-gray-100" x-text="person.total_assigned"></p>
                        </div>
                        <div class="border border-gray-100 dark:border-warmdark-border rounded-lg p-3 bg-white dark:bg-warmdark-panel relative overflow-hidden">
                            <div class="absolute top-0 left-0 w-1 h-full" :class="person.total_pending > 0 ? 'bg-amber-500' : 'bg-gray-200 dark:bg-gray-700'"></div>
                            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-0.5 ml-1">Pending</p>
                            <p class="text-lg font-bold ml-1" :class="person.total_pending > 0 ? 'text-amber-600 dark:text-amber-500' : 'text-gray-900 dark:text-gray-100'" x-text="person.total_pending"></p>
                        </div>
                    </div>
                </div>

                <!-- Card Footer (Action) -->
                <div class="p-4 border-t border-gray-100 dark:border-warmdark-border bg-gray-50/50 dark:bg-warmdark-bg/50">
                    <button @click="openModal(person)" class="w-full text-center text-sm font-bold text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors flex items-center justify-center gap-1.5">
                        View Performance Report
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                </div>
            </div>
        </template>

        <!-- NO DATA STATE -->
        <div x-show="personnelList.length === 0" class="col-span-full py-20 text-center bg-gray-50 dark:bg-warmdark-bg rounded-2xl border border-dashed border-gray-300 dark:border-warmdark-border">
            <svg class="w-10 h-10 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <p class="text-sm text-gray-500 font-medium">No Human Grammarian personnel have been mapped to this department.</p>
        </div>

    </div>

    <!-- INITIAL EMPTY STATE -->
    <div x-show="!selectedDept" class="py-24 text-center bg-white dark:bg-warmdark-panel rounded-2xl border border-gray-200 dark:border-warmdark-border shadow-sm">
        <div class="w-16 h-16 rounded-full bg-gray-50 dark:bg-warmdark-bg border border-gray-100 dark:border-warmdark-border flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">Select a department from the dropdown to load personnel metrics.</p>
    </div>

    <!-- LOADING SPINNER -->
    <div x-show="isLoading" class="py-24 flex flex-col justify-center items-center gap-4" x-cloak>
        <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <p class="text-xs text-gray-500 font-bold uppercase tracking-widest">Loading Analytics...</p>
    </div>

    <!-- PRIMARY MODAL: PERFORMANCE REPORT -->
    <div x-show="modalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6 bg-slate-900/60 backdrop-blur-sm" x-cloak>
        <div @click.away="closeModal()" class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-2xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh] border border-gray-200 dark:border-warmdark-border transform transition-all">
            
            <!-- Modal Header -->
            <div class="px-8 py-6 border-b border-gray-200 dark:border-warmdark-border flex justify-between items-start bg-gray-50/50 dark:bg-warmdark-bg/50">
                <div class="flex items-center gap-5">
                    <div class="w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 flex items-center justify-center font-bold text-2xl border border-blue-200 dark:border-blue-800/50 shadow-sm shrink-0">
                        <span x-text="selectedPerson ? selectedPerson.full_name.charAt(0) : ''"></span>
                    </div>
                    <div>
                        <h3 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight" x-text="selectedPerson ? selectedPerson.full_name : ''"></h3>
                        <div class="flex items-center gap-3 mt-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400 font-mono bg-gray-100 dark:bg-warmdark-hover px-2 py-0.5 rounded border border-gray-200 dark:border-warmdark-border" x-text="selectedPerson ? selectedPerson.school_id : ''"></span>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">• Human Grammarian</span>
                        </div>
                    </div>
                </div>
                <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors bg-white dark:bg-warmdark-hover hover:bg-gray-100 border border-gray-200 dark:border-warmdark-border p-2 rounded-lg shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-8 overflow-y-auto custom-scrollbar bg-white dark:bg-warmdark-panel">
                
                <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Overall Performance</h4>
                
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <!-- NEW Availability Card -->
                    <div class="border border-gray-200 dark:border-warmdark-border rounded-xl p-5 flex items-start gap-4 bg-white dark:bg-warmdark-panel shadow-sm">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0 border mt-1 transition-colors" :class="getAvailability(selectedPerson).bg + ' ' + getAvailability(selectedPerson).border + ' ' + getAvailability(selectedPerson).color">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="w-full">
                            <p class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider mb-1">Current Availability</p>
                            <p class="text-2xl font-extrabold leading-none transition-colors" 
                               :class="getAvailability(selectedPerson).color" 
                               x-text="getAvailability(selectedPerson).label"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-1.5" x-text="getAvailability(selectedPerson).text"></p>
                        </div>
                    </div>

                    <!-- Completion Rate Card -->
                    <div class="border border-gray-200 dark:border-warmdark-border rounded-xl p-5 flex items-center gap-4 bg-white dark:bg-warmdark-panel shadow-sm">
                        <div class="w-12 h-12 rounded-lg bg-emerald-50 dark:bg-green-900/10 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100 dark:border-green-900/30">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="w-full">
                            <!-- Detailed Approved Count inside the Clearance Box -->
                            <div class="flex justify-between items-end mb-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Clearance Rate</span>
                                    <span class="text-[10px] bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-1.5 py-0.5 rounded border border-emerald-200 dark:border-emerald-800/30 font-bold" x-show="selectedPerson" x-text="selectedPerson ? selectedPerson.total_approved + ' Approved' : '0 Approved'"></span>
                                </div>
                                <span class="text-sm font-extrabold text-gray-900 dark:text-gray-100 leading-none" x-text="selectedPerson ? getCompletionRate(selectedPerson) + '%' : '0%'"></span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 border border-gray-200 dark:border-gray-700 mt-1">
                                <div class="bg-emerald-500 h-full rounded-full" :style="'width: ' + (selectedPerson ? getCompletionRate(selectedPerson) : 0) + '%'"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Caseload Breakdown</h4>
                
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <!-- Clickable Total Assigned Box -->
                    <div class="bg-white dark:bg-warmdark-panel border-t-4 border-t-blue-500 border border-gray-200 dark:border-warmdark-border rounded-xl p-5 shadow-sm group">
                        <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Total Assigned</p>
                        <div class="flex items-center justify-between">
                            <p class="text-3xl font-extrabold text-gray-900 dark:text-white" x-text="selectedPerson ? selectedPerson.total_assigned : 0"></p>
                            
                            <!-- Button that opens the student list -->
                            <button @click.stop="openStudentsModal()" x-show="selectedPerson && selectedPerson.total_assigned > 0" class="text-blue-600 bg-blue-50 hover:bg-blue-100 dark:text-blue-400 dark:bg-blue-900/20 dark:hover:bg-blue-900/40 p-2.5 rounded-xl transition-colors border border-transparent hover:border-blue-200 dark:hover:border-blue-800/50" title="View List of Assigned Students">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-warmdark-panel border-t-4 border-t-amber-500 border border-gray-200 dark:border-warmdark-border rounded-xl p-5 shadow-sm">
                        <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Backlog (Pending)</p>
                        <p class="text-3xl font-extrabold text-amber-600 dark:text-amber-500" x-text="selectedPerson ? selectedPerson.total_pending : 0"></p>
                    </div>

                    <div class="bg-white dark:bg-warmdark-panel border-t-4 border-t-red-500 border border-gray-200 dark:border-warmdark-border rounded-xl p-5 shadow-sm">
                        <p class="text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Needs Revision</p>
                        <p class="text-3xl font-extrabold text-red-600 dark:text-red-500" x-text="selectedPerson ? selectedPerson.total_revision : 0"></p>
                    </div>
                </div>

                <!-- Insights Block -->
                <div class="bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border rounded-xl p-5 mb-6">
                    <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Admin Insight
                    </h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed" x-text="getInsightText(selectedPerson)"></p>
                </div>

                <!-- RECENT ASSIGNMENTS SECTION -->
                <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-4">Recent Assignments</h4>
                <div class="space-y-3">
                    <template x-if="selectedPerson && selectedPerson.recent_assignments && selectedPerson.recent_assignments.length > 0">
                        <template x-for="recent in selectedPerson.recent_assignments" :key="recent.control_number">
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-warmdark-bg border border-gray-100 dark:border-warmdark-border rounded-xl transition-colors">
                                <div class="min-w-0 pr-4">
                                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                                        <p class="text-sm font-bold text-gray-800 dark:text-gray-200 truncate" x-text="recent.research_leader"></p>
                                        <template x-if="recent.is_sub == 1">
                                            <span class="text-[9px] font-bold uppercase tracking-wider bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800/30">Sub-Reviewer</span>
                                        </template>
                                        <template x-if="recent.is_sub == 0">
                                            <span class="text-[9px] font-bold uppercase tracking-wider bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 px-2 py-0.5 rounded border border-blue-200 dark:border-blue-800/30">Original</span>
                                        </template>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-[200px] sm:max-w-xs" x-text="recent.thesis_title"></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-xs font-semibold text-gray-600 dark:text-gray-300" x-text="recent.formatted_date.split(' - ')[0]"></p>
                                    <p class="text-[10px] font-medium text-gray-400 dark:text-gray-500" x-text="recent.formatted_date.split(' - ')[1]"></p>
                                </div>
                            </div>
                        </template>
                    </template>
                    <template x-if="!selectedPerson || !selectedPerson.recent_assignments || selectedPerson.recent_assignments.length === 0">
                        <div class="p-6 text-center bg-gray-50 dark:bg-warmdark-bg border border-dashed border-gray-200 dark:border-warmdark-border rounded-xl">
                            <p class="text-sm text-gray-500 dark:text-gray-400 italic">No recent assignments.</p>
                        </div>
                    </template>
                </div>

            </div>
            
            <!-- Modal Footer -->
            <div class="px-8 py-5 bg-gray-50 dark:bg-warmdark-bg border-t border-gray-200 dark:border-warmdark-border flex justify-end">
                <button @click="closeModal()" class="bg-white dark:bg-warmdark-panel border border-gray-300 dark:border-warmdark-border text-gray-700 dark:text-gray-200 px-6 py-2.5 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-50 dark:hover:bg-warmdark-hover transition-colors">
                    Close Report
                </button>
            </div>
        </div>
    </div>

    <!-- SECONDARY MODAL: ASSIGNED STUDENTS LIST -->
    <div x-show="studentsModalOpen" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6 bg-slate-900/80 backdrop-blur-sm" x-cloak>
        <div @click.away="closeStudentsModal()" class="bg-white dark:bg-warmdark-panel rounded-2xl shadow-2xl w-full max-w-5xl overflow-hidden flex flex-col max-h-[85vh] border border-gray-200 dark:border-warmdark-border transform transition-all">
            
            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-200 dark:border-warmdark-border flex justify-between items-center bg-gray-50 dark:bg-warmdark-bg">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        List of Assigned Students
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="selectedPerson ? 'Currently assigned to: ' + selectedPerson.full_name : ''"></p>
                </div>
                <button @click="closeStudentsModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors p-2 rounded-lg hover:bg-gray-200 dark:hover:bg-warmdark-hover">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <!-- Body (Table) -->
            <div class="p-0 overflow-y-auto custom-scrollbar flex-1 bg-white dark:bg-warmdark-panel">
                <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-100 dark:bg-warmdark-bg text-[10px] uppercase text-gray-500 dark:text-gray-400 sticky top-0 shadow-sm z-10">
                        <tr>
                            <th class="px-6 py-3 font-bold tracking-wider">Control No.</th>
                            <th class="px-6 py-3 font-bold tracking-wider">Research Leader</th>
                            <th class="px-6 py-3 font-bold tracking-wider">Thesis Title</th>
                            <!-- NEW COLUMNS -->
                            <th class="px-6 py-3 font-bold tracking-wider text-center">Assigned Date</th>
                            <th class="px-6 py-3 font-bold tracking-wider text-center">Role Type</th>
                            <th class="px-6 py-3 font-bold tracking-wider text-center">Current Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-warmdark-border">
                        <template x-if="selectedPerson && selectedPerson.assigned_students && selectedPerson.assigned_students.length > 0">
                            <template x-for="student in selectedPerson.assigned_students" :key="student.control_number">
                                <tr class="hover:bg-gray-50 dark:hover:bg-warmdark-hover transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-800 dark:text-gray-200 whitespace-nowrap" x-text="student.control_number"></td>
                                    <td class="px-6 py-4 font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap" x-text="student.research_leader"></td>
                                    <td class="px-6 py-4 leading-relaxed max-w-xs truncate" :title="student.thesis_title" x-text="student.thesis_title"></td>
                                    
                                    <!-- NEW DATA -->
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <p class="text-xs font-semibold text-gray-700 dark:text-gray-300" x-text="student.formatted_date.split(' - ')[0]"></p>
                                        <p class="text-[10px] text-gray-500 dark:text-gray-400 font-medium" x-text="student.formatted_date.split(' - ')[1]"></p>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <template x-if="student.is_sub == 1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 px-2.5 py-1 rounded border border-amber-200 dark:border-amber-800/30 inline-block w-[100px] text-center">Sub-Reviewer</span>
                                        </template>
                                        <template x-if="student.is_sub == 0">
                                            <span class="text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 px-2.5 py-1 rounded border border-blue-200 dark:border-blue-800/30 inline-block w-[100px] text-center">Original</span>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <template x-if="student.current_status == 'Approved'">
                                            <span class="text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 px-2.5 py-1 rounded border border-emerald-200 dark:border-emerald-800/30 inline-block w-[110px] text-center">Approved</span>
                                        </template>
                                        <template x-if="student.current_status == 'Needs Revision'">
                                            <span class="text-[10px] font-bold uppercase tracking-wider bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-2.5 py-1 rounded border border-red-200 dark:border-red-800/30 inline-block w-[110px] text-center">Needs Revision</span>
                                        </template>
                                        <template x-if="student.current_status == 'Pending'">
                                            <span class="text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 px-2.5 py-1 rounded border border-amber-200 dark:border-amber-800/30 inline-block w-[110px] text-center">Pending</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </template>
                        <template x-if="!selectedPerson || !selectedPerson.assigned_students || selectedPerson.assigned_students.length === 0">
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-400 dark:text-gray-500">
                                    No students are assigned to this reviewer.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-warmdark-bg border-t border-gray-200 dark:border-warmdark-border flex justify-end">
                <button @click="closeStudentsModal()" class="bg-white dark:bg-warmdark-panel border border-gray-300 dark:border-warmdark-border text-gray-700 dark:text-gray-200 px-5 py-2 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-100 dark:hover:bg-warmdark-hover transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Report
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function personnelWorkloadApp() {
    return {
        selectedDept: '',
        personnelList: [],
        isLoading: false,
        modalOpen: false,
        studentsModalOpen: false, 
        selectedPerson: null,

        fetchPersonnelStats() {
            if (!this.selectedDept) {
                this.personnelList = [];
                return;
            }
            this.isLoading = true;
            
            fetch(`../../backend/ajax/fetch_human_grammarian_personnel_stats.php?dept_id=${this.selectedDept}`)
                .then(res => res.json())
                .then(data => {
                    this.personnelList = data;
                    this.isLoading = false;
                })
                .catch(err => {
                    console.error("Error fetching data:", err);
                    this.isLoading = false;
                });
        },

        getCompletionRate(person) {
            if (!person || person.total_assigned == 0) return 0;
            return Math.round((person.total_approved / person.total_assigned) * 100);
        },

        getInsightText(person) {
            if (!person) return '';
            let total = parseInt(person.total_assigned);
            let pending = parseInt(person.total_pending);
            
            if (total === 0) return "This personnel currently has no assigned students for this school year.";
            if (pending > 10) return `High Workload Alert: This reviewer has ${pending} documents waiting in their queue. Consider reassigning new students to other personnel.`;
            if (pending > 0) return `This reviewer is actively processing documents, with ${pending} submissions currently waiting for their evaluation.`;
            
            return "This reviewer has cleared their entire backlog! They are currently free to take on new assignments.";
        },

        // NEW: Availability Helper Methods
        getAvailability(person) {
            if (!person) return { label: 'Unknown', color: 'text-gray-500', bg: 'bg-gray-100', border: 'border-gray-200', text: '' };
            let pending = parseInt(person.total_pending);

            if (pending === 0) {
                return { 
                    label: 'Highly Available', 
                    color: 'text-emerald-600 dark:text-emerald-400', 
                    bg: 'bg-emerald-50 dark:bg-emerald-900/10', 
                    border: 'border-emerald-100 dark:border-emerald-800/30', 
                    text: 'Ready for new students' 
                };
            }
            if (pending <= 5) {
                return { 
                    label: 'Available', 
                    color: 'text-blue-600 dark:text-blue-400', 
                    bg: 'bg-blue-50 dark:bg-blue-900/10', 
                    border: 'border-blue-100 dark:border-blue-800/30', 
                    text: 'Accepting assignments' 
                };
            }
            if (pending <= 10) {
                return { 
                    label: 'Busy', 
                    color: 'text-amber-600 dark:text-amber-500', 
                    bg: 'bg-amber-50 dark:bg-amber-900/10', 
                    border: 'border-amber-100 dark:border-amber-800/30', 
                    text: 'Moderate caseload' 
                };
            }
            return { 
                label: 'At Capacity', 
                color: 'text-red-600 dark:text-red-500', 
                bg: 'bg-red-50 dark:bg-red-900/10', 
                border: 'border-red-100 dark:border-red-800/30', 
                text: 'Delay new assignments' 
            };
        },

        openModal(person) {
            this.selectedPerson = person;
            this.modalOpen = true;
            document.body.style.overflow = 'hidden'; 
        },

        closeModal() {
            this.modalOpen = false;
            if (!this.studentsModalOpen) {
                document.body.style.overflow = '';
            }
            setTimeout(() => { 
                if(!this.studentsModalOpen) this.selectedPerson = null; 
            }, 300); 
        },

        openStudentsModal() {
            this.studentsModalOpen = true;
        },

        closeStudentsModal() {
            this.studentsModalOpen = false;
        }
    }
}
</script>