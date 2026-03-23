<?php
require_once 'config.php';
require_once '../includes/Util.php';
require_once '../models/User.php';

// Require authentication
Util::requireAuth();

$db = Database::getInstance();
$currentUser = $_SESSION['user_id'];
$message = '';

// Get active conversation or start a new one
$conversationUser = null;
if (isset($_GET['user'])) {
    $userId = filter_var($_GET['user'], FILTER_VALIDATE_INT);
    $conversationUser = User::getById($userId);
    if (!$conversationUser) {
        header('Location: messages.php');
        exit();
    }
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conversationUser) {
    $content = trim($_POST['content'] ?? '');
    if (!empty($content)) {
        try {
            $stmt = $db->prepare(
                "INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)"
            );
            $stmt->execute([$currentUser, $conversationUser['id'], $content]);
            header("Location: messages.php?user=" . $conversationUser['id']);
            exit();
        } catch (PDOException $e) {
            $message = 'Không thể gửi tin nhắn';
        }
    }
}

// Get list of conversations
$conversations = $db->query("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = $currentUser THEN m.receiver_id
            ELSE m.sender_id 
        END as user_id,
        p.name, p.avatar,
        u.role,
        (
            SELECT content 
            FROM messages m2 
            WHERE (
                (m2.sender_id = $currentUser AND m2.receiver_id = CASE 
                    WHEN m.sender_id = $currentUser THEN m.receiver_id
                    ELSE m.sender_id 
                END)
                OR 
                (m2.receiver_id = $currentUser AND m2.sender_id = CASE 
                    WHEN m.sender_id = $currentUser THEN m.receiver_id
                    ELSE m.sender_id 
                END)
            )
            ORDER BY m2.timestamp DESC 
            LIMIT 1
        ) as last_message,
        (
            SELECT timestamp 
            FROM messages m2 
            WHERE (
                (m2.sender_id = $currentUser AND m2.receiver_id = CASE 
                    WHEN m.sender_id = $currentUser THEN m.receiver_id
                    ELSE m.sender_id 
                END)
                OR 
                (m2.receiver_id = $currentUser AND m2.sender_id = CASE 
                    WHEN m.sender_id = $currentUser THEN m.receiver_id
                    ELSE m.sender_id 
                END)
            )
            ORDER BY m2.timestamp DESC 
            LIMIT 1
        ) as last_time
    FROM messages m
    JOIN users u ON (
        CASE 
            WHEN m.sender_id = $currentUser THEN m.receiver_id
            ELSE m.sender_id 
        END = u.id
    )
    JOIN profiles p ON u.id = p.user_id
    WHERE m.sender_id = $currentUser OR m.receiver_id = $currentUser
    ORDER BY last_time DESC
")->fetchAll();

// Get messages for current conversation
$messages = [];
if ($conversationUser) {
    $stmt = $db->prepare("
        SELECT m.*, 
            sp.name as sender_name, sp.avatar as sender_avatar,
            rp.name as receiver_name, rp.avatar as receiver_avatar
        FROM messages m
        JOIN profiles sp ON m.sender_id = sp.user_id
        JOIN profiles rp ON m.receiver_id = rp.user_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
        OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.timestamp ASC
    ");
    $stmt->execute([$currentUser, $conversationUser['id'], $conversationUser['id'], $currentUser]);
    $messages = $stmt->fetchAll();
}

require_once '../includes/header.php';
?>

<div class="container" style="max-width: 1200px; margin: 2rem auto;">
    <h1 style="margin-bottom: 2rem;">Tin nhắn</h1>

    <?php if ($message): ?>
        <div class="flash-message error">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div style="
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 1.5rem;
        background-color: var(--bg-secondary);
        border-radius: 0.5rem;
        overflow: hidden;
    ">
        <!-- Conversations List -->
        <div style="
            border-right: 1px solid var(--border-color);
            max-height: 80vh;
            overflow-y: auto;
        ">
            <?php foreach ($conversations as $conv): ?>
                <a href="?user=<?= $conv['user_id'] ?>" style="
                    display: block;
                    padding: 1rem;
                    text-decoration: none;
                    color: var(--text-primary);
                    border-bottom: 1px solid var(--border-color);
                    background-color: <?= $conversationUser && $conversationUser['id'] == $conv['user_id'] ? 'var(--bg-primary)' : 'transparent' ?>;
                ">
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <img src="<?= $conv['avatar'] ? '../uploads/avatars/' . $conv['avatar'] : '../assets/images/default-avatar.png' ?>"
                             alt="<?= htmlspecialchars($conv['name']) ?>"
                             style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                <strong style="
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                ">
                                    <?= htmlspecialchars($conv['name']) ?>
                                </strong>
                                <small style="color: var(--text-secondary);">
                                    <?= date('H:i', strtotime($conv['last_time'])) ?>
                                </small>
                            </div>
                            <div style="
                                color: var(--text-secondary);
                                white-space: nowrap;
                                overflow: hidden;
                                text-overflow: ellipsis;
                            ">
                                <?= htmlspecialchars($conv['last_message']) ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>

            <?php if (empty($conversations)): ?>
                <div style="padding: 2rem; text-align: center; color: var(--text-secondary);">
                    Chưa có cuộc trò chuyện nào
                </div>
            <?php endif; ?>
        </div>

        <!-- Messages Area -->
        <div style="display: flex; flex-direction: column; max-height: 80vh;">
            <?php if ($conversationUser): ?>
                <!-- Conversation Header -->
                <div style="
                    padding: 1rem;
                    border-bottom: 1px solid var(--border-color);
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                ">
                    <img src="<?= $conversationUser['avatar'] ? '../uploads/avatars/' . $conversationUser['avatar'] : '../assets/images/default-avatar.png' ?>"
                         alt="<?= htmlspecialchars($conversationUser['name']) ?>"
                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                    <div>
                        <strong><?= htmlspecialchars($conversationUser['name']) ?></strong>
                        <div style="color: var(--text-secondary); font-size: 0.875rem;">
                            <?= $conversationUser['role'] === 'kol_koc' ? 'KOL/KOC' : ucfirst($conversationUser['role']) ?>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div id="messages" style="flex: 1; overflow-y: auto; padding: 1rem;">
                    <?php
                    $lastDate = null;
                    foreach ($messages as $msg):
                        $messageDate = date('Y-m-d', strtotime($msg['timestamp']));
                        if ($lastDate !== $messageDate):
                            $lastDate = $messageDate;
                    ?>
                        <div style="
                            text-align: center;
                            margin: 1rem 0;
                            color: var(--text-secondary);
                            font-size: 0.875rem;
                        ">
                            <?php
                            $today = date('Y-m-d');
                            $yesterday = date('Y-m-d', strtotime('-1 day'));
                            
                            if ($messageDate === $today) {
                                echo 'Hôm nay';
                            } elseif ($messageDate === $yesterday) {
                                echo 'Hôm qua';
                            } else {
                                echo date('d/m/Y', strtotime($messageDate));
                            }
                            ?>
                        </div>
                    <?php endif; ?>

                        <div style="
                            display: flex;
                            gap: 1rem;
                            margin: 1rem 0;
                            <?= $msg['sender_id'] === $currentUser ? 'flex-direction: row-reverse;' : '' ?>
                        ">
                            <img src="<?= $msg['sender_id'] === $currentUser ? 
                                        ($msg['sender_avatar'] ? '../uploads/avatars/' . $msg['sender_avatar'] : '../assets/images/default-avatar.png') :
                                        ($msg['sender_avatar'] ? '../uploads/avatars/' . $msg['sender_avatar'] : '../assets/images/default-avatar.png') ?>"
                                 alt="<?= htmlspecialchars($msg['sender_id'] === $currentUser ? $msg['sender_name'] : $msg['sender_name']) ?>"
                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            
                            <div style="max-width: 60%;">
                                <div style="
                                    background-color: <?= $msg['sender_id'] === $currentUser ? 'var(--accent-color)' : 'var(--bg-primary)' ?>;
                                    color: <?= $msg['sender_id'] === $currentUser ? 'white' : 'var(--text-primary)' ?>;
                                    padding: 0.75rem 1rem;
                                    border-radius: 1rem;
                                    margin-bottom: 0.25rem;
                                ">
                                    <?= nl2br(htmlspecialchars($msg['content'])) ?>
                                </div>
                                <div style="
                                    color: var(--text-secondary);
                                    font-size: 0.75rem;
                                    <?= $msg['sender_id'] === $currentUser ? 'text-align: right;' : '' ?>
                                ">
                                    <?= date('H:i', strtotime($msg['timestamp'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Message Input -->
                <form method="POST" action="" style="
                    padding: 1rem;
                    border-top: 1px solid var(--border-color);
                    display: flex;
                    gap: 1rem;
                ">
                    <textarea name="content" class="form-control" 
                        placeholder="Nhập tin nhắn..."
                        style="resize: none;"
                        rows="1"
                        required></textarea>
                    <button type="submit" class="btn">Gửi</button>
                </form>
            <?php else: ?>
                <div style="
                    flex: 1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: var(--text-secondary);
                ">
                    Chọn một cuộc trò chuyện để bắt đầu
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Scroll to bottom of messages
const messagesDiv = document.getElementById('messages');
if (messagesDiv) {
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// Auto-expand textarea
const textarea = document.querySelector('textarea[name="content"]');
if (textarea) {
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>