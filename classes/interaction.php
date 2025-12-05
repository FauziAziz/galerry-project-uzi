<?php
// Class like sama komentar
class Interaction {
    private $conn;
    private $table = "interactions";

    public $id;
    public $photo_id;
    public $user_id;
    public $type;
    public $comment_text;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Method nya
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (photo_id, user_id, type, comment_text) 
                  VALUES (:photo_id, :user_id, :type, :comment_text)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":photo_id", $this->photo_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":type", $this->type);
        $stmt->bindParam(":comment_text", $this->comment_text);
        
        return $stmt->execute();
    }

    // Method ccek sudah like apa blom
    public function hasLiked() {
        $query = "SELECT id FROM " . $this->table . " 
                  WHERE photo_id = :photo_id AND user_id = :user_id AND type = 'like'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":photo_id", $this->photo_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Method unlike
    public function unlike() {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE photo_id = :photo_id AND user_id = :user_id AND type = 'like'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":photo_id", $this->photo_id);
        $stmt->bindParam(":user_id", $this->user_id);
        
        return $stmt->execute();
    }

    public function getCommentsByPhoto() {
        $query = "SELECT i.*, u.username 
                  FROM " . $this->table . " i 
                  LEFT JOIN users u ON i.user_id = u.id 
                  WHERE i.photo_id = :photo_id AND i.type = 'comment' 
                  ORDER BY i.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":photo_id", $this->photo_id);
        $stmt->execute();
        
        return $stmt;
    }
}
?>