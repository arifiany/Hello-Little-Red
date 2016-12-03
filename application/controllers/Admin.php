<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once(APPPATH . 'core/MY_Controller.php');

class Admin extends MY_Controller
{
    protected $data = array();
    
    function __construct()
    {
        parent::__construct();
        $this->load->library('ion_auth');
        $this->load->model('blog_model');
        $this->load->model('page_model');
        $this->load->model('themes_model');
        $this->load->model('resources_model');
        $this->load->model('photo_model');
        $this->load->model('contact_model');
        $this->load->model('store_model');
        $this->load->model('commission_model');
        $this->load->model('look_model');
        $this->load->model('pagination_model');
        $this->load->model('writing_model');
        $this->load->model('site_model');
        $this->load->library("pagination");
        $this->load->library('ion_auth');
        $this->load->helper("url");
        $this->load->library('form_validation');
        $user                    = $this->ion_auth->user()->row();
        $this->data['user']      = $user;
        $this->data['query']     = '';
        $this->data['site_data'] = $this->site_model->get_data();
        $site_data               = $this->site_model->get_data();
        $site_title              = '';
        foreach ($site_data as $site) {
            $site_title = $site->title;
        }
    }
    
    /* 
    ==================================================
    |                  Basic Options                 |
    ==================================================
    */
    
    public function index()
    {
        if (!$this->ion_auth->logged_in()) {
            redirect('admin/login');
        } else {
            redirect('admin/dashboard');
        }
    }
    
    public function login()
    {
        
        $site_data  = $this->site_model->get_data();
        $site_title = '';
        foreach ($site_data as $site) {
            $site_title = $site->title;
        }
        $this->data['page_title'] = 'Admin Login | ' . $site_title;
        if (!$this->ion_auth->logged_in()) {
            $this->load->library('form_validation');
            $this->form_validation->set_rules('username', 'Username', 'trim|required');
            $this->form_validation->set_rules('password', 'Password', 'trim|required');
            $this->form_validation->set_rules('ajax', 'AJAX', 'trim|is_natural');
            if ($this->form_validation->run() === FALSE) {
                if ($this->input->post('ajax')) {
                    $response['username_error'] = form_error('username');
                    $response['password_error'] = form_error('password');
                    header("content-type:application/json");
                    echo json_encode($response);
                    exit;
                }
                $this->load->helper('form');
                $this->render('admin/login_view', 'admin_master');
            } else {
                $remember = (bool) $this->input->post('remember');
                $username = $this->input->post('username');
                $password = $this->input->post('password');
                $this->ion_auth->set_hook('post_login_successful', 'get_gravatar_hash', $this, '_gravatar', array());
                
                if ($this->ion_auth->login($username, $password, $remember)) {
                    if ($this->input->post('ajax')) {
                        $response['logged_in'] = 1;
                        header("content-type:application/json");
                        echo json_encode($response);
                        exit;
                    }
                    redirect('dashboard', 'admin_master');
                } else {
                    if ($this->input->post('ajax')) {
                        $response['username'] = $username;
                        $response['password'] = $password;
                        $response['error']    = $this->ion_auth->errors();
                        header("content-type:application/json");
                        echo json_encode($response);
                        exit;
                    }
                    $_SESSION['auth_message'] = $this->ion_auth->errors();
                    $this->session->mark_as_flash('auth_message');
                    redirect('admin/login', 'admin_master');
                }
            }
        } else {
            redirect('admin/dashboard');
            
        }
    }
    
    public function logout()
    {
        $this->ion_auth->logout();
        redirect('admin/login', 'refresh');
    }
    
    public function _gravatar()
    {
        if ($this->form_validation->valid_email($_SESSION['email'])) {
            $gravatar_url         = md5(strtolower(trim($_SESSION['email'])));
            $_SESSION['gravatar'] = $gravatar_url;
        }
        return TRUE;
    }
    
    public function dashboard()
    {
        $site_data  = $this->site_model->get_data();
        $site_title = '';
        foreach ($site_data as $site) {
            $site_title = $site->title;
        }
        $this->data['page_title'] = 'Admin dashboard | ' . $site_title;
        if (!$this->ion_auth->logged_in()) {
            redirect('admin/login');
        }
        
        $this->data['updates'] = $this->site_model->get_statuses();
        $this->form_validation->set_rules('title', 'title', 'trim');
        if ($this->form_validation->run() === FALSE) {
            $this->render('admin/dashboard_view', 'admin_master');
        } else {
            $title       = $this->input->post('title');
            $description = $this->input->post('description');
            $keywords    = $this->input->post('keywords');
            
            $this->site_model->update_data($title, $description, $keywords);
            $this->session->set_flashdata('message', 'Site data updated!');
            redirect('admin/dashboard');
        }
    }
    
