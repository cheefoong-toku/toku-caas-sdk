<?php

namespace Toku\CaaS\V1\CallHandle;

class CallController {

    const STATE_NONE = 0;
    const CUSTOM_STATE_START = 100;
    const VOICE_FEMALE = "F";
    const VOICE_MALE = "M";
    const RINGTONE_AU = "AU";
    const RINGTONE_EU = "EU";
    const RINGTONE_JP = "JP";
    const RINGTONE_UK = "UK";
    const RINGTONE_US = "US";
    const CALL_TYPE_P2I = "P2I";
    const CALL_TYPE_I2P = "I2P";
    const CALL_TYPE_IP_FORWARD = "IP_FORWARD";
    const CALL_TYPE_WS_FORWARD = "WS_FORWARD"; //websocket/webrtc forward
    const CALL_STATUS_SETUP = "SETUP";
    const CALL_STATUS_ALERT = "ALERT";
    const CALL_STATUS_DISCONNECT = "DISCONNECT";
    const CALL_STATUS_DTMF = "DTMF";
    const CALL_STATUS_SPEECH_TO_TEXT = "SPEECH_TO_TEXT";
    const CALL_STATUS_RECORD = "RECORD";

    private $response;
    private $custom_state;
    public $P;

    //function __construct()
    function __construct($P = null) {
        if ($P === null) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST')
                parse_str(file_get_contents("php://input"), $this->P);
            else
                parse_str($_SERVER['QUERY_STRING'], $this->P);
        }

        $this->custom_state = $this->GetParam('custom_state', CallController::STATE_NONE);

