<?php
/**
 * Authentication & Profile API Endpoints
 *
 * Handles user registration, login, logout, and profile management
 *
 * @package Batumi_Zone_Core
 * @since 0.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Batumi_Auth_API {

    /**
     * API namespace
     */
    private $namespace = 'batumizone/v1';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {

        // POST /auth/register
        register_rest_route($this->namespace, '/auth/register', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'register_user'),
            'permission_callback' => '__return_true', // Public endpoint
            'args'                => array(
                'username' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_user',
                    'validate_callback' => array($this, 'validate_username'),
                ),
                'email' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => array($this, 'validate_email'),
                ),
                'password' => array(
                    'required' => true,
                ),
                'phone' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_phone'),
                ),
                'whatsapp_phone' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));

        // POST /auth/login
        register_rest_route($this->namespace, '/auth/login', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'login_user'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'username' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'password' => array(
                    'required' => true,
                ),
            ),
        ));

        // POST /auth/logout
        register_rest_route($this->namespace, '/auth/logout', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'logout_user'),
            'permission_callback' => array($this, 'is_authenticated'),
        ));

        // GET /me
        register_rest_route($this->namespace, '/me', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_profile'),
            'permission_callback' => array($this, 'is_authenticated'),
        ));

        // PUT /me
        register_rest_route($this->namespace, '/me', array(
            'methods'             => 'PUT',
            'callback'            => array($this, 'update_profile'),
            'permission_callback' => array($this, 'is_authenticated'),
            'args'                => array(
                'email' => array(
                    'sanitize_callback' => 'sanitize_email',
                    'validate_callback' => 'is_email',
                ),
                'phone' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => array($this, 'validate_phone'),
                ),
                'whatsapp_phone' => array(
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }

    /**
     * Register new user
     */
    public function register_user($request) {
        $username = $request['username'];
        $email = $request['email'];
        $password = $request['password'];
        $phone = $request['phone'];
        $whatsapp_phone = isset($request['whatsapp_phone']) ? $request['whatsapp_phone'] : '';

        // Check if username exists
        if (username_exists($username)) {
            return new WP_Error(
                'username_exists',
                __('Username already exists. Please choose a different username.', 'batumizone-core'),
                array('status' => 409)
            );
        }

        // Check if email exists
        if (email_exists($email)) {
            return new WP_Error(
                'email_exists',
                __('Email address already registered. Please use a different email.', 'batumizone-core'),
                array('status' => 409)
            );
        }

        // Create user
        $user_id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass'  => $password,
            'role'       => 'subscriber', // Default role for posters
        ));

        if (is_wp_error($user_id)) {
            return new WP_Error(
                'registration_failed',
                $user_id->get_error_message(),
                array('status' => 500)
            );
        }

        // Store phone in user meta
        update_user_meta($user_id, 'phone', $phone);

        if (!empty($whatsapp_phone)) {
            update_user_meta($user_id, 'whatsapp_phone', $whatsapp_phone);
        }

        // Auto-login the user (set authentication cookies)
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        // Return user data
        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Registration successful. You are now logged in.', 'batumizone-core'),
            'user_id' => $user_id,
            'user'    => $this->get_user_data($user_id),
        ), 201);
    }

    /**
     * Login user
     */
    public function login_user($request) {
        $username = $request['username'];
        $password = $request['password'];

        // Authenticate
        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            return new WP_Error(
                'invalid_credentials',
                __('Invalid username or password.', 'batumizone-core'),
                array('status' => 401)
            );
        }

        // Set authentication cookies
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Login successful.', 'batumizone-core'),
            'user'    => $this->get_user_data($user->ID),
        ), 200);
    }

    /**
     * Logout user
     */
    public function logout_user($request) {
        wp_logout();

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Logout successful.', 'batumizone-core'),
        ), 200);
    }

    /**
     * Get current user profile
     */
    public function get_profile($request) {
        $user_id = get_current_user_id();

        return new WP_REST_Response($this->get_user_data($user_id), 200);
    }

    /**
     * Update user profile
     */
    public function update_profile($request) {
        $user_id = get_current_user_id();

        // Update email if provided
        if (isset($request['email']) && !empty($request['email'])) {
            $email = $request['email'];

            // Check if email is already used by another user
            $existing_user_id = email_exists($email);
            if ($existing_user_id && $existing_user_id != $user_id) {
                return new WP_Error(
                    'email_exists',
                    __('Email address already used by another account.', 'batumizone-core'),
                    array('status' => 409)
                );
            }

            $result = wp_update_user(array(
                'ID'         => $user_id,
                'user_email' => $email,
            ));

            if (is_wp_error($result)) {
                return new WP_Error(
                    'update_failed',
                    $result->get_error_message(),
                    array('status' => 500)
                );
            }
        }

        // Update phone (required)
        if (isset($request['phone'])) {
            update_user_meta($user_id, 'phone', $request['phone']);
        }

        // Update WhatsApp phone (optional)
        if (isset($request['whatsapp_phone'])) {
            update_user_meta($user_id, 'whatsapp_phone', $request['whatsapp_phone']);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => __('Profile updated successfully.', 'batumizone-core'),
            'user'    => $this->get_user_data($user_id),
        ), 200);
    }

    /**
     * Get formatted user data
     */
    private function get_user_data($user_id) {
        $user = get_userdata($user_id);

        if (!$user) {
            return null;
        }

        return array(
            'id'             => $user->ID,
            'username'       => $user->user_login,
            'email'          => $user->user_email,
            'phone'          => get_user_meta($user->ID, 'phone', true),
            'whatsapp_phone' => get_user_meta($user->ID, 'whatsapp_phone', true),
            'role'           => !empty($user->roles) ? $user->roles[0] : 'subscriber',
            'registered'     => $user->user_registered,
        );
    }

    /**
     * Check if user is authenticated
     */
    public function is_authenticated() {
        return is_user_logged_in();
    }

    /**
     * Validate username
     */
    public function validate_username($username, $request, $param) {
        if (empty($username)) {
            return new WP_Error('invalid_username', __('Username cannot be empty.', 'batumizone-core'));
        }

        if (strlen($username) < 3) {
            return new WP_Error('invalid_username', __('Username must be at least 3 characters long.', 'batumizone-core'));
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return new WP_Error('invalid_username', __('Username can only contain letters, numbers, and underscores.', 'batumizone-core'));
        }

        return true;
    }

    /**
     * Validate email
     */
    public function validate_email($email, $request, $param) {
        if (empty($email)) {
            return new WP_Error('invalid_email', __('Email cannot be empty.', 'batumizone-core'));
        }

        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Please provide a valid email address.', 'batumizone-core'));
        }

        return true;
    }

    /**
     * Validate phone number
     * Basic validation: must have at least 10 digits
     */
    public function validate_phone($phone, $request, $param) {
        if (empty($phone)) {
            return new WP_Error('invalid_phone', __('Phone number cannot be empty.', 'batumizone-core'));
        }

        // Remove all non-digit characters for validation
        $digits_only = preg_replace('/\D/', '', $phone);

        if (strlen($digits_only) < 10) {
            return new WP_Error('invalid_phone', __('Phone number must contain at least 10 digits.', 'batumizone-core'));
        }

        return true;
    }
}

// Initialize
new Batumi_Auth_API();
