#!/bin/bash
# Nuxt I18N Widget - 快速服务器部署脚本
# 使用方法：bash quick-deploy.sh

# ============ 配置区域 ============
# 修改以下变量为你的服务器信息
SERVER_USER="your_username"           # SSH 用户名
SERVER_HOST="your_server_ip"          # 服务器 IP 或域名
SERVER_PATH="/public_html/widget"     # 服务器上的目标路径
SSH_PORT="22"                         # SSH 端口，默认 22

# ============ 部署流程 ============

echo "=========================================="
echo "  Nuxt I18N Widget 服务器部署工具"
echo "=========================================="
echo ""

# 检查构建产物
if [ ! -d ".output/public" ]; then
    echo "❌ 错误：找不到构建产物"
    echo "   请先运行：npm run generate"
    exit 1
fi

echo "✓ 找到构建产物"
echo ""

# 打包文件
echo "📦 正在打包文件..."
tar -czf widget-deploy.tar.gz -C .output/public .

if [ $? -eq 0 ]; then
    echo "✓ 打包完成：widget-deploy.tar.gz"
else
    echo "❌ 打包失败"
    exit 1
fi
echo ""

# 上传到服务器
echo "📤 正在上传到服务器..."
echo "   服务器：$SERVER_HOST"
echo "   路径：$SERVER_PATH"
echo ""

scp -P $SSH_PORT widget-deploy.tar.gz $SERVER_USER@$SERVER_HOST:/tmp/

if [ $? -ne 0 ]; then
    echo "❌ 上传失败，请检查服务器连接信息"
    exit 1
fi

echo "✓ 上传完成"
echo ""

# SSH 执行部署命令
echo "🚀 正在服务器上部署..."
ssh -p $SSH_PORT $SERVER_USER@$SERVER_HOST << 'ENDSSH'
    # 进入目标目录的父目录
    cd $(dirname $SERVER_PATH)
    
    # 创建 widget 目录
    mkdir -p widget
    
    # 备份旧文件（如果存在）
    if [ -d "widget/_nuxt" ]; then
        echo "   备份旧文件..."
        tar -czf widget-backup-$(date +%Y%m%d-%H%M%S).tar.gz widget/
    fi
    
    # 清空目录
    rm -rf widget/*
    
    # 解压新文件
    echo "   解压文件..."
    tar -xzf /tmp/widget-deploy.tar.gz -C widget/
    
    # 设置权限
    echo "   设置权限..."
    chmod -R 755 widget/
    find widget/ -type f -exec chmod 644 {} \;
    
    # 清理临时文件
    rm /tmp/widget-deploy.tar.gz
    
    echo "   ✓ 服务器部署完成"
ENDSSH

if [ $? -eq 0 ]; then
    echo ""
    echo "=========================================="
    echo "  ✅ 部署成功！"
    echo "=========================================="
    echo ""
    echo "访问以下 URL 测试："
    echo "  https://你的域名.com/widget/fr/"
    echo "  https://你的域名.com/widget/de/"
    echo "  https://你的域名.com/widget/es/"
    echo ""
else
    echo ""
    echo "❌ 部署失败"
    exit 1
fi

# 清理本地临时文件
rm widget-deploy.tar.gz
