# Chat-for-Theme App 对接方案分析

## 📱 App 概述

**Chat-for-Theme** 是一个基于 **Expo + React Native + TypeScript** 的移动端客服聊天 App，用于客服人员在手机上处理客户咨询。

### **技术栈：**
- **框架**：Expo 51.0 + React Native 0.74
- **导航**：React Navigation (Native Stack)
- **语言**：TypeScript
- **推送**：Expo Notifications
- **文件选择**：Expo Image Picker + Document Picker

---

## 🏗️ App 架构分析

### **1. 页面结构**

```
App.tsx (主入口)
├── ChatList.tsx (会话列表页)
│   ├── 显示所有客户会话
│   ├── 显示在线状态
│   ├── 显示未读消息数
│   └── 点击进入聊天页
│
└── Chat.tsx (聊天页)
    ├── 显示消息列表
    ├── 显示在线/离线状态
    ├── 发送文本消息
    ├── 发送图片（占位）
    └── 发送文件（占位）
```

### **2. 服务层**

```typescript
services/
├── heartbeat.ts        // 心跳检测（在线状态）
└── notifications.ts    // 推送通知
```

### **3. 当前状态**

| 功能 | 状态 | 说明 |
|------|------|------|
| UI 界面 | ✅ 完成 | 会话列表、聊天界面 |
| 本地消息 | ✅ 完成 | 使用 useState 存储 |
| 在线状态 | ⚠️ 占位 | 有心跳逻辑但未对接 |
| 消息发送 | ⚠️ 占位 | 仅本地添加，未发送到服务器 |
| 消息接收 | ❌ 未实现 | 需要轮询或 WebSocket |
| 推送通知 | ⚠️ 占位 | 已集成 Expo Push，未对接后端 |
| 图片/文件 | ⚠️ 占位 | 已集成选择器，未上传 |

---

## 🔗 对接步骤分析

### **第一步：后端 API 设计**

需要在 **Tanzanite Setting 插件**或 **WordPress** 中创建以下 REST API：

#### **1.1 会话列表 API**

```
GET /wp-json/tanzanite/v1/chat/conversations
```

**参数：**
- `agent_id` (integer) - 客服 ID（当前登录用户）
- `status` (string) - 会话状态（active/closed）
- `page` (integer) - 页码
- `per_page` (integer) - 每页数量

**返回：**
```json
{
  "items": [
    {
      "id": "conv-001",
      "customer_id": 123,
      "customer_name": "张三",
      "customer_avatar": "https://...",
      "customer_phone": "+86 138 xxxx xxxx",
      "last_message": "你好，我想咨询...",
      "last_message_time": "2024-01-01 14:20:00",
      "unread_count": 2,
      "status": "active",
      "online": true
    }
  ],
  "meta": {
    "total": 50,
    "page": 1,
    "per_page": 20
  }
}
```

#### **1.2 消息列表 API**

```
GET /wp-json/tanzanite/v1/chat/messages/{conversation_id}
```

**参数：**
- `page` (integer) - 页码
- `per_page` (integer) - 每页数量（默认 50）
- `before_id` (integer) - 获取此消息之前的消息（分页加载）

**返回：**
```json
{
  "items": [
    {
      "id": 1,
      "conversation_id": "conv-001",
      "sender_id": 123,
      "sender_name": "张三",
      "sender_type": "customer",
      "message": "你好，我想咨询订单问题",
      "type": "text",
      "attachment_url": null,
      "created_at": "2024-01-01 14:20:00",
      "is_read": true
    },
    {
      "id": 2,
      "conversation_id": "conv-001",
      "sender_id": 456,
      "sender_name": "客服小王",
      "sender_type": "agent",
      "message": "您好，请问订单号是多少？",
      "type": "text",
      "attachment_url": null,
      "created_at": "2024-01-01 14:21:00",
      "is_read": true
    }
  ],
  "meta": {
    "total": 100,
    "has_more": true
  }
}
```

#### **1.3 发送消息 API**

```
POST /wp-json/tanzanite/v1/chat/send
```

**请求体：**
```json
{
  "conversation_id": "conv-001",
  "message": "您好，我来帮您查询",
  "type": "text",
  "attachment_url": null
}
```

**返回：**
```json
{
  "success": true,
  "message": {
    "id": 3,
    "conversation_id": "conv-001",
    "sender_id": 456,
    "sender_name": "客服小王",
    "sender_type": "agent",
    "message": "您好，我来帮您查询",
    "type": "text",
    "created_at": "2024-01-01 14:22:00"
  }
}
```

#### **1.4 在线状态 API**

```
GET /wp-json/tanzanite/v1/chat/status
```

**参数：**
- `conversation_ids` (string) - 会话 ID 列表，逗号分隔

