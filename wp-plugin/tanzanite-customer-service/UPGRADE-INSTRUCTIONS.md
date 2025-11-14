# 客服管理插件升级说明

## 新增功能

### 1. 头像上传功能
- 在后台管理页面，每个客服的头像字段旁边添加"上传头像"按钮
- 点击按钮打开 WordPress 媒体库
- 选择图片后自动填充头像 URL 字段
- 支持预览已上传的头像

### 2. 聊天记录存储
- 创建两个数据库表：
  - `wp_tz_cs_messages`: 存储所有聊天消息
  - `wp_tz_cs_conversations`: 存储会话列表
- 支持访客、登录用户、客服三种身份
- 自动记录消息时间、已读状态
- 支持文本、图片、商品卡片、订单卡片等多种消息类型

## 需要添加的代码

### 在 tanzanite-customer-service.php 中添加：

1. **在文件开头引入数据库类**：
```php
require_once plugin_dir_path( __FILE__ ) . 'includes/class-database.php';
```

2. **在 `__construct()` 中添加激活钩子**：
```php
register_activation_hook( __FILE__, [ 'TZ_CS_Database', 'create_tables' ] );
```

3. **添加新的 REST API 端点**（在 `register_rest_routes()` 方法中）：

```php
// 发送消息
register_rest_route( 'tanzanite/v1', '/customer-service/messages', [
    'methods'  => 'POST',
    'callback' => [ $this, 'send_message' ],
    'permission_callback' => '__return_true',
] );

// 获取消息列表
register_rest_route( 'tanzanite/v1', '/customer-service/messages/(?P<conversation_id>[a-zA-Z0-9_-]+)', [
    'methods'  => 'GET',
    'callback' => [ $this, 'get_messages' ],
    'permission_callback' => '__return_true',
] );

// 获取会话列表（客服端）
register_rest_route( 'tanzanite/v1', '/customer-service/conversations', [
    'methods'  => 'GET',
    'callback' => [ $this, 'get_conversations' ],
    'permission_callback' => [ $this, 'check_agent_permission' ],
] );
```

4. **添加新的方法**：

```php
/**
 * 发送消息
 */
public function send_message( WP_REST_Request $request ): WP_REST_Response {
    global $wpdb;
    
    $conversation_id = sanitize_text_field( $request->get_param( 'conversation_id' ) );
    $message = sanitize_textarea_field( $request->get_param( 'message' ) );
    $sender_type = sanitize_text_field( $request->get_param( 'sender_type' ) ); // visitor, user, agent
    $sender_name = sanitize_text_field( $request->get_param( 'sender_name' ) );
    $agent_id = sanitize_text_field( $request->get_param( 'agent_id' ) );
    
    $table_messages = $wpdb->prefix . 'tz_cs_messages';
    $table_conversations = $wpdb->prefix . 'tz_cs_conversations';
    
    // 插入消息
    $wpdb->insert(
        $table_messages,
        [
            'conversation_id' => $conversation_id,
            'sender_type' => $sender_type,
            'sender_id' => get_current_user_id(),
            'sender_name' => $sender_name,
            'agent_id' => $agent_id,
            'message' => $message,
            'message_type' => 'text',
            'created_at' => current_time( 'mysql' ),
        ]
    );
    
    // 更新会话
    $wpdb->replace(
        $table_conversations,
        [
            'id' => $conversation_id,
            'agent_id' => $agent_id,
            'last_message' => $message,
            'last_message_time' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' ),
        ]
    );
    
    return new WP_REST_Response( [
        'success' => true,
        'message_id' => $wpdb->insert_id,
    ], 200 );
}

/**
 * 获取消息列表
 */
public function get_messages( WP_REST_Request $request ): WP_REST_Response {
    global $wpdb;
    
    $conversation_id = $request->get_param( 'conversation_id' );
    $limit = intval( $request->get_param( 'limit' ) ?: 50 );
    
    $table_messages = $wpdb->prefix . 'tz_cs_messages';
    
    $messages = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table_messages 
        WHERE conversation_id = %s 
        ORDER BY created_at DESC 
        LIMIT %d",
        $conversation_id,
        $limit
    ) );
    
    return new WP_REST_Response( [
        'success' => true,
        'data' => array_reverse( $messages ),
    ], 200 );
}

/**
 * 获取会话列表（客服端）
 */
public function get_conversations( WP_REST_Request $request ): WP_REST_Response {
    global $wpdb;
    
    $table_conversations = $wpdb->prefix . 'tz_cs_conversations';
    
    $conversations = $wpdb->get_results(
        "SELECT * FROM $table_conversations 
        WHERE status = 'active' 
        ORDER BY updated_at DESC 
        LIMIT 100"
    );
    
    return new WP_REST_Response( [
        'success' => true,
        'data' => $conversations,
    ], 200 );
}

/**
 * 检查客服权限
 */
public function check_agent_permission(): bool {
    return current_user_can( 'manage_options' );
}
```

### 在表格中添加头像上传按钮

在 `render_admin_page()` 方法的表格部分，将头像字段改为：

```php
<td>
    <div class="avatar-upload-wrapper">
        <?php if ( ! empty( $agent['avatar'] ) ): ?>
            <img src="<?php echo esc_url( $agent['avatar'] ); ?>" class="avatar-preview" alt="Avatar">
        <?php else: ?>
            <div class="avatar-preview placeholder">无</div>
        <?php endif; ?>
        <input type="hidden" name="agents[<?php echo $index; ?>][avatar]" value="<?php echo esc_url( $agent['avatar'] ?? '' ); ?>" class="avatar-url-input">
        <button type="button" class="button upload-avatar-btn" data-index="<?php echo $index; ?>">上传头像</button>
    </div>
</td>
```

### 在 JavaScript 中添加媒体库上传功能

在页面底部的 `<script>` 标签中添加：

```javascript
// 头像上传
$(document).on('click', '.upload-avatar-btn', function(e) {
    e.preventDefault();
    const button = $(this);
    const index = button.data('index');
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
```

## 安装步骤

1. 将 `includes/class-database.php` 文件放到插件的 `includes` 目录
2. 按照上述说明修改 `tanzanite-customer-service.php`
3. 停用并重新激活插件以创建数据库表
4. 测试头像上传和聊天记录功能

## API 使用示例

### 发送消息
```javascript
POST /wp-json/tanzanite/v1/customer-service/messages
{
  "conversation_id": "visitor_12345",
  "message": "你好，我想咨询一下产品",
  "sender_type": "visitor",
  "sender_name": "访客",
  "agent_id": "agent_1"
}
```

### 获取消息
```javascript
GET /wp-json/tanzanite/v1/customer-service/messages/visitor_12345?limit=50
```

### 获取会话列表（客服端）
```javascript
GET /wp-json/tanzanite/v1/customer-service/conversations
```
