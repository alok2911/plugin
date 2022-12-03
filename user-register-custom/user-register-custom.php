<?php
/*
  Plugin Name: Custom fields Registration
  Plugin URI: https://alok.com
  Description: Enables the plugin and the registration page create.
  Version: 1.1
  Author: Alok Kumar
  Author URI: http://alok.com
 */


defined( 'ABSPATH' ) || exit; 
if( ! function_exists('get_plugin_data') ){
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}
$plugin_data = get_plugin_data( __FILE__ );
if( ! class_exists( 'updateChecker' ) ) {

    class updateChecker{

        public $plugin_slug;
        public $version;
        public $cache_key;
        public $cache_allowed;

        public function __construct() {

            $this->plugin_slug = plugin_basename( __DIR__ );
            $this->version = '1.1';
            $this->cache_key = 'check_custom_update';
            $this->cache_allowed = false;

            add_filter( 'plugins_api', array( $this, 'info' ), 20, 3 );
            add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
            add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );

        }

        public function request(){

            $remote = get_transient( $this->cache_key );

            if( false === $remote || ! $this->cache_allowed ) {

                $remote = wp_remote_get(
                    plugin_dir_url( __FILE__ ).'upgrade.json',
                    array(
                        'timeout' => 10,
                        'headers' => array(
                            'Accept' => 'application/json'
                        )
                    )
                );

                if(
                    is_wp_error( $remote )
                    || 200 !== wp_remote_retrieve_response_code( $remote )
                    || empty( wp_remote_retrieve_body( $remote ) )
                ) {
                    return false;
                }

                set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

            }

            $remote = json_decode( wp_remote_retrieve_body( $remote ) );

            return $remote;

        }


        function info( $res, $action, $args ) {

            // print_r( $action );
            // print_r( $args );

            // do nothing if you're not getting plugin information right now
            if( 'plugin_information' !== $action ) {
                return $res;
            }

            // do nothing if it is not our plugin
            if( $this->plugin_slug !== $args->slug ) {
                return $res;
            }

            // get updates
            $remote = $this->request();

            if( ! $remote ) {
                return $res;
            }

            $res = new stdClass();

            $res->name = $remote->name;
            $res->slug = $remote->slug;
            $res->version = $remote->version;
            $res->tested = $remote->tested;
            $res->requires = $remote->requires;
            $res->author = $remote->author;
            $res->author_profile = $remote->author_profile;
            $res->download_link = $remote->download_url;
            $res->trunk = $remote->download_url;
            $res->requires_php = $remote->requires_php;
            $res->last_updated = $remote->last_updated;

            $res->sections = array(
                'description' => $remote->sections->description,
                'installation' => $remote->sections->installation,
                'changelog' => $remote->sections->changelog
            );

            if( ! empty( $remote->banners ) ) {
                $res->banners = array(
                    'low' => $remote->banners->low,
                    'high' => $remote->banners->high
                );
            }

            return $res;

        }

        public function update( $transient ) {

            if ( empty($transient->checked ) ) {
                return $transient;
            }

            $remote = $this->request();

            if(
                $remote
                && version_compare( $this->version, $remote->version, '<' )
                && version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' )
                && version_compare( $remote->requires_php, PHP_VERSION, '<' )
            ) {
                $res = new stdClass();
                $res->slug = $this->plugin_slug;
                $res->plugin = plugin_basename( __FILE__ ); 
                $res->new_version = $remote->version;
                $res->tested = $remote->tested;
                $res->package = $remote->download_url;

                $transient->response[ $res->plugin ] = $res;

        }

            return $transient;

        }

        public function purge( $upgrader, $options ){

            if (
                $this->cache_allowed
                && 'update' === $options['action']
                && 'plugin' === $options[ 'type' ]
            ) {
                // just clean the cache when new plugin version is installed
                delete_transient( $this->cache_key );
            }

        }


    }

    new updateChecker();

}


