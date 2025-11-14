<?php
/**
 * 客服认证类
 * 
 * @package Tanzanite_Customer_Service
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TZ_CS_Agent_Auth {
    
    /**
     * 客服登录
     * 
     * @param string $agent_id 客服工号
     * @param string $password 密码
     * @param string $device_info 设备信息
     * @return array|WP_Error
     */
    public static function login( string $agent_id, string $password, string $device_info = '' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'tz_cs_agents';
        
        // 1. 查找客服
        $agent = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE agent_id = %s AND status = 'active'",
            $agent_id
        ) );
        
        if ( ! $agent ) {
            return new WP_Error( 'invalid_agent', '客服账号不存在或已禁用' );
        }
        
        // 2. 验证密码
        if ( ! password_verify( $password, $agent->password ) ) {
            return new WP_Error( 'invalid_password', '密码错误' );
        }
        
        // 3. 生成 Token
        $token = self::generate_token( $agent_id, $device_info );
        
        // 4. 更新最后登录时间
        $wpdb->update(
            $table,
            [ 'last_login' => current_time( 'mysql' ) ],
            [ 'agent_id' => $agent_id ],
            [ '%s' ],
            [ '%s' ]
        );
        
        return [
            'success' => true,
            'token'   => $token,
            'agent'   => [
                'agent_id' => $agent->agent_id,
                'name'     => $agent->name,
                'email'    => $agent->email,
                'avatar'   => $agent->avatar,
            ],
        ];
    }
    
    /**
     * 生成 Token
     * 
     * @param string $agent_id 客服工号
     * @param string $device_info 设备信息
     * @return string
     */
    private static function generate_token( string $agent_id, string $device_info = '' ): string {
        global $wpdb;
        $table = $wpdb->prefix . 'tz_cs_tokens';
        
        // 生成随机 Token（64 字符）
        $token = bin2hex( random_bytes( 32 ) );
        
        // 过期时间：30 天
        $expires_at = date( 'Y-m-d H:i:s', strtotime( '+30 days' ) );
        
        // 保存到数据库
        $wpdb->insert(
            $table,
            [
                'agent_id'    => $agent_id,
                'token'       => $token,
                'device_info' => $device_info,
                'expires_at'  => $expires_at,
                'created_at'  => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s' ]
        );
        
        return $token;
    }
    
    /**
     * 验证 Token
     * 
     * @param string $token Token
     * @return array|false
     */
    public static function verify_token( string $token ) {
        global $wpdb;
        $table_tokens = $wpdb->prefix . 'tz_cs_tokens';
        $table_agents = $wpdb->prefix . 'tz_cs_agents';
        
        // 1. 查找 Token
        $token_data = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_tokens WHERE token = %s AND expires_at > NOW()",
            $token
        ) );
        
        if ( ! $token_data ) {
            return false;
        }
        
        // 2. 查找客服
        $agent = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_agents WHERE agent_id = %s AND status = 'active'",
            $token_data->agent_id
        ) );
        
        if ( ! $agent ) {
            return false;
        }
        
        return [
            'agent_id' => $agent->agent_id,
            'name'     => $agent->name,
            'email'    => $agent->email,
            'avatar'   => $agent->avatar,
        ];
    }
    
    /**
     * 登出（删除 Token）
     * 
     * @param string $token Token
     * @return bool
     */
    public static function logout( string $token ): bool {
        global $wpdb;
        $table = $wpdb->prefix . 'tz_cs_tokens';
        
        $wpdb->delete( $table, [ 'token' => $token ], [ '%s' ] );
        
        return true;
    }
    
    /**
     * 清理过期 Token
     */
    public static function cleanup_expired_tokens(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'tz_cs_tokens';
        
        $wpdb->query( "DELETE FROM $table WHERE expires_at < NOW()" );
    }
}
