<?php
/**
 * Plugin Name: Tanzanite Customer Service
 * Plugin URI: https://tanzanite.site
 * Description: 客服管理插件 - 管理客服信息并提供 REST API
 * Version: 1.0.0
 * Author: Tanzanite
 * Text Domain: tanzanite-cs
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 定义插件常量
define( 'TZ_CS_VERSION', '1.0.0' );
define( 'TZ_CS_PLUGIN_FILE', __FILE__ );
define( 'TZ_CS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TZ_CS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// 引入数据库类
require_once TZ_CS_PLUGIN_DIR . 'includes/class-database.php';

// 引入自动回复API类
require_once TZ_CS_PLUGIN_DIR . 'api/class-auto-reply-api.php';

/**
 * 客服管理插件主类
 */
class Tanzanite_Customer_Service_Plugin {
    
    /**
     * 单例实例
     */
    private static $instance = null;
    
    /**
     * 获取单例实例
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 构造函数
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * 初始化钩子
     */
    private function init_hooks(): void {
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }
    
    /**
     * 加载管理页面脚本和样式
     */
    public function enqueue_admin_scripts( $hook ): void {
        // 在客服管理、聊天记录和自动回复设置页面加载
        if ( 'toplevel_page_tanzanite-customer-service' !== $hook && 
             'customer-service_page_tanzanite-cs-chat-records' !== $hook &&
             'customer-service_page_tanzanite-cs-auto-reply' !== $hook ) {
            return;
        }
        
        // 加载媒体库
        wp_enqueue_media();
        
        // 内联样式
        wp_add_inline_style( 'wp-admin', $this->get_admin_css() );
    }
    
