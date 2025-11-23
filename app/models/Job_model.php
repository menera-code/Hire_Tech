<?php
class Job_model extends Model
{
    protected $table = 'jobs';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all jobs for job seekers
     */
    public function getAllJobs()
    {
        try {
            return $this->db->table($this->table)
                ->order_by('created_at', 'DESC')
                ->get_all() ?: [];
        } catch (Exception $e) {
            error_log("Error in getAllJobs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get jobs by employer
     */
    public function getEmployerJobs($employerId)
    {
        try {
            return $this->db->table($this->table)
                ->where('user_id', $employerId)
                ->order_by('created_at', 'DESC')
                ->get_all() ?: [];
        } catch (Exception $e) {
            error_log("Error in getEmployerJobs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count all jobs in the system
     */
    public function countAllJobs()
    {
        try {
            $result = $this->db->table($this->table)->get_all();
            return $result ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countAllJobs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Count saved jobs for user
     */
    public function countSavedJobs($userId)
    {
        try {
            $result = $this->db->table('saved_jobs')
                ->where('user_id', $userId)
                ->get_all();
            return $result ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countSavedJobs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent jobs for job seekers
     */
    public function getRecentJobs($limit = 5)
    {
        try {
            return $this->db->table($this->table)
                ->order_by('created_at', 'DESC')
                ->limit($limit)
                ->get_all() ?: [];
        } catch (Exception $e) {
            error_log("Error in getRecentJobs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count employer jobs
     */
    public function countEmployerJobs($employerId)
    {
        try {
            $result = $this->db->table($this->table)
                ->where('user_id', $employerId)
                ->get_all();
            return $result ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countEmployerJobs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent jobs for employer
     */
    public function getEmployerRecentJobs($employerId, $limit = 5)
    {
        try {
            return $this->db->table($this->table)
                ->where('user_id', $employerId)
                ->order_by('created_at', 'DESC')
                ->limit($limit)
                ->get_all() ?: [];
        } catch (Exception $e) {
            error_log("Error in getEmployerRecentJobs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count active employer jobs
     */
    public function countActiveEmployerJobs($employerId)
    {
        try {
            $result = $this->db->table($this->table)
                ->where('user_id', $employerId)
                ->get_all();
            return $result ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countActiveEmployerJobs: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Create job application
     */
    public function createApplication($data)
    {
        try {
            // Check if application already exists
            $existing = $this->db->table('applications')
                ->where('user_id', $data['user_id'])
                ->where('job_id', $data['job_id'])
                ->get();

            if ($existing) {
                return false;
            }

            // Create new application
            return $this->db->table('applications')->insert($data);
        } catch (Exception $e) {
            error_log("Error in createApplication: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Save or unsave job for user (toggle functionality)
     */
    public function saveJob($userId, $jobId)
    {
        try {
            // Check if job is already saved
            $existing = $this->db->table('saved_jobs')
                ->where('user_id', $userId)
                ->where('job_id', $jobId)
                ->get();

            if ($existing) {
                // Job is already saved, so unsave it
                $this->db->table('saved_jobs')
                    ->where('user_id', $userId)
                    ->where('job_id', $jobId)
                    ->delete();
                return ['action' => 'unsaved', 'success' => true];
            } else {
                // Save the job
                $result = $this->db->table('saved_jobs')->insert([
                    'user_id' => $userId,
                    'job_id' => $jobId,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                return ['action' => 'saved', 'success' => $result];
            }
        } catch (Exception $e) {
            error_log("Error in saveJob: " . $e->getMessage());
            return ['action' => 'error', 'success' => false];
        }
    }

    /**
     * Get job by ID with all fields
     */
    public function getJobById($jobId)
    {
        try {
            return $this->db->table($this->table)
                ->where('id', $jobId)
                ->get() ?: null;
        } catch (Exception $e) {
            error_log("Error in getJobById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create new job with all fields
     */
    public function createJob($data)
    {
        try {
            if (!isset($data['created_at'])) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            if (!isset($data['updated_at'])) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
            return $this->db->table($this->table)->insert($data);
        } catch (Exception $e) {
            error_log("Error in createJob: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update job with all fields
     */
    public function updateJob($jobId, $data)
    {
        try {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->table($this->table)
                ->where('id', $jobId)
                ->update($data);
        } catch (Exception $e) {
            error_log("Error in updateJob: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete job
     */
    public function deleteJob($jobId)
    {
        try {
            // Delete related records first
            $this->db->table('applications')->where('job_id', $jobId)->delete();
            $this->db->table('saved_jobs')->where('job_id', $jobId)->delete();

            // Delete the job
            return $this->db->table($this->table)
                ->where('id', $jobId)
                ->delete();
        } catch (Exception $e) {
            error_log("Error in deleteJob: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's saved jobs
     */
    public function getUserSavedJobs($userId)
    {
        try {
            return $this->db->table('saved_jobs sj')
                ->select('j.*, sj.created_at as saved_at')
                ->join('jobs j', 'sj.job_id = j.id')
                ->where('sj.user_id', $userId)
                ->order_by('sj.created_at', 'DESC')
                ->get_all() ?: [];
        } catch (Exception $e) {
            error_log("Error in getUserSavedJobs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if job is saved by user
     */
    public function isJobSaved($userId, $jobId)
    {
        try {
            $result = $this->db->table('saved_jobs')
                ->where('user_id', $userId)
                ->where('job_id', $jobId)
                ->get();
            return !empty($result);
        } catch (Exception $e) {
            error_log("Error in isJobSaved: " . $e->getMessage());
            return false;
        }
    }

    /**
 * Search jobs with filters - FIXED VERSION
 */
/**
 * Simple raw SQL search - GUARANTEED TO WORK
 */
/**
 * Search jobs with filters - FIXED VERSION (using correct DB methods)
 */
public function searchJobs($filters = [])
{
    try {
        // Start with base query
        $query = $this->db->table($this->table);
        
        $conditions = [];
        $bindings = [];

        // Location search
        if (!empty($filters['location'])) {
            $conditions[] = "location LIKE ?";
            $bindings[] = '%' . $filters['location'] . '%';
        }

        // Job type filter
        if (!empty($filters['job_type'])) {
            $conditions[] = "job_type = ?";
            $bindings[] = $filters['job_type'];
        }

        // Category filter
        if (!empty($filters['category'])) {
            $conditions[] = "category LIKE ?";
            $bindings[] = '%' . $filters['category'] . '%';
        }

        // Search in title only (to avoid complex conditions)
        if (!empty($filters['search'])) {
            $conditions[] = "title LIKE ?";
            $bindings[] = '%' . $filters['search'] . '%';
        }

        // Apply conditions if any exist
        if (!empty($conditions)) {
            $whereClause = implode(' AND ', $conditions);
            $query->where($whereClause, $bindings);
        }

        return $query->order_by('created_at', 'DESC')->get_all() ?: [];

    } catch (Exception $e) {
        error_log("Error in searchJobs: " . $e->getMessage());
        return [];
    }
}

/**
 * Apply simple salary filter
 */
private function applySimpleSalaryFilter($query, $salaryRange)
{
    switch ($salaryRange) {
        case '0-20000':
            $query->where("salary LIKE '%₱1%' OR salary LIKE '%₱2%' OR salary LIKE '%15000%' OR salary LIKE '%18000%'");
            break;
        case '20000-40000':
            $query->where("salary LIKE '%₱2%' OR salary LIKE '%₱3%' OR salary LIKE '%₱4%' OR salary LIKE '%25000%' OR salary LIKE '%30000%' OR salary LIKE '%35000%'");
            break;
        case '40000-60000':
            $query->where("salary LIKE '%₱4%' OR salary LIKE '%₱5%' OR salary LIKE '%₱6%' OR salary LIKE '%45000%' OR salary LIKE '%50000%' OR salary LIKE '%55000%'");
            break;
        case '60000-0':
            $query->where("salary LIKE '%₱6%' OR salary LIKE '%₱7%' OR salary LIKE '%₱8%' OR salary LIKE '%₱9%' OR salary LIKE '%65000%' OR salary LIKE '%70000%' OR salary LIKE '%80000%' OR salary LIKE '%90000%'");
            break;
    }
}
    /**
     * Get user's applied job IDs
     */
    public function getUserAppliedJobIds($userId)
    {
        try {
            $applications = $this->db->table('applications')
                ->where('user_id', $userId)
                ->get_all() ?: [];
            
            $appliedJobIds = [];
            foreach ($applications as $application) {
                $appliedJobIds[] = $application['job_id'];
            }
            return $appliedJobIds;
        } catch (Exception $e) {
            error_log("Error in getUserAppliedJobIds: " . $e->getMessage());
            return [];
        }
    }

    // Add to Job_model class

/**
 * Count all applications
 */
public function countAllApplications()
{
    try {
        $result = $this->db->table('applications')->count();
        return $result;
    } catch (Exception $e) {
        error_log("Error in countAllApplications: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get all jobs with employer info
 */
public function getAllJobsWithEmployer($limit = 10, $offset = 0)
{
    try {
        return $this->db->table('jobs j')
            ->select('j.*, u.name as employer_name, u.email as employer_email')
            ->join('users u', 'j.user_id = u.id')
            ->order_by('j.created_at', 'DESC')
            ->limit($limit, $offset)
            ->get_all() ?: [];
    } catch (Exception $e) {
        error_log("Error in getAllJobsWithEmployer: " . $e->getMessage());
        return [];
    }
}

/**
 * Get jobs by company name - NEW METHOD
 */
public function getJobsByCompany($companyName)
{
    try {
        error_log("Searching for jobs by company: " . $companyName);
        
        $jobs = $this->db->table($this->table)
            ->where('company', $companyName)
            ->order_by('created_at', 'DESC')
            ->get_all();
            
        error_log("Found " . count($jobs) . " jobs for company: " . $companyName);
        return $jobs ?: [];
    } catch (Exception $e) {
        error_log("Error in getJobsByCompany: " . $e->getMessage());
        return [];
    }
}
}