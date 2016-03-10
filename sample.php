<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {
	 private $data = array();
    /**
     * Index Page for this controller.
     *
     */
    public function __construct() {
        parent::__construct();
        $this->load->helper(array('form','url','email','sessionauth'));
        $this->load->library(array('form_validation','session','pagination','app/paginationlib'));
        $this->load->model(array('user_login_model','admin_model'));
		checkSessionsa();
		$this->data['user_id'] 			= $this->session->userdata('user_id');
		$this->data['user_role'] 		= $this->session->userdata('user_role');
		$this->data['user_name'] 		= $this->session->userdata('user_name');
		$this->data['email_id'] 		= $this->session->userdata('email_id');
		$this->data['phone'] 			= $this->session->userdata('phone');
    }
    public function index() {
        redirect('user_login/login', '');	
    }
    /*
     * 
     * Dashboard function
     * 
     */
	public function dashboard() 
	{
		$data = $this->data;
        $data['title'] = 'Admin dashboard';
		$data['menu'] = $this->load->view('template/admin/menu', $data, TRUE);
        $this->load->view('template/admin/header', $data);
        $this->load->view('admin/dashboard');
        $this->load->view('template/admin/footer');
    }
	public function changepassword() {
		$this->form_validation->set_rules('oldpassword', 'Old Password', 'trim|required|min_length[6]');
		$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
        $this->form_validation->set_rules('cpassword', 'Confirm Password', 'required|matches[password]');
		$data = $this->data;
		$data['title'] = 'Change password';
		if($this->form_validation->run() == FALSE) {
		} 
		else 
		{
			if($this->input->post()) {
				$inArr 		= array();
				$user_id 	= $this->session->userdata('user_id');
				$user_name 	= $this->session->userdata('user_name');
				
				$udata 		= $this->user_login_model->userDetailsRole($user_name);
				$mdpassold	= md5($this->input->post('oldpassword'));
				
				if($mdpassold==$udata[0]->password)
				{
					$inArr['password'] = md5($this->input->post('password'));
					$this->db->trans_start();
					$this->user_login_model->updateLoginDetails($user_id, $inArr);
					$this->db->trans_complete();
					if ($this->db->trans_status()) {
						$this->session->set_flashdata('success', UPDATE_RECORD_MESSAGE);
						redirect('admin/changepassword', '');
					}
				}
				else
				{
					$this->session->set_flashdata('error', OLDPASS_MATCH_FAIL_MESSAGE);
					redirect('admin/changepassword', '');
				}
			}
		}
		$data['menu'] = $this->load->view('template/admin/menu', $data, TRUE);
		$this->load->view('template/admin/header', $data);
		$this->load->view('admin/changepassword');
		$this->load->view('template/admin/footer');
    }
// Add User
	public function addusers() {
		$data = $this->data;
		$data['title'] 			= 'Add Users';
		$this->form_validation->set_rules('user_name', 'User Name', 'trim|required');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('first_name', 'First Name', 'trim|required|max_length[50]');
		$this->form_validation->set_rules('last_name', 'Last Name', 'trim|required|max_length[50]');
		$this->form_validation->set_rules('contact', 'Phone No.', 'trim|required|numeric');
		$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
        $this->form_validation->set_rules('cpassword', 'Confirm Password', 'required|matches[password]');
		if($this->input->post())
		{
			if($this->form_validation->run() === FALSE) 
			{
				$data['title'] = 'Add User';
				$data['validation_error'] = true;
			}
			else 
			{
				if($this->admin_model->addUsers()){
				$this->session->set_flashdata('success', ADD_RECORD_MESSAGE);
				redirect('admin/listusers', '');
			}	
			}
		}
		$data['menu'] = $this->load->view('template/admin/menu', $data, TRUE);
		$this->load->view('template/admin/header', $data);
		$this->load->view('admin/addusers');
		$this->load->view('template/admin/footer');
    }
// List User	
	public function listusers(){
	    $data = $this->data;
		$data['title'] 			= 'List Users';
	    $pagingConfig   = $this->paginationlib->initPagination("/admin/listusers",$this->admin_model->get_count_clients()); 
		$page 			= ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
		$data['users'] = $this->admin_model->get_client_by_range($pagingConfig["per_page"], $page);
		$data["links"] =  $this->pagination->create_links();
		$data['menu'] = $this->load->view('template/admin/menu', $data, TRUE);
		$this->load->view('template/admin/header', $data);
		$this->load->view('admin/listusers', $data);
		$this->load->view('template/admin/footer');
	} 
	public function removeclient($id=null){
		if(isset($id)){
			$inArr['remove_status'] = 1;
			if($this->user_login_model->updateLoginDetails($id, $inArr)){
				$this->session->set_flashdata('success', REMOVE_STATUS_MESSAGE);
				redirect('admin/addclient', '');
			}
			else{
				$this->session->set_flashdata('error', REMOVE_STATUS_FAIL_MESSAGE);
				redirect('admin/addclient', '');
			}
		}
	}
//Edit user
	function edit_user($user_id = null) {
		$data = $this->data;
        $data['title'] 		= 'Edit User';
		 if($user_id)
		 { 
			$data['user_data'] = $this->admin_model->edit_user($user_id);
			$data['menu'] = $this->load->view('template/admin/menu', $data, TRUE);
			$this->load->view('template/admin/header', $data);
			$this->load->view('admin/edituser', $data);
			$this->load->view('template/admin/footer');
		 }
		 else
		 {
			 $this->session->set_flashdata('error', 'existing id does not match');
				redirect('admin/listusers', ''); 
		 }
	}
	function update_user($user_id) {
		$updatedata = $this->data;
		$updatedata['title'] 			= 'Update User';
		$this->form_validation->set_rules('user_name', 'User Name', 'trim|required');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
        $this->form_validation->set_rules('first_name', 'First Name', 'trim|required|max_length[50]');
		$this->form_validation->set_rules('last_name', 'Last Name', 'trim|required|max_length[50]');
		$this->form_validation->set_rules('contact', 'Phone No.', 'trim|required|numeric');
		$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
        $this->form_validation->set_rules('cpassword', 'Confirm Password', 'required|matches[password]');
		if($this->input->post())
		{
			if($this->form_validation->run() === FALSE) 
			{
				$updatedata['title'] = 'Update User';
				$updatedata['validation_error'] = true;
			}
			else 
			{
			//print_r($this->input->post());die;
				if($this->admin_model->update_user($user_id)){
				$this->session->set_flashdata('success', UPDATE_RECORD_MESSAGE);
					redirect('admin/listusers', '');
					}
				else
					{
					echo "not set";	
					}			
			}
		}
		$updatedata['menu'] = $this->load->view('template/admin/menu', $updatedata, TRUE);
		$this->load->view('template/admin/header', $updatedata);
		$this->load->view('admin/listusers');
		$this->load->view('template/admin/footer');		
	}
//Delete user
public function delete_user($user_id) {   
    $this->load->model("Admin_model");
    $this->Admin_model->did_delete_row($user_id);
    }
// -------------------------Customer section-----------------------
// Add Customer
	public function addcustomers() {
		$data = $this->data;
		$data['title'] 			= 'Add Customers';
		$this->form_validation->set_rules('first_name', 'First Name', 'trim|required');
		$this->form_validation->set_rules('last_name', 'Last Name', 'trim|required');
		if($this->input->post())
		{
			if($this->form_validation->run() === FALSE) 
			{
				$data['title'] = 'Add Customers';
				$data['validation_error'] = true;
			}
			else 
			{
			if($this->admin_model->addcustomers()){
				
			$this->session->set_flashdata('success', ADD_RECORD_MESSAGE);
				redirect('admin/listcustomers', '');
			}	
			}
		}		
		$data['menu'] = $this->load->view('template/admin/menu', $data, TRUE);
		$this->load->view('template/admin/header', $data);
		$this->load->view('admin/addcustomers');
		$this->load->view('template/admin/footer');
    }
// List Customer
     public function listcustomers(){
	    $data = $this->data; 
	    $data['title'] = 'List Customers';
	    $pagingConfig   = $this->paginationlib->initPagination("/admin/listcustomers",$this->admin_model->get_count_customers()); 
		$page 			= ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
		$data['customers'] = $this->admin_model->get_customers_by_range($pagingConfig["per_page"], $page);
	    $data["links"] =  $this->pagination->create_links();
		$data['menu'] = $this->load->view('template/admin/menu', $data, TRUE);
		$this->load->view('template/admin/header', $data);
		$this->load->view('admin/listcustomers', $data);
		$this->load->view('template/admin/footer');
	} 
// Edit Customer

	public	function edit_customer($id = null) {
		$data = $this->data;
        $data['title'] 		= 'Edit Customer';
		 if($id)
		 { 
			$data['customer_data'] = $this->admin_model->edit_customer($id);
			$data['menu'] = $this->load->view('template/admin/menu', $data, TRUE);
			$this->load->view('template/admin/header', $data);
			$this->load->view('admin/editcustomer', $data);
			$this->load->view('template/admin/footer');
		 }
		 else
		 {
			 $this->session->set_flashdata('error', 'existing id does not match');
				redirect('admin/listcustomers', ''); 
		 }
	}	
	
	function update_customer($id) {
		$updatedata = $this->data;
		$updatedata['title'] 			= 'Update Customer';
		$this->form_validation->set_rules('first_name', 'First Name', 'trim|required');
		$this->form_validation->set_rules('last_name', 'Last Name', 'trim|required');
		if($this->input->post())
		{
			if($this->form_validation->run() === FALSE) 
			{
				$updatedata['title'] = 'Update Customers';
				$updatedata['validation_error'] = true;
			}
			else 
			{
			if($this->admin_model->update_customer($id)){
				
			$this->session->set_flashdata('success', ADD_RECORD_MESSAGE);
				redirect('admin/listcustomers', '');
			}	
			}
		}		
		$updatedata['menu'] = $this->load->view('template/admin/menu', $updatedata, TRUE);
		$this->load->view('template/admin/header', $updatedata);
		$this->load->view('admin/editcustomers');
		$this->load->view('template/admin/footer');

	}
// Delete Customer 
 public function deletecustomer($id) {   
		$this->load->model("Admin_model");
		$this->Admin_model->customer_delete_row($id);
		redirect($_SERVER['HTTP_REFERER']);
				
 }
 
 public function searchcustomer() {
	 
		$data = $this->data;
		$data['title'] 			= 'Search Customer';
		$search = $this->input->get('search');
		
		if($search){
			
				$customer_data = $this->admin_model->search_customer($search);
				$data['customer_data'] = $customer_data;
				$data['visit_data'] = $this->admin_model->visit_data($customer_data['id']);
				$data['menu'] = $this->load->view('template/admin/menu', $data, TRUE);
				$this->load->view('template/admin/header', $data);
				$this->load->view('admin/serachcustomer',$data);
				$this->load->view('template/admin/footer');		
			}
			else
				
			{
				$this->session->set_flashdata('No', RECORD_MESSAGE);
				redirect($_SERVER['HTTP_REFERER']);
				
			}
	}
	
	public function ajexsearch(){
		
				/**/
				if($this->input->post('first_name'))
					{
					//echo $this->input->post('first_name');
					$data['ajax_data'] = $this->admin_model->ajax_data($this->input->post('first_name'));
					
					$html = $this->load->view('admin/ajaxdata', $data, TRUE);
				     echo $html;
					/*	$first_name = $dbConnection->real_escape_string($_POST['first_name']);
						$sqlCountries="SELECT * FROM customers WHERE fname LIKE '%$first_name%'";
						$resCountries=$dbConnection->query($sqlCountries);
				 
						if($resCountries === false) {
							trigger_error('Error: ' . $dbConnection->error, E_USER_ERROR);
						}else{
							$rows_returned = $resCountries->num_rows;
						}
				 
				 
				 if($rows_returned > 0){
							while($rowCountries = $resCountries->fetch_assoc()) 
							{ 
								echo '<div class="show"  id="my_'.$rowCountries['id'].'" align="left"><span class="country_name" >'.$rowCountries['fname'].' '.$rowCountries['lname'].'</span></div>'; 
								
							}
						}else{
							echo '<div class="show" align="left">No matching records.</div>'; 
						}*/
					}
	}
	
}