    /**
     * 获取管理页面 CSS
     */
    private function get_admin_css(): string {
        return '
            .tz-cs-admin .tz-settings-wrapper {
                box-sizing: border-box;
                max-width: 1500px;
                margin: 40px auto;
                padding: 32px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
                display: flex;
                flex-direction: column;
                gap: 24px;
                font-size: 14px;
                color: #1f2937;
            }
            
            .tz-cs-admin .tz-settings-header {
                display: flex;
                flex-direction: column;
                gap: 12px;
                align-items: center;
                text-align: center;
            }
            
            .tz-cs-admin .tz-settings-header h1 {
                margin: 0;
                font-size: 14px;
                font-weight: 700;
                letter-spacing: 0.02em;
                text-transform: uppercase;
                color: #111827;
            }
            
            .tz-cs-admin .tz-settings-header p {
                margin: 0;
                max-width: 960px;
                color: #4b5563;
            }
            
            .tz-cs-admin .tz-settings-section {
                background: #f8fafc;
                border-radius: 8px;
                padding: 16px;
                display: flex;
                flex-direction: column;
                gap: 12px;
                border: 1px solid #e5e7eb;
            }
            
            .tz-cs-admin .wp-list-table {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                background: #fff;
            }
            
            .tz-cs-admin .wp-list-table thead th {
                background: #f3f4f6;
                color: #111827;
                font-weight: 600;
                font-size: 14px;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .tz-cs-admin .wp-list-table tbody tr {
                border-bottom: 1px solid #f3f4f6;
            }
            
            .tz-cs-admin .wp-list-table tbody tr:last-child {
                border-bottom: none;
            }
            
            .tz-cs-admin .button {
                background: #1f2937;
                border-color: #1f2937;
                color: #fff;
                border-radius: 6px;
                padding: 8px 16px;
                font-weight: 500;
                transition: all 0.2s;
            }
            
            .tz-cs-admin .button:hover {
                background: #111827;
                border-color: #111827;
            }
            
            .tz-cs-admin .button-primary {
                background: #1f2937;
                border-color: #1f2937;
            }
            
            .tz-cs-admin .button-primary:hover {
                background: #111827;
                border-color: #111827;
            }
            
            .tz-cs-admin input[type="text"],
            .tz-cs-admin input[type="email"],
            .tz-cs-admin input[type="url"],
            .tz-cs-admin input[type="number"],
            .tz-cs-admin select {
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                padding: 8px 12px;
                font-size: 14px;
            }
            
            .tz-cs-admin input[type="text"]:focus,
            .tz-cs-admin input[type="email"]:focus,
            .tz-cs-admin input[type="url"]:focus,
            .tz-cs-admin input[type="number"]:focus,
            .tz-cs-admin select:focus {
                border-color: #1f2937;
                box-shadow: 0 0 0 1px #1f2937;
            }
            
            .tz-cs-admin .notice {
                border-radius: 6px;
                border-left-width: 4px;
            }
            
            .tz-cs-admin .card {
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                background: #f8fafc;
                padding: 16px;
            }
            
            .tz-cs-admin .card h2 {
                margin-top: 0;
                font-size: 16px;
                font-weight: 600;
                color: #111827;
            }
            
            .tz-cs-admin .card code {
                display: block;
                padding: 12px;
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                font-family: "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, "Courier New", monospace;
                font-size: 13px;
                color: #1f2937;
                overflow-x: auto;
            }
            
            .tz-cs-admin .avatar-upload-wrapper {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .tz-cs-admin .avatar-preview {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid #e5e7eb;
                background: #f3f4f6;
            }
            
            .tz-cs-admin .avatar-preview.placeholder {
                display: flex;
                align-items: center;
                justify-content: center;
                color: #9ca3af;
                font-size: 12px;
            }
            
            .tz-cs-admin .upload-avatar-btn {
                padding: 6px 12px;
                font-size: 13px;
            }
            
            @media (max-width: 1600px) {
                .tz-cs-admin .tz-settings-wrapper {
                    width: 90%;
                }
            }
            
            @media (max-width: 1024px) {
                .tz-cs-admin .tz-settings-wrapper {
                    width: calc(100% - 48px);
                    margin: 24px auto;
                    padding: 24px;
                }
            }
        ';
    }
    
    /**
     * 注册管理菜单
     */
    public function register_admin_menu(): void {
        add_menu_page(
            __( 'Customer Service', 'tanzanite-cs' ),
            __( 'Customer Service', 'tanzanite-cs' ),
            'manage_options',
            'tanzanite-customer-service',
            [ $this, 'render_admin_page' ],
            'dashicons-businessman',
            30
        );
        
        // 添加子菜单：聊天记录
        add_submenu_page(
            'tanzanite-customer-service',
            __( 'Chat Records', 'tanzanite-cs' ),
            __( '聊天记录', 'tanzanite-cs' ),
            'manage_options',
            'tanzanite-cs-chat-records',
            [ $this, 'render_chat_records_page' ]
        );
        
        // 添加子菜单：自动回复设置
        add_submenu_page(
            'tanzanite-customer-service',
            __( 'Auto Reply Settings', 'tanzanite-cs' ),
            __( '自动回复设置', 'tanzanite-cs' ),
            'manage_options',
            'tanzanite-cs-auto-reply',
            [ $this, 'render_auto_reply_page' ]
        );
    }
    
    /**
     * 注册 REST API 路由
     */
    public function register_rest_routes(): void {
        // 获取客服列表
        register_rest_route( 'tanzanite/v1', '/customer-service/agents', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_agents' ],
            'permission_callback' => '__return_true',
        ] );
        
        // 发送消息
        register_rest_route( 'tanzanite/v1', '/customer-service/messages', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'rest_send_message' ],
            'permission_callback' => '__return_true',
        ] );
        
