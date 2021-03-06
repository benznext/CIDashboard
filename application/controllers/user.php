<?php defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller {

	function __construct()
	{
		parent::__construct();
			
		$this->load->database();		
		$this->lang->load('users');
		$this->load->library('Datatables');		
		$this->load->library('table');        
		$this->load->helper(array('url','language'));		
		$this->form_validation->set_error_delimiters(
			$this->config->item('error_start_delimiter', 'template'), 
			$this->config->item('error_end_delimiter', 'template'));	
		
	}

	//redirect if needed, otherwise display the user list
	function index()
	{
		
		$this->data['title'] = "Current Users";
		$this->data['js'] 	 = array('js/user.js');	
		if (!$this->users->logged_in())
		{
			r_direct_login();
		}elseif ($this->users->is_admin()) 
		{			
			$this->session->set_flashdata('message', '' );				
			$this->data['message'] = $this->notification->messages()? $this->notification->messages(): $this->session->flashdata('message');;
			$this->data['main_content'] = 'users/users';
			$this->load->view('users/template', $this->data); 
		}
		else 
		{
			r_direct('dashboard');	
		}
	}
	
	function get_users_groups($id=null)
    {
	
		header('Content-Type: application/json');			
		$groups = $this->users->get_users_groups($id)->result();		
		echo json_encode($groups, true);	
    }
	
	
	
	function datatable()
    {		
        $this->datatables->select('id,email,active,first_name,last_name,phone')
            ->unset_column('id')
			->add_column('actions', get_buttons('$1', 'user'), 'id')
			->add_column('status', user_status('$1', '$1'), 'id, active')
			->add_column('groups', '$1', 'id')
            ->from('users');			
        echo $this->datatables->generate();
    }

	
	//create a new user
	function add()
	{
		if (!$this->users->logged_in())
		{		
			r_direct_login();
		}
		elseif ($this->users->is_admin())
		{
			$method = $this->input->server('REQUEST_METHOD');
			$groups=$this->users->groups()->result_array();
			if($method == 'POST'){
				header('Content-Type: application/json');
				$tables = $this->config->item('tables','users');
				//validate form input
				$this->form_validation->set_rules('first_name', $this->lang->line('create_user_validation_fname_label'), 'required');
				$this->form_validation->set_rules('last_name', $this->lang->line('create_user_validation_lname_label'), 'required');
				$this->form_validation->set_rules('email', $this->lang->line('create_user_validation_email_label'), 'required|valid_email|is_unique['.$tables['users'].'.email]');
				$this->form_validation->set_rules('phone', $this->lang->line('create_user_validation_phone_label'), 'required');
				$this->form_validation->set_rules('company', $this->lang->line('create_user_validation_company_label'), 'required');
				$this->form_validation->set_rules('password', $this->lang->line('create_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'users') . ']|max_length[' . $this->config->item('max_password_length', 'users') . ']|matches[password_confirm]');
				$this->form_validation->set_rules('password_confirm', $this->lang->line('create_user_validation_password_confirm_label'), 'required');				
				
				if ($this->form_validation->run() == true)
				{
					$username = strtolower($this->input->post('first_name')) . ' ' . strtolower($this->input->post('last_name'));
					$email    = strtolower($this->input->post('email'));
					$password = $this->input->post('password');

					$additional_data = array(						
						'first_name' => $this->input->post('first_name'),
						'last_name'  => $this->input->post('last_name'),
						'company'    => $this->input->post('company'),
						'phone'      => $this->input->post('phone'),
						'profile_pic' => $this->input->post('profile_pic'),
					);
					
					$id = $this->users->register($username, $password, $email, $additional_data);
					if ($id)
					{
						$message = $this->notification->messages();
						// Only allow updating groups if user is admin
						if ($this->users->is_admin())
						{
							//Update the groups user belongs to
							$groupData = $this->input->post('groups');
							if (isset($groupData) && !empty($groupData)) {
								foreach ($groupData as $grp) {
									$this->users->add_to_group($grp, $id);
								}
							}
						}						
						echo json_encode(array( 
							'response' => 'success', 
							'message' => $message, 
							'redirect' => base_url('user')
							), 
						true);	
						
					}else{
						$message =  $this->notification->errors();
						echo json_encode(array( 
							'response' => 'danger', 
							'message' => $message ), 
						true);						
					}					
				}
				else{
					$message = validation_errors();	
					echo json_encode(array( 
						'response' => 'danger', 
						'message' => $message ), 
					true);						
				}
			}else{
				//display the create user form
				//set the flash data error message if there is one
				$this->data['message'] = (validation_errors() ? validation_errors() : ($this->notification->errors() ? $this->notification->errors() : $this->session->flashdata('message')));
				$this->data['groups'] = $groups;
				$this->data['first_name'] = array(
					'name'  => 'first_name',
					'id'    => 'first_name',
					'class'    => 'form-control',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('first_name'),
				);
				$this->data['last_name'] = array(
					'name'  => 'last_name',
					'id'    => 'last_name',
					'class'    => 'form-control',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('last_name'),
				);
				$this->data['email'] = array(
					'name'  => 'email',
					'id'    => 'email',
					'class'    => 'form-control',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('email'),
				);
				$this->data['company'] = array(
					'name'  => 'company',
					'id'    => 'company',
					'class'    => 'form-control',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('company'),
				);
				$this->data['phone'] = array(
					'name'  => 'phone',
					'id'    => 'phone',
					'class'    => 'form-control',
					'type'  => 'text',
					'value' => $this->form_validation->set_value('phone'),
				);
				$this->data['password'] = array(
					'name'  => 'password',
					'id'    => 'password',
					'class'    => 'form-control',
					'type'  => 'password',
					'value' => $this->form_validation->set_value('password'),
				);
				$this->data['password_confirm'] = array(
					'name'  => 'password_confirm',
					'id'    => 'password_confirm',
					'class'    => 'form-control',
					'type'  => 'password',
					'value' => $this->form_validation->set_value('password_confirm'),
				);	
				$this->data['title'] = 'Add New User';
				$this->data['main_content'] = 'users/add';
				$this->load->view('users/template', $this->data);			
			}
		}
		else 
		{
			r_direct('dashboard');
		}
		
		
		
		
		
	}

	//edit a user
	function edit($id=null)
	{
	
		if (!$this->users->logged_in())
		{		
			r_direct_login();
		}
		elseif ($this->users->is_admin() && $id)
		{
			$method = $this->input->server('REQUEST_METHOD');
			$user = $this->users->user($id)->row();
			$groups=$this->users->groups()->result_array();
			$currentGroups = $this->users->get_users_groups($id)->result();

			//validate form input
			$this->form_validation->set_rules('first_name', $this->lang->line('edit_user_validation_fname_label'), 'required');
			$this->form_validation->set_rules('last_name', $this->lang->line('edit_user_validation_lname_label'), 'required');
			$this->form_validation->set_rules('phone', $this->lang->line('edit_user_validation_phone_label'), 'required');
			$this->form_validation->set_rules('company', $this->lang->line('edit_user_validation_company_label'), 'required');
			
			if ($method=='POST')
			{
				header('Content-Type: application/json');
				// do we have a valid request?
				if ($this->_valid_csrf_nonce() === FALSE)
				{					
					$this->session->set_flashdata('message', $this->lang->line('error_csrf') );	
					echo json_encode(array( 
						'response' => 'info', 
						'message' => $this->lang->line('error_csrf'),
						'redirect' => base_url('user')
						), 
					true);	
					
				}else{

					//update the password if it was posted
					if ($this->input->post('password'))
					{
						$this->form_validation->set_rules('password', $this->lang->line('edit_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'users') . ']|max_length[' . $this->config->item('max_password_length', 'users') . ']|matches[password_confirm]');
						$this->form_validation->set_rules('password_confirm', $this->lang->line('edit_user_validation_password_confirm_label'), 'required');
					}

					if ($this->form_validation->run() === TRUE)
					{
						$data = array(
							'first_name' => $this->input->post('first_name'),
							'last_name'  => $this->input->post('last_name'),
							'company'    => $this->input->post('company'),
							'phone'      => $this->input->post('phone'),
						);

						//update the password if it was posted
						if ($this->input->post('password'))
						{
							$data['password'] = $this->input->post('password');
						}


						// Only allow updating groups if user is admin
						if ($this->users->is_admin())
						{
							//Update the groups user belongs to
							$groupData = $this->input->post('groups');

							if (isset($groupData) && !empty($groupData)) {

								$this->users->remove_from_group('', $id);

								foreach ($groupData as $grp) {
									$this->users->add_to_group($grp, $id);
								}

							}
						}

					//check to see if we are updating the user
					   if($this->users->update($user->id, $data))
						{
							$this->session->set_flashdata('message', $this->notification->messages() );						
							$r_direct = $this->users->is_admin()? 'user' : 'dashboard';
							echo json_encode(array( 
								'response' => 'success', 
								'message' => $this->notification->messages(), 
								'redirect' => base_url($r_direct)
								), 
							true);	

						}
						else
						{
							//redirect them back to the admin page if admin, or to the base url if non admin
							$this->session->set_flashdata('message', $this->notification->errors() );
							echo json_encode(array( 
								'response' => 'danger', 
								'message' => $this->notification->errors(),
								'redirect' => base_url('user/edit/'.$id)
								), 
							true);	

						}

					}else{
						$this->session->set_flashdata('message', validation_errors() ? validation_errors() : $this->notification->errors() );	
						echo json_encode(array( 
							'response' => 'danger', 
							'message' => validation_errors(),
							'redirect' => base_url('user/edit/'.$id)
							), 
						true);					
					}
				}
			}else{

				//display the edit user form
				$this->data['csrf'] = $this->_get_csrf_nonce();

				//set the flash data error message if there is one
				$this->data['message'] = (validation_errors() ? validation_errors() : ($this->notification->errors() ? $this->notification->errors() : $this->notification->messages()));

				//pass the user to the view
				$this->data['user'] = $user;
				$this->data['groups'] = $groups;
				$this->data['currentGroups'] = $currentGroups;

				$this->data['first_name'] = array(
					'name'  => 'first_name',
					'id'    => 'first_name',
					'type'  => 'text',
					'class'  => 'form-control ',
					'value' => $this->form_validation->set_value('first_name', $user->first_name),
				);
				$this->data['last_name'] = array(
					'name'  => 'last_name',
					'id'    => 'last_name',
					'type'  => 'text',
					'class'  => 'form-control ',
					'value' => $this->form_validation->set_value('last_name', $user->last_name),
				);
				$this->data['company'] = array(
					'name'  => 'company',
					'id'    => 'company',
					'type'  => 'text',
					'class'  => 'form-control ',
					'value' => $this->form_validation->set_value('company', $user->company),
				);
				$this->data['phone'] = array(
					'name'  => 'phone',
					'id'    => 'phone',
					'type'  => 'text',
					'class'  => 'form-control ',
					'value' => $this->form_validation->set_value('phone', $user->phone),
				);
				$this->data['password'] = array(
					'name' => 'password',
					'id'   => 'password',
					'class'  => 'form-control ',
					'type' => 'password'
				);
				$this->data['password_confirm'] = array(
					'name' => 'password_confirm',
					'id'   => 'password_confirm',
					'class'  => 'form-control ',
					'type' => 'password'
				);
				$this->data['title'] = 'Edit User';		
				$this->data['main_content'] = 'users/edit';
				$this->load->view('users/template', $this->data);
			}
		}
		else 
		{
			r_direct('dashboard');
		}
		
		
		
	}
	
	
	//create a new user
	function profile_pic($id=null)
	{
	
		if (!$this->users->logged_in())
		{		
			r_direct_login();
		}
		elseif ($id)
		{
			$method = $this->input->server('REQUEST_METHOD');
			$user = $this->users->user($id)->row();
			
			$config['upload_path'] = './uploads/';
			$config['allowed_types'] = 'gif|jpg|png';
			//$config['max_size']	= '100';
			//$config['max_width']  = '1024';
			//$config['max_height']  = '768';
			$this->load->library('upload', $config);		
			if($method == 'POST'){				
				
				header('Content-Type: application/json');
				if ( ! $this->upload->do_upload('file'))
				//if ( ! $this->upload->do_multi_upload('file'))
				{					
					$this->notification->set_error($this->upload->display_errors());
					$message = $this->notification->errors();
					
					echo json_encode(array( 
					'response' => 'danger', 
					'message' => $message
					), true);
				}
				else
				{
					//$file_data = $this->upload->get_multi_upload_data();
					$file_data = $this->upload->data();
					$data = array(
							'profile_pic' => $config['upload_path'] . element('file_name', $file_data)
						);
					if($this->users->update($user->id, $data))
					{
						$this->session->set_flashdata('message', $this->notification->messages() );						
						$r_direct = $this->users->is_admin()? 'user' : 'dashboard';
						echo json_encode(array( 
							'response' => 'success', 
							'message' => $this->notification->messages(), 
							'redirect' => base_url($r_direct)
							), 
						true);	

					}
					else
					{
						//redirect them back to the admin page if admin, or to the base url if non admin
						$this->session->set_flashdata('message', $this->notification->errors() );
						echo json_encode(array( 
							'response' => 'danger', 
							'message' => $this->notification->errors(),
							'redirect' => base_url('user/profile/'.$id)
							), 
						true);	

					}
						
				}
				
			}else{
				$this->data['message'] = $this->notification->errors() ? $this->notification->errors() : ($this->notification->messages()? $this->notification->messages() : $this->session->flashdata('message'));			
				$this->data['title'] = 'Add New Media';
				$this->data['main_content'] = 'media/add';
				$this->load->view('media/template', $this->data);			
			}
		}
		else 
		{
			r_direct('dashboard');
		}
		
	}
	
	
	//edit a user
	function profile()
	{
		$id = $this->users->get_user_id();
		if (!$this->users->logged_in())
		{		
			r_direct_login();
		}
		elseif ($id)
		{
			$method = $this->input->server('REQUEST_METHOD');
			$user = $this->users->user($id)->row();
			$groups=$this->users->groups()->result_array();
			$currentGroups = $this->users->get_users_groups($id)->result();

			//validate form input
			$this->form_validation->set_rules('first_name', $this->lang->line('edit_user_validation_fname_label'), 'required');
			$this->form_validation->set_rules('last_name', $this->lang->line('edit_user_validation_lname_label'), 'required');
			$this->form_validation->set_rules('phone', $this->lang->line('edit_user_validation_phone_label'), 'required');
			$this->form_validation->set_rules('company', $this->lang->line('edit_user_validation_company_label'), 'required');
			
			if ($method=='POST')
			{
								
				header('Content-Type: application/json');
				// do we have a valid request?
				if ($this->_valid_csrf_nonce() === FALSE)
				{					
					$this->session->set_flashdata('message', $this->lang->line('error_csrf') );	
					echo json_encode(array( 
						'response' => 'info', 
						'message' => $this->lang->line('error_csrf'),
						'redirect' => base_url('user')
						), 
					true);	
					
				}else{

					//update the password if it was posted
					if ($this->input->post('password'))
					{
						$this->form_validation->set_rules('password', $this->lang->line('edit_user_validation_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'users') . ']|max_length[' . $this->config->item('max_password_length', 'users') . ']|matches[password_confirm]');
						$this->form_validation->set_rules('password_confirm', $this->lang->line('edit_user_validation_password_confirm_label'), 'required');
					}
					
					if ($this->form_validation->run() === TRUE)
					{
						$data = array(
							'first_name' => $this->input->post('first_name'),
							'last_name'  => $this->input->post('last_name'),
							'company'    => $this->input->post('company'),
							'phone'      => $this->input->post('phone'),
						);

						//update the password if it was posted
						if ($this->input->post('password'))
						{
							$data['password'] = $this->input->post('password');
						}


						// Only allow updating groups if user is admin
						if ($this->users->is_admin())
						{
							//Update the groups user belongs to
							$groupData = $this->input->post('groups');

							if (isset($groupData) && !empty($groupData)) {

								$this->users->remove_from_group('', $id);

								foreach ($groupData as $grp) {
									$this->users->add_to_group($grp, $id);
								}

							}
						}

					//check to see if we are updating the user
					   if($this->users->update($user->id, $data))
						{
							$this->session->set_flashdata('message', $this->notification->messages() );						
							$r_direct = $this->users->is_admin()? 'user' : 'dashboard';
							echo json_encode(array( 
								'response' => 'success', 
								'message' => $this->notification->messages()
								//'redirect' => base_url($r_direct)
								), 
							true);	

						}
						else
						{
							//redirect them back to the admin page if admin, or to the base url if non admin
							$this->session->set_flashdata('message', $this->notification->errors() );
							echo json_encode(array( 
								'response' => 'danger', 
								'message' => $this->notification->errors(),
								'redirect' => base_url('user/profile/'.$id)
								), 
							true);	

						}

					}else{
						$this->session->set_flashdata('message', validation_errors() ? validation_errors() : $this->notification->errors() );	
						echo json_encode(array( 
							'response' => 'danger', 
							'message' => $this->notification->errors(),
							//'redirect' => base_url('user/profile/'.$id)
							), 
						true);					
					}
				}
			}else{

				//display the edit user form
				$this->data['csrf'] = $this->_get_csrf_nonce();

				//set the flash data error message if there is one
				$this->data['message'] = (validation_errors() ? validation_errors() : ($this->notification->errors() ? $this->notification->errors() : $this->notification->messages()));

				//pass the user to the view
				$this->data['user'] = $user;
				$this->data['groups'] = $groups;
				$this->data['currentGroups'] = $currentGroups;
	
				$this->data['profile_pic'] = array(
					'name'  => 'profile_pic',
					'id'    => 'profile_pic',
					'type'  => 'file',
					'class'  => 'form-control ',
					'value' => $this->form_validation->set_value('profile_pic', $user->profile_pic),
				);
				$this->data['first_name'] = array(
					'name'  => 'first_name',
					'id'    => 'first_name',
					'type'  => 'text',
					'class'  => 'form-control ',
					'value' => $this->form_validation->set_value('first_name', $user->first_name),
				);
				$this->data['last_name'] = array(
					'name'  => 'last_name',
					'id'    => 'last_name',
					'type'  => 'text',
					'class'  => 'form-control ',
					'value' => $this->form_validation->set_value('last_name', $user->last_name),
				);
				$this->data['company'] = array(
					'name'  => 'company',
					'id'    => 'company',
					'type'  => 'text',
					'class'  => 'form-control ',
					'value' => $this->form_validation->set_value('company', $user->company),
				);
				$this->data['phone'] = array(
					'name'  => 'phone',
					'id'    => 'phone',
					'type'  => 'text',
					'class'  => 'form-control ',
					'value' => $this->form_validation->set_value('phone', $user->phone),
				);
				$this->data['password'] = array(
					'name' => 'password',
					'id'   => 'password',
					'class'  => 'form-control ',
					'type' => 'password'
				);
				$this->data['password_confirm'] = array(
					'name' => 'password_confirm',
					'id'   => 'password_confirm',
					'class'  => 'form-control ',
					'type' => 'password'
				);
				$this->data['title'] = 'User Profile';		
				$this->data['main_content'] = 'users/profile';
				$this->load->view('users/template', $this->data);
			}
		}
		else 
		{
			r_direct('dashboard');
		}
		
		
		
	}
	
	
	function delete($id=null)
	{
		
		if (!$this->users->logged_in())
		{
			//redirect them to the login page
			r_direct_login();
		}		
		elseif ($this->users->is_admin() && $id)
		{			
			$user = $this->users->delete_user($id);									
			$message = ($this->notification->errors()? $this->notification->errors() : $this->notification->messages());				
			header('Content-Type: application/json');	
			if($user)
			echo json_encode(array( 
				'response' => 'success', 
				'message' => $message,
				'redirect' => base_url('user'),
				), 
			true);				
			else
			echo json_encode(array( 
				'response' => 'danger', 
				'message' => $message,
				'redirect' => base_url('user'),
				), 
			true);	
		}
		else
		{					
			r_direct('dashboard');
		}	
	}
	
	
	
	
	function user()
	{
		if (!$this->users->logged_in())
		{
			//redirect them to the login page
			r_direct_login();
		}
		elseif ($this->users->is_admin()) //remove this elseif if you want to enable this for non-admins
		{
			
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			
			$groups=$this->users->groups()->result_array();
			$currentGroups = $this->users->get_users_groups()->result();
			$this->data['groups'] = $groups;
			$this->data['currentGroups'] = $currentGroups;		
			$this->data['main_content'] = 'users/user';
			$this->load->view('users/template', $this->data); 
		}
		else
		{
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			
			$groups=$this->users->groups()->result_array();
			$currentGroups = $this->users->get_users_groups()->result();
			$this->data['groups'] = $groups;
			$this->data['currentGroups'] = $currentGroups;		
			$this->data['main_content'] = 'users/user';
			$this->load->view('users/template', $this->data);  
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	

	//log the user in
	function login()
	{
		$this->data['title'] = "Login";

		//validate form input
		$this->form_validation->set_rules('identity', 'Identity', 'required');
		$this->form_validation->set_rules('password', 'Password', 'required');
		if ($this->form_validation->run() == true)
		{
			//check to see if the user is logging in
			//check for "remember me"
			$remember = (bool) $this->input->post('remember');

			
			if ($this->users_model->login($this->input->post('identity'), $this->input->post('password'), $remember))
			{
				//if the login is successful
				//redirect them back to the home page				
				$this->session->set_flashdata('message', $this->notification->errors());	
				r_direct('dashboard');
			}
			else
			{
				//if the login was un-successful
				//redirect them back to the login page
				$this->session->set_flashdata('message', $this->notification->errors());
				r_direct_login();
			}
		}elseif ($this->users->logged_in()){
			$this->session->set_flashdata('message', $this->notification->errors());
			r_direct('dashboard');	
		}else{
			//the user is not logging in so display the login page
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

			$this->data['identity'] = array('name' => 'identity',
				'id' => 'identity',
				'type' => 'text',
				'value' => $this->form_validation->set_value('identity'),
			);
			$this->data['password'] = array('name' => 'password',
				'id' => 'password',
				'type' => 'password',
			);
			$this->_render_page('users/login', $this->data);
		}
	}
	
	
	
	//log the user out
	function logout()
	{
		$this->data['title'] = "Logout";
		//log the user out
		$logout = $this->users->logout();
		//redirect them to the login page
		$this->session->set_flashdata('message', $this->notification->messages());
		r_direct_login();
	}

	//change password
	function change_password()
	{
		$this->form_validation->set_rules('old', $this->lang->line('change_password_validation_old_password_label'), 'required');
		$this->form_validation->set_rules('new', $this->lang->line('change_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'users') . ']|max_length[' . $this->config->item('max_password_length', 'users') . ']|matches[new_confirm]');
		$this->form_validation->set_rules('new_confirm', $this->lang->line('change_password_validation_new_password_confirm_label'), 'required');

		if (!$this->users->logged_in())
		{
			r_direct('dashboard');
		}

		$user = $this->users->user()->row();

		if ($this->form_validation->run() == false)
		{
			//display the form
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

			$this->data['min_password_length'] = $this->config->item('min_password_length', 'users');
			$this->data['old_password'] = array(
				'name' => 'old',
				'id'   => 'old',
				'type' => 'password',
			);
			$this->data['new_password'] = array(
				'name' => 'new',
				'id'   => 'new',
				'type' => 'password',
				'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
			);
			$this->data['new_password_confirm'] = array(
				'name' => 'new_confirm',
				'id'   => 'new_confirm',
				'type' => 'password',
				'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
			);
			$this->data['user_id'] = array(
				'name'  => 'user_id',
				'id'    => 'user_id',
				'type'  => 'hidden',
				'value' => $user->id,
			);

			//render
			//$this->_render_page('user/change_password', $this->data);
			$this->data['main_content'] = 'users/change_password';
			$this->load->view('users/template', $this->data); 
			
		}
		else
		{
			$identity = $this->session->userdata('identity');

			$change = $this->users->change_password($identity, $this->input->post('old'), $this->input->post('new'));

			if ($change)
			{
				//if the password was successfully changed
				$this->session->set_flashdata('message', $this->notification->messages());
				$this->logout();
			}
			else
			{
				$this->session->set_flashdata('message', $this->notification->errors());
				
				
				r_direct('user/change_password');	
			}
		}
	}

	//forgot password
	function forgot_password()
	{
		//setting validation rules by checking wheather identity is username or email
		if($this->config->item('identity', 'users') == 'username' )
		{
		   $this->form_validation->set_rules('email', $this->lang->line('forgot_password_username_identity_label'), 'required');
		}
		else
		{
		   $this->form_validation->set_rules('email', $this->lang->line('forgot_password_validation_email_label'), 'required|valid_email');
		}


		if ($this->form_validation->run() == false)
		{
			//setup the input
			$this->data['email'] = array('name' => 'email',
				'id' => 'email',
			);

			if ( $this->config->item('identity', 'users') == 'username' ){
				$this->data['identity_label'] = $this->lang->line('forgot_password_username_identity_label');
			}
			else
			{
				$this->data['identity_label'] = $this->lang->line('forgot_password_email_identity_label');
			}

			//set any errors and display the form
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
			$this->_render_page('users/forgot_password', $this->data);		
			
		}
		else
		{
			// get identity from username or email
			if ( $this->config->item('identity', 'users') == 'username' ){
				$identity = $this->users->where('username', strtolower($this->input->post('email')))->users()->row();
			}
			else
			{
				$identity = $this->users->where('email', strtolower($this->input->post('email')))->users()->row();
			}
	            	if(empty($identity)) {

	            		if($this->config->item('identity', 'users') == 'username')
		            	{
                                   $this->notification->set_message('forgot_password_username_not_found');
		            	}
		            	else
		            	{
		            	   $this->notification->set_message('forgot_password_email_not_found');
		            	}

		                $this->session->set_flashdata('message', $this->notification->messages());
							
						r_direct('user/forgot_password');
            		}

			//run the forgotten password method to email an activation code to the user
			$forgotten = $this->users->forgotten_password($identity->{$this->config->item('identity', 'users')});

			if ($forgotten)
			{
				//if there were no errors
				$this->session->set_flashdata('message', $this->notification->messages());
				r_direct();		 //we should display a confirmation page here instead of the login page
			}
			else
			{
				$this->session->set_flashdata('message', $this->notification->errors());
				r_direct('user/forgot_password');
			}
		}
	}

	//reset password - final step for forgotten password
	public function reset_password($code = NULL)
	{
		if (!$code)
		{
			show_404();
		}

		$user = $this->users->forgotten_password_check($code);

		if ($user)
		{
			//if the code is valid then display the password reset form

			$this->form_validation->set_rules('new', $this->lang->line('reset_password_validation_new_password_label'), 'required|min_length[' . $this->config->item('min_password_length', 'users') . ']|max_length[' . $this->config->item('max_password_length', 'users') . ']|matches[new_confirm]');
			$this->form_validation->set_rules('new_confirm', $this->lang->line('reset_password_validation_new_password_confirm_label'), 'required');

			if ($this->form_validation->run() == false)
			{
				//display the form

				//set the flash data error message if there is one
				$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');

				$this->data['min_password_length'] = $this->config->item('min_password_length', 'users');
				$this->data['new_password'] = array(
					'name' => 'new',
					'id'   => 'new',
				'type' => 'password',
					'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
				);
				$this->data['new_password_confirm'] = array(
					'name' => 'new_confirm',
					'id'   => 'new_confirm',
					'type' => 'password',
					'pattern' => '^.{'.$this->data['min_password_length'].'}.*$',
				);
				$this->data['user_id'] = array(
					'name'  => 'user_id',
					'id'    => 'user_id',
					'type'  => 'hidden',
					'value' => $user->id,
				);
				$this->data['csrf'] = $this->_get_csrf_nonce();
				$this->data['code'] = $code;

				//render
				//$this->_render_page('user/reset_password', $this->data);
				$this->data['main_content'] = 'users/reset_password';
				$this->load->view('users/template', $this->data);
			}
			else
			{
				// do we have a valid request?
				if ($this->_valid_csrf_nonce() === FALSE || $user->id != $this->input->post('user_id'))
				{

					//something fishy might be up
					$this->users->clear_forgotten_password_code($code);

					show_error($this->lang->line('error_csrf'));

				}
				else
				{
					// finally change the password
					$identity = $user->{$this->config->item('identity', 'users')};

					$change = $this->users->reset_password($identity, $this->input->post('new'));

					if ($change)
					{
						//if the password was successfully changed
						$this->session->set_flashdata('message', $this->notification->messages());
						$this->logout();
					}
					else
					{
						$this->session->set_flashdata('message', $this->notification->errors());
						
						r_direct('user/reset_password');
					}
				}
			}
		}
		else
		{
			//if the code is invalid then send them back to the forgot password page
			$this->session->set_flashdata('message', $this->notification->errors());
			r_direct('user/forgot_password');	
		}
	}


	//activate the user
	function activate($id, $code=false)
	{
		if ($code !== false)
		{
			$activation = $this->users->activate($id, $code);
		}
		else if ($this->users->is_admin())
		{
			$activation = $this->users->activate($id);
		}

		if ($activation)
		{
			//redirect them to the auth page
			$this->session->set_flashdata('message', $this->notification->messages());
			r_direct();	
		}
		else
		{
			//redirect them to the forgot password page
			$this->session->set_flashdata('message', $this->notification->errors());
			r_direct('user/forgot_password');	
		}
	}

	//deactivate the user
	function deactivate($id = NULL)
	{
		if (!$this->users->logged_in() || !$this->users->is_admin())
		{
			//redirect them to the home page because they must be an administrator to view this
			return show_error('You must be an administrator to view this page.');
		}

		$id = (int) $id;

		
		$this->load->library('form_validation');
		$this->form_validation->set_rules('confirm', $this->lang->line('deactivate_validation_confirm_label'), 'required');
		$this->form_validation->set_rules('id', $this->lang->line('deactivate_validation_user_id_label'), 'required|alpha_numeric');

		if ($this->form_validation->run() == FALSE)
		{
			// insert csrf check
			$this->data['csrf'] = $this->_get_csrf_nonce();
			$this->data['user'] = $this->users->user($id)->row();
			$this->data['modal_title'] 	 = lang('deactivate_heading');
			$this->data['modal_content'] = 'users/deactivate';
			echo $this->load->view('template/modal', $this->data); 		
			
		}
		else
		{
			header('Content-Type: application/json');
			// do we really want to deactivate?
			if ($this->input->post('confirm') == 'yes')
			{
				// do we have a valid request?
				if ($this->_valid_csrf_nonce() === FALSE || $id != $this->input->post('id'))
				{
					show_error($this->lang->line('error_csrf'));
				}

				// do we have the right userlevel?
				if ($this->users->logged_in() && $this->users->is_admin())
				{
					$this->users->deactivate($id);
					$message = $this->notification->messages()? $this->notification->messages(): $this->session->flashdata('message');
					echo json_encode( 
						array( 
							'response' => 'success', 
							'message' => $message
						), true);	
				}
			}

			//redirect them back to the auth page
			//r_direct('user');	
		}
		
		
		
		
	}


	function _get_csrf_nonce()
	{
		$this->load->helper('string');
		$key   = random_string('alnum', 8);
		$value = random_string('alnum', 20);
		$this->session->set_flashdata('csrfkey', $key);
		$this->session->set_flashdata('csrfvalue', $value);

		return array($key => $value);
	}

	function _valid_csrf_nonce()
	{
		if ($this->input->post($this->session->flashdata('csrfkey')) !== FALSE &&
			$this->input->post($this->session->flashdata('csrfkey')) == $this->session->flashdata('csrfvalue'))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	function _render_page($view, $data=null, $render=false)
	{

		$this->viewdata = (empty($data)) ? $this->data: $data;

		$view_html = $this->load->view($view, $this->viewdata, $render);

		if (!$render) return $view_html;
	}
	
	
	
	/**
	function users()
	{
		if (!$this->users->logged_in())
		{
			//redirect them to the login page
			redirect('user', 'refresh');
		}
		elseif ($this->users->is_admin()) //remove this elseif if you want to enable this for non-admins
		{
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');	

			//list the users
			$this->data['users'] = $this->users->users()->result();
			foreach ($this->data['users'] as $k => $user)
			{
				$this->data['users'][$k]->groups = $this->users->get_users_groups($user->id)->result();
			}
			
			$this->data['main_content'] = 'users/users';
			$this->data['sidebar'] = 'user/sidebar';
			$this->load->view('users/template', $this->data); 
		}
		else
		{
			//redirect non-admins
			redirect('dashboard/index', 'refresh');
		}
	}

	// create a new group
	function create_department()
	{
		$this->data['title'] = $this->lang->line('create_group_title');

		if (!$this->users->logged_in() || !$this->users->is_admin())
		{
			redirect('user', 'refresh');
		}

		//validate form input
		$this->form_validation->set_rules('group_name', $this->lang->line('create_group_validation_name_label'), 'required|alpha_dash');

		if ($this->form_validation->run() == TRUE)
		{
			$new_group_id = $this->users->create_group($this->input->post('group_name'), $this->input->post('description'));
			if($new_group_id)
			{
				// check to see if we are creating the group
				// redirect them back to the admin page
				$this->session->set_flashdata('message', $this->notification->messages());
				redirect('user/departments', 'location', 301);
			}
		}
		else
		{
			//display the create group form
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors() ? validation_errors() : ($this->notification->errors() ? $this->notification->errors() : $this->session->flashdata('message')));

			$this->data['group_name'] = array(
				'name'  => 'group_name',
				'id'    => 'group_name',
				'class'    => 'form-control',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('group_name'),
			);
			$this->data['description'] = array(
				'name'  => 'description',
				'id'    => 'description',
				'class'    => 'form-control',
				'type'  => 'text',
				'value' => $this->form_validation->set_value('description'),
			);

			//$this->_render_page('user/create_group', $this->data);
			$this->data['main_content'] = 'users/create_department';
			$this->data['sidebar'] = 'user/sidebar';
			$this->load->view('users/template', $this->data);
		}
	}

	//edit a group
	function edit_department($id)
	{
		// bail if no group id given
		if(!$id || empty($id))
		{
			redirect('user/departments', 'location', 301);
		}

		$this->data['title'] = $this->lang->line('edit_group_title');

		if (!$this->users->logged_in() || !$this->users->is_admin())
		{
			redirect('user', 'refresh');
		}

		$group = $this->users->group($id)->row();

		//validate form input
		$this->form_validation->set_rules('group_name', $this->lang->line('edit_group_validation_name_label'), 'required|alpha_dash');

		if (isset($_POST) && !empty($_POST))
		{
			if ($this->form_validation->run() === TRUE)
			{
				$group_update = $this->users->update_group($id, $_POST['group_name'], $_POST['group_description']);

				if($group_update)
				{
					$this->session->set_flashdata('message', $this->lang->line('edit_group_saved'));
				}
				else
				{
					$this->session->set_flashdata('message', $this->notification->errors());
				}
				redirect('user/departments', 'location', 301);
			}
		}

		//set the flash data error message if there is one
		$this->data['message'] = (validation_errors() ? validation_errors() : ($this->notification->errors() ? $this->notification->errors() : $this->session->flashdata('message')));

		//pass the user to the view
		$this->data['group'] = $group;

		$this->data['group_name'] = array(
			'name'  => 'group_name',
			'id'    => 'group_name',
			'class'    => 'form-control',
			'type'  => 'text',
			'value' => $this->form_validation->set_value('group_name', $group->name),
		);
		$this->data['group_description'] = array(
			'name'  => 'group_description',
			'id'    => 'group_description',
			'class'    => 'form-control',
			'type'  => 'text',
			'value' => $this->form_validation->set_value('group_description', $group->description),
		);

		//$this->_render_page('user/edit_group', $this->data);
		
		$this->data['main_content'] = 'users/edit_department';
		$this->data['sidebar'] = 'user/sidebar';
		$this->load->view('users/template', $this->data);
	}
	
	function departments()
	{
		if (!$this->users->logged_in())
		{
			//redirect them to the login page
			redirect('user', 'refresh');
		}
		elseif ($this->users->is_admin()) //remove this elseif if you want to enable this for non-admins
		{
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');	

			//list the groups
			$groups=$this->users->groups()->result_array();
			$this->data['groups'] = $groups;
			$this->data['main_content'] = 'users/departments';
			$this->data['sidebar'] = 'user/sidebar';
			$this->load->view('users/template', $this->data); 
		}
		else
		{
			//redirect non-admins
			redirect('dashboard/index', 'refresh');
		}
	}**/

}
