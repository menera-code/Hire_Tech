<?php
class Application_model extends Model
{
    protected $table = 'applications';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Count user applications
     */
    public function countUserApplications($userId)
    {
        try {
            $result = $this->db->table($this->table)
                ->where('user_id', $userId)
                ->get_all();
            return $result ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countUserApplications: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Count interviews for user
     */
    public function countInterviews($userId)
    {
        try {
            $result = $this->db->table($this->table)
                ->where('user_id', $userId)
                ->where('status', 'Interview Scheduled')
                ->get_all();
            return $result ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countInterviews: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Count rejected applications for user
     */
    public function countRejectedApplications($userId)
    {
        try {
            $result = $this->db->table($this->table)
                ->where('user_id', $userId)
                ->where('status', 'Rejected')
                ->get_all();
            return $result ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countRejectedApplications: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Count employer applications
     */
    public function countEmployerApplications($employerId)
    {
        try {
            $result = $this->db->table('applications a')
                ->select('a.id')
                ->join('jobs j', 'a.job_id = j.id')
                ->where('j.user_id', $employerId)
                ->get_all();
            return $result ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countEmployerApplications: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Count employer interviews
     */
    public function countEmployerInterviews($employerId)
    {
        try {
            $result = $this->db->table('applications a')
                ->select('a.id')
                ->join('jobs j', 'a.job_id = j.id')
                ->where('j.user_id', $employerId)
                ->where('a.status', 'Interview Scheduled')
                ->get_all();
            return $result ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countEmployerInterviews: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Count new applicants for employer
     */
    public function countNewApplicants($employerId)
    {
        try {
            $result = $this->db->table('applications a')
                ->select('a.id')
                ->join('jobs j', 'a.job_id = j.id')
                ->where('j.user_id', $employerId)
                ->where('a.status', 'Applied')
                ->get_all();
            return $result ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countNewApplicants: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get recent applications for user
     */
    public function getRecentApplications($userId, $limit = 5)
    {
        try {
            return $this->db->table('applications a')
                ->select('a.*, j.title as job_title, j.company as job_company, j.location, j.description as job_description, j.job_type, j.salary, j.requirements, j.benefits, j.category')
                ->join('jobs j', 'a.job_id = j.id')
                ->where('a.user_id', $userId)
                ->order_by('a.created_at', 'DESC')
                ->limit($limit)
                ->get_all() ?: [];
        } catch (Exception $e) {
            error_log("Error in getRecentApplications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all applications for user
     */
    public function getUserApplications($userId)
    {
        try {
            return $this->db->table('applications a')
                ->select('a.*, j.title as job_title, j.company as job_company, j.location, j.job_type, j.salary, j.requirements, j.benefits, j.category')
                ->join('jobs j', 'a.job_id = j.id')
                ->where('a.user_id', $userId)
                ->order_by('a.created_at', 'DESC')
                ->get_all() ?: [];
        } catch (Exception $e) {
            error_log("Error in getUserApplications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent applications for employer
     */
    public function getEmployerRecentApplications($employerId, $limit = 5)
    {
        try {
            return $this->db->table('applications a')
                ->select('a.*, j.title as job_title, j.company as job_company, u.name as applicant_name, j.requirements, j.benefits, j.category')
                ->join('jobs j', 'a.job_id = j.id')
                ->join('users u', 'a.user_id = u.id')
                ->where('j.user_id', $employerId)
                ->order_by('a.created_at', 'DESC')
                ->limit($limit)
                ->get_all() ?: [];
        } catch (Exception $e) {
            error_log("Error in getEmployerRecentApplications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all applications for employer
     */
    public function getEmployerApplications($employerId)
    {
        try {
            return $this->db->table('applications a')
                ->select('a.*, j.title as job_title, j.company as job_company, u.name as applicant_name, u.email as applicant_email, j.requirements, j.benefits, j.category')
                ->join('jobs j', 'a.job_id = j.id')
                ->join('users u', 'a.user_id = u.id')
                ->where('j.user_id', $employerId)
                ->order_by('a.created_at', 'DESC')
                ->get_all() ?: [];
        } catch (Exception $e) {
            error_log("Error in getEmployerApplications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update application status
     */
    public function updateApplicationStatus($applicationId, $status)
    {
        try {
            return $this->db->table($this->table)
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
     * Get application by ID
     */
    public function getApplicationById($applicationId)
    {
        try {
            return $this->db->table('applications a')
                ->select('a.*, j.title as job_title, j.company as job_company, j.description as job_description, j.requirements, j.benefits, j.category, u.name as applicant_name, u.email as applicant_email')
                ->join('jobs j', 'a.job_id = j.id')
                ->join('users u', 'a.user_id = u.id')
                ->where('a.id', $applicationId)
                ->get() ?: null;
        } catch (Exception $e) {
            error_log("Error in getApplicationById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user has already applied for a job
     */
    public function hasUserApplied($userId, $jobId)
    {
        try {
            $result = $this->db->table($this->table)
                ->where('user_id', $userId)
                ->where('job_id', $jobId)
                ->get();
            return !empty($result);
        } catch (Exception $e) {
            error_log("Error in hasUserApplied: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's applied job IDs
     */
    public function getUserAppliedJobIds($userId)
    {
        try {
            $applications = $this->db->table($this->table)
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

    /**
     * Schedule interview for an application
     */
    public function scheduleInterview($applicationId, $interviewData)
    {
        try {
            return $this->db->table($this->table)
                ->where('id', $applicationId)
                ->update([
                    'status' => 'Interview Scheduled',
                    'interview_date' => $interviewData['interview_date'],
                    'interview_type' => $interviewData['interview_type'],
                    'interview_notes' => $interviewData['interview_notes'],
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        } catch (Exception $e) {
            error_log("Error in scheduleInterview: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject application
     */
    public function rejectApplication($applicationId)
    {
        try {
            return $this->db->table($this->table)
                ->where('id', $applicationId)
                ->update([
                    'status' => 'Rejected',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        } catch (Exception $e) {
            error_log("Error in rejectApplication: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Hire applicant
     */
    public function hireApplicant($applicationId)
    {
        try {
            return $this->db->table($this->table)
                ->where('id', $applicationId)
                ->update([
                    'status' => 'Hired',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        } catch (Exception $e) {
            error_log("Error in hireApplicant: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete an application by ID and user ID
     */
    public function deleteApplication($application_id, $user_id)
    {
        try {
            return $this->db->table($this->table)
                ->where('id', $application_id)
                ->where('user_id', $user_id)
                ->delete();
        } catch (Exception $e) {
            error_log("Error in deleteApplication: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get application by ID and user ID
     */
    public function getApplicationByIdAndUser($application_id, $user_id)
    {
        try {
            return $this->db->table('applications a')
                ->select('a.*, j.title as job_title, j.company as job_company, j.location, j.description as job_description, j.job_type, j.salary, j.requirements, j.benefits, j.category')
                ->join('jobs j', 'a.job_id = j.id')
                ->where('a.id', $application_id)
                ->where('a.user_id', $user_id)
                ->get() ?: null;
        } catch (Exception $e) {
            error_log("Error in getApplicationByIdAndUser: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get upcoming interviews for job seeker with calendar integration
     */
    public function getUserInterviewsWithCalendar($userId)
    {
        try {
            return $this->db->table('applications a')
                ->select('a.*, j.title as job_title, j.company, j.location as job_location, u.name as employer_name, u.email as employer_email, j.description as job_description')
                ->join('jobs j', 'a.job_id = j.id')
                ->join('users u', 'j.user_id = u.id')
                ->where('a.user_id', $userId)
                ->where('a.status', 'Interview Scheduled')
                ->where('a.interview_date >=', date('Y-m-d H:i:s'))
                ->order_by('a.interview_date', 'ASC')
                ->get_all() ?: [];
        } catch (Exception $e) {
            error_log("Error in getUserInterviewsWithCalendar: " . $e->getMessage());
            return [];
        }
    }

    /**
 * Get interview details for job seeker - UPDATED VERSION
 */
public function getInterviewDetailsForJobSeeker($applicationId, $userId)
{
    try {
        return $this->db->table('applications a')
            ->select('a.*, j.title as job_title, j.company, j.location as job_location, u.name as employer_name, u.email as employer_email, j.description as job_description, j.requirements, j.benefits')
            ->join('jobs j', 'a.job_id = j.id')
            ->join('users u', 'j.user_id = u.id')
            ->where('a.id', $applicationId)
            ->where('a.user_id', $userId)
            ->get() ?: null;
    } catch (Exception $e) {
        error_log("Error in getInterviewDetailsForJobSeeker: " . $e->getMessage());
        return null;
    }
}
    /**
     * Update interview with calendar integration data
     */
    public function updateInterviewWithCalendar($applicationId, $interviewData)
    {
        try {
            $updateData = [
                'status' => 'Interview Scheduled',
                'interview_date' => $interviewData['interview_date'],
                'interview_type' => $interviewData['interview_type'],
                'interview_notes' => $interviewData['interview_notes'] ?? null,
                'interview_location' => $interviewData['interview_location'] ?? null,
                'interview_duration' => $interviewData['interview_duration'] ?? 60,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Add calendar data if available
            if (!empty($interviewData['google_calendar_event_id'])) {
                $updateData['calendar_event_id'] = $interviewData['google_calendar_event_id'];
            }
            if (!empty($interviewData['calendar_link'])) {
                $updateData['calendar_link'] = $interviewData['calendar_link'];
            }

            return $this->db->table($this->table)
                ->where('id', $applicationId)
                ->update($updateData);
        } catch (Exception $e) {
            error_log("Error in updateInterviewWithCalendar: " . $e->getMessage());
            return false;
        }
    }

    // Add to Application_model class

/**
 * Count all applications
 */
public function countAllApplications()
{
    try {
        $result = $this->db->table($this->table)->count();
        return $result;
    } catch (Exception $e) {
        error_log("Error in countAllApplications: " . $e->getMessage());
        return 0;
    }
}
}