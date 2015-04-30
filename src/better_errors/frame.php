<?php
// error handler function
function FrameErrorHandler($errNum, $errstr, $errfile, $errline) {
  $errorType = "ERROR";
  switch ($errNum) {
    case E_USER_ERROR: $errorType = "ERROR"; break;
    case E_USER_WARNING: $errorType = "WARNING"; break;
    case E_USER_NOTICE: $errorType = "NOTICE"; break;
  }

  Frame::Frame()->except($errorType, $errNum, $errstr, $errline, $errfile);
}
set_error_handler("FrameErrorHandler");

class Frame {
  private $frames = array();
  private $exceptios = array();

  static $instance = null;

  function __construct() {
  }

  public static function Frame() {
    if (Frame::$instance === null) {
      Frame::$instance = new Frame();
    }

    return Frame::$instance;
  }

  public function except($errType, $errNum, $message, $lineNum, $fileName) {
    $trace = debug_backtrace();
    print("<pre>");
    // print_r(debug_backtrace());
    print("</pre>");
    $this->exceptions = array(
      "type" => $errType ." #".$errNum,
      "message" => $message,
      "path" => $_SERVER["REQUEST_URI"]
    );
    $this->createFrame($message, $lineNum, $fileName, $trace[2]);
  }

  public function createFrame($message, $lineNum, $fileName, $trace) {
    $frame = array();
    $frame["filename"] = $fileName;
    $frame["message"] = $message;
    $frame["lines"] = file($fileName);
    $frame["line_num"] = $lineNum;

    $frame["request"] = $_REQUEST;
    if (isset($trace["local"])) {
      $frame["local"] = $trace["local"];
    } else {
      $frame["local"] = array();
    }
    unset($frame["local"]["this"]);

    if (isset($trace["object"])) {
      $frame["instance"] = get_object_vars($trace["object"]);
      $frame["class_name"] = $trace["class"];
      $frame["method_name"] = $trace["function"];
    } else {
      $frame["instance"] = array();
      $frame["class_name"] = "";
      $frame["method_name"] = "";
    }

    $this->frames[] = $frame;

  }

  public function inspect($local) {
    $this->exceptions = array(
      "type" => "Frame",
      "message" => "Inspect",
      "path" => $_SERVER["REQUEST_URI"]
    );

    $debugTrace = debug_backtrace();
    $trace = $debugTrace[1];
    $trace["local"] = $local;
    $this->createFrame("Frame", $debugTrace[0]["line"], $debugTrace[0]["file"], $trace);
  }

  public function render() {
    global $GlobalFramegerFrames, $GlobalFramegerException;
    $GlobalFramegerFrames = $this->frames;
    $GlobalFramegerException = $this->exceptions;
    include("debug_template.php");
  }
}
