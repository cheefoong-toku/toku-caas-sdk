<?php

namespace Toku\CaaS\V1\CallHandle;

/*! Call Handle Controller Class */
/*!
 * This class use for both Programmable Call and Programmable VOIP service.  
 * The class provides the standard value used by the service and provides function 
 * to form the call control JSON response.
 */

class CallController {
    
    /** \addtogroup <TTS_VOICE_TYPE Voice type
     * Voice type for PlayTTS()
     *  @{
     */
    const VOICE_FEMALE = "F"; //!< Female voice
    const VOICE_MALE = "M"; //!< Male voice
    /** @}*/
    
    /** \addtogroup <RINGTONE_TYPE Ringtone
     * Ringtone type for MakeCall()
     *  @{
     */
    const RINGTONE_AU = "AU"; //!< AU ringtone
    const RINGTONE_EU = "EU"; //!< EU ringtone
    const RINGTONE_JP = "JP"; //!< JP ringtone
    const RINGTONE_UK = "UK"; //!< UK ringtone
    const RINGTONE_US = "US"; //!< US ringtone
    /** @}*/
    
    /** \addtogroup <CALL_TYPE Call Type
     * call_type in event parameter
     *  @{
     */
    const CALL_TYPE_P2I = "P2I"; //!< Call type - Call from phone to VOIP
    const CALL_TYPE_I2P = "I2P"; //!< Call type - Call from VOIP to phone
    const CALL_TYPE_IP_FORWARD = "IP_FORWARD"; //!< Call type - VOIP call forward
    const CALL_TYPE_WS_FORWARD = "WS_FORWARD"; //!< Call type - Websocket/WebRTC call forward
    /** @}*/
    
    /** \addtogroup <CALL_STATUS Call Status
     * call_status in event parameter
     *  @{
     */
    const CALL_STATUS_SETUP = "SETUP"; //!< Call status event - Setup
    const CALL_STATUS_ALERT = "ALERT"; //!< Call status event - Alerting
    const CALL_STATUS_DISCONNECT = "DISCONNECT"; //!< Call status event - Disconnect
    const CALL_STATUS_DTMF = "DTMF"; //!< Call status event - DTMF
    const CALL_STATUS_SPEECH_TO_TEXT = "SPEECH_TO_TEXT"; //!< Call status event - Speech to text
    const CALL_STATUS_RECORD = "RECORD"; //!< Call Status Event - Recording
    const CALL_STATUS_RECORD_START = "START_RECORD"; //!< Call Status Event - Start Recording
    const CALL_STATUS_RECORD_END = "END_RECORD"; //!< Call Status Event - End Recording
    
    /** @}*/
    
    private $response; //!< An array stored added call handle command
    private $P; //!< An array stored extracted event parameters
    
    //! Constructor
    /*!
      \param array $P Event parameter, always put null, application will get the paramter passed from the webhook request automatically
      \sa GetParam()
     */
    public function __construct($P = null) {
        if ($P === null) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST')
                parse_str(file_get_contents("php://input"), $this->P);
            else
                parse_str($_SERVER['QUERY_STRING'], $this->P);
        }

