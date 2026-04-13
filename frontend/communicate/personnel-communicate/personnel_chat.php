<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$user_id = $_SESSION['user'];
$service_role = $_SESSION['service_role'] ?? '';

// 1. Get the actual personnel ID
$stmtP = $conn->prepare("SELECT id FROM personnel WHERE user_id = ?");
$stmtP->bind_param("i", $user_id);
$stmtP->execute();
$resP = $stmtP->get_result()->fetch_assoc();
$actual_personnel_id = $resP ? $resP['id'] : 0;
$stmtP->close();

// 2. Fetch all assigned departments from the new junction table
$assigned_depts = [];
$stmtDepts = $conn->prepare("SELECT department_id FROM personnel_departments WHERE user_id = ?");
$stmtDepts->bind_param("i", $user_id);
$stmtDepts->execute();
$resDepts = $stmtDepts->get_result();
while ($row = $resDepts->fetch_assoc()) {
    $assigned_depts[] = $row['department_id'];
}
$stmtDepts->close();

$student_list = [];

// 3. Fetch students based on assigned departments (REMOVED GLOBAL GRAMMARLY EXCEPTION)
if (!empty($assigned_depts)) {
    // Create placeholders (e.g., "?, ?, ?") based on how many departments they have
    $placeholders = implode(',', array_fill(0, count($assigned_depts), '?'));
    $types = str_repeat('i', count($assigned_depts));
    
    $stmt = $conn->prepare("SELECT id, research_leader as full_name, control_number FROM students WHERE department_id IN ($placeholders) ORDER BY id DESC");
    $stmt->bind_param($types, ...$assigned_depts);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $student_list[] = $row;
    }
    $stmt->close();
}
?>

