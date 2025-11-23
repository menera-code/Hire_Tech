<?php
class Admin_model extends Model
{
    protected $table = 'users';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all users with pagination
     */
    public function getAllUsers($limit = 10, $offset = 0)
    {
        try {
            $result = $this->db->table($this->table)
                ->order_by('created_at', 'DESC')
                ->get_all();

            if (is_array($result)) {
                return array_slice($result, $offset, $limit);
            }
            return [];
        } catch (Exception $e) {
            error_log("Error in getAllUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count all users
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
     * Get recent users
     */
    public function getRecentUsers($limit = 5)
    {
        try {
            $result = $this->db->table($this->table)
                ->order_by('created_at', 'DESC')
                ->get_all();

            if (is_array($result)) {
                return array_slice($result, 0, $limit);
            }
            return [];
        } catch (Exception $e) {
            error_log("Error in getRecentUsers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count users by role
     */
    public function countByRole($role)
    {
        try {
            $result = $this->db->table($this->table)
                ->where('role', $role)
                ->get_all();
            return is_array($result) ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countByRole: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all jobs with employer info
     */
    public function getAllJobs($limit = 10, $offset = 0)
    {
        try {
            $result = $this->db->table('jobs j')
                ->select('j.*, u.name as employer_name, u.email as employer_email')
                ->join('users u', 'j.user_id = u.id')
                ->order_by('j.created_at', 'DESC')
                ->get_all();

            if (is_array($result)) {
                return array_slice($result, $offset, $limit);
            }
            return [];
        } catch (Exception $e) {
            error_log("Error in getAllJobs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count all jobs
     */
    public function countAllJobs()
    {
        try {
            $result = $this->db->table('jobs')->get_all();
            return is_array($result) ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countAllJobs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent jobs
     */
    public function getRecentJobs($limit = 5)
    {
        try {
            $result = $this->db->table('jobs')
                ->order_by('created_at', 'DESC')
                ->get_all();

            if (is_array($result)) {
                return array_slice($result, 0, $limit);
            }
            return [];
        } catch (Exception $e) {
            error_log("Error in getRecentJobs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count all applications
     */
    public function countAllApplications()
    {
        try {
            $result = $this->db->table('applications')->get_all();
            return is_array($result) ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countAllApplications: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get admin dashboard statistics
     */
    public function getAdminStats()
    {
        $stats = [];

        try {
            // Total users
            $stats['total_users'] = $this->countAllUsers();
            
            // Total employers
            $stats['total_employers'] = $this->countByRole('employer');
            
            // Total job seekers
            $stats['total_job_seekers'] = $this->countByRole('job_seeker');
            
            // Total jobs
            $stats['total_jobs'] = $this->countAllJobs();
            
            // Total applications
            $stats['total_applications'] = $this->countAllApplications();
            
            // Recent users (last 7 days) - simplified for now
            $stats['recent_users'] = $this->countAllUsers();
            
            // Recent jobs (last 7 days) - simplified for now
            $stats['recent_jobs'] = $this->countAllJobs();

        } catch (Exception $e) {
            error_log("Error getting admin stats: " . $e->getMessage());
            // Set default values
            $stats = array_fill_keys([
                'total_users', 'total_employers', 'total_job_seekers', 
                'total_jobs', 'total_applications', 'recent_users', 'recent_jobs'
            ], 0);
        }

        return $stats;
    }

 /**
     * Delete user and all related data - FIXED VERSION (No transactions)
     */
    public function deleteUser($userId)
    {
        try {
            // Prevent deletion if user doesn't exist
            $user = $this->db->table($this->table)->where('id', $userId)->get();
            if (!$user) {
                return false;
            }

            // Delete related records first
            $this->db->table('applications')->where('user_id', $userId)->delete();
            $this->db->table('saved_jobs')->where('user_id', $userId)->delete();
            
            // For employers, delete their jobs and related applications
            if ($user['role'] === 'employer') {
                $jobs = $this->db->table('jobs')->where('user_id', $userId)->get_all();
                if (is_array($jobs)) {
                    foreach ($jobs as $job) {
                        $this->db->table('applications')->where('job_id', $job['id'])->delete();
                        $this->db->table('saved_jobs')->where('job_id', $job['id'])->delete();
                    }
                }
                $this->db->table('jobs')->where('user_id', $userId)->delete();
            }
            
            // Delete user profiles
            $this->db->table('user_profiles')->where('user_id', $userId)->delete();
            $this->db->table('company_profiles')->where('user_id', $userId)->delete();
            
            // Finally delete the user
            $result = $this->db->table($this->table)
                ->where('id', $userId)
                ->delete();

            return $result;

        } catch (Exception $e) {
            error_log("Error in deleteUser: " . $e->getMessage());
            return false;
        }
    }
    /**
 * Update user status - UPDATED VERSION
 */
public function updateUserStatus($userId, $status)
{
    try {
        // Validate status
        if (!in_array($status, ['active', 'suspended'])) {
            error_log("Invalid status provided: " . $status);
            return false;
        }

        // Check if user exists
        $user = $this->db->table($this->table)->where('id', $userId)->get();
        if (!$user) {
            error_log("User not found for status update: " . $userId);
            return false;
        }

        $result = $this->db->table($this->table)
            ->where('id', $userId)
            ->update(['status' => $status]);

        if ($result) {
            error_log("User status updated - ID: $userId, Status: $status");
        } else {
            error_log("Failed to update user status - ID: $userId, Status: $status");
        }

        return $result;

    } catch (Exception $e) {
        error_log("Error in updateUserStatus: " . $e->getMessage());
        return false;
    }
}

    /**
 * Delete job and all related data
 */
public function deleteJob($jobId)
{
    try {
        // Delete related records
        $this->db->table('applications')->where('job_id', $jobId)->delete();
        $this->db->table('saved_jobs')->where('job_id', $jobId)->delete();

        // Delete the job
        $result = $this->db->table('jobs')
            ->where('id', $jobId)
            ->delete();

        return $result;

    } catch (Exception $e) {
        error_log("Error in deleteJob: " . $e->getMessage());
        return false;
    }
}

    /**
     * Update job featured status
     */
    public function updateJobFeatured($jobId, $featured)
    {
        try {
            return $this->db->table('jobs')
                ->where('id', $jobId)
                ->update(['featured' => $featured]);
        } catch (Exception $e) {
            error_log("Error in updateJobFeatured: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all applications with details
     */
    public function getAllApplications($limit = 10, $offset = 0)
    {
        try {
            $result = $this->db->table('applications a')
                ->select('a.*, j.title as job_title, j.company as job_company, u.name as applicant_name, u.email as applicant_email, emp.name as employer_name')
                ->join('jobs j', 'a.job_id = j.id')
                ->join('users u', 'a.user_id = u.id')
                ->join('users emp', 'j.user_id = emp.id')
                ->order_by('a.created_at', 'DESC')
                ->get_all();

            if (is_array($result)) {
                return array_slice($result, $offset, $limit);
            }
            return [];
        } catch (Exception $e) {
            error_log("Error in getAllApplications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update application status
     */
    public function updateApplicationStatus($applicationId, $status)
    {
        try {
            return $this->db->table('applications')
                ->where('id', $applicationId)
                ->update([
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        } catch (Exception $e) {
            error_log("Error in updateApplicationStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Debug method to check database connectivity
     */
    public function debugDatabase()
    {
        $debugInfo = [];

        try {
            // Check users
            $users = $this->db->table('users')->get_all();
            $debugInfo['users'] = is_array($users) ? count($users) : 0;
            $debugInfo['users_data'] = is_array($users) ? array_slice($users, 0, 3) : [];

            // Check jobs
            $jobs = $this->db->table('jobs')->get_all();
            $debugInfo['jobs'] = is_array($jobs) ? count($jobs) : 0;
            $debugInfo['jobs_data'] = is_array($jobs) ? array_slice($jobs, 0, 3) : [];

            // Check applications
            $applications = $this->db->table('applications')->get_all();
            $debugInfo['applications'] = is_array($applications) ? count($applications) : 0;

        } catch (Exception $e) {
            $debugInfo['error'] = $e->getMessage();
        }

        return $debugInfo;
    }

/**
 * Search applications with filters
 */
public function searchApplications($searchTerm = '', $status = 'all', $limit = 10, $offset = 0)
{
    try {
        $query = $this->db->table('applications a')
            ->select('a.*, j.title as job_title, j.company as job_company, u.name as applicant_name, u.email as applicant_email, emp.name as employer_name')
            ->join('jobs j', 'a.job_id = j.id')
            ->join('users u', 'a.user_id = u.id')
            ->join('users emp', 'j.user_id = emp.id')
            ->order_by('a.created_at', 'DESC');

        // Add search conditions
        if (!empty($searchTerm)) {
            $query->where_like('u.name', $searchTerm)
                  ->or_where_like('u.email', $searchTerm)
                  ->or_where_like('j.title', $searchTerm)
                  ->or_where_like('j.company', $searchTerm);
        }

        // Add status filter
        if ($status !== 'all') {
            $query->where('a.status', $status);
        }

        $result = $query->get_all();

        if (is_array($result)) {
            return array_slice($result, $offset, $limit);
        }
        return [];
    } catch (Exception $e) {
        error_log("Error in searchApplications: " . $e->getMessage());
        return [];
    }
}

/**
 * Count search applications
 */
public function countSearchApplications($searchTerm = '', $status = 'all')
{
    try {
        $query = $this->db->table('applications a')
            ->join('jobs j', 'a.job_id = j.id')
            ->join('users u', 'a.user_id = u.id')
            ->join('users emp', 'j.user_id = emp.id');

        // Add search conditions
        if (!empty($searchTerm)) {
            $query->where_like('u.name', $searchTerm)
                  ->or_where_like('u.email', $searchTerm)
                  ->or_where_like('j.title', $searchTerm)
                  ->or_where_like('j.company', $searchTerm);
        }

        // Add status filter
        if ($status !== 'all') {
            $query->where('a.status', $status);
        }

        $result = $query->get_all();
        return is_array($result) ? count($result) : 0;
    } catch (Exception $e) {
        error_log("Error in countSearchApplications: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get application statistics
 */
public function getApplicationStats()
{
    try {
        $stats = [];
        
        // Total applications
        $stats['total'] = $this->countAllApplications();
        
        // Applications by status
        $statuses = ['Applied', 'Approved', 'Rejected', 'Interview Scheduled', 'Hired'];
        foreach ($statuses as $status) {
            $result = $this->db->table('applications')
                ->where('status', $status)
                ->get_all();
            $stats[strtolower(str_replace(' ', '_', $status))] = is_array($result) ? count($result) : 0;
        }
        
        return $stats;
    } catch (Exception $e) {
        error_log("Error in getApplicationStats: " . $e->getMessage());
        return [];
    }
}

/**
 * Delete application
 */
public function deleteApplication($applicationId)
{
    try {
        $result = $this->db->table('applications')
            ->where('id', $applicationId)
            ->delete();

        return $result;
    } catch (Exception $e) {
        error_log("Error in deleteApplication: " . $e->getMessage());
        return false;
    }
}

}