**返回：**
```json
{
  "statuses": [
    {
      "conversation_id": "conv-001",
      "customer_id": 123,
      "online": true,
      "last_seen": 1704096000
    },
    {
      "conversation_id": "conv-002",
      "customer_id": 124,
      "online": false,
      "last_seen": 1704092400
    }
  ]
}
```

#### **1.5 标记已读 API**

```
POST /wp-json/tanzanite/v1/chat/mark-read/{conversation_id}
```

**返回：**
```json
{
  "success": true,
  "unread_count": 0
}
```

#### **1.6 上传文件 API**

```
POST /wp-json/tanzanite/v1/chat/upload
```

**请求：** `multipart/form-data`
- `file` - 文件
- `conversation_id` - 会话 ID

**返回：**
```json
{
  "success": true,
  "url": "https://example.com/uploads/2024/01/image.jpg",
  "type": "image",
  "size": 102400
}
```

---

### **第二步：App 端修改**

#### **2.1 配置 API 基础 URL**

修改 `src/services/heartbeat.ts`：

```typescript
const BASE_URL = 'https://your-domain.com'; // 替换为你的域名

// 或者使用环境变量
const BASE_URL = process.env.EXPO_PUBLIC_API_URL || 'https://your-domain.com';
```

#### **2.2 创建 API 服务层**

创建 `src/services/api.ts`：

```typescript
const BASE_URL = 'https://your-domain.com';

// 获取会话列表
export async function fetchConversations(agentId: number) {
  const res = await fetch(
    `${BASE_URL}/wp-json/tanzanite/v1/chat/conversations?agent_id=${agentId}`,
    {
      method: 'GET',
      credentials: 'include', // 发送 Cookie
      headers: {
        'Content-Type': 'application/json',
      }
    }
  );
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

// 获取消息列表
export async function fetchMessages(conversationId: string, page = 1) {
  const res = await fetch(
    `${BASE_URL}/wp-json/tanzanite/v1/chat/messages/${conversationId}?page=${page}`,
    {
      method: 'GET',
      credentials: 'include',
    }
  );
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

// 发送消息
export async function sendMessage(conversationId: string, message: string, type = 'text') {
  const res = await fetch(
    `${BASE_URL}/wp-json/tanzanite/v1/chat/send`,
    {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        conversation_id: conversationId,
        message,
        type
      })
    }
  );
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

// 上传文件
export async function uploadFile(conversationId: string, file: any) {
  const formData = new FormData();
  formData.append('file', {
    uri: file.uri,
    type: file.type || 'image/jpeg',
    name: file.fileName || 'upload.jpg'
  } as any);
  formData.append('conversation_id', conversationId);

  const res = await fetch(
    `${BASE_URL}/wp-json/tanzanite/v1/chat/upload`,
    {
      method: 'POST',
      credentials: 'include',
      body: formData
    }
  );
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}
```

#### **2.3 修改 ChatList.tsx**

```typescript
import { fetchConversations } from '@/services/api';

export default function ChatList({ navigation }: Props) {
  const [chats, setChats] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadConversations();
    const interval = setInterval(loadConversations, 30000); // 每 30 秒刷新
    return () => clearInterval(interval);
  }, []);

  const loadConversations = async () => {
    try {
      const agentId = 1; // TODO: 从登录状态获取
      const data = await fetchConversations(agentId);
      setChats(data.items.map(item => ({
        id: item.id,
        title: item.customer_name,
        last: item.last_message,
        time: formatTime(item.last_message_time),
        unread: item.unread_count,
        online: item.online
      })));
    } catch (error) {
      console.error('加载会话失败:', error);
    } finally {
      setLoading(false);
    }
  };

  // ... 其余代码
}
```

#### **2.4 修改 Chat.tsx**

