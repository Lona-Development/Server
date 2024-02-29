<?php

require 'vendor/autoload.php';
use LonaDB\LonaDB;

return new class {
    public function run($LonaDB, $data, $server, $fd) : void {
        $functions = [];

        if($data['login']['name'] !== 'root') {
            $response = json_encode(["success" => false, "err" => "not_root", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
        }

        $evalFunc = "\$functions['" . $data['process'] . "'] = new class { \n";
        $evalFunc .= "public function Execute(\$LonaDB) : mixed {\n";
        $evalFunc .= $data['function'] . "\n";
        $evalFunc .= "}\n};";

        try{
            eval($evalFunc);

            try{
                $answer = $functions[$data['process']]->Execute($LonaDB);
            }
            catch(e){
                $answer = e;
            }
        }
        catch(e){
            $answer = e;
        }

        $response = json_encode(["success" => true, "response" => $answer, "process" => $data['process']]);
        $server->send($fd, $response);
        $server->close($fd);
    }
};
