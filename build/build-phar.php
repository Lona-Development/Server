<?php
function builderLog(string $message){
    echo(date("Y-m-d h:m", time())." ".$message."\n");
}

echo chr(27).chr(91).'H'.chr(27).chr(91).'J';
echo "[LonaDB Phar compiler]\n";

$config = json_decode(file_get_contents("build_config.json"));

$path = $config->{"path"};
$filename = $config->{"filename"};
$version = $config->{"version"};
$branch = "debug";

if($config->{"debug"}) $path = $path."/debug";
else {
    $path = $path."/release";
    $branch = "release";
}

echo "[CONF] Debug=".$config->{"debug"}."\n";
echo "[CONF] Path=".$path."\n";
echo "[CONF] Filename=".$filename."\n";
echo "[CONF] Version=".$version."\n";

echo "\nBuild? (Y/n)\n";
$handle = fopen ("php://stdin","r");
$line = fgets($handle);
if(trim(strtolower($line)) === 'no' || trim(strtolower($line)) === "n"){
    echo "ABORTING!\n";
    exit;
}

$start = time();

builderLog("[COMPOSER] Running 'composer install'");
exec("cd src ; ./composer.phar install");

if(file_exists($path."/".$filename."-".$version.".phar")){
    unlink($path."/".$filename."-".$version.".phar");
    builderLog("[CLEANUP] Deleted an old build");
}
if(file_exists("build/run-phar.sh")){
    unlink(__DIR__."/run-phar.sh");
    builderLog("[CLEANUP] Deleted an old runner");
}

try {
    builderLog("[BUILD] Creating a new Phar object");
    $phar = new Phar($path."/".$filename."-".$version.".phar", 0, $path."/".$filename."-".$version.".phar");

    builderLog("[BUILD] Adding files to the Phar archive");
    $phar->buildFromDirectory(__DIR__ . '/../src');

    builderLog("[BUILD] Set the default stub file");
    $phar->setDefaultStub('LonaDB/LonaDB.php', 'LonaDB/LonaBD.php');

    $phar->setAlias($filename."-".$version.".phar");

    builderLog("[BUILD] Saving the new Phar archive");
    $phar->stopBuffering();

    builderLog("[INFO] Phar archive created successfully");

    builderLog("[RUN] Generating run script");
    file_put_contents("./build/run-phar.sh", "cd ".$path." ; php ".$filename."-".$version.".phar -dextension=openswoole.so");

    builderLog("[RUN] Adding Permissions to run script");
    exec("chmod 777 ./build/run-phar.sh");

    echo "Done!\nBuilt in ".(time() - $start) ." ms!\n";
} catch (Exception $e) {
    builderLog('[ERROR] '.$e->getMessage());
}
