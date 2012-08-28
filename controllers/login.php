<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This is a login module for PyroCMS
 *
 * @author              Khalil TABBAL
 * @link                http://www.khalil-tabbal.com
 * @package             GWADAWEB
 * @subpackage          LOGIN
 */
class login extends Public_Controller
{

        public function __construct()
        {
                parent::__construct();


                $this->load->model('login_m');
                $this->lang->load('login');
                $this->load->model(array('users/user_m', 'users/profile_m'));

                $this->load->spark('facebook/0.0.1');
                $this->load->spark('console/0.7.0');
        }

        public function index()
        {

                // check if there is a facebook user logged in
                $facebook_user = $this->facebook->getUser();

                if ($facebook_user)
                {
                        // We have a user ID, so probably a logged in user.
                        // If not, we'll get an exception, which we handle below.

                        try
                        {
                                // we check if the user has authorized application
                                $user_info = $this->facebook->api('/' . $facebook_user);

                                // we check if this user correspond to a PyroCMS user
                                $params = array('facebook_id' => $facebook_user);

                                $pyrocms_user = $this->profile_m->get_profile($params);

                                if ($pyrocms_user)
                                {
                                        // we login the user
                                        $logged_in = $this->ion_auth->force_login($pyrocms_user->user_id, FALSE);

                                        // redirect him to the following view
                                        if (Settings::get('login_redirect') != '')
                                        {
                                                redirect(Settings::get('login_redirect'));
                                        }
                                        else
                                        {
                                                // build vue loggedin
                                                $this->template
                                                        ->title($this->module_details['name'])
                                                        ->build('loggedin');
                                        }
                                }
                                else
                                {
                                        // create PyroCMS user corresponding to Facebook user and log him
                                        $pyrocms_user_id = $this->create_pyro_user($user_info);

                                        if ($pyrocms_user_id)
                                        {
                                                // we open a session for the new user just created
                                                $login = $this->ion_auth->force_login($pyrocms_user_id, FALSE);

                                                // we redirect the user just loggedin
                                                if (Settings::get('login_redirect') != '')
                                                {
                                                        redirect(Settings::get('login_redirect'));
                                                }
                                                else
                                                {
                                                        // build vue loggedin
                                                        $this->template
                                                                ->title($this->module_details['name'])
                                                                ->build('loggedin');
                                                }
                                        }
                                }
                        }
                        catch (FacebookApiException $e)
                        {
                                // we log the errors in the debug file
                                error_log($e, 'error');

                                // we build the login view
                                $this->template
                                        ->title($this->module_details['name'], 'Se connecter à Facebook')
                                        ->build('login');
                        }
                }
                else
                {
                        // we build the login view
                        $this->template
                                ->title($this->module_details['name'], 'Se connecter à Facebook')
                                ->build('login');
                }
        }

        /**
         * Create a PyroCMS user based on Facebook users informations
         * 
         * @param array $user_info
         * @return mixed
         */
        public function create_pyro_user($user_info)
        {
                $facebook_id  = $user_info['id'];
                $email        = $user_info['email'];
                $first_name   = $user_info['first_name'];
                $last_name    = $user_info['last_name'];
                $display_name = $first_name . ' ' . $last_name;
                $username     = strtolower($first_name) . '-' . strtolower($last_name);

                if (isset($user_info['gender']))
                {
                        $gender = substr($user_info['gender'], 0, 1);
                }
                else
                {
                        $gender = NULL;
                }
                if (isset($user_info['location']['name']))
                {
                        $address_line3 = $user_info['location']['name'];
                }
                else
                {
                        $address_line3 = NULL;
                }

                // Additional Data for User Profile
                $additional_data = array(
                    'created_by'    => 1,
                    'display_name'  => $display_name,
                    'first_name'    => $first_name,
                    'last_name'     => $last_name,
                    'gender'        => $gender,
                    'lang'          => 'fr',
                    'address_line3' => $address_line3,
                    'facebook_id'   => $facebook_id,
                );

                // we record the new PyroCMS user
                $pyrocms_user_id = $this->ion_auth->register($username, NULL, $email, '2', $additional_data);

                if ($pyrocms_user_id)
                {
                        return $pyrocms_user_id;
                }
                else
                {
                        return FALSE;
                }
        }

}