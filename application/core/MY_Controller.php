<?php

if(!defined('BASEPATH'))
    die;

class MY_Controller extends CI_Controller
{
    private $site_params = [];
    private $system_enum = [];
    
    public $session;
    public $user;
    
    function __construct(){
        parent::__construct();
        
        $this->output->set_header('X-Powered-By: ' . config_item('system_vendor'));
        $this->output->set_header('X-Deadpool: ' . config_item('header_message'));
        
        $this->load->library('SiteEnum', '', 'enum');
        $this->load->library('SiteParams', '', 'setting');
        $this->load->library('SiteTheme', '', 'theme');
        $this->load->library('SiteMenu', '', 'menu');
        $this->load->library('SiteForm', '', 'form');

        $cookie_name = config_item('sess_cookie_name');
        $hash = $this->input->cookie($cookie_name);
        
        if($hash){
            $this->load->model('Usersession_model', 'USession');
            $session = $this->USession->getBy('hash', $hash);
            $this->session = $session;
            
            if($session){
                $this->load->model('User_model', 'User');
                $user = $this->User->get($session->user);
                
                $this->user = $user;
                $this->user->perms = array();
                
                if($user){
                    $this->load->model('User_perms', 'UPerms');
                    $user_perms = $this->UPerms->getBy('user', $user->id, true);
                    $this->user->perms = prop_values($user_perms, 'perms');
                }
            }
        }
        
        if($this->theme->current() == 'admin/')
            $this->lang->load('admin', config_item('language'));
        
        $this->user = (object)array(
            'id' => 1,
            'fullname' => 'Lorem Ipsum',
            'perms' => array()
        );
    }
    
    /**
     * Return to client as ajax respond.
     * @param mixed data The data to return.
     * @param mixed error The error data.
     * @param mixed append Additional data to append to result.
     */
    public function ajax($data, $error=false, $append=null){
        $result = array(
            'data' => $data,
            'error'=> $error
        );
        
        if($append)
            $result = array_merge($result, $append);
        
        $cb = $this->input->get('cb');
        if(!$cb)
            $cb = $this->input->get('callback');
        
        $json = json_encode($result);
        $cset = config_item('charset');
        
        if($cb){
            $json = "$cb($json);";
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/javascript', $cset)
                ->set_output($json)
                ->_display();
            exit;
        }else{
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json', $cset)
                ->set_output($json)
                ->_display();
            exit;
        }
    }
    
    /**
     * Check if current admin user can do something
     * @param string perms The perms to check.
     * @return boolean true on allowed, false otherwise.
     */
    public function can_i($perms){
        if(!$this->user)
            return false;
        return in_array($perms, $this->user->perms);
    }
    
    /**
     * Redirect to some URL.
     * @param string next Target URL.
     * @param integer status Redirect status.
     */
    public function redirect($next='/', $status=NULL){
        if(substr($next, 0, 4) != 'http')
            $next = base_url($next);
        
        redirect($next, 'auto', $status);
    }
    
    /**
     * Print page.
     * @param string view The view to load.
     * @param array params The parameters to send to view.
     */
    public function respond($view, $params=array()){
        $page_title = '';
        if(array_key_exists('title', $params))
            $page_title = $params['title'] . ' - ';
        $page_title.= $this->setting->item('site_name');
        
        $params['page_title'] = $page_title;
        
        $this->theme->load($view, $params);
    }
    
    /**
     * Print 404 page
     */
    public function show_404(){
        $this->output->set_status_header('404');

        $object = (object)array(
            'email' => 'iqbalfawz@gmail.com',
            'multiple' => array(1,4,6,10),
            'boolean' => 1,
            'file' => '/media/aa/bb/cc/lorem-ipsum-file.jpg',
            'select' => 'c',
            'textarea' => 'lorem ipsum the text area',
            'image' => '/media/04762ff570a7b9dcf6d524819344d00c.jpg',
            'time' => date('H:i:s'),
            'month' => date('m'),
            'datetime' => date('Y-m-d H:i:s'),
            'date' => date('Y-m-d'),
            'color' => '#FFFFFF',
            'url' => 'https://www.google.com',
            'slug' => 'lorem-ipsum',
            'text' => 'what the fuck',
            'tel'=> '085710029739',
            'search' => 'lorem ipsum',
            'number' => 12,
            'password' => 'fuck',
            'tinymce' => 'what?'
        );
        
        $this->form
            ->setForm('test/form')
            ->setError('with-error', 'Youre an error')
            ->setObject($object);
        
        $params = array(
            'title' => _l('Page not found')
        );
        
        $this->respond('404', $params);
    }
}