function wpdocs_theme_name_scripts() {
    wp_enqueue_style( 'style-bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css' );
    wp_enqueue_style( 'style-custom', plugin_dir_url( __FILE__ ) . '/assets/css/style.css' );
    wp_enqueue_script( 'script-name', '//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js', '4.1.1', true );
    wp_enqueue_script( 'script-name', '//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js', array(), '3.2.1', true );
}
add_action( 'wp_enqueue_scripts', 'wpdocs_theme_name_scripts' );



 function registration_form( $username, $password, $email, $first_name, $last_name, $contact, $agent_email, $company ) {
    echo '
    <style>
    div {
        margin-bottom:2px;
    }
     
    input{
        margin-bottom:4px;
    }
    </style>
    ';
 
 echo '<div class="container register-form">
            <div class="form">
            <form action="' . $_SERVER['REQUEST_URI'] . '" method="post">
                <div class="form-content">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                            <input type="text" class="form-control" name="username" placeholder="Username *" value="' . ( isset( $_POST['username'] ) ? $username : null ) . '">
                            </div>
                            <div class="form-group">
                            <input type="text" name="fname" class="form-control" placeholder="First Name" value="' . ( isset( $_POST['fname']) ? $first_name : null ) . '">
                            
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                            <input type="text" name="email" class="form-control" placeholder="Email *"  value="' . ( isset( $_POST['email']) ? $email : null ) . '">
                                
                            </div>
                            <div class="form-group">
                            <input type="text" name="lname" class="form-control" placeholder="Last Name" value="' . ( isset( $_POST['lname']) ? $last_name : null ) . '">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                            <input type="text" name="contact" class="form-control" placeholder="Contact Number" value="' . ( isset( $_POST['contact']) ? $contact : null ) . '">
                             
                            </div>
                            <div class="form-group">
                             <input type="text" class="form-control" placeholder="Company" name="company" value="' . ( isset( $_POST['company']) ? $company : null ) . '">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">

                            <input type="text" name="agent_email" class="form-control" placeholder="Agent Email" value="' . ( isset( $_POST['agent_email']) ? $agent_email : null ) . '">
                            </div>
                            <div class="form-group">
                            <input type="password" name="password" class="form-control" placeholder="Password *" value="' . ( isset( $_POST['password'] ) ? $password : null ) . '">
                            
                            </div>
                        </div>
                    </div>
                    <input type="submit" name="submit" class="btnSubmit" value="Register"/>
                </div>
                </form>
            </div>
        </div>';
}

function registration_validation( $username, $password, $email, $first_name, $last_name, $contact, $agent_email, $company)  {
    global $reg_errors;
    $reg_errors = new WP_Error;

    if ( empty( $username ) || empty( $password ) || empty( $email ) ) {
        $reg_errors->add('field', 'Required form field is missing');
    }

    if ( 4 > strlen( $username ) ) {
        $reg_errors->add( 'username_length', 'Username too short. At least 4 characters is required' );
    }

    if ( username_exists( $username ) ){
        $reg_errors->add('user_name', 'Sorry, that username already exists!');
    }

    if ( ! validate_username( $username ) ) {
        $reg_errors->add( 'username_invalid', 'Sorry, the username you entered is not valid' );
    }

    if ( 5 > strlen( $password ) ) {
            $reg_errors->add( 'password', 'Password length must be greater than 5' );
    }
    if ( !is_email( $email ) ) {
        $reg_errors->add( 'email_invalid', 'Email is not valid' );
    }

    if ( !is_email( $agent_email ) ) {
        $reg_errors->add( 'agent_email', 'Email is not valid' );
    }

    if ( email_exists( $email ) ) {
        $reg_errors->add( 'email', 'Email Already in use' );
    }

    if ( is_wp_error( $reg_errors ) ) {
        foreach ( $reg_errors->get_error_messages() as $error ) {
            echo '<div>';
            echo '<strong>ERROR</strong>:';
            echo $error . '<br/>';
            echo '</div>';
             
        }
     
    }
}

function complete_registration() {
    global $reg_errors, $username, $password, $email, $first_name, $last_name, $contact, $agent_email, $company; 
    if ( 1 > count( $reg_errors->get_error_messages() ) ) {
        $userdata = array(
        'user_login'    =>   $username,
        'user_email'    =>   $email,
        'user_pass'     =>   $password,
        'first_name'    =>   $first_name,
        'last_name'     =>   $last_name,
        );
        $user = wp_insert_user( $userdata );
        update_user_meta( $user, 'contact', $contact );
        update_user_meta( $user, 'agent_email', $agent_email );
        update_user_meta( $user, 'company', $company );
        $_POST = array();
        echo 'Registration complete. Goto <a href="' . get_site_url() . '/wp-login.php">login page</a>.';   
    }
}

