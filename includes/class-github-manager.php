<?php
if (!defined('ABSPATH')) {
    exit;
}

class EGP_GitHub_Manager {
    private $token;
    private $repo;
    private $api_base = 'https://api.github.com';
    
    public function __construct() {
        $this->token = get_option('egp_github_token');
        $this->repo = get_option('egp_github_repo');
    }
    
    public function create_page_branch($post_id, $page_data) {
        try {
            // Generate unique branch name
            $branch_name = $this->generate_branch_name($post_id);
            
            // Get main branch reference
            $main_ref = $this->api_request('GET', "repos/{$this->repo}/git/refs/heads/main");
            if (is_wp_error($main_ref)) {
                return $main_ref;
            }
            
            // Create new branch
            $new_branch = $this->api_request('POST', "repos/{$this->repo}/git/refs", [
                'ref' => "refs/heads/{$branch_name}",
                'sha' => $main_ref['object']['sha']
            ]);
            
            if (is_wp_error($new_branch)) {
                return $new_branch;
            }
            
            // Create/update page file
            $file_result = $this->save_page_file($branch_name, $page_data);
            if (is_wp_error($file_result)) {
                return $file_result;
            }
            
            // Create pull request
            $pr_result = $this->create_pull_request($branch_name, $page_data);
            if (is_wp_error($pr_result)) {
                return $pr_result;
            }
            
            return [
                'success' => true,
                'branch' => $branch_name,
                'pr_url' => $pr_result['html_url'],
                'pr_number' => $pr_result['number']
            ];
            
        } catch (Exception $e) {
            return new WP_Error('egp_error', $e->getMessage());
        }
    }
    
    private function generate_branch_name($post_id) {
        $post_title = sanitize_title(get_the_title($post_id));
        $timestamp = date('Y-m-d-H-i-s');
        return "page-{$post_id}-{$post_title}-{$timestamp}";
    }
    
    private function save_page_file($branch_name, $page_data) {
        $file_path = "pages/page-{$page_data['post_id']}.json";
        $content = json_encode($page_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        // Check if file already exists
        $existing_file = $this->api_request('GET', "repos/{$this->repo}/contents/{$file_path}?ref={$branch_name}");
        
        $commit_data = [
            'message' => "Update page: {$page_data['title']}",
            'content' => base64_encode($content),
            'branch' => $branch_name
        ];
        
        // If file exists, we need the SHA to update it
        if (!is_wp_error($existing_file) && isset($existing_file['sha'])) {
            $commit_data['sha'] = $existing_file['sha'];
        }
        
        return $this->api_request('PUT', "repos/{$this->repo}/contents/{$file_path}", $commit_data);
    }
    
    private function create_pull_request($branch_name, $page_data) {
        $preview_url = get_permalink($page_data['post_id']) . '?preview=true';
        
        $pr_body = "🎨 **Elementor Page Update**\n\n";
        $pr_body .= "**Page:** {$page_data['title']}\n";
        $pr_body .= "**Preview:** [View Page]({$preview_url})\n";
        $pr_body .= "**Author:** {$page_data['author']}\n";
        $pr_body .= "**Updated:** {$page_data['updated_at']}\n\n";
        $pr_body .= "This pull request contains changes made through the Elementor editor.\n";
        $pr_body .= "Review and merge to publish the changes.";
        
        return $this->api_request('POST', "repos/{$this->repo}/pulls", [
            'title' => "📝 Update: {$page_data['title']}",
            'head' => $branch_name,
            'base' => 'main',
            'body' => $pr_body
        ]);
    }
    
    private function api_request($method, $endpoint, $data = null) {
        $url = rtrim($this->api_base, '/') . '/' . ltrim($endpoint, '/');
        
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'token ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Elementor-Git-Publisher/1.0.0'
            ],
            'timeout' => 30
        ];
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($data);
            $args['headers']['Content-Type'] = 'application/json';
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code >= 400) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['message']) ? $error_data['message'] : "HTTP {$code}";
            return new WP_Error('github_api_error', "GitHub API Error: {$error_message}");
        }
        
        return json_decode($body, true);
    }
}