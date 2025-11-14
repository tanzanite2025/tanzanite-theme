<?php
/**
 * 自动回复 REST API
 * 
 * @package Tanzanite_Customer_Service
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TZ_CS_Auto_Reply_API {
    
    /**
     * 注册 REST API 路由
     */
    public static function register_routes(): void {
        // 获取欢迎语
        register_rest_route( 'tanzanite/v1', '/auto-reply/welcome', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_welcome_message' ],
            'permission_callback' => '__return_true',
        ] );
        
        // 匹配关键词回复
        register_rest_route( 'tanzanite/v1', '/auto-reply/match', [
            'methods'  => 'POST',
            'callback' => [ __CLASS__, 'match_keyword' ],
            'permission_callback' => '__return_true',
            'args' => [
                'message' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'conversation_id' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ] );
        
        // 保存自动回复设置（管理员）
        register_rest_route( 'tanzanite/v1', '/auto-reply/settings', [
            'methods'  => 'POST',
            'callback' => [ __CLASS__, 'save_settings' ],
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ] );
        
        // 获取所有自动回复规则（管理员）
        register_rest_route( 'tanzanite/v1', '/auto-reply/rules', [
            'methods'  => 'GET',
            'callback' => [ __CLASS__, 'get_rules' ],
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            },
        ] );
    }
    
    /**
     * 获取欢迎语
     */
    public static function get_welcome_message( WP_REST_Request $request ) {
        global $wpdb;
        
        $conversation_id = $request->get_param( 'conversation_id' );
        
        // 检查24小时内是否已发送欢迎语
        if ( $conversation_id ) {
            $table_conversations = $wpdb->prefix . 'tz_cs_conversations';
            $conversation = $wpdb->get_row( $wpdb->prepare(
                "SELECT last_welcome_sent FROM $table_conversations WHERE id = %s",
                $conversation_id
            ) );
            
            if ( $conversation && $conversation->last_welcome_sent ) {
                $last_sent = strtotime( $conversation->last_welcome_sent );
                $now = current_time( 'timestamp' );
                
                // 24小时内已发送，不再发送
                if ( ( $now - $last_sent ) < 86400 ) {
                    return new WP_REST_Response( [
                        'success' => true,
                        'data' => [
                            'message' => null,
                            'already_sent' => true,
                        ],
                    ], 200 );
                }
            }
        }
        
        // 获取欢迎语
        $table_auto_replies = $wpdb->prefix . 'tz_cs_auto_replies';
        $welcome = $wpdb->get_row( $wpdb->prepare(
            "SELECT reply_message FROM $table_auto_replies 
             WHERE type = %s AND is_active = 1 
             ORDER BY priority DESC LIMIT 1",
            'welcome'
        ) );
        
        if ( ! $welcome ) {
            return new WP_REST_Response( [
                'success' => true,
                'data' => [
                    'message' => null,
                ],
            ], 200 );
        }
        
        // 更新最后发送时间
        if ( $conversation_id ) {
            $wpdb->update(
                $table_conversations,
                [ 'last_welcome_sent' => current_time( 'mysql' ) ],
                [ 'id' => $conversation_id ],
                [ '%s' ],
                [ '%s' ]
            );
        }
        
        return new WP_REST_Response( [
            'success' => true,
            'data' => [
                'message' => $welcome->reply_message,
            ],
        ], 200 );
    }
    
    /**
     * 匹配关键词回复
     */
    public static function match_keyword( WP_REST_Request $request ) {
        global $wpdb;
        
        $message = trim( $request->get_param( 'message' ) );
        $conversation_id = $request->get_param( 'conversation_id' );
        
        if ( empty( $message ) ) {
            return new WP_REST_Response( [
                'success' => true,
                'data' => [
                    'reply' => null,
                ],
            ], 200 );
        }
        
        // 获取所有启用的关键词规则
        $table_auto_replies = $wpdb->prefix . 'tz_cs_auto_replies';
        $rules = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_auto_replies 
             WHERE type = %s AND is_active = 1 
             ORDER BY priority DESC",
            'keyword'
        ) );
        
        $matched_reply = null;
        
        foreach ( $rules as $rule ) {
            // 精确匹配
            if ( $rule->match_type === 'exact' ) {
                if ( strcasecmp( $message, $rule->trigger_keyword ) === 0 ) {
                    $matched_reply = $rule->reply_message;
                    break;
                }
            }
        }
        
        // 如果匹配到关键词，更新对话标记
        if ( $matched_reply && $conversation_id ) {
            $table_conversations = $wpdb->prefix . 'tz_cs_conversations';
            
            // 增加自动回复计数
            $wpdb->query( $wpdb->prepare(
                "UPDATE $table_conversations 
                 SET has_auto_reply = 1, 
                     auto_reply_count = auto_reply_count + 1,
                     needs_human = CASE WHEN auto_reply_count + 1 >= 3 THEN 1 ELSE needs_human END
                 WHERE id = %s",
                $conversation_id
            ) );
        }
        
        return new WP_REST_Response( [
            'success' => true,
            'data' => [
                'reply' => $matched_reply,
            ],
        ], 200 );
    }
    
    /**
     * 保存自动回复设置
     */
    public static function save_settings( WP_REST_Request $request ) {
        global $wpdb;
        
        $type = $request->get_param( 'type' );
        $message = $request->get_param( 'message' );
        $keyword = $request->get_param( 'keyword' );
        $id = $request->get_param( 'id' );
        
        $table_auto_replies = $wpdb->prefix . 'tz_cs_auto_replies';
        
        $data = [
            'type' => $type,
            'reply_message' => $message,
            'trigger_keyword' => $keyword,
            'updated_at' => current_time( 'mysql' ),
        ];
        
        if ( $id ) {
            // 更新现有规则
            $wpdb->update(
                $table_auto_replies,
                $data,
                [ 'id' => $id ],
                [ '%s', '%s', '%s', '%s' ],
                [ '%d' ]
            );
        } else {
            // 创建新规则
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_auto_replies, $data );
            $id = $wpdb->insert_id;
        }
        
        return new WP_REST_Response( [
            'success' => true,
            'data' => [
                'id' => $id,
            ],
        ], 200 );
    }
    
    /**
     * 获取所有规则
     */
    public static function get_rules( WP_REST_Request $request ) {
        global $wpdb;
        
        $table_auto_replies = $wpdb->prefix . 'tz_cs_auto_replies';
        $rules = $wpdb->get_results(
            "SELECT * FROM $table_auto_replies ORDER BY type, priority DESC, created_at DESC"
        );
        
        return new WP_REST_Response( [
            'success' => true,
            'data' => $rules,
        ], 200 );
    }
}
