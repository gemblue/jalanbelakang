# JalanBelakang - Advanced PHP Backdoor Research Tool

![JalanBelakang Screenshot](https://image.web.id/images/Screenshot-2025-10-17-at-23.55.59.png)

## ⚡ Overview

**JalanBelakang** adalah tool penelitian backdoor PHP yang komprehensif dengan antarmuka web yang elegan dan fitur-fitur canggih untuk penetration testing dan analisis keamanan. Dibuat untuk tujuan edukasi dan penelitian keamanan siber.

## 🔥 Features

### 📁 **File Manager**
- Browse, view, edit, rename, delete files dan directories
- Upload dan download files
- Create new directories
- Navigate through directory structure
- Display file permissions, sizes, dan timestamps
- Drag & drop file operations

### 💻 **Terminal**
- Full command execution interface
- Real-time output display
- Command history
- Support untuk semua system commands
- Cross-platform compatibility

### 🔍 **Advanced Search**
- Search files by name atau content
- Regex pattern support
- Recursive directory searching
- Content-based file searching
- Fast indexing dan caching

### 🗄️ **Database Explorer**
- Connect ke multiple database types:
  - MySQL
  - PostgreSQL
  - SQLite
  - SQL Server
- Execute SQL queries dengan syntax highlighting
- Browse results dalam table format
- Export query results

### 🌐 **Network Tools**
- **Reverse Shell**: Multiple connection methods
- **Port Scanner**: Fast network reconnaissance
- **Packet Crafting**: Custom network packets
- **Bind Shell**: Server-side shell listeners
- Network interface monitoring

### ⚙️ **Process Manager**
- View running processes
- Kill processes by PID
- System resource monitoring
- Process tree visualization
- Performance metrics

### 📧 **Mail Sender**
- Send emails dengan attachments
- Support untuk HTML/plain text
- Use local files sebagai attachments
- SMTP configuration
- Mail queue management

### 🔄 **String Converter**
- Base64 encode/decode
- URL encode/decode
- Hash functions (MD5, SHA1, SHA256, SHA512)
- HTML encode/decode
- Hex encode/decode
- Custom encoding schemes

### 📝 **Script Executor**
- Multi-language support:
  - PHP
  - Python
  - Perl
  - Ruby
  - Node.js
  - Bash/Shell
- Syntax highlighting
- Error handling
- Output capturing

## 🎨 Design Features

- **Cyberpunk Theme**: Dark theme dengan neon green/pink accents
- **FontAwesome Icons**: Professional icon set
- **Responsive Design**: Works pada desktop dan mobile
- **Tab-based Navigation**: Organized workflow
- **Terminal-style Output**: Authentic hacker feel
- **Smooth Animations**: Modern UI interactions
- **Glowing Effects**: Cyberpunk visual enhancements

## 🔐 Security

- Password authentication (default: `backdoor123`)
- Session management
- Input sanitization
- Error handling
- CSRF protection ready
- Configurable access controls

## 📋 Installation & Usage

1. **Upload** `index.php` ke target server
2. **Access** via web browser
3. **Login** dengan password: `backdoor123`
4. **Navigate** through different tabs untuk various functions

```bash
# Clone repository
git clone https://github.com/gemblue/jalanbelakang.git

# Upload index.php to web server
cp index.php /var/www/html/

# Access via browser
http://yourserver.com/index.php
```

## ⚠️ Security Notice

**PENTING**: Tool ini dibuat untuk tujuan penelitian dan edukasi saja. 

- ✅ Gunakan hanya pada sistem yang Anda miliki
- ✅ Untuk penetration testing yang sah
- ✅ Educational purposes
- ❌ Jangan gunakan untuk aktivitas ilegal
- ❌ Tanpa izin pada sistem orang lain

## 🛠️ Configuration

### Default Settings
```php
// Ubah password default
$AUTH_PASSWORD = 'backdoor123';

// Database connections
$DB_CONFIGS = [
    'mysql' => 'mysql:host=localhost;dbname=test',
    'sqlite' => 'sqlite:/path/to/database.db'
];
```

### Advanced Options
- Custom themes
- Multiple authentication methods
- Database presets
- Network configurations
- Script execution limits

## 🔧 Technical Details

- **Language**: PHP 7.4+
- **Dependencies**: FontAwesome 6.4.0
- **Database**: PDO support for multiple DBMS
- **Frontend**: Vanilla JavaScript, CSS3
- **Compatibility**: Cross-platform web servers

## 📊 System Requirements

- PHP 7.4 atau higher
- Web server (Apache, Nginx, etc.)
- Write permissions untuk file operations
- Network access untuk external tools
- Database extensions untuk DB features

## 🤝 Contributing

Contributions welcome untuk educational purposes:

1. Fork repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📝 License

MIT License - Lihat [LICENSE](LICENSE) file untuk details.

## 🔗 Links

- **Repository**: https://github.com/gemblue/jalanbelakang
- **Issues**: https://github.com/gemblue/jalanbelakang/issues
- **Documentation**: Coming soon
- **Demo**: Available upon request

## ⭐ Acknowledgments

- FontAwesome untuk icon set
- PHP community untuk core functions
- Security researchers untuk best practices
- Open source community

---

**Disclaimer**: This tool is for educational and authorized testing purposes only. The developers are not responsible for any misuse or damage caused by this software. Always ensure you have proper authorization before testing on any systems.

## 🎯 Roadmap

- [ ] Plugin system
- [ ] Custom themes
- [ ] API integration
- [ ] Mobile app companion
- [ ] Advanced logging
- [ ] Multi-user support
- [ ] Encrypted communications
- [ ] Advanced evasion techniques

---

**Made with ❤️ for cybersecurity research and education**