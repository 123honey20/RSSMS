<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$student_id = $_SESSION['user'];

// 1. Get the Student's Department ID
$stmtStudent = $conn->prepare("SELECT id, department_id FROM students WHERE user_id = ?");
$stmtStudent->bind_param("i", $student_id);
$stmtStudent->execute();
$student_res = $stmtStudent->get_result()->fetch_assoc();
$actual_student_id = $student_res ? $student_res['id'] : 0;
$student_dept_id = $student_res ? $student_res['department_id'] : 0;
$stmtStudent->close();

// 2. FIXED QUERY: Fetch Personnel for THIS specific service using the Junction Table
$service_role_name = 'Ethics';
$stmtP = $conn->prepare("
    SELECT p.id as personnel_id, p.full_name, p.service_role 
    FROM personnel p
    JOIN personnel_departments pd ON p.user_id = pd.user_id
    WHERE p.service_role = ? AND pd.department_id = ?
");
$stmtP->bind_param("si", $service_role_name, $student_dept_id);
$stmtP->execute();
$resP = $stmtP->get_result();
$personnel_list = [];
while ($row = $resP->fetch_assoc()) {
    $personnel_list[] = $row;
}
$stmtP->close();
?>

<div class="max-w-6xl mx-auto h-[calc(100vh-120px)] flex flex-col transition-colors duration-200" x-data="studentChatApp()" x-init="init()">
    <div class="mb-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 tracking-tight flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-blue-400 shadow-sm"></div>
            <?php echo $service_role_name; ?> Communication
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Chat directly with the <?php echo $service_role_name; ?> personnel assigned to your department.</p>
    </div>

    <div class="flex-1 bg-white dark:bg-warmdark-panel rounded-xl shadow-sm border border-gray-200 dark:border-warmdark-border overflow-hidden flex transition-colors">
        
        <div class="w-1/3 border-r border-gray-200 dark:border-warmdark-border flex flex-col bg-gray-50/50 dark:bg-warmdark-bg transition-colors">
            <div class="p-4 border-b border-gray-200 dark:border-warmdark-border bg-white dark:bg-warmdark-panel transition-colors">
                <p class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Department Personnel</p>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1">
                <?php if (empty($personnel_list)): ?>
                    <p class="text-sm text-gray-500 dark:text-gray-400 p-4 text-center">No personnel available for your department in this service yet.</p>
                <?php else: ?>
                    <?php foreach ($personnel_list as $person): ?>
                        <button @click="selectPersonnel(<?php echo $person['personnel_id']; ?>, '<?php echo addslashes(htmlspecialchars($person['full_name'])); ?>')" 
                                :class="activeChat === <?php echo $person['personnel_id']; ?> ? 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-900/50' : 'border-transparent hover:bg-gray-100 dark:hover:bg-warmdark-hover'"
                                class="w-full flex items-center gap-3 p-3 rounded-lg border transition-colors text-left group">
                            <div class="relative shrink-0">
                                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold shadow-sm transition-colors">
                                    <?php echo strtoupper(substr($person['full_name'], 0, 1)); ?>
                                </div>
                                <div x-show="unreadCounts[<?php echo $person['personnel_id']; ?>] > 0" 
                                     x-text="unreadCounts[<?php echo $person['personnel_id']; ?>]" 
                                     class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-4 h-4 flex items-center justify-center rounded-full shadow-sm z-10" x-cloak>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold truncate pr-2 transition-colors" :class="unreadCounts[<?php echo $person['personnel_id']; ?>] > 0 ? 'text-black dark:text-white font-extrabold' : 'text-gray-900 dark:text-gray-200'"><?php echo htmlspecialchars($person['full_name']); ?></p>
                                <p class="text-[11px] truncate transition-colors" :class="unreadCounts[<?php echo $person['personnel_id']; ?>] > 0 ? 'text-blue-600 dark:text-blue-400 font-bold' : 'text-gray-500 dark:text-gray-400'">
                                    <span x-show="unreadCounts[<?php echo $person['personnel_id']; ?>] > 0" x-cloak>New Message!</span>
                                    <span x-show="!unreadCounts[<?php echo $person['personnel_id']; ?>]"><?php echo htmlspecialchars($person['service_role']); ?></span>
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Your Messages</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-sm">Select a personnel from the sidebar to start a conversation.</p>
            </div>

            <div x-show="activeChat" class="flex-1 flex flex-col h-full" x-cloak>
                <div class="h-16 border-b border-gray-200 dark:border-warmdark-border px-6 flex items-center justify-between bg-white dark:bg-warmdark-panel shrink-0 transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-bold text-gray-900 dark:text-gray-100" x-text="activeName"></span>
                        <span class="bg-green-100 dark:bg-green-500/20 text-green-700 dark:text-green-400 text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider transition-colors">Connected</span>
                    </div>
                </div>

                <div id="chat-messages-container" class="flex-1 overflow-y-auto p-6 bg-[#F9FAFB] dark:bg-warmdark-bg space-y-4 custom-scrollbar flex flex-col transition-colors">
                    <template x-if="messages.length === 0">
                        <div class="text-center text-sm text-gray-400 dark:text-gray-500 mt-10">No messages yet. Say Hello!</div>
                    </template>
                    <template x-for="(msg, index) in messages" :key="index">
                        <div class="w-full">
                            <template x-if="msg.sender === 'personnel'">
                                <div class="flex items-end gap-2 max-w-[80%] mt-2">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-xs shrink-0 transition-colors">P</div>
                                    <div class="bg-white dark:bg-warmdark-panel border border-gray-200 dark:border-warmdark-border p-3.5 rounded-2xl rounded-bl-sm shadow-sm transition-colors">
                                        <p class="text-[13px] text-gray-700 dark:text-gray-200 leading-relaxed" x-text="msg.message"></p>
                                        <span class="text-[9px] text-gray-400 dark:text-gray-500 mt-1 block" x-text="msg.time_formatted"></span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="msg.sender === 'student'">
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
                        <input type="text" x-model="newMessage" placeholder="Type your message here..." class="flex-1 bg-gray-50 dark:bg-warmdark-bg border border-gray-200 dark:border-warmdark-border text-gray-900 dark:text-gray-100 rounded-full px-4 py-2.5 text-sm focus:outline-none focus:border-blue-400 dark:focus:border-blue-500 focus:bg-white dark:focus:bg-warmdark-panel transition-colors" required>
                        <button type="submit" :disabled="isSending" class="bg-blue-600 dark:bg-blue-700 text-white p-2.5 rounded-full hover:bg-blue-700 dark:hover:bg-blue-600 shadow-sm transition-colors flex items-center justify-center disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function studentChatApp() {
    return {
        activeChat: null,
        activeName: '',
        messages: [],
        newMessage: '',
        studentId: <?php echo $actual_student_id; ?>,
        serviceType: '<?php echo addslashes($service_role_name); ?>',
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
            fetch(`../../backend/ajax/get_unread_counts_student.php?student_id=${this.studentId}&service_type=${encodeURIComponent(this.serviceType)}`)
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
                    student_id: this.studentId,
                    personnel_id: this.activeChat,
                    service_type: this.serviceType,
                    reader: 'student'
                })
            }).then(() => this.fetchUnreadCounts()); 
        },

        selectPersonnel(id, name) {
            this.activeChat = id;
            this.activeName = name;
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
            fetch(`../../backend/ajax/get_chat_messages.php?student_id=${this.studentId}&personnel_id=${this.activeChat}&service_type=${encodeURIComponent(this.serviceType)}`)
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
                    student_id: this.studentId,
                    personnel_id: this.activeChat,
                    sender: 'student',
                    service_type: this.serviceType,
                    message: msgText
                })
            })
            .then(res => res.json())
            .then(data => {
                this.isSending = false;
                if(data.success) this.fetchMessages(true);
                else alert('Failed to send message.');
            })
            .catch(() => this.isSending = false);
        },

        scrollToBottom() {
            setTimeout(() => {
                const container = document.getElementById('chat-messages-container');
                if(container) container.scrollTop = container.scrollHeight;
            }, 100);
        }
    }
}
</script>