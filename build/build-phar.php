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

if($config->{"debug"}) {
    $path = $path."/debug";
    $debug = "True";
    $version = $version."-debug";
}
else {
    $debug = "False";
    $path = $path."/release";
    $branch = "release";
    $version = $version."-release";
}


echo "[CONF] Debug=".$debug."\n";
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
if (file_exists("src/composer.phar")) {
    exec("cd src ; printf '\n' | php composer.phar install");
} else {
    exec("cd src ; printf '\n' | composer install");
}

if(file_exists($path."/".$filename."-".$version.".phar")){
    unlink($path."/".$filename."-".$version.".phar");
    builderLog("[CLEANUP] Deleted an old build");
}
if(file_exists("build/run-phar.sh")){
    unlink(__DIR__."/run-phar.sh");
    builderLog("[CLEANUP] Deleted an old runner");
}

if(!file_exists($path)){
    mkdir($path);
    builderLog("[BUILD] Created the builddirectory");
}

try {
    builderLog("[BUILD] Creating a new Phar object");
    $phar = new Phar($path."/".$filename."-".$version.".phar", 0, $path."/".$filename."-".$version.".phar");

    builderLog("[BUILD] Adding files to the Phar archive");
    $phar->buildFromDirectory(__DIR__ . '/../src');

    builderLog("[BUILD] Set default stub file");
    $phar->setDefaultStub('LonaDB/LonaDB.php', 'LonaDB/LonaBD.php');

    builderLog("[BUILD] Set alias file");
    $phar->setAlias($filename."-".$version.".phar");

    builderLog("[BUILD] Set signature algorithm");
    $phar->setSignatureAlgorithm(Phar::SHA1);

    builderLog("[BUILD] Compress files");
    $phar->compressFiles(Phar::GZ);

    builderLog("[BUILD] Saving the new Phar archive");
    $phar->stopBuffering();

    builderLog("[INFO] Phar archive created successfully");

    builderLog("[RUN] Generating run script");
    file_put_contents("./build/run-phar.sh", 'cd '.$path.' ; ../../../bin/php7/bin/php '.$filename.'-'.$version.'.phar');

    builderLog("[RUN] Adding Permissions to run script");
    exec("chmod 777 ./build/run-phar.sh");

    echo "Done!\nBuilt in ".(time() - $start) ." ms!\n";
} catch (Exception $e) {
    builderLog('[ERROR] '.$e->getMessage());
}
