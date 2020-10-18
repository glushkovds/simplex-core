<?php
$baseDir = $argv[1];
$files = scandir($baseDir);
$dirs = explode('/', $baseDir);
$namespace = 'Simplex\\' . implode('\\', array_map('ucfirst', $dirs));
$passed = 0;
foreach ($files as $file) {
    if (!is_file("$baseDir/$file")) {
        continue;
    }
    $contentsSrc = file_get_contents("$baseDir/$file");
    $contentsNew = str_replace('<?php', "<?php\n\nnamespace $namespace;\n", $contentsSrc);
    $matches = [];
    preg_match('@class ([\w\d]+)@i', $contentsSrc, $matches);
    $className = $matches[1];
    if (empty($className)) {
        throw new Exception("cant find class for $file");
    }
    if (substr($className, 0, 2) == 'SF') {
        $oldClassName = $className;
        $className = substr($className, 2);
        $contentsNew = str_replace($oldClassName, $className, $contentsNew);
    }
    file_put_contents("$baseDir/$className.php", $contentsNew);
    $passed++;
}
echo "Passed $passed\n";