<div class="max-w-6xl mx-auto h-[calc(100vh-120px)] flex flex-col transition-colors duration-200" x-data="chatApp()" x-init="init()">
    
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-blue-500 shadow-sm"></div>
                Student Communications
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage incoming messages from students requiring your assistance.</p>
        </div>
        <div class="bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border shadow-sm px-4 py-1.5 rounded-lg text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider transition-colors">
            Department Access
        </div>
    </div>

    <div class="flex-1 bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden flex transition-colors">
        
        <div class="w-1/3 border-r border-gray-200 dark:border-warmdark-border flex flex-col bg-gray-50/50 dark:bg-warmdark-bg transition-colors">
            <div class="p-4 border-b border-gray-200 dark:border-warmdark-border bg-white dark:bg-warmdark-panel flex justify-between items-center transition-colors">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Student Inquiries</p>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1">
                <?php if (empty($student_list)): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400 p-4 text-center">No students available.</p>
                <?php else: ?>
                    <?php foreach ($student_list as $student): ?>
                        <button @click="selectStudent(<?php echo $student['id']; ?>, '<?php echo addslashes(htmlspecialchars($student['full_name'])); ?>', '<?php echo addslashes(htmlspecialchars($student['control_number'])); ?>')" 
                                :class="activeChat === <?php echo $student['id']; ?> ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-900/50' : 'border-transparent hover:bg-gray-100 dark:hover:bg-warmdark-hover'"
                                class="w-full flex items-center gap-3 p-3 rounded-lg border transition-colors text-left group">
                            
                            <div class="relative shrink-0">
                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold shadow-sm transition-colors">
                                    <?php echo strtoupper(substr($student['full_name'], 0, 1) ?: 'S'); ?>
                                </div>
                                <div x-show="unreadCounts[<?php echo $student['id']; ?>] > 0" 
                                     x-text="unreadCounts[<?php echo $student['id']; ?>]" 
                                     class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-4 h-4 flex items-center justify-center rounded-full shadow-sm z-10" x-cloak>
                                </div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center mb-0.5">
                                    <p class="text-sm font-bold truncate pr-2" 
                                       :class="unreadCounts[<?php echo $student['id']; ?>] > 0 ? 'text-black dark:text-white font-extrabold' : 'text-gray-900 dark:text-gray-200'">
                                       <?php echo htmlspecialchars($student['full_name'] ?: 'Unknown Student'); ?>
                                    </p>
                                    <span class="text-[9px] bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-bold px-1.5 py-0.5 rounded shadow-inner shrink-0 transition-colors">
                                        <?php echo htmlspecialchars($student['control_number']); ?>
                                    </span>
                                </div>
                                <p class="text-[11px] truncate" :class="unreadCounts[<?php echo $student['id']; ?>] > 0 ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400'">
                                    <span x-show="unreadCounts[<?php echo $student['id']; ?>] > 0" x-cloak>New Message!</span>
                                    <span x-show="!unreadCounts[<?php echo $student['id']; ?>]">Student Account</span>
                                </p>
                            </div>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="w-2/3 flex flex-col bg-white dark:bg-warmdark-panel transition-colors">
            
            <div x-show="!activeChat" class="flex-1 flex flex-col items-center justify-center text-center p-8">
                <div class="w-16 h-16 bg-gray-50 dark:bg-warmdark-bg text-gray-300 dark:text-gray-600 rounded-full flex items-center justify-center mb-4 border border-gray-100 dark:border-warmdark-border transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Select a Conversation</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm">Choose a student from the sidebar to view their inquiries.</p>
            </div>

            <div x-show="activeChat" class="flex-1 flex flex-col h-full" x-cloak>
                <div class="h-16 border-b border-gray-200 dark:border-warmdark-border px-6 flex items-center justify-between bg-white dark:bg-warmdark-panel shrink-0 transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="activeName"></span>
                            <span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium" x-text="'Control No: ' + activeControl"></span>
                        </div>
                    </div>
                    <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-[10px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider border border-blue-200 dark:border-blue-900/50 shadow-sm transition-colors">
                        <?php echo htmlspecialchars($service_role); ?>
                    </span>
                </div>

                <div id="chat-messages-container" class="flex-1 overflow-y-auto p-6 bg-[#F9FAFB] dark:bg-warmdark-bg space-y-4 custom-scrollbar flex flex-col transition-colors">
                    
                    <template x-if="messages.length === 0">
                        <div class="text-center text-sm text-gray-400 dark:text-gray-500 mt-10">No messages yet. Send a message to start the conversation!</div>
                    </template>

                    <template x-for="(msg, index) in messages" :key="index">
                        <div class="w-full">
                            
                            <template x-if="msg.sender === 'student'">
                                <div class="flex items-end gap-2 max-w-[80%]">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 flex items-center justify-center font-bold text-xs shrink-0 transition-colors">S</div>
                                    <div class="bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border p-3.5 rounded-2xl rounded-bl-sm shadow-sm transition-colors">
                                        <p class="text-[13px] text-gray-700 dark:text-gray-200 leading-relaxed" x-text="msg.message"></p>
                                        <span class="text-[9px] text-gray-400 dark:text-gray-500 mt-1 block" x-text="msg.time_formatted"></span>
                                    </div>
                                </div>
                            </template>

                            <template x-if="msg.sender === 'personnel'">
                                <div class="flex items-end justify-end gap-2 w-full mt-2">
                                    <div class="bg-blue-600 dark:bg-blue-700 p-3.5 rounded-2xl rounded-br-sm shadow-sm max-w-[80%] transition-colors">
                                        <p class="text-[13px] text-white leading-relaxed" x-text="msg.message"></p>
                                        <span class="text-[9px] text-blue-200 mt-1 block text-right" x-text="msg.time_formatted"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="p-4 bg-white dark:bg-warmdark-panel border-t border-gray-200 dark:border-warmdark-border shrink-0 transition-colors">
                    <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                        <input type="text" x-model="newMessage" placeholder="Type your reply here..." 
                               class="flex-1 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-full px-4 py-2.5 text-sm focus:outline-none focus:border-blue-400 dark:focus:border-blue-500 focus:bg-white dark:focus:bg-warmdark-panel transition-colors" required>
                        <button type="submit" :disabled="isSending" 
                                class="bg-blue-600 dark:bg-blue-700 text-white p-2.5 rounded-full hover:bg-blue-700 dark:hover:bg-blue-600 shadow-sm transition-colors flex items-center justify-center disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
function chatApp() {
    return {
        activeChat: null, 
        activeName: '',
        activeControl: '',
        messages: [],
        newMessage: '',
        personnelId: <?php echo $actual_personnel_id; ?>,
        serviceType: '<?php echo addslashes($service_role); ?>',
        chatInterval: null,
        isSending: false,
        unreadCounts: {},
        globalInterval: null,

        init() {
            this.fetchUnreadCounts();
            this.globalInterval = setInterval(() => {
                if (!document.hidden) {
                    this.fetchUnreadCounts();
                }
            }, 5000);
        },

        fetchUnreadCounts() {
            fetch(`../../backend/ajax/get_unread_counts.php?personnel_id=${this.personnelId}&service_type=${encodeURIComponent(this.serviceType)}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    this.unreadCounts = data.counts;
                }
            });
        },

        markAsRead() {
            fetch('../../backend/ajax/mark_chat_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_id: this.activeChat,
                    personnel_id: this.personnelId,
                    service_type: this.serviceType,
                    reader: 'personnel'
                })
            }).then(() => this.fetchUnreadCounts());
        },

        selectStudent(id, name, control) {
            this.activeChat = id;
            this.activeName = name;
            this.activeControl = control;
            this.messages = []; 
            
            this.markAsRead();
            this.fetchMessages();
            
            if(this.chatInterval) clearInterval(this.chatInterval);
            this.chatInterval = setInterval(() => {
                if (!document.hidden) {
                    this.fetchMessages(false);
                }
            }, 5000); 
        },

        fetchMessages(scrollToBottom = true) {
            if(!this.activeChat) return;
            
            fetch(`../../backend/ajax/get_chat_messages.php?student_id=${this.activeChat}&personnel_id=${this.personnelId}&service_type=${encodeURIComponent(this.serviceType)}`)
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    const isNewMessage = this.messages.length !== data.messages.length;
                    this.messages = data.messages;
                    
                    if (scrollToBottom || isNewMessage) {
                        this.scrollToBottom();
                        if (isNewMessage) {
                            this.markAsRead();
                        }
                    }
                }
            });
        },

        sendMessage() {
            if(this.newMessage.trim() === '' || !this.activeChat) return;
            
            this.isSending = true;
            const msgText = this.newMessage;
            this.newMessage = ''; 

            fetch('../../backend/ajax/save_chat_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_id: this.activeChat,
                    personnel_id: this.personnelId,
                    sender: 'personnel',
                    service_type: this.serviceType,
                    message: msgText
                })
            })
            .then(res => res.json())
            .then(data => {
                this.isSending = false;
                if(data.success) {
                    this.fetchMessages(true);
                } else {
                    alert('Failed to send message.');
                }
            })
            .catch(() => this.isSending = false);
        },

        scrollToBottom() {
            setTimeout(() => {
                const container = document.getElementById('chat-messages-container');
                if(container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 100);
        }
    }
}
</script>