    public function profile()
    {
        $site_data  = $this->site_model->get_data();
        $site_title = '';
        foreach ($site_data as $site) {
            $site_title = $site->title;
        }
        $this->data['page_title'] = 'Edit Profile | ' . $site_title;
        if (!$this->ion_auth->logged_in()) {
            redirect('admin');
        }
        $this->data['page_title']        = 'User Profile';
        $user                            = $this->ion_auth->user()->row();
        $this->data['user']              = $user;
        $this->data['current_user_menu'] = '';
        
        $this->load->library('form_validation');
        $this->form_validation->set_rules('first_name', 'First name', 'trim');
        $this->form_validation->set_rules('last_name', 'Last name', 'trim');
        $this->form_validation->set_rules('company', 'Company', 'trim');
        $this->form_validation->set_rules('phone', 'Phone', 'trim');
        
        if ($this->form_validation->run() === FALSE) {
            $this->render('admin/profile_view', 'admin_master');
        } else {
            $new_data = array(
                'first_name' => $this->input->post('first_name'),
                'last_name' => $this->input->post('last_name'),
                'company' => $this->input->post('company'),
                'phone' => $this->input->post('phone')
            );
            if (strlen($this->input->post('password')) >= 6)
                $new_data['password'] = $this->input->post('password');
            $this->ion_auth->update($user->id, $new_data);
            
            $this->session->set_flashdata('message', $this->ion_auth->messages());
            redirect('admin/profile', 'refresh');
        }
    }
    
    
    /* 
    ==================================================
    |                      Blog                      |
    ==================================================
    */
    
