<?php

class Photo {
    private $conn;
    private $table = "photos";

    public $id;
    public $user_id;
    public $title;
    public $description;
    public $hashtag;
    public $image_path;

    public function __construct($db) {
        $this->conn = $db;
    }

    // upload
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, title, description, hashtag, image_path) 
                  VALUES (:user_id, :title, :description, :hashtag, :image_path)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam (":user_id", $this->user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":hashtag", $this->hashtag);
        $stmt->bindParam(":image_path", $this->image_path);
        
        return $stmt->execute();
    }
    //
    public function getAll($hashtag = null) {
        $query = "SELECT p.*, u.username, 
                  (SELECT COUNT(*) FROM interactions WHERE photo_id = p.id AND type = 'like') as like_count,
                  (SELECT COUNT(*) FROM interactions WHERE photo_id = p.id AND type = 'comment') as comment_count
                  FROM " . $this->table . " p 
                  LEFT JOIN users u ON p.user_id = u.id";
        

        if ($hashtag) {
            $query .= " WHERE p.hashtag LIKE :hashtag";
        }
        
         $query .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($hashtag) {
            $hashtag_param = "%{$hashtag}%";
            $stmt->bindParam(":hashtag", $hashtag_param);
        }
        
        $stmt->execute();
        return $stmt;
    }


    public function getById() {
        $query = "SELECT p.*, u.username 
                  FROM " . $this->table . " p 
                  LEFT JOIN users u ON p.user_id = u.id 
                  WHERE p.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        return $stmt;
    }

    // Method delete
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
         $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }
}
?>