<?php
declare(strict_types=1);

/**
 * Rwanda Connect - Messages
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$userId = getCurrentUserId();
$db = getDb();

// For now, we'll create a basic messages structure
// In a real implementation, this would connect to a messages table

$pageTitle = 'Messages - Rwanda Connect';
$pageDescription = 'Your messages and conversations.';

require_once __DIR__ . '/../../includes/header.php';
?>

<main class="page messages-page">
    <div class="page-container">
        <header class="page-header">
            <h1>Messages</h1>
            <p>Your conversations and notifications.</p>
        </header>
        
        <div class="messages-layout">
            <!-- Conversations List -->
            <aside class="conversations-sidebar">
                <div class="sidebar-header">
                    <h2>Conversations</h2>
                    <button class="btn btn-primary btn-sm" id="new-message-btn">✍️ New Message</button>
                </div>
                
                <div class="conversation-search">
                    <input type="search" placeholder="Search conversations..." id="conversation-search">
                </div>
                
                <div class="conversations-list">
                    <div class="empty-state">
                        <span class="empty-icon">💬</span>
                        <p>No conversations yet</p>
                        <p class="empty-hint">Start a conversation by messaging a connection or replying to a notification.</p>
                    </div>
                </div>
            </aside>
            
            <!-- Message Thread -->
            <main class="messages-main">
                <div class="empty-thread">
                    <span class="empty-icon">💬</span>
                    <h2>Select a conversation</h2>
                    <p>Choose a conversation from the left to start messaging.</p>
                </div>
            </main>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<style>
/* Messages Page Styles */
.messages-page {
    min-height: 100vh;
}

.page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 24px;
}

.page-header {
    margin-bottom: 32px;
}

.page-header h1 {
    margin: 0 0 8px;
    font-size: clamp(2rem, 4vw, 3rem);
}

.page-header p {
    margin: 0;
    color: var(--muted);
}

.messages-layout {
    display: grid;
    grid-template-columns: 380px 1fr;
    gap: 24px;
    min-height: 600px;
}

@media (max-width: 1024px) {
    .messages-layout {
        grid-template-columns: 1fr;
    }
}

.conversations-sidebar {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    display: flex;
    flex-direction: column;
    height: fit-content;
    position: sticky;
    top: 100px;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--line);
}

.sidebar-header h2 {
    margin: 0;
    font-size: 1.2rem;
}

.conversation-search {
    padding: 16px 24px;
    border-bottom: 1px solid var(--line);
}

.conversation-search input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    font-size: .9rem;
}

.conversation-search input:focus {
    outline: none;
    border-color: var(--green);
    box-shadow: 0 0 0 3px var(--green-light);
}

.conversations-list {
    flex: 1;
    overflow-y: auto;
    max-height: 600px;
}

.conversation-item {
    display: flex;
    gap: 12px;
    padding: 16px 24px;
    border-bottom: 1px solid var(--line);
    cursor: pointer;
    transition: all var(--transition);
}

.conversation-item:hover {
    background: var(--green-light);
}

.conversation-item.active {
    background: var(--green-light);
    border-left: 3px solid var(--green);
}

.conversation-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--green-light);
    color: var(--green);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.conversation-info {
    flex: 1;
    min-width: 0;
}

.conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 4px;
}

.conversation-name {
    font-weight: 600;
    font-size: .95rem;
}

.conversation-time {
    font-size: .75rem;
    color: var(--muted);
    white-space: nowrap;
}

.conversation-preview {
    color: var(--muted);
    font-size: .85rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.unread-badge {
    background: var(--green);
    color: white;
    font-size: .7rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 999px;
}

.messages-main {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: var(--radius-xl);
    display: flex;
    flex-direction: column;
}

.empty-thread {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 48px;
    text-align: center;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-thread h2 {
    margin: 0 0 8px;
    font-size: 1.5rem;
}

.empty-thread p {
    margin: 0 0 24px;
    color: var(--muted);
    max-width: 300px;
}

/* Thread View */
.thread-header {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 24px;
    border-bottom: 1px solid var(--line);
}

.thread-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--green-light);
    color: var(--green);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
}

.thread-info h3 {
    margin: 0 0 4px;
    font-size: 1.1rem;
}

.thread-status {
    font-size: .8rem;
    color: var(--success);
}

.thread-messages {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.message {
    max-width: 70%;
    animation: fadeIn 0.3s ease;
}

.message.sent {
    align-self: flex-end;
}

.message.received {
    align-self: flex-start;
}

.message-bubble {
    padding: 12px 16px;
    border-radius: var(--radius-lg);
    position: relative;
}

.message.sent .message-bubble {
    background: var(--green);
    color: white;
    border-radius: var(--radius-xl) var(--radius-xl) 4px var(--radius-xl);
}

.message.received .message-bubble {
    background: var(--background);
    color: var(--ink);
    border-radius: var(--radius-xl) var(--radius-xl) var(--radius-xl) 4px;
}

.message-text {
    margin: 0;
    line-height: 1.5;
}

.message-time {
    font-size: .65rem;
    opacity: 0.7;
    margin-top: 4px;
    text-align: right;
}

.message.received .message-time {
    text-align: left;
}

.thread-input {
    display: flex;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid var(--line);
    background: var(--surface);
    border-radius: 0 0 var(--radius-xl) var(--radius-xl);
}

.thread-input input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid var(--line);
    border-radius: var(--radius-md);
    font-size: .95rem;
}

.thread-input input:focus {
    outline: none;
    border-color: var(--green);
    box-shadow: 0 0 0 3px var(--green-light);
}

.thread-input button {
    padding: 12px 20px;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .page-container {
        padding: 24px 16px;
    }
    
    .messages-layout {
        grid-template-columns: 1fr;
    }
    
    .conversations-sidebar {
        position: static;
    }
}
</style>