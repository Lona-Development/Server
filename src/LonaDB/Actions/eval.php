<?php

return new class {
    public function run($lonaEval, $data, $server, $fd) : void {
        if($data['login']['name'] !== 'root') {
            $response = json_encode(["success" => false, "err" => "not_root", "process" => $data['process']]);
            $server->send($fd, $response);
            $server->close($fd);
        }

        $evalFunc = "function evaluate_".$data['process']."(\$lona) { ";
        $evalFunc .= $data['function'];
        $evalFunc .= " }";

        eval($evalFunc);

        eval("\$answer = evaluate_".$data['process']."(\$lonaEval);");

        echo $answer;

        $response = json_encode(["success" => true, "response" => $answer, "process" => $data['process']]);
        $server->send($fd, $response);
        $server->close($fd);
    }
};