```typescript
import { fetchMessages, sendMessage, uploadFile } from '@/services/api';

export default function Chat({ route }: Props) {
  const { chatId } = route.params;
  const [msgs, setMsgs] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadMessages();
    const interval = setInterval(loadNewMessages, 5000); // 每 5 秒检查新消息
    return () => clearInterval(interval);
  }, [chatId]);

  const loadMessages = async () => {
    try {
      const data = await fetchMessages(chatId);
      setMsgs(data.items.map(item => ({
        id: item.id.toString(),
        text: item.message,
        mine: item.sender_type === 'agent',
        time: formatTime(item.created_at)
      })));
    } catch (error) {
      console.error('加载消息失败:', error);
    } finally {
      setLoading(false);
    }
  };

  const loadNewMessages = async () => {
    // 只加载最新的消息
    try {
      const lastMsgId = msgs.length > 0 ? msgs[msgs.length - 1].id : 0;
      const data = await fetchMessages(chatId, 1);
      const newMsgs = data.items
        .filter(item => item.id > lastMsgId)
        .map(item => ({
          id: item.id.toString(),
          text: item.message,
          mine: item.sender_type === 'agent',
          time: formatTime(item.created_at)
        }));
      if (newMsgs.length > 0) {
        setMsgs(prev => [...prev, ...newMsgs]);
      }
    } catch (error) {
      console.error('加载新消息失败:', error);
    }
  };

  const send = async () => {
    if (!text.trim()) return;
    const messageText = text.trim();
    setText('');

    // 乐观更新 UI
    const tempMsg = {
      id: `temp-${Date.now()}`,
      text: messageText,
      mine: true,
      time: new Date().toLocaleTimeString().slice(0, 5)
    };
    setMsgs(prev => [...prev, tempMsg]);

    try {
      const result = await sendMessage(chatId, messageText);
      // 替换临时消息为服务器返回的消息
      setMsgs(prev => prev.map(m => 
        m.id === tempMsg.id 
          ? { ...m, id: result.message.id.toString() }
          : m
      ));
    } catch (error) {
      console.error('发送失败:', error);
      // 移除临时消息
      setMsgs(prev => prev.filter(m => m.id !== tempMsg.id));
      Alert.alert('发送失败', '请检查网络连接');
    }
  };

  const onPickImage = async () => {
    try {
      setSheetVisible(false);
      const perm = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (!perm.granted) {
        Alert.alert('权限提示', '需要相册权限');
        return;
      }
      
      const res = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        quality: 0.8
      });
      
      if (res.canceled) return;
      const asset = res.assets[0];

      // 上传图片
      const uploadResult = await uploadFile(chatId, asset);
      
      // 发送图片消息
      await sendMessage(chatId, '[图片]', 'image');
      
      // 刷新消息列表
      loadMessages();
    } catch (error) {
      console.error('上传图片失败:', error);
      Alert.alert('上传失败', '请重试');
    }
  };

  // ... 其余代码
}
```

---

### **第三步：身份认证**

#### **3.1 登录流程**

创建 `src/services/auth.ts`：

```typescript
const BASE_URL = 'https://your-domain.com';

export async function login(username: string, password: string) {
  const res = await fetch(
    `${BASE_URL}/wp-json/tanzanite/v1/auth/login`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ username, password })
    }
  );
  
  if (!res.ok) throw new Error('登录失败');
  
  const data = await res.json();
  
  // 保存 token 或 cookie
  await AsyncStorage.setItem('auth_token', data.token);
  await AsyncStorage.setItem('user_id', data.user.id.toString());
  
  return data;
}

export async function logout() {
  await AsyncStorage.removeItem('auth_token');
  await AsyncStorage.removeItem('user_id');
}

export async function getAuthToken() {
  return await AsyncStorage.getItem('auth_token');
}
```

#### **3.2 添加登录页**

创建 `src/screens/Login.tsx`：

```typescript
export default function Login({ navigation }: Props) {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async () => {
    if (!username || !password) {
      Alert.alert('提示', '请输入用户名和密码');
      return;
    }

    setLoading(true);
    try {
      await login(username, password);
      navigation.replace('ChatList');
    } catch (error) {
      Alert.alert('登录失败', '用户名或密码错误');
    } finally {
      setLoading(false);
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>客服登录</Text>
      <TextInput
        style={styles.input}
        placeholder="用户名"
        value={username}
        onChangeText={setUsername}
        autoCapitalize="none"
      />
      <TextInput
        style={styles.input}
        placeholder="密码"
        value={password}
        onChangeText={setPassword}
        secureTextEntry
      />
      <TouchableOpacity
        style={styles.button}
        onPress={handleLogin}
        disabled={loading}
      >
        <Text style={styles.buttonText}>
          {loading ? '登录中...' : '登录'}
        </Text>
      </TouchableOpacity>
    </View>
  );
}
```

---

### **第四步：推送通知对接**

#### **4.1 注册推送 Token**

修改 `App.tsx`：

```typescript
useEffect(() => {
  (async () => {
    const { token, granted } = await registerForPushNotificationsAsync();
    if (granted && token) {
      // 上报到后端
      await fetch(`${BASE_URL}/wp-json/tanzanite/v1/push/register`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          token,
          platform: Platform.OS,
          device_id: await getDeviceId()
        })
      });
    }
  })();
}, []);
```

#### **4.2 后端推送 API**

```
POST /wp-json/tanzanite/v1/push/register
```

**请求体：**
```json
{
  "token": "ExponentPushToken[xxxxxx]",
  "platform": "ios",
  "device_id": "unique-device-id"
}
```