        // 获取消息列表
        register_rest_route( 'tanzanite/v1', '/customer-service/messages/(?P<conversation_id>[a-zA-Z0-9_-]+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_messages' ],
            'permission_callback' => '__return_true',
        ] );
        
        // 获取会话列表（客服端）
        register_rest_route( 'tanzanite/v1', '/customer-service/conversations', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'rest_get_conversations' ],
            'permission_callback' => [ $this, 'check_agent_permission' ],
        ] );
        
        // 注册自动回复API路由
        TZ_CS_Auto_Reply_API::register_routes();
    }
    
    /**
     * REST API: 获取客服列表
     */
    public function rest_get_agents( \WP_REST_Request $request ): \WP_REST_Response {
        $agents = get_option( 'tz_customer_service_agents', [] );
        
        // 只返回启用的客服
        $active_agents = array_filter( $agents, fn( $agent ) => ( $agent['status'] ?? 'active' ) === 'active' );
        
        // 按排序字段排序
        usort( $active_agents, fn( $a, $b ) => ( $a['order'] ?? 0 ) - ( $b['order'] ?? 0 ) );
        
        // 格式化输出
        $formatted = array_map( fn( $agent ) => [
            'id'     => $agent['id'] ?? '',
            'name'   => $agent['name'] ?? '',
            'email'  => $agent['email'] ?? '',
            'avatar' => $agent['avatar'] ?? '',
        ], $active_agents );
        
        return new \WP_REST_Response( [
            'success' => true,
            'data'    => array_values( $formatted ),
        ], 200 );
    }
    
    /**
     * REST API: 发送消息
     */
    public function rest_send_message( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        
        $conversation_id = sanitize_text_field( $request->get_param( 'conversation_id' ) );
        $message = sanitize_textarea_field( $request->get_param( 'message' ) );
        $sender_type = sanitize_text_field( $request->get_param( 'sender_type' ) ); // visitor, user, agent
        $sender_name = sanitize_text_field( $request->get_param( 'sender_name' ) );
        $sender_email = sanitize_email( $request->get_param( 'sender_email' ) );
        $agent_id = sanitize_text_field( $request->get_param( 'agent_id' ) );
        $message_type = sanitize_text_field( $request->get_param( 'message_type' ) ?: 'text' );
        $metadata = $request->get_param( 'metadata' );
        
        if ( empty( $conversation_id ) || empty( $message ) || empty( $sender_name ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => '缺少必需参数',
            ], 400 );
        }
        
        $table_messages = $wpdb->prefix . 'tz_cs_messages';
        $table_conversations = $wpdb->prefix . 'tz_cs_conversations';
        
        // 插入消息
        $result = $wpdb->insert(
            $table_messages,
            [
                'conversation_id' => $conversation_id,
                'sender_type' => $sender_type,
                'sender_id' => get_current_user_id(),
                'sender_name' => $sender_name,
                'sender_email' => $sender_email,
                'agent_id' => $agent_id,
                'message_type' => $message_type,
                'message' => $message,
                'metadata' => $metadata ? wp_json_encode( $metadata ) : null,
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );
        
        if ( false === $result ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => '消息保存失败',
            ], 500 );
        }
        
        $message_id = $wpdb->insert_id;
        
        // 更新或创建会话记录
        $wpdb->replace(
            $table_conversations,
            [
                'id' => $conversation_id,
                'agent_id' => $agent_id,
                'visitor_name' => $sender_type !== 'agent' ? $sender_name : '',
                'visitor_email' => $sender_type !== 'agent' ? $sender_email : '',
                'user_id' => $sender_type === 'user' ? get_current_user_id() : 0,
                'last_message' => $message,
                'last_message_time' => current_time( 'mysql' ),
                'status' => 'active',
                'updated_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ]
        );
        
        return new \WP_REST_Response( [
            'success' => true,
            'message_id' => $message_id,
            'data' => [
                'id' => $message_id,
                'conversation_id' => $conversation_id,
                'sender_name' => $sender_name,
                'message' => $message,
                'created_at' => current_time( 'mysql' ),
            ],
        ], 200 );
    }
    
    /**
     * REST API: 获取消息列表
     */
    public function rest_get_messages( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        
        $conversation_id = $request->get_param( 'conversation_id' );
        $limit = intval( $request->get_param( 'limit' ) ?: 50 );
        $offset = intval( $request->get_param( 'offset' ) ?: 0 );
        
        if ( empty( $conversation_id ) ) {
            return new \WP_REST_Response( [
                'success' => false,
                'message' => '缺少会话ID',
            ], 400 );
        }
        
        $table_messages = $wpdb->prefix . 'tz_cs_messages';
        
        $messages = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_messages 
            WHERE conversation_id = %s 
            ORDER BY created_at ASC 
            LIMIT %d OFFSET %d",
            $conversation_id,
            $limit,
            $offset
        ) );
        
        // 解析 metadata JSON
        foreach ( $messages as $message ) {
            if ( ! empty( $message->metadata ) ) {
                $message->metadata = json_decode( $message->metadata, true );
            }
        }
        
        return new \WP_REST_Response( [
            'success' => true,
            'data' => $messages,
            'total' => count( $messages ),
        ], 200 );
    }
    
    /**
     * REST API: 获取会话列表（客服端）
     */
    public function rest_get_conversations( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        
        $status = sanitize_text_field( $request->get_param( 'status' ) ?: 'active' );
        $limit = intval( $request->get_param( 'limit' ) ?: 100 );
        
        $table_conversations = $wpdb->prefix . 'tz_cs_conversations';
        
        $conversations = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_conversations 
            WHERE status = %s 
            ORDER BY updated_at DESC 
            LIMIT %d",
            $status,
            $limit
        ) );
        
        return new \WP_REST_Response( [
            'success' => true,
            'data' => $conversations,
            'total' => count( $conversations ),
        ], 200 );
    }
    
    /**
     * 检查客服权限
     */
    public function check_agent_permission(): bool {
        return current_user_can( 'manage_options' );
    }
    
    /**
     * 渲染管理页面
     */
    public function render_admin_page(): void {
        // 处理手动创建数据库表
        if ( isset( $_GET['create_tables'] ) && $_GET['create_tables'] === '1' ) {
            TZ_CS_Database::create_tables();
            wp_redirect( admin_url( 'admin.php?page=tanzanite-customer-service&tables_created=1' ) );
            exit;
        }
        
        // 处理表单提交
        if ( isset( $_POST['tz_cs_save'] ) && check_admin_referer( 'tz_customer_service_save' ) ) {
            $agents = [];
            
            if ( isset( $_POST['agents'] ) && is_array( $_POST['agents'] ) ) {
                foreach ( $_POST['agents'] as $agent ) {
                    $agents[] = [
                        'id'     => sanitize_text_field( $agent['id'] ?? '' ),
                        'name'   => sanitize_text_field( $agent['name'] ?? '' ),
                        'email'  => sanitize_email( $agent['email'] ?? '' ),
                        'avatar' => esc_url_raw( $agent['avatar'] ?? '' ),
                        'status' => sanitize_text_field( $agent['status'] ?? 'active' ),
                        'order'  => intval( $agent['order'] ?? 0 ),
                    ];
                }
            }
            
            update_option( 'tz_customer_service_agents', $agents );
            echo '<div class="notice notice-success"><p>' . __( 'Customer service agents saved successfully.', 'tanzanite-cs' ) . '</p></div>';
        }
        
        // 获取现有配置
        $agents = get_option( 'tz_customer_service_agents', [] );
        
        ?>
        <div class="wrap tz-cs-admin">
            <div class="tz-settings-wrapper">
                <div class="tz-settings-header">
                    <h1><?php _e( 'Customer Service Management', 'tanzanite-cs' ); ?></h1>
                    <p><?php _e( '管理客服信息，配置客服邮箱、头像与排序。启用的客服将通过 REST API 提供给前端使用。', 'tanzanite-cs' ); ?></p>
                </div>
                
                <div class="tz-settings-section">
                    <form method="post" id="tz-customer-service-form">
                <?php wp_nonce_field( 'tz_customer_service_save' ); ?>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php _e( 'Order', 'tanzanite-cs' ); ?></th>
                            <th><?php _e( 'Name', 'tanzanite-cs' ); ?></th>
                            <th><?php _e( 'Email', 'tanzanite-cs' ); ?></th>
                            <th><?php _e( 'Avatar URL', 'tanzanite-cs' ); ?></th>
                            <th style="width: 100px;"><?php _e( 'Status', 'tanzanite-cs' ); ?></th>
                            <th style="width: 80px;"><?php _e( 'Action', 'tanzanite-cs' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="tz-agents-list">
                        <?php if ( empty( $agents ) ) : ?>
                            <tr class="tz-agent-row">
                                <td><input type="number" name="agents[0][order]" value="1" min="0" style="width: 60px;"></td>
                                <td><input type="text" name="agents[0][name]" value="" placeholder="Customer Service" class="regular-text" required></td>
                                <td><input type="email" name="agents[0][email]" value="" placeholder="support@example.com" class="regular-text" required></td>
                                <td>
                                    <div class="avatar-upload-wrapper">
                                        <div class="avatar-preview placeholder">无</div>
                                        <input type="hidden" name="agents[0][avatar]" value="" class="avatar-url-input">
                                        <button type="button" class="button upload-avatar-btn" data-index="0">上传头像</button>
                                    </div>
                                </td>
                                <td>
                                    <select name="agents[0][status]">
                                        <option value="active"><?php _e( 'Active', 'tanzanite-cs' ); ?></option>
                                        <option value="inactive"><?php _e( 'Inactive', 'tanzanite-cs' ); ?></option>
                                    </select>
                                </td>
                                <td><button type="button" class="button tz-remove-agent"><?php _e( 'Remove', 'tanzanite-cs' ); ?></button></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ( $agents as $index => $agent ) : ?>
                                <tr class="tz-agent-row">
                                    <input type="hidden" name="agents[<?php echo $index; ?>][id]" value="<?php echo esc_attr( $agent['id'] ?? uniqid() ); ?>">
                                    <td><input type="number" name="agents[<?php echo $index; ?>][order]" value="<?php echo esc_attr( $agent['order'] ?? $index ); ?>" min="0" style="width: 60px;"></td>
                                    <td><input type="text" name="agents[<?php echo $index; ?>][name]" value="<?php echo esc_attr( $agent['name'] ?? '' ); ?>" placeholder="Customer Service" class="regular-text" required></td>
                                    <td><input type="email" name="agents[<?php echo $index; ?>][email]" value="<?php echo esc_attr( $agent['email'] ?? '' ); ?>" placeholder="support@example.com" class="regular-text" required></td>
                                    <td>
                                        <div class="avatar-upload-wrapper">
                                            <?php if ( ! empty( $agent['avatar'] ) ) : ?>
                                                <img src="<?php echo esc_url( $agent['avatar'] ); ?>" class="avatar-preview" alt="Avatar">
                                            <?php else : ?>
                                                <div class="avatar-preview placeholder">无</div>
                                            <?php endif; ?>
                                            <input type="hidden" name="agents[<?php echo $index; ?>][avatar]" value="<?php echo esc_url( $agent['avatar'] ?? '' ); ?>" class="avatar-url-input">
                                            <button type="button" class="button upload-avatar-btn" data-index="<?php echo $index; ?>">上传头像</button>
                                        </div>
                                    </td>
                                    <td>
                                        <select name="agents[<?php echo $index; ?>][status]">
                                            <option value="active" <?php selected( $agent['status'] ?? 'active', 'active' ); ?>><?php _e( 'Active', 'tanzanite-cs' ); ?></option>
                                            <option value="inactive" <?php selected( $agent['status'] ?? 'active', 'inactive' ); ?>><?php _e( 'Inactive', 'tanzanite-cs' ); ?></option>
                                        </select>
                                    </td>
                                    <td><button type="button" class="button tz-remove-agent"><?php _e( 'Remove', 'tanzanite-cs' ); ?></button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <p style="margin-top: 20px; display: flex; gap: 12px;">
                    <button type="button" id="tz-add-agent" class="button"><?php _e( 'Add Agent', 'tanzanite-cs' ); ?></button>
                    <button type="submit" name="tz_cs_save" class="button button-primary"><?php _e( 'Save Changes', 'tanzanite-cs' ); ?></button>
                </p>
            </form>
                </div>
            
                <div class="card">
                    <h2><?php _e( 'API Endpoint', 'tanzanite-cs' ); ?></h2>
                    <p><?php _e( '前端可通过以下 REST API 端点获取启用的客服列表：', 'tanzanite-cs' ); ?></p>
                    <code>GET <?php echo esc_url( rest_url( 'tanzanite/v1/customer-service/agents' ) ); ?></code>
                    <p style="margin-top: 12px; color: #6b7280; font-size: 13px;">
                        <?php _e( '返回的数据包含客服 ID、姓名、邮箱、头像 URL，按排序字段升序排列。', 'tanzanite-cs' ); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            let agentIndex = <?php echo count( $agents ); ?>;
            
            // 添加客服
            $('#tz-add-agent').on('click', function() {
                const row = `
                    <tr class="tz-agent-row">
                        <input type="hidden" name="agents[${agentIndex}][id]" value="${Date.now()}">
                        <td><input type="number" name="agents[${agentIndex}][order]" value="${agentIndex}" min="0" style="width: 60px;"></td>
                        <td><input type="text" name="agents[${agentIndex}][name]" value="" placeholder="Customer Service" class="regular-text" required></td>
                        <td><input type="email" name="agents[${agentIndex}][email]" value="" placeholder="support@example.com" class="regular-text" required></td>
                        <td>
                            <div class="avatar-upload-wrapper">
                                <div class="avatar-preview placeholder">无</div>
                                <input type="hidden" name="agents[${agentIndex}][avatar]" value="" class="avatar-url-input">
                                <button type="button" class="button upload-avatar-btn" data-index="${agentIndex}">上传头像</button>
                            </div>
                        </td>
                        <td>
                            <select name="agents[${agentIndex}][status]">
                                <option value="active"><?php _e( 'Active', 'tanzanite-cs' ); ?></option>
                                <option value="inactive"><?php _e( 'Inactive', 'tanzanite-cs' ); ?></option>
                            </select>
                        </td>
                        <td><button type="button" class="button tz-remove-agent"><?php _e( 'Remove', 'tanzanite-cs' ); ?></button></td>
                    </tr>
                `;
                $('#tz-agents-list').append(row);
                agentIndex++;
            });
            
            // 删除客服
            $(document).on('click', '.tz-remove-agent', function() {
                if (confirm('<?php _e( 'Are you sure you want to remove this agent?', 'tanzanite-cs' ); ?>')) {
                    $(this).closest('tr').remove();
                }
            });
            
            // 头像上传
            $(document).on('click', '.upload-avatar-btn', function(e) {
                e.preventDefault();
                const button = $(this);
                const wrapper = button.closest('.avatar-upload-wrapper');
                
                const mediaUploader = wp.media({
                    title: '选择头像',
                    button: { text: '使用此图片' },
                    multiple: false,
                    library: { type: 'image' }
                });
                
                mediaUploader.on('select', function() {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    const imageUrl = attachment.url;
                    
                    // 更新隐藏字段
                    wrapper.find('.avatar-url-input').val(imageUrl);
                    
                    // 更新预览
                    let preview = wrapper.find('.avatar-preview');
                    if (preview.hasClass('placeholder')) {
                        preview.replaceWith('<img src="' + imageUrl + '" class="avatar-preview" alt="Avatar">');
                    } else {
                        preview.attr('src', imageUrl);
                    }
                });
                
                mediaUploader.open();
            });
        });
        </script>
        <?php
    }
    
    /**
     * 渲染聊天记录页面
     */
    public function render_chat_records_page(): void {
        global $wpdb;
        
        $table_conversations = $wpdb->prefix . 'tz_cs_conversations';
        $table_messages = $wpdb->prefix . 'tz_cs_messages';
        
        // 获取筛选参数
        $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : 'all';
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        
        // 构建查询
        $where = "WHERE 1=1";
        if ( $status !== 'all' ) {
            $where .= $wpdb->prepare( " AND status = %s", $status );
        }
        if ( ! empty( $search ) ) {
            $where .= $wpdb->prepare( " AND (visitor_name LIKE %s OR visitor_email LIKE %s OR id LIKE %s)", 
                '%' . $wpdb->esc_like( $search ) . '%',
                '%' . $wpdb->esc_like( $search ) . '%',
                '%' . $wpdb->esc_like( $search ) . '%'
            );
        }
        
        // 获取会话列表
        $conversations = $wpdb->get_results( 
            "SELECT * FROM $table_conversations $where ORDER BY updated_at DESC LIMIT 100"
        );
        
        // 获取每个会话的消息数量
        foreach ( $conversations as $conversation ) {
            $conversation->message_count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_messages WHERE conversation_id = %s",
                $conversation->id
            ) );
        }
        
        ?>
        <div class="wrap tz-cs-admin">
            <div class="tz-settings-wrapper">
                <div class="tz-settings-header">
                    <h1><?php _e( '聊天记录管理', 'tanzanite-cs' ); ?></h1>
                    <p><?php _e( '查看和管理所有客服聊天记录。', 'tanzanite-cs' ); ?></p>
                </div>
                
                <!-- 筛选和搜索 -->
                <div class="tz-settings-section" style="margin-bottom: 20px;">
                    <form method="get" style="display: flex; gap: 12px; align-items: center;">
                        <input type="hidden" name="page" value="tanzanite-cs-chat-records">
                        
                        <select name="status" style="height: 32px; border-radius: 6px; border: 1px solid #e5e7eb;">
                            <option value="all" <?php selected( $status, 'all' ); ?>>全部状态</option>
                            <option value="active" <?php selected( $status, 'active' ); ?>>进行中</option>
                            <option value="closed" <?php selected( $status, 'closed' ); ?>>已关闭</option>
                        </select>
                        
                        <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" 
                               placeholder="搜索会话ID、访客姓名或邮箱..." 
                               style="width: 300px; height: 32px; border-radius: 6px; border: 1px solid #e5e7eb; padding: 0 10px;">
                        
                        <button type="submit" class="button button-primary">筛选</button>
                        <?php if ( $status !== 'all' || ! empty( $search ) ) : ?>
                            <a href="<?php echo admin_url( 'admin.php?page=tanzanite-cs-chat-records' ); ?>" class="button">重置</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- 会话列表 -->
                <div class="tz-settings-section">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th style="width: 150px;">会话ID</th>
                                <th>访客信息</th>
                                <th>客服</th>
                                <th style="width: 80px;">消息数</th>
                                <th>最后消息</th>
                                <th style="width: 150px;">更新时间</th>
                                <th style="width: 80px;">状态</th>
                                <th style="width: 100px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $conversations ) ) : ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px; color: #6b7280;">
                                        暂无聊天记录
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $conversations as $conv ) : ?>
                                    <tr>
                                        <td style="text-align: center;">
                                            <?php if ( $conv->has_auto_reply ) : ?>
                                                <span style="font-size: 18px;" title="触发了 <?php echo intval( $conv->auto_reply_count ); ?> 次自动回复<?php echo $conv->needs_human ? '，需要人工介入' : ''; ?>">🤖</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?php echo esc_html( $conv->id ); ?></code></td>
                                        <td>
                                            <strong><?php echo esc_html( $conv->visitor_name ?: '匿名' ); ?></strong><br>
                                            <small style="color: #6b7280;"><?php echo esc_html( $conv->visitor_email ); ?></small>
                                        </td>
                                        <td><?php echo esc_html( $conv->agent_id ?: '-' ); ?></td>
                                        <td>
                                            <?php echo intval( $conv->message_count ); ?>
                                            <?php if ( $conv->has_auto_reply ) : ?>
                                                <br><small style="color: #6b7280;">自动: <?php echo intval( $conv->auto_reply_count ); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo esc_html( $conv->last_message ); ?>
                                        </td>
                                        <td><?php echo esc_html( $conv->updated_at ); ?></td>
                                        <td>
                                            <?php if ( $conv->needs_human ) : ?>
                                                <span style="color: #ef4444;">⚠️ 需人工</span>
                                            <?php elseif ( $conv->status === 'active' ) : ?>
                                                <span style="color: #10b981;">● 进行中</span>
                                            <?php else : ?>
                                                <span style="color: #6b7280;">● 已关闭</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo admin_url( 'admin.php?page=tanzanite-cs-chat-records&action=view&conversation_id=' . urlencode( $conv->id ) ); ?>" 
                                               class="button button-small">查看</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 渲染自动回复设置页面
     */
    public function render_auto_reply_page(): void {
        global $wpdb;
        
        $table_auto_replies = $wpdb->prefix . 'tz_cs_auto_replies';
        
        // 处理表单提交
        if ( isset( $_POST['tz_cs_save_auto_reply'] ) && check_admin_referer( 'tz_auto_reply_save' ) ) {
            $type = sanitize_text_field( $_POST['type'] ?? '' );
            $message = sanitize_textarea_field( $_POST['message'] ?? '' );
            $keyword = sanitize_text_field( $_POST['keyword'] ?? '' );
            $id = intval( $_POST['id'] ?? 0 );
            
            $data = [
                'type' => $type,
                'reply_message' => $message,
                'trigger_keyword' => $keyword,
                'updated_at' => current_time( 'mysql' ),
            ];
            
            if ( $id > 0 ) {
                // 更新
                $wpdb->update(
                    $table_auto_replies,
                    $data,
                    [ 'id' => $id ],
                    [ '%s', '%s', '%s', '%s' ],
                    [ '%d' ]
                );
                echo '<div class="notice notice-success"><p>自动回复规则已更新</p></div>';
            } else {
                // 新建
                $data['created_at'] = current_time( 'mysql' );
                $wpdb->insert( $table_auto_replies, $data );
                echo '<div class="notice notice-success"><p>自动回复规则已创建</p></div>';
            }
        }
        
        // 处理删除
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'delete' && isset( $_GET['id'] ) ) {
            $id = intval( $_GET['id'] );
            $wpdb->delete( $table_auto_replies, [ 'id' => $id ], [ '%d' ] );
            echo '<div class="notice notice-success"><p>规则已删除</p></div>';
        }
        
        // 获取所有规则
        $welcome_rules = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_auto_replies WHERE type = %s ORDER BY created_at DESC",
            'welcome'
        ) );
        
        $keyword_rules = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_auto_replies WHERE type = %s ORDER BY priority DESC, created_at DESC",
            'keyword'
        ) );
        
        ?>
        <div class="wrap tz-cs-admin">
            <div class="tz-settings-wrapper">
                <div class="tz-settings-header">
                    <h1><?php _e( '自动回复设置', 'tanzanite-cs' ); ?></h1>
                    <p><?php _e( '配置欢迎语和关键词自动回复规则', 'tanzanite-cs' ); ?></p>
                </div>
                
                <!-- 欢迎语设置 -->
                <div class="tz-settings-section">
                    <h2 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600;">👋 欢迎语设置</h2>
                    <p style="margin: 0 0 12px 0; color: #6b7280; font-size: 13px;">
                        用户打开聊天窗口时自动发送（24小时内只发送一次）
                    </p>
                    
                    <?php if ( empty( $welcome_rules ) ) : ?>
                        <form method="post" style="background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <?php wp_nonce_field( 'tz_auto_reply_save' ); ?>
                            <input type="hidden" name="type" value="welcome">
                            
                            <textarea name="message" rows="4" class="large-text" 
                                      placeholder="👋 您好！欢迎来到客服中心..." 
                                      required style="width: 100%; border-radius: 6px; border: 1px solid #e5e7eb; padding: 8px 12px;"></textarea>
                            
                            <p style="margin-top: 12px;">
                                <button type="submit" name="tz_cs_save_auto_reply" class="button button-primary">保存欢迎语</button>
                            </p>
                        </form>
                    <?php else : ?>
                        <?php foreach ( $welcome_rules as $rule ) : ?>
                            <div style="background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: start; gap: 12px;">
                                    <div style="flex: 1; white-space: pre-wrap; font-size: 14px; color: #1f2937;">
                                        <?php echo esc_html( $rule->reply_message ); ?>
                                    </div>
                                    <a href="?page=tanzanite-cs-auto-reply&action=delete&id=<?php echo $rule->id; ?>" 
                                       class="button button-small"
                                       onclick="return confirm('确定要删除此欢迎语吗？')">删除</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- 关键词自动回复 -->
                <div class="tz-settings-section">
                    <h2 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600;">🤖 关键词自动回复</h2>
                    <p style="margin: 0 0 12px 0; color: #6b7280; font-size: 13px;">
                        用户发送特定关键词时自动回复（精确匹配）
                    </p>
                    
                    <!-- 添加新规则表单 -->
                    <form method="post" style="background: #fff; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 16px;">
                        <?php wp_nonce_field( 'tz_auto_reply_save' ); ?>
                        <input type="hidden" name="type" value="keyword">
                        
                        <div style="display: grid; grid-template-columns: 200px 1fr; gap: 12px; margin-bottom: 12px;">
                            <div>
                                <label style="display: block; margin-bottom: 4px; font-size: 13px; font-weight: 500;">触发关键词</label>
                                <input type="text" name="keyword" class="regular-text" 
                                       placeholder="订单" required 
                                       style="width: 100%; border-radius: 6px; border: 1px solid #e5e7eb; padding: 6px 10px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 4px; font-size: 13px; font-weight: 500;">自动回复内容</label>
                                <textarea name="message" rows="2" class="large-text" 
                                          placeholder="📦 请点击右侧 'My Orders' 标签查看您的订单..." 
                                          required style="width: 100%; border-radius: 6px; border: 1px solid #e5e7eb; padding: 6px 10px;"></textarea>
                            </div>
                        </div>
                        
                        <button type="submit" name="tz_cs_save_auto_reply" class="button button-primary">添加规则</button>
                    </form>
                    
                    <!-- 现有规则列表 -->
                    <?php if ( ! empty( $keyword_rules ) ) : ?>
                        <div style="background: #fff; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <table class="wp-list-table widefat fixed striped" style="border: none;">
                                <thead>
                                    <tr>
                                        <th style="width: 150px;">关键词</th>
                                        <th>回复内容</th>
                                        <th style="width: 100px;">操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ( $keyword_rules as $rule ) : ?>
                                        <tr>
                                            <td><strong><?php echo esc_html( $rule->trigger_keyword ); ?></strong></td>
                                            <td style="white-space: pre-wrap; font-size: 13px;"><?php echo esc_html( $rule->reply_message ); ?></td>
                                            <td>
                                                <a href="?page=tanzanite-cs-auto-reply&action=delete&id=<?php echo $rule->id; ?>" 
                                                   class="button button-small"
                                                   onclick="return confirm('确定要删除此规则吗？')">删除</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <p style="text-align: center; color: #9ca3af; padding: 24px;">暂无关键词规则</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}

// 插件激活钩子 - 创建数据库表
register_activation_hook( __FILE__, [ 'TZ_CS_Database', 'create_tables' ] );

// 初始化插件
add_action( 'plugins_loaded', function() {
    Tanzanite_Customer_Service_Plugin::instance();
} );
