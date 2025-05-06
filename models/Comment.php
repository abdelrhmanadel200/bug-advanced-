<?php
class Comment {
    private $id;
    private $bug_id;
    private $user_id;
    private $content;
    private $created_at;

    public function __construct($id = null, $bug_id = null, $user_id = null, $content = null) {
        $this->id = $id;
        $this->bug_id = $bug_id;
        $this->user_id = $user_id;
        $this->content = $content;
        $this->created_at = date('Y-m-d H:i:s');
        
        if ($id) {
            $this->loadComment();
        }
    }

    private function loadComment() {
        global $db;
        
        $stmt = $db->prepare("SELECT * FROM comments WHERE id = ?");
        $stmt->execute([$this->id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($comment) {
            $this->bug_id = $comment['bug_id'];
            $this->user_id = $comment['user_id'];
            $this->content = $comment['content'];
            $this->created_at = $comment['created_at'];
        }
    }

    public function getId() {
        return $this->id;
    }

    public function getBugId() {
        return $this->bug_id;
    }

    public function getUserId() {
        return $this->user_id;
    }

    public function getContent() {
        return $this->content;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

    public function getUserDetails() {
        global $db;
        
        $stmt = $db->prepare("SELECT name, role FROM users WHERE id = ?");
        $stmt->execute([$this->user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function save() {
        global $db;
        
        if ($this->id) {
            // Update existing comment
            $stmt = $db->prepare("UPDATE comments SET content = ? WHERE id = ?");
            return $stmt->execute([$this->content, $this->id]);
        } else {
            // Insert new comment
            $stmt = $db->prepare("INSERT INTO comments (bug_id, user_id, content, created_at) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$this->bug_id, $this->user_id, $this->content, $this->created_at]);
            
            if ($result) {
                $this->id = $db->lastInsertId();
                return true;
            }
            
            return false;
        }
    }

    public function delete() {
        global $db;
        
        if ($this->id) {
            $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
            return $stmt->execute([$this->id]);
        }
        
        return false;
    }

    public static function getCommentsByBugId($bugId) {
        global $db;
        
        $stmt = $db->prepare("SELECT c.*, u.name as user_name, u.role as user_role 
                             FROM comments c 
                             JOIN users u ON c.user_id = u.id 
                             WHERE c.bug_id = ? 
                             ORDER BY c.created_at ASC");
        $stmt->execute([$bugId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
