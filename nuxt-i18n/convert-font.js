/**
 * 字体格式转换脚本
 * 将 TTF 转换为 WOFF2 和 WOFF
 * 
 * 使用方法：
 * 1. 安装依赖：npm install ttf2woff2 ttf2woff
 * 2. 运行脚本：node convert-font.cjs
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// 字体文件路径
const inputPath = path.join(__dirname, 'app/assets/fonts/AerialFasterRegular-Yqd5o.ttf');
const outputWoff2Path = path.join(__dirname, 'app/assets/fonts/AerialFasterRegular.woff2');
const outputWoffPath = path.join(__dirname, 'app/assets/fonts/AerialFasterRegular.woff');

console.log('开始转换字体...');
console.log('输入文件:', inputPath);

// 检查输入文件是否存在
if (!fs.existsSync(inputPath)) {
  console.error('❌ 错误：找不到字体文件！');
  console.error('请确保文件路径正确:', inputPath);
  process.exit(1);
}

// 读取 TTF 文件
const ttfBuffer = fs.readFileSync(inputPath);
console.log('✅ 成功读取 TTF 文件，大小:', (ttfBuffer.length / 1024).toFixed(2), 'KB');

// 转换为 WOFF2
try {
  const ttf2woff2Module = await import('ttf2woff2');
  const ttf2woff2 = ttf2woff2Module.default;
  const woff2Buffer = ttf2woff2(ttfBuffer);
  fs.writeFileSync(outputWoff2Path, woff2Buffer);
  console.log('✅ 成功生成 WOFF2 文件，大小:', (woff2Buffer.length / 1024).toFixed(2), 'KB');
  console.log('   压缩率:', ((1 - woff2Buffer.length / ttfBuffer.length) * 100).toFixed(1), '%');
} catch (error) {
  console.error('❌ WOFF2 转换失败:', error.message);
  console.log('💡 请先安装依赖：npm install ttf2woff2');
}

// 转换为 WOFF
try {
  const ttf2woffModule = await import('ttf2woff');
  const ttf2woff = ttf2woffModule.default;
  const woffBuffer = Buffer.from(ttf2woff(ttfBuffer).buffer);
  fs.writeFileSync(outputWoffPath, woffBuffer);
  console.log('✅ 成功生成 WOFF 文件，大小:', (woffBuffer.length / 1024).toFixed(2), 'KB');
  console.log('   压缩率:', ((1 - woffBuffer.length / ttfBuffer.length) * 100).toFixed(1), '%');
} catch (error) {
  console.error('❌ WOFF 转换失败:', error.message);
  console.log('💡 请先安装依赖：npm install ttf2woff');
}

console.log('\n🎉 字体转换完成！');
console.log('输出文件：');
console.log('  - WOFF2:', outputWoff2Path);
console.log('  - WOFF:', outputWoffPath);
