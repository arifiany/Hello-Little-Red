<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class themes extends MY_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('themes_model');
        $this->load->model('look_model');
        $this->load->model('site_model');
		$this->load->library('ion_auth');
        $this->load->library("pagination");
        $this->load->helper("url");
        $this->data['current'] = "";
        $this->data['pagetitle'] = "";
        $this->data['image'] = "";
		$this->data['explanation'] = 'Free themes.';
        $this->data['keywords'] = '';
	}

	public function index()
	{
		// set page title
		$this->data['title'] = 'Themes - '.$this->config->item('site_title', 'ion_auth');
		// set current menu highlight
		$this->data['current'] = 'themes';
		$this->data['explanation'] = 'Free themes and templates that I made for various platforms.<br>';

		$this->data['categories'] = $this->themes_model->get_categories();
		// render view
		$this->data['test'] = "";
		foreach ($this->data['categories'] as $category ) {
			$link = '<a href="'.base_url().'themes/'.$category->slug.'">'.$category->slug."</a> - ";
			$this->data['test'] = $this->data['test'] .$link; 
		}
		$config["base_url"] = base_url() . "themes/index";
        $config["total_rows"] = $this->themes_model->total_count();
        $config["per_page"] = 9;
        $config["uri_segment"] = 3;
	    $config['display_pages'] = FALSE;
	    $config['next_link'] = 'Next Page';
		$config['next_tag_open'] = '<span class="button big next">';
		$config['next_tag_close'] = '</span>';
	    $config['prev_link'] = 'Previous Page';
		$config['prev_tag_open'] = '<span class="button big previous">';
		$config['prev_tag_close'] = '</span>';
		$config['last_link'] = '';
		$config['first_link'] = '';
        $this->pagination->initialize($config);
	    $this->data['paginglinks'] = $this->pagination->create_links();

	    $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $this->data["posts"] = $this->themes_model->
            get_themes($config["per_page"], $page);
		$this->render('blog/themes','public_master');
	}

	public function shorten_string($string, $wordsreturned)
	{
		$retval = $string;
		$string = preg_replace('/(?<=\S,)(?=\S)/', ' ', $string);
		$string = str_replace("\n", " ", $string);
		$array = explode(" ", $string);
		if (count($array)<=$wordsreturned)
		{
			$retval = $string;
		}
		else
		{
			array_splice($array, $wordsreturned);
			$retval = implode(" ", $array)." ...";
		}
		return $retval;
	}
	
	
	public function theme($id, $preview='') // get a post based on id
	{
		$this->data['pagetitle'] 			= '';
		$this->data['query'] 			= $this->themes_model->get_theme($id);
		$this->data['theme_id'] 		= $id;
		$this->data['categories'] = $this->themes_model->get_categories();
		
		if( $this->ion_auth->logged_in() )
			$this->data['user'] = $this->ion_auth->user()->row(); // get current user login details
		
		$this->load->helper('form');
		$this->load->library(array('form_validation'));
		

		
		if($preview=='') {
			if($this->themes_model->get_theme($id))
			{
				foreach($this->data['query'] as $row)
				{
					$this->data['title'] = $row->theme_name.' - '.$this->config->item('site_title', 'ion_auth');
					$this->data['explanation'] = substr($row->theme_body, 1, 200);
					$this->data['image'] = $row->theme_image;


				}
				
				$this->render('blog/theme','public_master');
			}
		} else if($preview=="preview") {
			$this->render('blog/theme_preview', 'preview_master');
		} else {
			show_404();
		}
	}
	
	public function type($slug = FALSE)
	{
		$this->data['title'] = $slug.' - '.$this->config->item('site_title', 'ion_auth');
		$this->data['pagetitle'] = $slug;
		$this->data['categories'] = $this->themes_model->get_categories();
		$this->data['current'] = 'themes/'.$slug;
		
		$this->data['test'] = "";
		foreach ($this->data['categories'] as $category ) {
			$link = '<a href="'.base_url().'themes/'.$category->slug.'">'.$category->slug."</a> - ";
			$this->data['test'] = $this->data['test'] .$link; 
		}

		if( $slug == FALSE )
			redirect(themes);
		else
		{
			$this->data['category'] = $this->themes_model->get_category(NULL,$slug); // get category details
			$config["base_url"] = base_url() . "themes/type/".$slug;
	        $config["total_rows"] = $this->themes_model->total_count_slug($slug);
	        $config["per_page"] = 9;
	        $config["uri_segment"] = 4;
		    $config['display_pages'] = FALSE;
		    $config['next_link'] = 'Next Page';
			$config['next_tag_open'] = '<span class="button big next">';
			$config['next_tag_close'] = '</span>';
		    $config['prev_link'] = 'Previous Page';
			$config['prev_tag_open'] = '<span class="button big previous">';
			$config['prev_tag_close'] = '</span>';
			$config['last_link'] = '';
			$config['first_link'] = '';
	        $this->pagination->initialize($config);
		    $this->data['paginglinks'] = $this->pagination->create_links();

		    $page = ($this->uri->segment(4)) ? $this->uri->segment(4) : 0;
	        $this->data["posts"] = $this->themes_model->
	            get_category_theme($slug, $config["per_page"], $page);
		}
		
		$this->render('blog/themes','public_master');
	}
}

/* End of file blog.php */
/* Location: ./application/controllers/blog.php */