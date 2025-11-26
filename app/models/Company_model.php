<?php

class Company_model extends Model
{
    protected $companyProfilesTable = 'company_profiles';
    protected $usersTable = 'users';
    protected $jobsTable = 'jobs';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all companies - FIXED VERSION
     */
    public function getAllCompanies($filters = [])
    {
        try {
            error_log("Starting getAllCompanies...");
            
            // Get all company profiles from database
            $companies = $this->db->table($this->companyProfilesTable)->get();
            
            error_log("Raw companies from DB: " . print_r($companies, true));
            error_log("Companies type: " . gettype($companies));

            // FIX: Handle single company result (associative array)
            if (is_array($companies) && isset($companies['id'])) {
                // Single company returned as associative array, convert to array of arrays
                $companies = [$companies];
                error_log("Converted single company to array of arrays");
            }

            // If we didn't get an array or it's empty, return empty
            if (!is_array($companies) || empty($companies)) {
                error_log("No companies found in database");
                return [
                    'companies' => [],
                    'total_companies' => 0,
                    'total_pages' => 0,
                    'current_page' => 1
                ];
            }
            
            error_log("Companies count after fix: " . count($companies));
            
            // Process each company safely
            $processedCompanies = [];
            
            foreach ($companies as $company) {
                // Skip if not an array or missing required fields
                if (!is_array($company) || !isset($company['id']) || !isset($company['user_id'])) {
                    error_log("Skipping invalid company: " . print_r($company, true));
                    continue;
                }
                
                error_log("Processing company: " . $company['company_name']);
                
                // Get user data for this company
                $userData = $this->getUserData($company['user_id']);
                
                // Create processed company data
                $processedCompany = [
                    'id' => $company['id'],
                    'user_id' => $company['user_id'],
                    'company_name' => $company['company_name'] ?? 'Unknown Company',
                    'company_description' => $company['company_description'] ?? 'No description available',
                    'company_website' => $company['company_website'] ?? '',
                    'company_size' => $company['company_size'] ?? '',
                    'company_industry' => $company['company_industry'] ?? '',
                    'company_address' => $company['company_address'] ?? '',
                    'company_logo' => $company['company_logo'] ?? '',
                    'phone' => $company['phone'] ?? '',
                    'updated_at' => $company['updated_at'] ?? date('Y-m-d H:i:s'),
                    'contact_name' => $userData['name'] ?? 'Contact',
                    'contact_email' => $userData['email'] ?? 'email@company.com',
                    'member_since' => $userData['created_at'] ?? ($company['updated_at'] ?? date('Y-m-d H:i:s')),
                    'active_jobs' => $this->getCompanyActiveJobsCount($company['user_id']),
                    'total_jobs' => $this->getCompanyActiveJobsCount($company['user_id'])
                ];
                
                $processedCompanies[] = $processedCompany;
            }
            
            error_log("Processed companies count: " . count($processedCompanies));
            
            // Apply filters manually in PHP
            $filteredCompanies = $this->applyFiltersManually($processedCompanies, $filters);
            
            $total_companies = count($filteredCompanies);
            $page = $filters['page'] ?? 1;
            $per_page = $filters['per_page'] ?? 12;
            $offset = ($page - 1) * $per_page;
            
            $paginatedCompanies = array_slice($filteredCompanies, $offset, $per_page);

            return [
                'companies' => $paginatedCompanies,
                'total_companies' => $total_companies,
                'total_pages' => ceil($total_companies / $per_page),
                'current_page' => $page
            ];

        } catch (Exception $e) {
            error_log("Error in getAllCompanies: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            return [
                'companies' => [],
                'total_companies' => 0,
                'total_pages' => 0,
                'current_page' => 1
            ];
        }
    }