function custom_registration_function() {
    if ( isset($_POST['submit'] ) ) {
        registration_validation(
        $_POST['username'],
        $_POST['password'],
        $_POST['email'],
        $_POST['fname'],
        $_POST['lname'],
        $_POST['contact'],
        $_POST['agent_email'],
        $_POST['company'],
        );
         
        // sanitize user form input
        global $username, $password, $email, $first_name, $last_name, $contact, $agent_email, $company;
        $username   =   sanitize_user( $_POST['username'] );
        $password   =   esc_attr( $_POST['password'] );
        $email      =   sanitize_email( $_POST['email'] );
        $first_name =   sanitize_text_field( $_POST['fname'] );
        $last_name  =   sanitize_text_field( $_POST['lname'] );
        $contact    =   sanitize_text_field( $_POST['contact'] );
        $agent_email  =   sanitize_email( $_POST['agent_email'] );
        $company  =   sanitize_text_field( $_POST['company'] );
 
        // call @function complete_registration to create the user
        // only when no WP_error is found
        complete_registration(
        $username,
        $password,
        $email,
        $first_name,
        $last_name,
        $contact,
        $agent_email,
        $company,
        );
    }
 
    registration_form(
        $username,
        $password,
        $email,
        $first_name,
        $last_name,
        $contact,
        $agent_email,
        $company,
        
    );
}

// Register a new shortcode: [cr_custom_registration]
add_shortcode( 'ak_custom_registration', 'custom_registration_shortcode' );
 
// The callback function that will replace [book]
function custom_registration_shortcode() {
    ob_start();
    custom_registration_function();
    return ob_get_clean();
}


function pluginAk_activate() { 
   $check_page_exist = get_page_by_title('registration', 'OBJECT', 'page');
// Check if the page already exists
if(empty($check_page_exist)) {
    $page_id = wp_insert_post(
        array(
        'comment_status' => 'close',
        'ping_status'    => 'close',
        'post_author'    => 1,
        'post_title'     => ucwords('registration'),
        'post_name'      => strtolower(str_replace(' ', '-', trim('registration'))),
        'post_status'    => 'publish',
        'post_content'   => '[ak_custom_registration]',
        'post_type'      => 'page',
        'post_parent'    => 'id_of_the_parent_page_if_it_available'
        )
    );
}
}
register_activation_hook( __FILE__, 'pluginAk_activate' );



//Add fields for admin side
add_action( 'edit_user_profile', 'ak_custom_user_profile_fields' );
function ak_custom_user_profile_fields( $user )
{
    echo '<h3 class="heading">My Custom Fields</h3>';
    ?>
    
    <table class="form-table">
    <tr>
        <th><label for="contact">Contact Number</label></th>
        <td>
            <input type="text" class="input-text form-control" value="<?php echo esc_attr( get_the_author_meta( 'contact', $user->ID ) ); ?>" name="contact" id="contact" />
        </td>
        
    </tr>
    <tr>
        <th><label for="agent_email">Agent Email</label></th>
        <td>
            <input type="text" class="input-text form-control" name="agent_email" value="<?php echo esc_attr( get_the_author_meta( 'agent_email', $user->ID ) ); ?>" id="agent_email" />
        </td>
    </tr>
    <tr>
        <th><label for="company">Company Name</label></th>
        <td>
            <input type="text" class="input-text form-control" value="<?php echo esc_attr( get_the_author_meta( 'company', $user->ID ) ); ?>" name="company" id="company" /><br />
        </td>
    </tr>
    </table>
    
    <?php
}

//Update by admin edit profile page
function save_custom_user_profile_fields($user_id){
    if(!current_user_can('manage_options'))
        return false;
    update_user_meta($user_id, 'company', $_POST['company']);
    update_user_meta($user_id, 'contact', $_POST['contact']);
    update_user_meta($user_id, 'agent_email', $_POST['agent_email']);
}
add_action('user_register', 'save_custom_user_profile_fields');
add_action('profile_update', 'save_custom_user_profile_fields');