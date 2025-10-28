<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class User_model extends Model
{
    protected $table = 'users';

    /**
     * Create a new user account
     */
    public function create(array $data)
    {
        // Hash password if provided and not empty
        if(isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');

        $inserted = $this->db->table($this->table)->insert($data);

        return $inserted ? true : false;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email)
    {
        $result = $this->db->table($this->table)
                           ->where('email', $email)
                           ->get();

        if (empty($result)) return null;

        // Handle array of rows or single row
        if (isset($result[0])) return $result[0];

        return $result;
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool
    {
        $result = $this->db->table($this->table)
                           ->where('email', $email)
                           ->get();

        if (!$result) return false;

        // Return true if at least one row found
        if (isset($result[0])) return true;

        return !empty($result);
    }

    /**
     * Find user by Google ID
     */
    public function findByGoogleId(string $googleId)
    {
        $result = $this->db->table($this->table)
                           ->where('google_id', $googleId)
                           ->get();

        if (empty($result)) return null;

        if (isset($result[0])) return $result[0];

        return $result;
    }

    /**
     * Update user data
     */
    public function updateUser(int $userId, array $data)
    {
        return $this->db->table($this->table)
                        ->where('id', $userId)
                        ->update($data);
    }

    /**
     * Create or update user from Google OAuth
     */
    public function createOrUpdateGoogleUser(array $googleData)
    {
        // Check if user exists by google_id
        $existingUser = $this->findByGoogleId($googleData['google_id']);
        
        if($existingUser) {
            // Update existing user
            $this->updateUser($existingUser['id'], [
                'name' => $googleData['name'],
                'email' => $googleData['email'],
                'avatar' => $googleData['avatar']
            ]);
            return $existingUser;
        }
        
        // Check if user exists by email
        $existingUserByEmail = $this->findByEmail($googleData['email']);
        if($existingUserByEmail) {
            // Update existing user with Google ID
            $this->updateUser($existingUserByEmail['id'], [
                'google_id' => $googleData['google_id'],
                'avatar' => $googleData['avatar']
            ]);
            return $existingUserByEmail;
        }
        
        // Create new user
        $userData = [
            'name' => $googleData['name'],
            'email' => $googleData['email'],
            'google_id' => $googleData['google_id'],
            'avatar' => $googleData['avatar'],
            'role' => 'job_seeker', // Default role
            'password' => null, // No password for Google users
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $inserted = $this->create($userData);
        if($inserted) {
            // Get the newly created user
            return $this->findByGoogleId($googleData['google_id']);
        }
        
        return false;
    }

    /**
     * Get user by ID
     */
    public function findById(int $userId)
    {
        $result = $this->db->table($this->table)
                           ->where('id', $userId)
                           ->get();

        if (empty($result)) return null;

        if (isset($result[0])) return $result[0];

        return $result;
    }
}