        $this->response = [];
        $this->response["commands"] = [];
    }

    //! Get specific event parameter
    /*!
      \param string $name event parameter's name
      \param any $default default value if specified parameter not found

      \return <em>string</em> Event parameter's value
      \sa See avaialble parameter for event: 
        <a href="https://apidocs.toku.co/Additional-Information-Toku-API/call-service-webhook/call-handle-webhook#call-handle-dtmf-parameters" target="_blank">DTMF</a>, 
        <a href="https://apidocs.toku.co/Additional-Information-Toku-API/call-service-webhook/call-status-others-webhook#callstatusevent" target="_blank">Call</a>, 
        <a href="https://apidocs.toku.co/Additional-Information-Toku-API/call-service-webhook/call-status-others-webhook#call-recording-callback-webhook" target="_blank">Recording</a>
     */
    public function GetParam($name, $default=null) {
        if (!isset($this->P[$name]))
            return $default;
        return $this->P[$name];
    }

    //! Get event parameter - call_type
    /*!
      \return <em>string</em> event 'call_type' parameter
      \sa
     */
    public function GetCallType() {
        if (!isset($this->P['call_type']))
            return "PSTN";

        return $this->P['call_type'];
    }

    //! Get event parameter - call_status
    /*!
      \return <em>string</em> event 'call_status' parameter
      \sa GetParam()
     */
    public function GetCallStatus() {
        if (!isset($this->P['call_status']))
            return null;

        return $this->P['call_status'];
    }

    //! Get event parameter - call_cause
    /*!
      \return <em>string</em> event 'call_cause' parameter
      \sa GetParam()
     */
    public function GetCallCause() {
        if (!isset($this->P['call_cause']))
            return null;

        return $this->P['call_cause'];
    }

    //! Get event parameter - number
    /*!
      \return <em>string</em> event 'number' parameter
      \sa GetParam()
     */
    public function GetPhoneNumber() {
        return $this->P['number'];
    }

    //! Get event parameter - dtmf
    /*!
      \return <em>string</em> event 'dtmf' parameter
      \sa GetParam()
     */
    public function GetDTMF() {
        return $this->GetParam('dtmf', "");
    }

    //! Get event parameter - calling_party
    /*!
      \return <em>string</em> event 'calling_party' parameter
      \sa GetParam()
     */
    public function GetCallingParty() {
        return $this->P['calling_party'];
    }

    //! Get event parameter - calling party number based on difference call type
    /*!
      \return <em>string</em> event 'calling_party' parameter
      \sa GetParam()
     */
    public function GetDefaultCallingParty() {
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

    //! Get event parameter - called_party
    /*!
      \return <em>string</em> event 'called_party' parameter
      \sa GetParam()
     */
    public function GetCalledParty() {
        return $this->P['called_party'];
    }

    //! Get event parameter - voip_caller_number
    /*!
      \return <em>string</em> event 'called_party' parameter
      \sa GetParam()
     */
    public function GetSIPCallerNumber() {
        if (!isset($this->P['voip_caller_number']))
            return "";
        return $this->P['voip_caller_number'];
    }

    //! Get event parameter - voip_login
    /*!
      \return <em>string</em> event 'voip_caller_number' parameter
      \sa GetParam()
     */
    public function GetSIPLogin($bAddTag = true) {
        if (!isset($this->P['voip_login']))
            return "";
        return ($bAddTag ? "sip:" : "") . $this->P['voip_login'];
    }

    //! Get event parameter - call_connected
    /*!
      \return <em>string</em> event 'call_connected' parameter
      \sa GetParam()
     */
    public function IsCallConnected() {
        if (!isset($this->P['call_connected']))
            return false;

        return $this->P['call_connected'] ? true : false;
    }
    
    //! Call handle function, delay next call handle command by given duration (secs)
    /*!
      \param integer $duration Duration in seconds.
      \return <em>class</em> CallController object instance
      \sa GetParam()
     */
    public function Sleep($duration = 1) {
        return $this->AddCommands([
                    "function" => "Sleep",
                    "duration" => $duration,
        ]);
    }

    //! Call handle function, drap session with specified duration (secs)
    /*!
      \param integer $duration Duration in seconds.
      \sa
     */
    public function DropSession($duration = 30) {
        return $this->AddCommands([
                    "function" => "DropSession",
                    "duration" => $duration,
        ]);
    }

    //! Call handle function, speech to text
    /*!
      \param string $language Output language of speech to text
      \param boolean $handle_interrupt A speech to text event will be sent if set to true.
      \param integer $duration Speech to text recording duration in seconds.
      \return <em>class</em> CallController object instance
      \sa
     */
    public function SpeechToText($language, $handle_interrupt = false, $duration = 30) {
        return $this->AddCommands([
                    "function" => "SpeechToText",
                    "duration" => $duration,
                    "handle_interrupt" => $handle_interrupt,
                    "language" => $language,
        ]);
    }

    //! Call handle function, start recording on current channel (single channel)
    /*!
      \param integer $duration Recording duration in seconds.
      \param string $record_callback_url Record callback URL.
      \param string $record_callback_method HTTP method, 'POST' or 'GET'.
      \return <em>class</em> CallController object instance
      \sa
     */
    public function StartRecording($duration = 30, $record_callback_url = "", $record_callback_method = "GET") {
        return $this->AddCommands([
                    "function" => "StartRecording",
                    "duration" => $duration,
                    "record_callback_method" => $record_callback_method,
                    "record_callback_url" => $record_callback_url,
        ]);
    }

    //! Call handle function, make outbound call
    /*!
    \param string $called_party Called party number.
    \param string $calling_party Calling party number.
    \param boolean $handle_interrupt Set to true if need to receive B party disconnect event, a webhook will be sent to call handle callback URL.
    \param boolean $record_call Set to true if need to record the call conversation.
    \param string $record_callback_url Record record callback URL.
    \param string $record_callback_method Record callback method.
    \param boolean $record_wait_sync Set to true if need to download the recording file uppon receive the recording callback webhook, this is to ensure the recording event to be sent only when the media file are ready to download.
    \param string $ringtone Ringtone see @ref RINGTONE_TYPE
    \param boolean $record_split Set to true if need to record call conversation into sperate recording file.
      \return <em>class</em> CallController object instance
     */
    public function MakeCall($called_party, $calling_party = null, $handle_interrupt = false, $record_call = false, $record_callback_url = "", $record_callback_method = "GET", $record_wait_sync = false, $ringtone = "", $record_split = false) {

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

    //! Call handle function, play media file from media storage (not open for public)
    /*!
      \param string $name Storage wave file name
      \param integer $dtmf Set number of DTMF to be capture, valid range between 0 ~ 20, set to 0 if no DTMF input needed to be captured. 
      \param boolean $handle_interrupt Set to true if need to capture DTMF, a webhook will be send to call handle callback URL, an empty will be sent if no DTMF captured as long as this parameter set to true.
      \param integer $replay Number of times the message will be replay, usually replay will be used if $dtmf > 0, the message will be replay if no DTMF captured. 
      \param string $no_dtmf_value Play difference wave file if $dtmf > 0 and no DTMF captured.
      \return <em>class</em> CallController object instance
      \sa PlayTTS(), PlayURL()
     */
    private function PlayFile($name, $dtmf = 0, $handle_interrupt = false, $replay = 0, $no_dtmf_value = null) {
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

    //! Call handle function, play system media
    /*!
      \param string $name System wave file, only "beep" are available currently
      \param integer $dtmf Set number of DTMF to be capture, valid range between 0 ~ 20, set to 0 if no DTMF input needed to be captured. 
      \param boolean $handle_interrupt Set to true if need to capture DTMF, a webhook will be send to call handle callback URL, an empty will be sent if no DTMF captured as long as this parameter set to true.
      \param integer $replay Number of times the message will be replay, usually replay will be used if $dtmf > 0, the message will be replay if no DTMF captured. 
      \param string $no_dtmf_value Play difference system wave file if $dtmf > 0 and no DTMF captured.
      \return <em>class</em> CallController object instance
      \sa PlayTTS(), PlayURL()
     */
    public function PlaySystem($name, $dtmf = 0, $handle_interrupt = false, $replay = 0, $no_dtmf_value = null) {
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

    //! Call handle function, play media via given URL
    /*!
      \param string $url Public accessible media file via URL
      \param integer $dtmf Set number of DTMF to be capture, valid range between 0 ~ 20, set to 0 if no DTMF input needed to be captured. 
      \param boolean $handle_interrupt Set to true if need to capture DTMF, a webhook will be send to call handle callback URL, an empty will be sent if no DTMF captured as long as this parameter set to true.
      \param integer $replay Number of times the message will be replay, usually replay will be used if $dtmf > 0, the message will be replay if no DTMF captured. 
      \param string $no_dtmf_url Play difference message if $dtmf > 0 and no DTMF captured.
      \return <em>class</em> CallController object instance
      \sa PlayTTS(), PlayFile()
     */
    public function PlayUrl($url, $dtmf = 0, $handle_interrupt = false, $replay = 0, $no_dtmf_url = null) {
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

    //! Call handle function, play text to speech
    /*!
      \param string $message Text to speech message to be played.
      \param string $language Specified langauge to be used for the message, see <a href="https://apidocs.toku.co/Additional-Information-Toku-API/text-to-speech-tts-support-toku" target="_blank">Languages supported</a>.
      \param char $voice Voice type, see @ref TTS_VOICE_TYPE.
      \param integer $dtmf Set number of DTMF to be capture, valid range between 0 ~ 20, set to 0 if no DTMF input needed to be captured.
      \param boolean $handle_interrupt Set to true if need to capture DTMF, a webhook will be send to call handle callback URL, an empty will be sent if no DTMF captured as long as this parameter set to true.
      \param integer $replay Number of times the message will be replay, usually replay will be used if $dtmf > 0, the message will be replay if no DTMF captured.
      \param string $no_dtmf_message Play difference message if $dtmf > 0 and no DTMF captured.

      \return <em>class</em> CallController object instance
      \sa PlayUrl()
    */

    public function PlayTTS($message, $language = "en", $voice = "f", $dtmf = 0, $handle_interrupt = false, $replay = 0, $no_dtmf_message = null) {
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

    //! Call handle function: PlayTranslate
    /*!
      \param string $message Text message to be translate played.
      \param string $language Specified langauge to be transalted to.
      \param char $voice Voice type, see @ref TTS_VOICE_TYPE.
      \param integer $dtmf Set number of DTMF to be capture, valid range between 0 ~ 20, set to 0 if no DTMF input needed to be captured.
      \param boolean $handle_interrupt Set to true if need to capture DTMF, a webhook will be send to call handle callback URL, an empty will be sent if no DTMF captured as long as this parameter set to true.
      \param integer $replay Number of times the message will be replay, usually replay will be used if $dtmf > 0, the message will be replay if no DTMF captured.
      \param string $no_dtmf_message Play difference message if $dtmf > 0 and no DTMF captured.

      \return <em>class</em> CallController object instance
      \sa
     */
    public function PlayTranslate($message, $language = "en", $voice = "f", $dtmf = 0, $handle_interrupt = false, $replay = 0, $no_dtmf_message = null) {
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

    //! Call handle function: Hangup
    /*!
      \return <em>class</em> CallController object instance
      \sa DropSession()
     */
    public function Hangup() {
        return $this->AddCommands([
                    "function" => "Hangup"
        ]);
    }

    //! Add command into command response array
    /*!
      \param array $cmds Call handle command array, see <a href="https://apidocs.toku.co/Additional-Information-Toku-API/call-service-webhook/call-handle-command" target="_blank">Call Handle Command</a>
      \return <em>class</em> CallController object instance 
      \sa Response()
     */
    public function AddCommands($cmds) {
        array_push($this->response["commands"], $cmds);

        return $this;
    }

    //! Print out call handle command
    /*!
      \param boolean $bReturn true = return call handle commands JSON string, false = output to webhook response.
      \return <em>class</em> CallController object instance
      \sa AddCommands()
     */
    public function Response($bReturn = false) {
        if (!$bReturn) {
            echo json_encode($this->response);
            exit();
        }
        return json_encode($this->response);
    }

}

?>
