<?php
class User_model extends Model
{
    protected $table = 'users';

    public function __construct()
    {
        parent::__construct();
    }

    public function create(array $data)
    {
        if(isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->table($this->table)->insert($data);
    }

    public function find($id, $with_deleted = false)
    {
        $result = $this->db->table($this->table)
            ->where('id', $id)
            ->get();
        return isset($result[0]) ? $result[0] : $result;
    }

    public function findByEmail(string $email)
    {
        $result = $this->db->table($this->table)
            ->where('email', $email)
            ->get();
        return isset($result[0]) ? $result[0] : $result;
    }

    public function emailExists(string $email): bool
    {
        $result = $this->db->table($this->table)
            ->where('email', $email)
            ->get();
        return !empty($result);
    }

    public function findByGoogleId(string $googleId)
    {
        $result = $this->db->table($this->table)
            ->where('google_id', $googleId)
            ->get();
        return isset($result[0]) ? $result[0] : $result;
    }

    public function findByFacebookId(string $facebookId)
    {
        $result = $this->db->table($this->table)
            ->where('facebook_id', $facebookId)
            ->get();
        return isset($result[0]) ? $result[0] : $result;
    }

    public function updateUser(int $userId, array $data)
    {
        return $this->db->table($this->table)
            ->where('id', $userId)
            ->update($data);
    }

    public function createOrUpdateGoogleUser(array $googleData)
    {
        $existingUser = $this->findByGoogleId($googleData['google_id']);
        if($existingUser) {
            $this->updateUser($existingUser['id'], [
                'name' => $googleData['name'],
                'email' => $googleData['email'],
                'avatar' => $googleData['avatar']
            ]);
            return $existingUser;
        }
        
        $existingUserByEmail = $this->findByEmail($googleData['email']);
        if($existingUserByEmail) {
            $this->updateUser($existingUserByEmail['id'], [
                'google_id' => $googleData['google_id'],
                'avatar' => $googleData['avatar']
            ]);
            return $existingUserByEmail;
        }
        
        $userData = [
            'name' => $googleData['name'],
            'email' => $googleData['email'],
            'google_id' => $googleData['google_id'],
            'avatar' => $googleData['avatar'],
            'role' => 'job_seeker',
            'password' => null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $inserted = $this->create($userData);
        return $inserted ? $this->findByGoogleId($googleData['google_id']) : false;
    }

    public function createOrUpdateFacebookUser(array $facebookData)
    {
        $existingUser = $this->findByFacebookId($facebookData['facebook_id']);
        if($existingUser) {
            $this->updateUser($existingUser['id'], [
                'name' => $facebookData['name'],
                'email' => $facebookData['email'],
                'avatar' => $facebookData['avatar']
            ]);
            return $existingUser;
        }
        
        $existingUserByEmail = $this->findByEmail($facebookData['email']);
        if($existingUserByEmail) {
            $this->updateUser($existingUserByEmail['id'], [
                'facebook_id' => $facebookData['facebook_id'],
                'avatar' => $facebookData['avatar']
            ]);
            return $existingUserByEmail;
        }
        
        $userData = [
            'name' => $facebookData['name'],
            'email' => $facebookData['email'],
            'facebook_id' => $facebookData['facebook_id'],
            'avatar' => $facebookData['avatar'],
            'role' => 'job_seeker',
            'password' => null,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $inserted = $this->create($userData);
        return $inserted ? $this->findByFacebookId($facebookData['facebook_id']) : false;
    }

    public function findById(int $userId)
    {
        return $this->find($userId);
    }

    public function getUserById($id)
    {
        $result = $this->db->table($this->table)
            ->where('id', $id)
            ->get();
        return isset($result[0]) ? $result[0] : $result;
    }

    public function isJobSeeker($userId)
    {
        $user = $this->find($userId);
        return $user && $user['role'] === 'job_seeker';
    }

    public function isEmployer($userId)
    {
        $user = $this->find($userId);
        return $user && $user['role'] === 'employer';
    }

    public function countByRole($role)
    {
        $result = $this->db->table($this->table)
            ->where('role', $role)
            ->count();
        return $result;
    }

    public function getRecentUsers($limit = 5)
    {
        $result = $this->db->table($this->table)
            ->order_by('created_at', 'DESC')
            ->limit($limit)
            ->get();
        return is_array($result) && isset($result[0]) ? $result : ($result ? [$result] : []);
    }

    public function getUserProfile($userId)
    {
        $result = $this->db->table('user_profiles')
            ->where('user_id', $userId)
            ->get();
        return isset($result[0]) ? $result[0] : $result;
    }

    public function updateProfile($userId, $data)
    {
        // Check if profile exists
        $existing = $this->getUserProfile($userId);
        if ($existing) {
            return $this->db->table('user_profiles')
                ->where('user_id', $userId)
                ->update($data);
        } else {
            $data['user_id'] = $userId;
            return $this->db->table('user_profiles')->insert($data);
        }
    }

    /**
     * Count all users - FIXED VERSION
     */
    public function countAllUsers()
    {
        try {
            $result = $this->db->table($this->table)->get_all();
            return is_array($result) ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countAllUsers: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Delete user (admin function)
     */
    public function deleteUser($userId)
    {
        try {
            // Delete related records first
            $this->db->table('applications')->where('user_id', $userId)->delete();
            $this->db->table('saved_jobs')->where('user_id', $userId)->delete();
            
            // For employers, delete their jobs and related applications
            $user = $this->find($userId);
            if ($user && $user['role'] === 'employer') {
                $jobs = $this->db->table('jobs')->where('user_id', $userId)->get_all();
                foreach ($jobs as $job) {
                    $this->db->table('applications')->where('job_id', $job['id'])->delete();
                    $this->db->table('saved_jobs')->where('job_id', $job['id'])->delete();
                }
                $this->db->table('jobs')->where('user_id', $userId)->delete();
            }
            
            // Delete user profiles
            $this->db->table('user_profiles')->where('user_id', $userId)->delete();
            $this->db->table('company_profiles')->where('user_id', $userId)->delete();
            
            // Finally delete the user
            return $this->db->table($this->table)
                ->where('id', $userId)
                ->delete();
        } catch (Exception $e) {
            error_log("Error in deleteUser: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users with pagination - FIXED VERSION
     */
    public function getAllUsers($limit = 10, $offset = 0)
    {
        try {
            return $this->db->table($this->table)
                ->order_by('created_at', 'DESC')
                ->limit($limit, $offset)
                ->get_all() ?: [];
        } catch (Exception $e) {
            error_log("Error in getAllUsers: " . $e->getMessage());
            return [];
        }
    }

    public function deleteUserDataByFacebookId($facebookId)
{
    try {
        error_log("Attempting to delete data for Facebook ID: " . $facebookId);
        
        // Find user by Facebook ID
        $user = $this->findByFacebookId($facebookId);
        
        if (!$user) {
            error_log("No user found with Facebook ID: " . $facebookId);
            return true; // Return true as data doesn't exist
        }
        
        error_log("Found user to delete: " . $user['email'] . " (ID: " . $user['id'] . ")");
        
        // Use your existing deleteUser method
        $result = $this->deleteUser($user['id']);
        
        if ($result) {
            error_log("Successfully deleted user data for Facebook ID: " . $facebookId);
        } else {
            error_log("Failed to delete user data for Facebook ID: " . $facebookId);
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error deleting Facebook user data: " . $e->getMessage());
        return false;
    }
}
}