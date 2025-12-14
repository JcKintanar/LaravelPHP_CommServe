<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SupabaseService
{
    protected $url;
    protected $key;
    protected $serviceKey;

    public function __construct()
    {
        $this->url = config('services.supabase.url');
        $this->key = config('services.supabase.key');
        $this->serviceKey = config('services.supabase.service_key');
    }

    /**
     * Query data from a table
     */
    public function from(string $table)
    {
        return new SupabaseQuery($this->url, $this->key, $table);
    }

    /**
     * Insert data into a table
     */
    public function insert(string $table, array $data)
    {
        $response = Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ])->post("{$this->url}/rest/v1/{$table}", $data);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Supabase insert failed', [
            'table' => $table,
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        throw new \Exception('Failed to insert data: ' . $response->body());
    }

    /**
     * Update data in a table
     */
    public function update(string $table, array $data, array $conditions)
    {
        $query = http_build_query($conditions);
        
        $response = Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ])->patch("{$this->url}/rest/v1/{$table}?{$query}", $data);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Failed to update data: ' . $response->body());
    }

    /**
     * Delete data from a table
     */
    public function delete(string $table, array $conditions)
    {
        $query = http_build_query($conditions);
        
        $response = Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key
        ])->delete("{$this->url}/rest/v1/{$table}?{$query}");

        return $response->successful();
    }

    /**
     * Sign up new user (Supabase Auth)
     */
    public function signUp(string $email, string $password, array $metadata = [])
    {
        $response = Http::withHeaders([
            'apikey' => $this->key,
            'Content-Type' => 'application/json'
        ])->post("{$this->url}/auth/v1/signup", [
            'email' => $email,
            'password' => $password,
            'data' => $metadata
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Sign up failed: ' . $response->body());
    }

    /**
     * Sign in user (Supabase Auth)
     */
    public function signIn(string $email, string $password)
    {
        $response = Http::withHeaders([
            'apikey' => $this->key,
            'Content-Type' => 'application/json'
        ])->post("{$this->url}/auth/v1/token?grant_type=password", [
            'email' => $email,
            'password' => $password
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Sign in failed: ' . $response->body());
    }
}

class SupabaseQuery
{
    protected $url;
    protected $key;
    protected $table;
    protected $select = '*';
    protected $filters = [];
    protected $order = [];
    protected $limit = null;
    protected $offset = null;

    public function __construct(string $url, string $key, string $table)
    {
        $this->url = $url;
        $this->key = $key;
        $this->table = $table;
    }

    public function select(string $columns = '*')
    {
        $this->select = $columns;
        return $this;
    }

    public function eq(string $column, $value)
    {
        $this->filters[] = "{$column}=eq.{$value}";
        return $this;
    }

    public function neq(string $column, $value)
    {
        $this->filters[] = "{$column}=neq.{$value}";
        return $this;
    }

    public function gt(string $column, $value)
    {
        $this->filters[] = "{$column}=gt.{$value}";
        return $this;
    }

    public function lt(string $column, $value)
    {
        $this->filters[] = "{$column}=lt.{$value}";
        return $this;
    }

    public function like(string $column, string $pattern)
    {
        $this->filters[] = "{$column}=like.{$pattern}";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc')
    {
        $this->order[] = "{$column}.{$direction}";
        return $this;
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function get()
    {
        $queryParams = ['select' => $this->select];
        
        if (!empty($this->filters)) {
            foreach ($this->filters as $filter) {
                parse_str($filter, $parsed);
                $queryParams = array_merge($queryParams, $parsed);
            }
        }
        
        if (!empty($this->order)) {
            $queryParams['order'] = implode(',', $this->order);
        }
        
        if ($this->limit !== null) {
            $queryParams['limit'] = $this->limit;
        }
        
        if ($this->offset !== null) {
            $queryParams['offset'] = $this->offset;
        }

        $query = http_build_query($queryParams);
        
        $response = Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key
        ])->get("{$this->url}/rest/v1/{$this->table}?{$query}");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Query failed: ' . $response->body());
    }

    public function first()
    {
        $result = $this->limit(1)->get();
        return !empty($result) ? $result[0] : null;
    }
}
