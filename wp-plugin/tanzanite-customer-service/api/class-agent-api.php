<?php
/**
 * 客服端 API 类
 * 
 * @package Tanzanite_Customer_Service
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TZ_CS_Agent_API {
    
    /**
     * 注册 API 路由
     */
    public static function register_routes(): void {
        // 客服登录
        register_rest_route( 'tanzanite/v1', '/agent/login', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'login' ],
            'permission_callback' => '__return_true',
        ] );
        
        // 客服登出
        register_rest_route( 'tanzanite/v1', '/agent/logout', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'logout' ],
            'permission_callback' => '__return_true',
        ] );
        
        // 获取客服信息
        register_rest_route( 'tanzanite/v1', '/agent/me', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_agent_info' ],
            'permission_callback' => [ __CLASS__, 'verify_agent_token' ],
        ] );
        
        // 获取会话列表
        register_rest_route( 'tanzanite/v1', '/agent/conversations', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_conversations' ],
            'permission_callback' => [ __CLASS__, 'verify_agent_token' ],
        ] );
        
        // 获取会话消息
        register_rest_route( 'tanzanite/v1', '/agent/conversations/(?P<conversation_id>[a-zA-Z0-9_-]+)/messages', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_messages' ],
            'permission_callback' => [ __CLASS__, 'verify_agent_token' ],
        ] );
        
        // 发送消息
        register_rest_route( 'tanzanite/v1', '/agent/messages', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'send_message' ],
            'permission_callback' => [ __CLASS__, 'verify_agent_token' ],
        ] );
        
        // 标记消息已读
        register_rest_route( 'tanzanite/v1', '/agent/messages/read', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'mark_as_read' ],
            'permission_callback' => [ __CLASS__, 'verify_agent_token' ],
        ] );
    }
    
    /**
     * 验证客服 Token
     */
    public static function verify_agent_token( WP_REST_Request $request ): bool {
        $token = $request->get_header( 'Authorization' );
        
        if ( ! $token ) {
            return false;
        }
        
        // 移除 "Bearer " 前缀
        $token = str_replace( 'Bearer ', '', $token );
        
        $agent = TZ_CS_Agent_Auth::verify_token( $token );
        
        if ( ! $agent ) {
            return false;
        }
        
        // 将客服信息存储到请求中
        $request->set_param( '_agent', $agent );
        
        return true;
    }
    
    /**
     * 客服登录
     */
    public static function login( WP_REST_Request $request ): WP_REST_Response {
        $agent_id    = $request->get_param( 'agent_id' );
        $password    = $request->get_param( 'password' );
        $device_info = $request->get_param( 'device_info' ) ?? '';
        
        if ( ! $agent_id || ! $password ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => '客服工号和密码不能为空',
            ], 400 );
        }
        
        $result = TZ_CS_Agent_Auth::login( $agent_id, $password, $device_info );
        
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => $result->get_error_message(),
            ], 401 );
        }
        
        return new WP_REST_Response( $result, 200 );
    }
    
    /**
     * 客服登出
     */
    public static function logout( WP_REST_Request $request ): WP_REST_Response {
        $token = $request->get_header( 'Authorization' );
        $token = str_replace( 'Bearer ', '', $token );
        
        if ( $token ) {
            TZ_CS_Agent_Auth::logout( $token );
        }
        
        return new WP_REST_Response( [
            'success' => true,
            'message' => '登出成功',
        ], 200 );
    }
    
    /**
     * 获取客服信息
     */
    public static function get_agent_info( WP_REST_Request $request ): WP_REST_Response {
        $agent = $request->get_param( '_agent' );
        
        return new WP_REST_Response( [
            'success' => true,
            'agent'   => $agent,
        ], 200 );
    }
    
    /**
     * 获取会话列表
     */
    public static function get_conversations( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'tz_cs_conversations';
        
        $status = $request->get_param( 'status' ) ?? 'all';
        $page   = max( 1, intval( $request->get_param( 'page' ) ?? 1 ) );
        $limit  = 20;
        $offset = ( $page - 1 ) * $limit;
        
        // 构建查询
        $where = "WHERE 1=1";
        if ( $status !== 'all' ) {
            $where .= $wpdb->prepare( " AND status = %s", $status );
        }
        
        // 获取会话列表
        $conversations = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table $where ORDER BY updated_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ) );
        
        // 获取总数
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table $where" );
        
        return new WP_REST_Response( [
            'success'       => true,
            'conversations' => $conversations,
            'pagination'    => [
                'page'       => $page,
                'limit'      => $limit,
                'total'      => intval( $total ),
                'total_pages' => ceil( $total / $limit ),
            ],
        ], 200 );
    }
    
    /**
     * 获取会话消息
     */
    public static function get_messages( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'tz_cs_messages';
        
        $conversation_id = $request->get_param( 'conversation_id' );
        $page            = max( 1, intval( $request->get_param( 'page' ) ?? 1 ) );
        $limit           = 50;
        $offset          = ( $page - 1 ) * $limit;
        
        // 获取消息列表
        $messages = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table WHERE conversation_id = %s ORDER BY created_at ASC LIMIT %d OFFSET %d",
            $conversation_id,
            $limit,
            $offset
        ) );
        
        // 获取总数
        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE conversation_id = %s",
            $conversation_id
        ) );
        
        return new WP_REST_Response( [
            'success'    => true,
            'messages'   => $messages,
            'pagination' => [
                'page'        => $page,
                'limit'       => $limit,
                'total'       => intval( $total ),
                'total_pages' => ceil( $total / $limit ),
            ],
        ], 200 );
    }
    
    /**
     * 发送消息
     */
    public static function send_message( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;
        $table_messages      = $wpdb->prefix . 'tz_cs_messages';
        $table_conversations = $wpdb->prefix . 'tz_cs_conversations';
        
        $agent           = $request->get_param( '_agent' );
        $conversation_id = $request->get_param( 'conversation_id' );
        $message         = $request->get_param( 'message' );
        $message_type    = $request->get_param( 'message_type' ) ?? 'text';
        
        if ( ! $conversation_id || ! $message ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => '会话ID和消息内容不能为空',
            ], 400 );
        }
        
        // 插入消息
        $result = $wpdb->insert(
            $table_messages,
            [
                'conversation_id' => $conversation_id,
                'sender_type'     => 'agent',
                'sender_id'       => $agent['agent_id'],
                'message'         => $message,
                'message_type'    => $message_type,
                'is_read'         => 0,
                'created_at'      => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%d', '%s' ]
        );
        
        if ( ! $result ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => '发送失败',
            ], 500 );
        }
        
        $message_id = $wpdb->insert_id;
        
        // 更新会话最后消息时间
        $wpdb->update(
            $table_conversations,
            [
                'last_message'    => $message,
                'last_message_at' => current_time( 'mysql' ),
                'updated_at'      => current_time( 'mysql' ),
            ],
            [ 'id' => $conversation_id ],
            [ '%s', '%s', '%s' ],
            [ '%s' ]
        );
        
        // 获取完整消息
        $full_message = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_messages WHERE id = %d",
            $message_id
        ) );
        
        return new WP_REST_Response( [
            'success' => true,
            'message' => $full_message,
        ], 200 );
    }
    
    /**
     * 标记消息已读
     */
    public static function mark_as_read( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'tz_cs_messages';
        
        $conversation_id = $request->get_param( 'conversation_id' );
        
        if ( ! $conversation_id ) {
            return new WP_REST_Response( [
                'success' => false,
                'message' => '会话ID不能为空',
            ], 400 );
        }
        
        // 标记访客发送的消息为已读
        $wpdb->update(
            $table,
            [ 'is_read' => 1 ],
            [
                'conversation_id' => $conversation_id,
                'sender_type'     => 'visitor',
            ],
            [ '%d' ],
            [ '%s', '%s' ]
        );
        
        return new WP_REST_Response( [
            'success' => true,
            'message' => '已标记为已读',
        ], 200 );
    }
}