    /**
     * Get user data for a company - FIXED VERSION
     */
    private function getUserData($userId)
    {
        try {
            error_log("Getting user data for user_id: " . $userId);
            
            $user = $this->db->table($this->usersTable)
                ->where('id', $userId)
                ->get();

            error_log("User data result: " . print_r($user, true));

            // FIX: Handle single user result (associative array)
            if (is_array($user) && isset($user['id'])) {
                // Single user returned as associative array
                return [
                    'name' => $user['name'] ?? 'N/A',
                    'email' => $user['email'] ?? 'N/A',
                    'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
            else if (!empty($user) && is_array($user) && isset($user[0])) {
                // Multiple users returned as array of arrays
                return [
                    'name' => $user[0]['name'] ?? 'N/A',
                    'email' => $user[0]['email'] ?? 'N/A',
                    'created_at' => $user[0]['created_at'] ?? date('Y-m-d H:i:s')
                ];
            }
            
            return [
                'name' => 'N/A',
                'email' => 'N/A',
                'created_at' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            error_log("Error getting user data for user_id {$userId}: " . $e->getMessage());
            return [
                'name' => 'N/A',
                'email' => 'N/A',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    /**
     * Get all unique industries - FIXED VERSION
     */
    public function getIndustries()
    {
        try {
            // Get all companies first
            $companies = $this->db->table($this->companyProfilesTable)->get();
            
            // FIX: Handle single company result
            if (is_array($companies) && isset($companies['id'])) {
                $companies = [$companies];
            }
            
            if (!is_array($companies)) {
                return ['Technology', 'Business', 'Healthcare', 'Education', 'Other'];
            }
            
            // Extract unique industries manually
            $industries = [];
            foreach ($companies as $company) {
                if (!empty($company['company_industry']) && !in_array($company['company_industry'], $industries)) {
                    $industries[] = $company['company_industry'];
                }
            }
            
            sort($industries);
            
            return !empty($industries) ? $industries : ['Technology', 'Business', 'Healthcare', 'Education', 'Other'];
            
        } catch (Exception $e) {
            error_log("Error in getIndustries: " . $e->getMessage());
            return ['Technology', 'Business', 'Healthcare', 'Education', 'Other'];
        }
    }

    /**
     * Get company by ID - FIXED VERSION
     */
    public function getCompanyById($companyId)
    {
        try {
            $company = $this->db->table($this->companyProfilesTable)
                ->where('id', $companyId)
                ->get();

            // FIX: Handle single company result
            if (is_array($company) && isset($company['id'])) {
                $companyData = $company;
            } else if (!empty($company) && is_array($company) && isset($company[0])) {
                $companyData = $company[0];
            } else {
                return null;
            }
            
            // Get user data
            $userData = $this->getUserData($companyData['user_id']);
            
            // Build complete company data
            $completeCompany = [
                'id' => $companyData['id'],
                'user_id' => $companyData['user_id'],
                'company_name' => $companyData['company_name'],
                'company_description' => $companyData['company_description'] ?? '',
                'company_website' => $companyData['company_website'] ?? '',
                'google_form_url' => $companyData['google_form_url'] ?? '', // CHANGED FROM 'comapany_url'
                'company_size' => $companyData['company_size'] ?? '',
                'company_industry' => $companyData['company_industry'] ?? '',
                'company_address' => $companyData['company_address'] ?? '',
                'company_logo' => $companyData['company_logo'] ?? '',
                'phone' => $companyData['phone'] ?? '',
                'updated_at' => $companyData['updated_at'],
                'contact_name' => $userData['name'],
                'contact_email' => $userData['email'],
                'member_since' => $userData['created_at'],
                'active_jobs' => $this->getCompanyActiveJobsCount($companyData['user_id']),
                'total_jobs' => $this->getCompanyActiveJobsCount($companyData['user_id']),
                'recent_jobs' => $this->getCompanyRecentJobs($companyData['user_id'])
            ];

            return $completeCompany;

        } catch (Exception $e) {
            error_log("Error in getCompanyById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get company profile by user ID - ADD THIS METHOD
     */
    public function getCompanyByUserId($userId)
    {
        try {
            $company = $this->db->table($this->companyProfilesTable)
                ->where('user_id', $userId)
                ->get();

            // Handle single company result
            if (is_array($company) && isset($company['id'])) {
                $companyData = $company;
            } else if (!empty($company) && is_array($company) && isset($company[0])) {
                $companyData = $company[0];
            } else {
                return null;
            }
            
            // Get user data
            $userData = $this->getUserData($companyData['user_id']);
            
            // Build complete company data
            $completeCompany = [
                'id' => $companyData['id'],
                'user_id' => $companyData['user_id'],
                'company_name' => $companyData['company_name'],
                'company_description' => $companyData['company_description'] ?? '',
                'company_website' => $companyData['company_website'] ?? '',
                'company_size' => $companyData['company_size'] ?? '',
                'company_industry' => $companyData['company_industry'] ?? '',
                'company_address' => $companyData['company_address'] ?? '',
                'company_logo' => $companyData['company_logo'] ?? '',
                'phone' => $companyData['phone'] ?? '',
                'updated_at' => $companyData['updated_at'],
                'contact_name' => $userData['name'],
                'contact_email' => $userData['email'],
                'member_since' => $userData['created_at'],
                'active_jobs' => $this->getCompanyActiveJobsCount($companyData['user_id']),
                'total_jobs' => $this->getCompanyActiveJobsCount($companyData['user_id']),
                'recent_jobs' => $this->getCompanyRecentJobs($companyData['user_id'])
            ];

            return $completeCompany;

        } catch (Exception $e) {
            error_log("Error in getCompanyByUserId: " . $e->getMessage());
            return null;
        }
    }

 /**
 * Get recent jobs for a company by company name - FIXED VERSION
 */
public function getCompanyRecentJobs($companyName, $limit = 5)
{
    try {
        error_log("Getting recent jobs for company: " . $companyName);
        
        // Get jobs by company name
        $jobs = $this->db->table($this->jobsTable)
            ->where('company', $companyName)
            ->get();

        // FIX: Handle different return types properly
        if ($jobs === false) {
            // Query returned false (no results or error)
            error_log("No jobs found for company: " . $companyName);
            return [];
        }
        
        if (is_array($jobs) && isset($jobs['id'])) {
            // Single job returned as associative array
            $jobs = [$jobs];
            error_log("Single job found, converted to array");
        } else if (!is_array($jobs)) {
            // Not an array at all
            error_log("Jobs result is not an array: " . gettype($jobs));
            return [];
        }
        
        // Sort by created_at descending manually
        if (!empty($jobs)) {
            usort($jobs, function($a, $b) {
                $timeA = strtotime($a['created_at'] ?? '');
                $timeB = strtotime($b['created_at'] ?? '');
                return $timeB - $timeA; // Descending order
            });
            
            // Limit results
            $jobs = array_slice($jobs, 0, $limit);
        }
        
        error_log("Returning " . count($jobs) . " recent jobs for company: " . $companyName);
        return $jobs;
        
    } catch (Exception $e) {
        error_log("Error in getCompanyRecentJobs: " . $e->getMessage());
        return [];
    }
}

    private function applyFiltersManually($companies, $filters)
    {
        if (empty($filters)) {
            return $companies;
        }

        $filteredCompanies = [];

        foreach ($companies as $company) {
            $matches = true;

            // Search filter
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $companyName = strtolower($company['company_name'] ?? '');
                $companyDesc = strtolower($company['company_description'] ?? '');
                $companyIndustry = strtolower($company['company_industry'] ?? '');
                
                if (strpos($companyName, $search) === false && 
                    strpos($companyDesc, $search) === false && 
                    strpos($companyIndustry, $search) === false) {
                    $matches = false;
                }
            }

            // Industry filter
            if (!empty($filters['industry']) && $matches) {
                $industry = strtolower($filters['industry']);
                $companyIndustry = strtolower($company['company_industry'] ?? '');
                if (strpos($companyIndustry, $industry) === false) {
                    $matches = false;
                }
            }

            // Company size filter
            if (!empty($filters['company_size']) && $matches) {
                if ($company['company_size'] !== $filters['company_size']) {
                    $matches = false;
                }
            }

            // Location filter
            if (!empty($filters['location']) && $matches) {
                $location = strtolower($filters['location']);
                $companyAddress = strtolower($company['company_address'] ?? '');
                if (strpos($companyAddress, $location) === false) {
                    $matches = false;
                }
            }

            if ($matches) {
                $filteredCompanies[] = $company;
            }
        }

        return $filteredCompanies;
    }

   public function getCompanyActiveJobsCount($userId)
{
    try {
        $count = $this->db->table($this->jobsTable)
            ->where('user_id', $userId)
            ->count();
            
        // FIX: Handle cases where count might return false
        if ($count === false) {
            return 0;
        }
        
        return is_numeric($count) ? $count : 0;
    } catch (Exception $e) {
        error_log("Error in getCompanyActiveJobsCount: " . $e->getMessage());
        return 0;
    }
}

    public function getCompanySizes()
    {
        return ['1-10', '11-50', '51-200', '201-500', '501+'];
    }
}