        $this->response = [];
        $this->response["commands"] = [];
        //$this->response["custom_state"] = 0;
    }

    function GetParam($name, $default) {
        if (!isset($this->P[$name]))
            return $default;
        return $this->P[$name];
    }

    function GetCallType() {
        if (!isset($this->P['call_type']))
            return "PSTN";

        return $this->P['call_type'];
    }

    function GetCallStatus() {
        if (!isset($this->P['call_status']))
            return null;

        return $this->P['call_status'];
    }

    function IsCallConnected() {
        if (!isset($this->P['call_connected']))
            return false;

        return $this->P['call_connected'];
    }

    function GetCallCause() {
        if (!isset($this->P['call_cause']))
            return null;

        return $this->P['call_cause'];
    }

    function GetPhoneNumber() {
        return $this->P['number'];
    }

    function GetDTMF() {
        return $this->GetParam('dtmf', "");
    }

    function GetCallingParty() {
        return $this->P['calling_party'];
    }

    function GetDefaultCallingParty() {
        if ($this->GetCallType() == CallController::CALL_TYPE_P2I)
            return $this->GetCallingParty();
        else if ($this->GetCallType() == CallController::CALL_TYPE_IP_FORWARD) {
            $sipCli = $this->GetSIPCallerNumber();
            if (strlen($sipCli) > 0)
                return $sipCli;

            return $this->GetCallingParty();
        } else
            return $this->GetPhoneNumber();
    }

    function GetCalledParty() {
        return $this->P['called_party'];
    }

    function GetSIPCallerNumber() {
        if (!isset($this->P['voip_caller_number']))
            return "";
        return $this->P['voip_caller_number'];
    }

    function GetSIPLogin($bAddTag = true) {
        if (!isset($this->P['voip_login']))
            return "";
        return ($bAddTag ? "sip:" : "") . $this->P['voip_login'];
    }

    function Sleep($duration = 1) {
        return $this->AddCommands([
                    "function" => "Sleep",
                    "duration" => $duration,
        ]);
    }

    function DropSession($duration = 30) {
        return $this->AddCommands([
                    "function" => "DropSession",
                    "duration" => $duration,
        ]);
    }

    function SpeechToText($language, $handle_interrupt = false, $duration = 30) {
        return $this->AddCommands([
                    "function" => "SpeechToText",
                    "duration" => $duration,
                    "handle_interrupt" => $handle_interrupt,
                    "language" => $language,
        ]);
    }

    function StartRecording($duration = 30, $record_callback_url = "", $record_callback_method = "GET") {
        return $this->AddCommands([
                    "function" => "StartRecording",
                    "duration" => $duration,
                    "record_callback_method" => $record_callback_method,
                    "record_callback_url" => $record_callback_url,
        ]);
    }

    function MakeCall($called_party, $calling_party = null, $handle_interrupt = false, $record_call = false, $record_callback_url = "", $record_callback_method = "GET", $record_wait_sync = false, $ringtone = "", $record_split = false) {

        if ($calling_party === null)
            $calling_party = $this->GetDefaultCallingParty();

        return $this->AddCommands([
                    "function" => "MakeCall",
                    "called_party" => $called_party,
                    "calling_party" => $calling_party,
                    "record_call" => $record_call,
                    "record_callback_method" => $record_callback_method,
                    "record_callback_url" => $record_callback_url,
                    "handle_interrupt" => $handle_interrupt,
                    "ringtone" => $ringtone,
                    "record_wait_sync" => $record_wait_sync,
                    "record_split" => $record_split,
        ]);
    }

    function PlayFile($name, $dtmf = 0, $handle_interrupt = false, $replay = 0, $no_dtmf_value = null) {
        if ($no_dtmf_value == null)
            $no_dtmf_value = $name;

        return $this->AddCommands([
                    "function" => "PlayFile",
                    "type" => "file",
                    "value" => $name,
                    "dtmf" => $dtmf,
                    "handle_interrupt" => $handle_interrupt,
                    "replay" => $replay,
                    "no_dtmf_value" => $no_dtmf_value
        ]);
    }

    function PlaySystem($name, $dtmf = 0, $handle_interrupt = false, $replay = 0, $no_dtmf_value = null) {
        //if ($handle_interrupt === null) {
        //    $handle_interrupt = ($dtmf > 0);
        //}
        if ($no_dtmf_value == null)
            $no_dtmf_value = $name;

        return $this->AddCommands([
                    "function" => "PlayFile",
                    "type" => "system",
                    "value" => $name,
                    "dtmf" => $dtmf,
                    "handle_interrupt" => $handle_interrupt,
                    "replay" => $replay,
                    "no_dtmf_value" => $no_dtmf_value
        ]);
    }

    function PlayUrl($url, $dtmf = 0, $handle_interrupt = false, $replay = 0, $no_dtmf_url = null) {
        //if ($handle_interrupt === null) {
        //    $handle_interrupt = ($dtmf > 0);
        //}
        if ($no_dtmf_url == null)
            $no_dtmf_url = $url;

        return $this->AddCommands([
                    "function" => "PlayFile",
                    "type" => "url",
                    "value" => $url,
                    "dtmf" => $dtmf,
                    "handle_interrupt" => $handle_interrupt,
                    "replay" => $replay,
                    "no_dtmf_value" => $no_dtmf_url
        ]);
    }

    function PlayTTS($message, $language = "en", $voice = "f", $dtmf = 0, $handle_interrupt = false, $replay = 0, $no_dtmf_message = null) {
        if ($no_dtmf_message === null)
            $no_dtmf_message = $message;

        return $this->AddCommands([
                    "function" => "PlayTTS",
                    "message" => $message,
                    "voice" => $voice,
                    "language" => $language,
                    "dtmf" => $dtmf,
                    "handle_interrupt" => $handle_interrupt ? 'true' : 'false',
                    "replay" => $replay,
                    "no_dtmf_message" => $no_dtmf_message,
        ]);
    }

    function PlayTranslate($message, $language = "en", $voice = "f", $dtmf = 0, $handle_interrupt = false, $replay = 0, $no_dtmf_message = null) {
        if ($no_dtmf_message === null)
            $no_dtmf_message = $message;

        return $this->AddCommands([
                    "function" => "PlayTranslate",
                    "message" => $message,
                    "voice" => $voice,
                    "language" => $language,
                    "dtmf" => $dtmf,
                    "handle_interrupt" => $handle_interrupt ? 'true' : 'false',
                    "replay" => $replay,
                    "no_dtmf_message" => $no_dtmf_message,
        ]);
    }

    function Hangup() {
        return $this->AddCommands([
                    "function" => "Hangup"
        ]);
    }

    function AddCommands($cmds) {
        array_push($this->response["commands"], $cmds);

        return $this;
    }

    function Response($bReturn = false) {
        if (!$bReturn) {
            echo json_encode($this->response);
            exit();
        }
        return json_encode($this->response);
    }

}

?>
