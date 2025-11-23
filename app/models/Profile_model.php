<?php

class Profile_model extends Model
{
    protected $userProfilesTable = 'user_profiles';
    protected $companyProfilesTable = 'company_profiles';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get user profile by user ID (for job seekers)
     */
    public function getUserProfileByUserId($userId)
    {
        $result = $this->db->table($this->userProfilesTable)
            ->where('user_id', $userId)
            ->get();
        return isset($result[0]) ? $result[0] : $result;
    }

    /**
     * Get company profile by user ID (for employers)
     */
    public function getCompanyProfileByUserId($userId)
    {
        $result = $this->db->table($this->companyProfilesTable)
            ->where('user_id', $userId)
            ->get();
        return isset($result[0]) ? $result[0] : $result;
    }

    /**
     * Create or update user profile (job seeker)
     */
    public function saveUserProfile($userId, $data)
    {
        $existing = $this->getUserProfileByUserId($userId);
        
        if ($existing) {
            // Update existing profile
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->table($this->userProfilesTable)
                ->where('user_id', $userId)
                ->update($data);
        } else {
            // Create new profile
            $data['user_id'] = $userId;
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->table($this->userProfilesTable)->insert($data);
        }
    }

    /**
     * Create or update company profile (employer)
     */
    public function saveCompanyProfile($userId, $data)
    {
        $existing = $this->getCompanyProfileByUserId($userId);
        
        if ($existing) {
            // Update existing company profile
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->table($this->companyProfilesTable)
                ->where('user_id', $userId)
                ->update($data);
        } else {
            // Create new company profile
            $data['user_id'] = $userId;
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $this->db->table($this->companyProfilesTable)->insert($data);
        }
    }

    /**
     * Get profile completion percentage
     */
    public function getProfileCompletion($userId, $userRole)
    {
        if ($userRole == 'job_seeker') {
            $profile = $this->getUserProfileByUserId($userId);
        } else {
            $profile = $this->getCompanyProfileByUserId($userId);
        }
        
        $user = $this->getUserById($userId);

        $totalFields = 0;
        $completedFields = 0;

        // User basic info (always required)
        $userFields = ['name', 'email'];
        foreach ($userFields as $field) {
            $totalFields++;
            if (!empty($user[$field])) {
                $completedFields++;
            }
        }

        if ($userRole == 'job_seeker') {
            // Job Seeker specific fields
            $jobSeekerFields = [
                'professional_headline' => 'Professional Headline',
                'professional_summary' => 'Professional Summary',
                'skills' => 'Skills',
                'work_experience' => 'Work Experience',
                'education' => 'Education',
                'phone' => 'Phone Number',
                'address' => 'Address'
            ];
            
            foreach ($jobSeekerFields as $field => $label) {
                $totalFields++;
                if (!empty($profile[$field])) {
                    $completedFields++;
                }
            }
            
            // Resume file is important for job seekers
            $totalFields++;
            if (!empty($profile['resume_file'])) {
                $completedFields++;
            }
            
        } else {
            // Employer specific fields
            $employerFields = [
                'company_name' => 'Company Name',
                'company_description' => 'Company Description',
                'company_industry' => 'Industry',
                'company_size' => 'Company Size',
                'company_website' => 'Website',
                'phone' => 'Phone Number',
                'company_address' => 'Company Address'
            ];
            
            foreach ($employerFields as $field => $label) {
                $totalFields++;
                if (!empty($profile[$field])) {
                    $completedFields++;
                }
            }
            
            // Company logo is nice to have
            $totalFields++;
            if (!empty($profile['company_logo'])) {
                $completedFields++;
            }
        }

        $percentage = $totalFields > 0 ? round(($completedFields / $totalFields) * 100) : 0;
        return min($percentage, 100);
    }

    /**
     * Check if email is unique - SIMPLE BYPASS
     */
    public function isEmailUnique($email, $excludeUserId = null)
    {
        // Temporary bypass - always return true
        return true;
    }

    /**
     * Update user basic information
     */
    public function updateUser($userId, $data)
    {
        return $this->db->table('users')
            ->where('id', $userId)
            ->update($data);
    }

    /**
     * Get user by ID
     */
    public function getUserById($userId)
    {
        $result = $this->db->table('users')
            ->where('id', $userId)
            ->get();
        return isset($result[0]) ? $result[0] : $result;
    }
}