    public function add_new_entry()
    {
        $site_data  = $this->site_model->get_data();
        $site_title = '';
        foreach ($site_data as $site) {
            $site_title = $site->title;
        }
        $user               = $this->ion_auth->user()->row();
        $this->data['user'] = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $this->data['title']      = 'Add new entry - ' . $this->config->item('site_title', 'ion_auth');
            $this->data['categories'] = $this->blog_model->get_categories();
            
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            //set validation rules
            $this->form_validation->set_rules('entry_name', 'Title', 'required');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/add_new_entry', 'admin_master');
            } else {
                //if valid
                $user       = $this->ion_auth->user()->row();
                $title      = $this->input->post('entry_name');
                $body       = $this->input->post('entry_body');
                $categories = $this->input->post('entry_category[]');
                $image      = $this->input->post('entry_image');
                $video      = $this->input->post('entry_video');
                
                $this->blog_model->add_new_entry($user->id, $title, $body, $categories, $image, $video);
                $this->session->set_flashdata('message', '1 new post added!');
                redirect('admin/add_new_entry');
            }
        }
    }
    
    public function delete_post($id)
    {
        $this->blog_model->delete_post($id);
        redirect('admin/manage_posts');
    }
    
    public function update_entry($id = '')
    {
        $this->data['page_title'] = 'Add Blog Entry | Hello Little Red';
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $this->data['page_title'] = 'Edit entry - ' . $this->config->item('site_title', 'ion_auth');
            $this->data['query']      = $this->blog_model->get_post($id);
            
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            //set validation rules
            $this->form_validation->set_rules('entry_name', 'Title', 'required');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/add_new_entry', 'admin_master');
            } else {
                //if valid
                $id    = $this->input->post('entry_id');
                $title = $this->input->post('entry_name');
                $body  = $this->input->post('entry_body');
                $image = $this->input->post('entry_image');
                $video = $this->input->post('entry_video');
                
                $this->blog_model->update_entry($id, $title, $body, $image, $video);
                $this->session->set_flashdata('message', 'Post updated');
                redirect('admin/update_entry');
            }
        }
    }
    
    public function manage_posts($offset = 0)
    {
        $this->data['page_title'] = 'Manage Blog Entries | Hello Little Red';
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $config                   = array();
            $config["base_url"]       = base_url() . "admin/manage_posts";
            $config["total_rows"]     = $this->pagination_model->total_count();
            $config["per_page"]       = 15;
            $config["uri_segment"]    = 3;
            $config['display_pages']  = FALSE;
            $config['next_link']      = 'Next Page';
            $config['next_tag_open']  = '<li><span class="button big next">';
            $config['next_tag_close'] = '</span></li>';
            $config['prev_link']      = 'Previous Page';
            $config['prev_tag_open']  = '<li><span class="button big previous">';
            $config['prev_tag_close'] = '</span></li>';
            $config['last_link']      = '';
            $config['first_link']     = '';
            $this->pagination->initialize($config);
            $this->data['paginglinks'] = $this->pagination->create_links();
            
            $page                = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
            $this->data["posts"] = $this->blog_model->get_posts($config["per_page"], $page);
            $this->render('admin/manage_posts', 'admin_master');
        }
    }
    
    
    
    /* 
    ==================================================
    |                Blog Categories                 |
    ==================================================
    */
    
    public function add_new_category()
    {
        
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Add New Category | Hello Little Red';
        $this->data['categories'] = $this->blog_model->get_categories();
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        } else {
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            // set validation rules
            $this->form_validation->set_rules('category_name', 'Name', 'required|max_length[200]');
            $this->form_validation->set_rules('category_slug', 'Slug', 'max_length[200]');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/add_new_category', 'admin_master');
            } else {
                //if valid
                $name = $this->input->post('category_name');
                
                if ($this->input->post('category_slug') != '')
                    $slug = $this->input->post('category_slug');
                else
                    $slug = strtolower(preg_replace('/[^A-Za-z0-9_-]+/', '-', $name));
                
                $this->blog_model->add_new_category($name, $slug);
                $this->session->set_flashdata('message', '1 new category added!');
                redirect('admin/add-new-category');
            }
        }
    }
    
    public function delete_category($id)
    {
        $this->blog_model->delete_category($id);
        redirect('admin/add_new_category');
    }
    
    
    /* 
    ==================================================
    |                      Pages                     |
    ==================================================
    */
    
    public function add_new_page()
    {
        $this->data['page_title'] = 'Add New Page | Hello Little Red';
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $this->data['title'] = 'Add new entry - ' . $this->config->item('site_title', 'ion_auth');
            
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            //set validation rules
            $this->form_validation->set_rules('page_name', 'Title', 'required');
            $this->form_validation->set_rules('page_body', 'Content', 'required');
            $this->form_validation->set_rules('page_slug', 'Slug', 'required');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/add_new_page', 'admin_master');
            } else {
                //if valid
                $user  = $this->ion_auth->user()->row();
                $title = $this->input->post('page_name');
                $body  = $this->input->post('page_body');
                $slug  = $this->input->post('page_slug');
                
                $this->page_model->add_new_page($user->id, $title, $body, $slug);
                $this->session->set_flashdata('message', '1 new post added!');
                redirect('admin/add_new_page');
            }
        }
    }
    
    public function manage_pages()
    {
        $this->data['page_title'] = 'Manage Pages | Hello Little Red';
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $this->data['posts'] = $this->page_model->get_pages();
            $this->render('admin/manage_pages', 'admin_master');
        }
    }
    
    public function delete_page($id)
    {
        $this->page_model->delete_page($id);
        redirect('admin/manage_pages');
    }
    
    
    public function update_page($id = '')
    {
        $this->data['page_title'] = 'Edit Page | Hello Little Red';
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            $this->data['query'] = $this->page_model->get_page_by_id($id);
            
            //set validation rules
            $this->form_validation->set_rules('page_name', 'Title', 'required');
            $this->form_validation->set_rules('page_body', 'Content', 'required');
            $this->form_validation->set_rules('page_slug', 'Slug', 'required');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/add_new_page', 'admin_master');
            } else {
                //if valid
                $id    = $this->input->post('page_id');
                $title = $this->input->post('page_name');
                $body  = $this->input->post('page_body');
                $slug  = $this->input->post('page_slug');
                
                $this->page_model->update_page($id, $title, $body, $slug);
                $this->session->set_flashdata('message', 'Page Updated!');
                redirect('admin/update_page');
            }
        }
    }
    
    
    /* 
    ==================================================
    |                     Themes                     |
    ==================================================
    */
    
    public function add_new_theme()
    {
        $this->data['page_title'] = 'Add Theme | Hello Little Red';
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $this->data['categories'] = $this->themes_model->get_categories();
            
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            //set validation rules
            $this->form_validation->set_rules('theme_name', 'Title', 'required');
            $this->form_validation->set_rules('theme_image', 'Image', 'required');
            $this->form_validation->set_rules('theme_code', 'Code', 'required');
            $this->form_validation->set_rules('theme_preview', 'Preview', 'required');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/add_new_theme', 'admin_master');
            } else {
                //if valid
                $user       = $this->ion_auth->user()->row();
                $name       = $this->input->post('theme_name');
                $body       = $this->input->post('theme_body');
                $categories = $this->input->post('theme_category[]');
                $image      = $this->input->post('theme_image');
                $preview    = $this->input->post('theme_preview');
                $code       = $this->input->post('theme_code');
                
                $this->themes_model->add_new_theme($user->id, $name, $image, $preview, $code, $body, $categories);
                $this->session->set_flashdata('message', '1 new theme added!');
                redirect('admin/add_new_theme');
            }
        }
    }
    
    public function delete_theme($id)
    {
        $this->blog_model->delete_post($id);
        redirect('admin/manage_posts');
    }
    
    public function update_theme($id = '')
    {
        $this->data['page_title'] = 'Edit Theme | Hello Little Red';
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $this->data['query'] = $this->themes_model->get_theme($id);
            
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            //set validation rules
            $this->form_validation->set_rules('theme_name', 'Title', 'required');
            $this->form_validation->set_rules('theme_image', 'Image', 'required');
            $this->form_validation->set_rules('theme_code', 'Code', 'required');
            $this->form_validation->set_rules('theme_preview', 'Preview', 'required');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/add_new_theme', 'admin_master');
            } else {
                //if valid
                $id         = $this->input->post('theme_id');
                $name       = $this->input->post('theme_name');
                $body       = $this->input->post('theme_body');
                $categories = $this->input->post('theme_category[]');
                $image      = $this->input->post('theme_image');
                $preview    = $this->input->post('theme_preview');
                $code       = $this->input->post('theme_code');
                
                $this->themes_model->update_theme($id, $name, $image, $preview, $code, $body);
                $this->session->set_flashdata('message', 'Theme updated');
                redirect('admin/update_theme');
            }
        }
    }
    
    public function manage_themes()
    {
        $this->data['page_title'] = 'Manage Blog Entries | Hello Little Red';
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $config["base_url"]       = base_url() . "admin/manage_themes";
            $config["total_rows"]     = $this->themes_model->total_count();
            $config["per_page"]       = 15;
            $config["uri_segment"]    = 3;
            $config['display_pages']  = FALSE;
            $config['next_link']      = 'Next Page';
            $config['next_tag_open']  = '<li><span class="button big next">';
            $config['next_tag_close'] = '</span></li>';
            $config['prev_link']      = 'Previous Page';
            $config['prev_tag_open']  = '<li><span class="button big previous">';
            $config['prev_tag_close'] = '</span></li>';
            $config['last_link']      = '';
            $config['first_link']     = '';
            $this->pagination->initialize($config);
            $this->data['paginglinks'] = $this->pagination->create_links();
            
            $page                     = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
            $this->data["posts"]      = $this->themes_model->get_themes($config["per_page"], $page);
            $this->data['categories'] = $this->themes_model->get_categories();
            $this->render('admin/manage_themes', 'admin_master');
        }
    }
    
    
    
    /* 
    ==================================================
    |               Theme Categories                 |
    ==================================================
    */
    
    public function add_new_theme_category()
    {
        
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Add New Category | Hello Little Red';
        $this->data['categories'] = $this->themes_model->get_categories();
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        } else {
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            // set validation rules
            $this->form_validation->set_rules('category_name', 'Name', 'required|max_length[200]');
            $this->form_validation->set_rules('category_slug', 'Slug', 'max_length[200]');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/add_new_theme_category', 'admin_master');
            } else {
                //if valid
                $name = $this->input->post('category_name');
                
                if ($this->input->post('category_slug') != '')
                    $slug = $this->input->post('category_slug');
                else
                    $slug = strtolower(preg_replace('/[^A-Za-z0-9_-]+/', '-', $name));
                
                $this->themes_model->add_new_category($name, $slug);
                $this->session->set_flashdata('message', '1 new category added!');
                redirect('admin/add-new-theme-category');
            }
        }
    }
    
    public function delete_theme_category($id)
    {
        $this->themes_model->delete_category($id);
        $this->session->set_flashdata('message', 'a category is deleted.');
        redirect('admin/add_new_theme_category');
    }
    
    
    
    /* 
    ==================================================
    |                   Resources                    |
    ==================================================
    */
    
    public function add_new_resource()
    {
        $this->data['page_title'] = 'Add Resource | Hello Little Red';
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $this->data['categories'] = $this->resources_model->get_types();
            
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            //set validation rules
            $this->form_validation->set_rules('resource_name', 'Title', 'required');
            $this->form_validation->set_rules('resource_download', 'Download Link', 'required');
            $this->form_validation->set_rules('resource_preview', 'Preview', 'required');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/add_new_resource', 'admin_master');
            } else {
                //if valid
                $user       = $this->ion_auth->user()->row();
                $name       = $this->input->post('resource_name');
                $body       = $this->input->post('resource_body');
                $categories = $this->input->post('resource_category');
                $preview    = $this->input->post('resource_preview');
                $download   = $this->input->post('resource_download');
                
                $this->resources_model->add_new_resource($user->id, $name, $preview, $download, $categories);
                $this->session->set_flashdata('message', '1 new resource added!');
                redirect('admin/add_new_resource');
            }
        }
    }
    
    public function delete_resource($id)
    {
        $this->resources_model->delete_resource($id);
        redirect('admin/manage_resources');
    }
    
    public function manage_resources()
    {
        $this->data['page_title'] = 'Manage Resources | Hello Little Red';
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $this->data['posts'] = $this->resources_model->get_resources();
            $this->render('admin/manage_resources', 'admin_master');
        }
    }
    
    /* 
    ==================================================
    |             Resources Categories               |
    ==================================================
    */
    
    public function add_new_resource_type()
    {
        
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Add New Category | Hello Little Red';
        $this->data['categories'] = $this->resources_model->get_types();
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        } else {
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            // set validation rules
            $this->form_validation->set_rules('category_name', 'Name', 'required|max_length[200]');
            $this->form_validation->set_rules('category_slug', 'Slug', 'max_length[200]');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/add_new_resource_category', 'admin_master');
            } else {
                //if valid
                $name = $this->input->post('category_name');
                
                if ($this->input->post('category_slug') != '')
                    $slug = $this->input->post('category_slug');
                else
                    $slug = strtolower(preg_replace('/[^A-Za-z0-9_-]+/', '-', $name));
                
                $this->resources_model->add_new_type($name, $slug);
                $this->session->set_flashdata('message', '1 new category added!');
                redirect('admin/add-new-resource-type');
            }
        }
    }
    
    public function delete_resource_category($id)
    {
        $this->resources_model->delete_type($id);
        $this->session->set_flashdata('message', 'a category is deleted.');
        redirect('admin/add_new_resource_type');
    }
    
    /* 
    ==================================================
    |             photos Categories               |
    ==================================================
    */
    
    public function add_new_photo_album($id = false)
    {
        
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Add Photo Album | Hello Little Red';
        $this->data['categories'] = $this->photo_model->get_albums();
        $this->data['query']      = $this->photo_model->get_album($id);
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        } else {
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            // set validation rules
            $this->form_validation->set_rules('album_name', 'Name', 'required|max_length[200]');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/add_new_photo_album', 'admin_master');
            } else {
                //if valid
                
                $name     = $this->input->post('album_name');
                $location = $this->input->post('album_location');
                $date     = $this->input->post('album_date');
                $cover    = $this->input->post('album_cover');
                $story    = $this->input->post('album_story');
                $embed    = $this->input->post('album_embed');
                
                
                if ($id == false) {
                    //print_r($name);print_r($location);die();
                    $user = $this->ion_auth->user()->row();
                    $this->photo_model->add_new_album($user->id, $name, $location, $date, $cover, $story, $embed);
                    $this->session->set_flashdata('message', '1 new album added!');
                    redirect('admin/add-new-photo-album');
                } else {
                    $id = $this->input->post('album_id');
                    $this->photo_model->update_album($id, $name, $location, $date, $cover, $story, $embed);
                    $this->session->set_flashdata('message', $name . ' Updated');
                    redirect('admin/add-new-photo-album');
                    
                }
            }
        }
    }
    
    public function delete_photo_album($id)
    {
        $this->photo_model->delete_album($id);
        $this->session->set_flashdata('message', 'a category is deleted.');
        redirect('admin/add_new_photo_album');
    }
    
    
    /* 
    ==================================================
    |                    Contacts                    |
    ==================================================
    */
    
    public function contacts()
    {
        
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Emails | Hello Little Red';
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        } else {
            $config                   = array();
            $config["base_url"]       = base_url() . "admin/contacts/";
            $config["total_rows"]     = $this->contact_model->total_count_emails();
            $config["per_page"]       = 15;
            $config["uri_segment"]    = 3;
            $config['display_pages']  = FALSE;
            $config['next_link']      = 'Next Page';
            $config['next_tag_open']  = '<li><span class="button big next">';
            $config['next_tag_close'] = '</span></li>';
            $config['prev_link']      = 'Previous Page';
            $config['prev_tag_open']  = '<li><span class="button big previous">';
            $config['prev_tag_close'] = '</span></li>';
            $config['last_link']      = '';
            $config['first_link']     = '';
            $this->pagination->initialize($config);
            $this->data['paginglinks'] = $this->pagination->create_links();
            $page                = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
            $this->data['posts'] = $this->contact_model->get_emails($config["per_page"], $page);;
            $this->render('admin/emails', 'admin_master');
        }
    }
    
    
    public function questions()
    {
        
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Questions | Hello Little Red';
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        } else {
            $config["base_url"] = base_url() . "questions/";
            $config["total_rows"] = $this->contact_model->total_count_quesions();
            $config["per_page"] = 15;
            $config["uri_segment"] = 3;
            $config['display_pages'] = FALSE;
            $config['next_link'] = 'Next Page';
            $config['next_tag_open'] = '<li><span class="button big next">';
            $config['next_tag_close'] = '</span></li>';
            $config['prev_link'] = 'Previous Page';
            $config['prev_tag_open'] = '<li><span class="button big previous">';
            $config['prev_tag_close'] = '</span></li>';
            $config['last_link'] = '';
            $config['first_link'] = '';
            $this->pagination->initialize($config);
            $this->data['paginglinks'] = $this->pagination->create_links();
            $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
            $this->data["posts"] = $this->contact_model->get_questions(true, $config["per_page"], $page);
            $this->render('admin/questions', 'admin_master');
        }
    }
    
    public function answer($id = '')
    {
        $this->data['page_title'] = 'Answer | Hello Little Red';
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $this->data['posts'] = $this->contact_model->get_question($id);
            
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            //set validation rules
            $this->form_validation->set_rules('answer', 'answer', 'required');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                echo $this->render('admin/answer', 'admin_master');
            } else {
                //if valid
                $id       = $this->input->post('id');
                $name     = $this->input->post('name');
                $question = $this->input->post('question');
                $answer   = $this->input->post('answer');
                
                $this->contact_model->answer_questions($id, $name, $question, $answer);
                $this->session->set_flashdata('message', 'Question answered.');
                redirect('admin/answer/' . $id);
            }
        }
    }
    
    /* 
    ==================================================
    |                     Design                     |
    ==================================================
    */
    
    public function design($id = false)
    {
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Store';
        $this->data['query']      = $this->store_model->get_design($id);
        $config                   = array();
        $config["base_url"]       = base_url() . "admin/designs/index";
        $config["total_rows"]     = $this->store_model->total_count();
        $config["per_page"]       = 15;
        $config["uri_segment"]    = 3;
        $config['display_pages']  = FALSE;
        $config['next_link']      = 'Next Page';
        $config['next_tag_open']  = '<li><span class="button big next">';
        $config['next_tag_close'] = '</span></li>';
        $config['prev_link']      = 'Previous Page';
        $config['prev_tag_open']  = '<li><span class="button big previous">';
        $config['prev_tag_close'] = '</span></li>';
        $config['last_link']      = '';
        $config['first_link']     = '';
        $this->pagination->initialize($config);
        $this->data['paginglinks'] = $this->pagination->create_links();
            
        $page                = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $this->data["categories"] =$this->store_model->get_designs($config["per_page"], $page);;
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        } else {
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            // set validation rules
            $this->form_validation->set_rules('name', 'Name', 'required|max_length[200]');
            $this->form_validation->set_rules('image', 'Image', 'required|max_length[200]');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/design', 'admin_master');
            } else {
                //if valid
                
                
                if ($id == false) {
                    //print_r($name);print_r($location);die();
                    $name      = $this->input->post('name');
                    $image     = $this->input->post('image');
                    $redbubble = $this->input->post('redbubble');
                    $tees      = $this->input->post('tees');
                    $this->store_model->add_new_design($name, $image, $redbubble, $tees);
                    $this->session->set_flashdata('message', '1 new design added!');
                    redirect('admin/design');
                } else {
                    $name      = $this->input->post('name');
                    $image     = $this->input->post('image');
                    $redbubble = $this->input->post('redbubble');
                    $tees      = $this->input->post('tees');
                    $this->store_model->update_design($id, $name, $image, $redbubble, $tees);
                    $this->session->set_flashdata('message', $name . ' Updated');
                    redirect('admin/design');
                    
                }
            }
        }
    }
    
    public function delete_design($id)
    {
        $this->store_model->delete_design($id);
        $this->session->set_flashdata('message', 'a category is deleted.');
        redirect('admin/design');
    }
    
    
    /* 
    ==================================================
    |                    Contacts                    |
    ==================================================
    */
    
    public function commissions($id = NULL)
    {
        
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Commissions | Hello Little Red';
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        }
        
        if ($id == NULL) {
            $this->data['posts'] = $this->commission_model->get_commissions();
            $this->render('admin/commission', 'admin_master');
        } else {
            $this->data['posts'] = $this->commission_model->get_commission($id);
            $this->render('admin/detail', 'admin_master');
        }
    }
    
    /* 
    ==================================================
    |                     Sidebar                    |
    ==================================================
    */
    
    public function sidebar($id = false)
    {
        
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Add Sidebar | Hello Little Red';
        $this->data['categories'] = $this->look_model->get_sidebars();
        $this->data['query']      = $this->look_model->get_sidebar($id);
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        } else {
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            // set validation rules
            $this->form_validation->set_rules('name', 'name', 'required|max_length[200]');
            $this->form_validation->set_rules('content', 'content', 'required');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/sidebar', 'admin_master');
            } else {
                //if valid
                
                
                if ($id == false) {
                    //print_r($name);print_r($location);die();
                    $user    = $this->ion_auth->user()->row();
                    $name    = $this->input->post('name');
                    $content = $this->input->post('content');
                    $this->look_model->add_new_sidebar($name, $content);
                    $this->session->set_flashdata('message', '1 new sidebar added!');
                    redirect('admin/sidebar');
                } else {
                    $name    = $this->input->post('name');
                    $content = $this->input->post('content');
                    $this->look_model->update_sidebar($id, $name, $content);
                    $this->session->set_flashdata('message', 'Album Updated');
                    redirect('admin/sidebar');
                    
                }
            }
        }
    }
    
    public function delete_sidebar($id)
    {
        $this->look_model->delete_sidebar($id);
        $this->session->set_flashdata('message', 'a category is deleted.');
        redirect('admin/add_new_photo_album');
    }
    
    /* 
    ==================================================
    |                 Social Medias                  |
    ==================================================
    */
    
    public function socmeds()
    {
        
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Social Medias | Hello Little Red';
        $this->data['query']      = $this->look_model->get_socmeds();
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) {
            show_404();
        } else {
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            $this->form_validation->set_rules('id', 'id', 'required');
            
            
            if ($this->form_validation->run() == FALSE) {
                $this->render('admin/socmeds', 'admin_master');
            } else {
                $codepen    = $this->input->post('codepen');
                $deviantart = $this->input->post('deviantart');
                $facebook   = $this->input->post('facebook');
                $flickr     = $this->input->post('flickr');
                $instagram  = $this->input->post('instagram');
                $linkedin   = $this->input->post('linkedin');
                $soundcloud = $this->input->post('soundcloud');
                $tumblr     = $this->input->post('tumblr');
                $twitter    = $this->input->post('twitter');
                $youtube    = $this->input->post('youtube');
                $behance    = $this->input->post('behance');
                $github     = $this->input->post('github');
                
                $this->look_model->update_socmeds($codepen, $deviantart, $facebook, $flickr, $instagram, $linkedin, $soundcloud, $tumblr, $twitter, $youtube, $behance, $github);
                $this->session->set_flashdata('message', 'Social Media Updated');
                redirect('admin/socmeds');
                
            }
        }
    }
    
    /* 
    ==================================================
    |                 Social Medias                  |
    ==================================================
    */
    
    public function header()
    {
        
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Header | Hello Little Red';
        $this->data['query']      = $this->look_model->get_headers();
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        } else {
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            $this->form_validation->set_rules('id', 'id', 'required');
            
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/header', 'admin_master');
            } else {
                //if valid
                $link = $this->input->post('link');
                
                $this->look_model->update_header($link);
                $this->session->set_flashdata('message', 'Header Updated');
                redirect('admin/header');
                
            }
        }
    }
    
    
    /* 
    ==================================================
    |                    Websites                    |
    ==================================================
    */
    
    public function website($id = false)
    {
        
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Add Website | Hello Little Red';
        $this->data['categories'] = $this->look_model->get_websites();
        $this->data['query']      = $this->look_model->get_website($id);
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        } else {
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            // set validation rules
            $this->form_validation->set_rules('name', 'name', 'required|max_length[200]');
            $this->form_validation->set_rules('link', 'link', 'required|max_length[200]');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/website', 'admin_master');
            } else {
                //if valid
                
                $name        = $this->input->post('name');
                $link        = $this->input->post('link');
                $icon        = $this->input->post('icon');
                $description = $this->input->post('description');
                
                if ($id == false) {
                    //print_r($name);print_r($location);die();
                    $this->look_model->add_new_website($name, $link, $icon, $description);
                    $this->session->set_flashdata('message', '1 new website added!');
                    redirect('admin/website');
                } else {
                    $id = $this->input->post('album_id');
                    $this->look_model->update_website($id, $name, $link, $icon, $description);
                    $this->session->set_flashdata('message', 'Website Updated');
                    redirect('admin/website');
                    
                }
            }
        }
    }
    
    public function delete_website($id)
    {
        $this->look_model->delete_website($id);
        $this->session->set_flashdata('message', 'a category is deleted.');
        redirect('admin/website');
    }
    
    /* 
    ==================================================
    |                     Writing                    |
    ==================================================
    */
    
    public function writing($id = false)
    {
        $user                     = $this->ion_auth->user()->row();
        $this->data['user']       = $user;
        $this->data['page_title'] = 'Writing';
        $this->data['query']      = $this->writing_model->get_story($id);
        $this->data['categories'] = $this->writing_model->get_stories();
        
        if (!$this->ion_auth->logged_in() && !$this->ion_auth->is_admin()) // block un-authorized access
            {
            show_404();
        } else {
            $this->load->helper('form');
            $this->load->library(array(
                'form_validation'
            ));
            
            // set validation rules
            $this->form_validation->set_rules('title', 'title', 'required|max_length[300]');
            $this->form_validation->set_rules('type', 'type', 'required|max_length[300]');
            $this->form_validation->set_rules('link1', 'link', 'required|max_length[300]');
            
            if ($this->form_validation->run() == FALSE) {
                //if not valid
                $this->render('admin/writing', 'admin_master');
            }
            
            else {
                //if valid
                
                $title    = $this->input->post('title');
                $type     = $this->input->post('type');
                $genre    = $this->input->post('genre');
                $rating   = $this->input->post('rating');
                $fandom   = $this->input->post('fandom');
                $pairs    = $this->input->post('pairs');
                $summary  = $this->input->post('summary');
                $link1    = $this->input->post('link1');
                $link2    = $this->input->post('link2');
                $link3    = $this->input->post('link3');
                $hide     = $this->input->post('hide');
                $language = $this->input->post('language');
                
                
                if ($id == false) {
                    $this->writing_model->add_new_story($title, $type, $genre, $rating, $fandom, $pairs, $summary, $link1, $link2, $link3, $hide);
                    $this->session->set_flashdata('message', '1 new design added!');
                    redirect('admin/writing');
                } else {
                    $this->writing_model->update_story($id, $title, $type, $genre, $rating, $fandom, $pairs, $summary, $link1, $link2, $link3, $hide);
                    $this->session->set_flashdata('message', $title . ' Updated');
                    redirect('admin/writing');
                    
                }
            }
        }
    }
    
    public function delete_story($id)
    {
        $this->writing_model->delete_story($id);
        $this->session->set_flashdata('message', 'a category is deleted.');
        redirect('admin/design');
    }
    
    
}