#### **4.3 发送推送（后端）**

当有新消息时，后端调用 Expo Push API：

```php
function send_push_notification($user_id, $message) {
    global $wpdb;
    
    // 获取用户的推送 token
    $tokens = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT token FROM {$wpdb->prefix}push_tokens WHERE user_id = %d",
            $user_id
        )
    );
    
    foreach ($tokens as $token_row) {
        $data = [
            'to' => $token_row->token,
            'title' => '新消息',
            'body' => $message,
            'data' => ['type' => 'chat_message']
        ];
        
        wp_remote_post('https://exp.host/--/api/v2/push/send', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);
    }
}
```

---

### **第五步：实时消息（可选）**

#### **方案 A：轮询（已实现）**
- 每 5 秒请求一次新消息
- 简单但耗电

#### **方案 B：WebSocket**

创建 `src/services/websocket.ts`：

```typescript
export class ChatWebSocket {
  private ws: WebSocket | null = null;
  private listeners: Map<string, Function[]> = new Map();

  connect(userId: number) {
    this.ws = new WebSocket(`wss://your-domain.com/ws?user=${userId}`);
    
    this.ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      this.emit(data.type, data);
    };
  }

  on(event: string, callback: Function) {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, []);
    }
    this.listeners.get(event)!.push(callback);
  }

  emit(event: string, data: any) {
    const callbacks = this.listeners.get(event) || [];
    callbacks.forEach(cb => cb(data));
  }

  send(data: any) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(data));
    }
  }

  disconnect() {
    if (this.ws) {
      this.ws.close();
      this.ws = null;
    }
  }
}
```

使用：

```typescript
const ws = new ChatWebSocket();

useEffect(() => {
  ws.connect(userId);
  
  ws.on('new_message', (data) => {
    setMsgs(prev => [...prev, data.message]);
  });

  return () => ws.disconnect();
}, []);
```

---

## 📋 完整对接清单

### **后端任务：**

- [ ] 创建聊天数据库表
  - [ ] `conversations` 表（会话）
  - [ ] `messages` 表（消息）
  - [ ] `push_tokens` 表（推送 token）
- [ ] 创建 REST API
  - [ ] 会话列表 API
  - [ ] 消息列表 API
  - [ ] 发送消息 API
  - [ ] 在线状态 API
  - [ ] 标记已读 API
  - [ ] 上传文件 API
  - [ ] 登录 API
  - [ ] 推送注册 API
- [ ] 实现推送通知
  - [ ] 集成 Expo Push API
  - [ ] 新消息触发推送
- [ ] （可选）实现 WebSocket

### **App 端任务：**

- [ ] 配置 API 基础 URL
- [ ] 创建 API 服务层
  - [ ] `api.ts`
  - [ ] `auth.ts`
  - [ ] `websocket.ts`（可选）
- [ ] 修改现有页面
  - [ ] `ChatList.tsx` - 对接会话列表 API
  - [ ] `Chat.tsx` - 对接消息 API
- [ ] 添加登录功能
  - [ ] 创建 `Login.tsx`
  - [ ] 添加身份认证逻辑
  - [ ] 保存登录状态
- [ ] 完善文件上传
  - [ ] 图片上传
  - [ ] 文件上传
  - [ ] 显示上传进度
- [ ] 推送通知对接
  - [ ] 注册推送 token
  - [ ] 处理推送消息
  - [ ] 点击通知跳转到聊天
- [ ] 优化体验
  - [ ] 加载状态
  - [ ] 错误处理
  - [ ] 离线缓存
  - [ ] 消息重发

---

## 🎯 优先级建议

### **第一阶段（核心功能）：**
1. ✅ 后端创建基础 API（会话列表、消息列表、发送消息）
2. ✅ App 对接 API（显示会话、显示消息、发送消息）
3. ✅ 添加登录功能

### **第二阶段（完善功能）：**
4. ✅ 文件上传功能
5. ✅ 在线状态显示
6. ✅ 推送通知

### **第三阶段（优化）：**
7. ✅ WebSocket 实时通信
8. ✅ 离线缓存
9. ✅ 消息已读状态
10. ✅ 输入状态提示

---

## 📝 总结

**Chat-for-Theme App** 是一个完整的移动端客服聊天框架，目前 UI 和基础功能已完成，需要对接后端 API 才能实现真实的聊天功能。

**核心对接点：**
1. **后端 REST API** - 提供会话、消息、发送等接口
2. **身份认证** - 客服登录验证
3. **文件上传** - 图片和文件上传到服务器
4. **推送通知** - 新消息实时提醒
5. **实时通信** - 轮询或 WebSocket

按照上述步骤逐步实现，即可完成 App 与后端的完整对接！🎉
