<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class Savedjob_model extends Model
{
    protected $table = 'saved_jobs';

    public function create($data)
    {
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

    public function getUserSavedJobs($userId)
    {
        $result = $this->db->table('saved_jobs sj')
            ->select('sj.*, j.title, j.company, j.location, j.description')
            ->join('jobs j', 'sj.job_id = j.id')
            ->where('sj.user_id', $userId)
            ->order_by('sj.created_at', 'DESC')
            ->get();
        return is_array($result) && isset($result[0]) ? $result : ($result ? [$result] : []);
    }

    public function isSaved($userId, $jobId)
    {
        $result = $this->db->table($this->table)
            ->where('user_id', $userId)
            ->where('job_id', $jobId)
            ->get();
        return !empty($result);
    }

    // Custom delete method for saved jobs (not overriding parent delete)
    public function removeSavedJob($userId, $jobId)
    {
        return $this->db->table($this->table)
            ->where('user_id', $userId)
            ->where('job_id', $jobId)
            ->delete();
    }

  public function countUserSavedJobs($userId)
{
    // Simple count query
    $result = $this->db->table($this->table)
        ->where('user_id', $userId)
        ->count();
    return $result;
}

// Add these methods to your Savedjob_model class

/**
 * Toggle save/unsave job
 */
public function toggleSaveJob($userId, $jobId)
{
    try {
        // Check if job is already saved
        $existing = $this->db->table($this->table)
            ->where('user_id', $userId)
            ->where('job_id', $jobId)
            ->get();

        if ($existing) {
            // Job is already saved, so unsave it
            $this->db->table($this->table)
                ->where('user_id', $userId)
                ->where('job_id', $jobId)
                ->delete();
            return ['action' => 'unsaved', 'success' => true];
        } else {
            // Save the job
            $result = $this->db->table($this->table)->insert([
                'user_id' => $userId,
                'job_id' => $jobId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            return ['action' => 'saved', 'success' => $result];
        }
    } catch (Exception $e) {
        error_log("Error in toggleSaveJob: " . $e->getMessage());
        return ['action' => 'error', 'success' => false];
    }
}

/**
 * Check if job is saved (alias for consistency)
 */
public function isJobSaved($userId, $jobId)
{
    return $this->isSaved($userId, $jobId);
}


}