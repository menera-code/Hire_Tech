<?php
class Userprofile_model extends Model
{
    protected $table = 'user_profiles';

    public function create($data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->table($this->table)->insert($data);
    }

    public function find($id, $with_deleted = false)
    {
        $result = $this->db->table($this->table)
            ->where('id', $id)
            ->get();
        return isset($result[0]) ? $result[0] : $result;
    }

    public function findByUserId($userId)
    {
        $result = $this->db->table($this->table)
            ->where('user_id', $userId)
            ->get();
        return isset($result[0]) ? $result[0] : $result;
    }

    public function update($userId, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->table($this->table)
            ->where('user_id', $userId)
            ->update($data);
    }

    public function createOrUpdate($userId, $data)
    {
        $existing = $this->findByUserId($userId);
        if ($existing) {
            return $this->update($userId, $data);
        } else {
            $data['user_id'] = $userId;
            return $this->create($data);
        }
    }
}