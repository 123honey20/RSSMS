<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../../backend/config/database.php";

$user_id = $_SESSION['user'];
$service_role = $_SESSION['service_role'] ?? '';
$department_id = $_SESSION['department_id'] ?? 0;

$stmtP = $conn->prepare("SELECT id FROM personnel WHERE user_id = ?");
$stmtP->bind_param("i", $user_id);
$stmtP->execute();
$resP = $stmtP->get_result()->fetch_assoc();
$actual_personnel_id = $resP ? $resP['id'] : 0;
$stmtP->close();

$student_list = [];

if ($service_role === 'Grammarly & AI Checking') {
    $stmt = $conn->prepare("SELECT id, research_leader as full_name, control_number FROM students ORDER BY id DESC");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $student_list[] = $row;
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT id, research_leader as full_name, control_number FROM students WHERE department_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $student_list[] = $row;
    }
    $stmt->close();
}
?>

<div class="max-w-6xl mx-auto h-[calc(100vh-120px)] flex flex-col" x-data="chatApp()" x-init="init()">
    
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-2">
                <div class="w-3 h-3 rounded-full bg-blue-500 shadow-sm"></div>
                Student Communications
            </h1>
            <p class="text-sm text-gray-500 mt-1">Manage incoming messages from students requiring your assistance.</p>
        </div>
        <div class="bg-white border border-gray-200 shadow-sm px-4 py-1.5 rounded-lg text-xs font-bold text-gray-600 uppercase tracking-wider">
            <?php echo $service_role === 'Grammarly & AI Checking' ? 'Global Access' : 'Department Access'; ?>
        </div>
    </div>

    <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex">
        
        <div class="w-1/3 border-r border-gray-200 flex flex-col bg-gray-50/50">
            <div class="p-4 border-b border-gray-200 bg-white flex justify-between items-center">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Student Inquiries</p>
            </div>
            
            <div class="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1">
                <?php if (empty($student_list)): ?>
                    <p class="text-sm text-gray-500 p-4 text-center">No students available.</p>
                <?php else: ?>
                    <?php foreach ($student_list as $student): ?>
                        <button @click="selectStudent(<?php echo $student['id']; ?>, '<?php echo addslashes(htmlspecialchars($student['full_name'])); ?>', '<?php echo addslashes(htmlspecialchars($student['control_number'])); ?>')" 
                                :class="activeChat === <?php echo $student['id']; ?> ? 'bg-blue-50 border-blue-200' : 'border-transparent hover:bg-gray-100'"
                                class="w-full flex items-center gap-3 p-3 rounded-lg border transition-colors text-left group">
                            
                            <div class="relative shrink-0">
                                <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold shadow-sm">
                                    <?php echo strtoupper(substr($student['full_name'], 0, 1) ?: 'S'); ?>
                                </div>
                                <div x-show="unreadCounts[<?php echo $student['id']; ?>] > 0" 
                                     x-text="unreadCounts[<?php echo $student['id']; ?>]" 
                                     class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-4 h-4 flex items-center justify-center rounded-full shadow-sm z-10" x-cloak>
                                </div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-center mb-0.5">
                                    <p class="text-sm font-bold text-gray-900 truncate pr-2" :class="unreadCounts[<?php echo $student['id']; ?>] > 0 ? 'text-black font-extrabold' : ''"><?php echo htmlspecialchars($student['full_name'] ?: 'Unknown Student'); ?></p>
                                    <span class="text-[9px] bg-gray-200 text-gray-600 font-bold px-1.5 py-0.5 rounded shadow-inner shrink-0"><?php echo htmlspecialchars($student['control_number']); ?></span>
                                </div>
                                <p class="text-[11px] truncate" :class="unreadCounts[<?php echo $student['id']; ?>] > 0 ? 'text-blue-600 font-bold' : 'text-gray-500'">
                                    <span x-show="unreadCounts[<?php echo $student['id']; ?>] > 0" x-cloak>New Message!</span>
                                    <span x-show="!unreadCounts[<?php echo $student['id']; ?>]">Student Account</span>
                                </p>
                            </div>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="w-2/3 flex flex-col bg-white">
            
            <div x-show="!activeChat" class="flex-1 flex flex-col items-center justify-center text-center p-8">
                <div class="w-16 h-16 bg-gray-50 text-gray-300 rounded-full flex items-center justify-center mb-4 border border-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Select a Conversation</h3>
                <p class="text-sm text-gray-500 mt-1 max-w-sm">Choose a student from the sidebar to view their inquiries.</p>
            </div>

            <div x-show="activeChat" class="flex-1 flex flex-col h-full" x-cloak>
                <div class="h-16 border-b border-gray-200 px-6 flex items-center justify-between bg-white shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-gray-900" x-text="activeName"></span>
                            <span class="text-[10px] text-gray-500 font-medium" x-text="'Control No: ' + activeControl"></span>
                        </div>
                    </div>
                    <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider border border-blue-200 shadow-sm">
                        <?php echo htmlspecialchars($service_role); ?>
                    </span>
                </div>

                <div id="chat-messages-container" class="flex-1 overflow-y-auto p-6 bg-[#F9FAFB] space-y-4 custom-scrollbar flex flex-col">
                    
                    <template x-if="messages.length === 0">
                        <div class="text-center text-sm text-gray-400 mt-10">No messages yet. Send a message to start the conversation!</div>
                    </template>

                    <template x-for="(msg, index) in messages" :key="index">
                        <div class="w-full">
                            <template x-if="msg.sender === 'student'">
                                <div class="flex items-end gap-2 max-w-[80%]">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-xs shrink-0">S</div>
                                    <div class="bg-white border border-gray-200 p-3.5 rounded-2xl rounded-bl-sm shadow-sm">
                                        <p class="text-[13px] text-gray-700 leading-relaxed" x-text="msg.message"></p>
                                        <span class="text-[9px] text-gray-400 mt-1 block" x-text="msg.time_formatted"></span>
                                    </div>
                                </div>
                            </template>

                            <template x-if="msg.sender === 'personnel'">
                                <div class="flex items-end justify-end gap-2 w-full mt-2">
                                    <div class="bg-blue-600 p-3.5 rounded-2xl rounded-br-sm shadow-sm max-w-[80%]">
                                        <p class="text-[13px] text-white leading-relaxed" x-text="msg.message"></p>
                                        <span class="text-[9px] text-blue-200 mt-1 block text-right" x-text="msg.time_formatted"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="p-4 bg-white border-t border-gray-200 shrink-0">
                    <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                        <input type="text" x-model="newMessage" placeholder="Type your reply here..." class="flex-1 bg-gray-50 border border-gray-200 rounded-full px-4 py-2.5 text-sm focus:outline-none focus:border-blue-400 focus:bg-white transition-colors" required>
                        <button type="submit" :disabled="isSending" class="bg-blue-600 text-white p-2.5 rounded-full hover:bg-blue-700 shadow-sm transition-colors flex items-center justify-center disabled:opacity-50">
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
        unreadCounts: {}, // NEW: Object to store unread counts per student
        globalInterval: null, // NEW: Interval to constantly fetch unread badges

        init() {
            // Start checking for unread badges immediately on page load
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
            }).then(() => this.fetchUnreadCounts()); // Refresh badges
        },

        selectStudent(id, name, control) {
            this.activeChat = id;
            this.activeName = name;
            this.activeControl = control;
            this.messages = []; 
            
            // Mark immediately as read when clicking the student
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
                        // If we are actively looking at this chat and a new message arrives, mark it as read immediately!
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