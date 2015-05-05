<?php
ob_start();

class Breakpoint {
  static $DEBUG_ERROR_LEVEL = E_USER_WARNING;
  static $MAX_FRAMES = 15;

  private $frames = array();
  private $exceptions = array();
  static $instance = null;
  static $level = null;

  private $startTime = null;

  function __construct() {
    if (isset($_SERVER["REQUEST_TIME_FLOAT"])) {
      $this->startTime = $_SERVER["REQUEST_TIME_FLOAT"];
    } else {
      $this->startTime = microtime(TRUE);
    }

    set_error_handler(array($this, "frameErrorHandler"));
    register_shutdown_function(array($this, "shutdownHandler"));
  }

  public static function getErrorType($errNum) {
    switch ($errNum) {
      case E_ERROR: return "fatal";
      case E_USER_ERROR: return "error";
      case E_USER_WARNING: return "warning";
      case E_USER_NOTICE: return "notice";
      case E_DEPRECATED: return "deprecated";
    }
  }

  public function frameErrorHandler($errNum, $errstr, $errfile, $errline) {

    $errorType = Breakpoint::getErrorType($errNum);
    if ($errorType === null) {
      return FALSE;
    }

    $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

    if ($errNum <= error_reporting()) {
      Breakpoint::Breakpoint()->except($errorType, $errNum, $errstr, $errline, $errfile, $trace);
      return TRUE;
    }
  }

  public static function shutdownHandler() {
    $errfile = "unknown file";
    $errstr = "shutdown";
    $errNum = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();
    if( $error !== NULL) {
      $errNum  = $error["type"];
      $errfile = $error["file"];
      $errline = $error["line"];
      $errstr  = $error["message"];
    }

    $errorType = Breakpoint::getErrorType($errNum);

    // If there's an error, show it
    if ($errorType !== null) {
      $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
      Breakpoint::Breakpoint()->except($errorType, $errNum, $errstr, $errline, $errfile, $trace);
    }

    Breakpoint::Breakpoint()->render();
    return TRUE;
  }

  public static function Breakpoint() {
    if (Breakpoint::$instance === null) {
      Breakpoint::$instance = new Breakpoint();
    }

    return Breakpoint::$instance;
  }

  public static function Inspect($local) {
    $instance = Breakpoint::Breakpoint();

    if ($instance->getFrameCount() > Breakpoint::$MAX_FRAMES) {
      return;
    }

    if (isset($_SERVER["REQUEST_URI"])) {
      $instance->exceptions[] = array(
        "type" => "inspect",
        "number" => "",
        "message" => "Halted for debugging",
        "path" => $_SERVER["REQUEST_URI"]
      );
    }

    $debugTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 5);
    if (isset($debugTrace[1])) {
      $trace = $debugTrace[1];
      $trace["local"] = $local;
    } else {
      $trace = array();
    }
    $instance->createFrame("Frame", $debugTrace[0]["line"], $debugTrace[0]["file"], $trace);
  }

  public function getFrameCount() {
    return count($this->frames);
  }

  public function except($errType, $errNum, $message, $lineNum, $fileName, $trace) {
    $this->exceptions[] = array(
      "type" => $errType,
      "number" => $errNum,
      "message" => $message,
      "path" => $_SERVER["REQUEST_URI"]
    );

    $trace = $trace[min(count($trace) - 1, 2)];
    $this->createFrame($message, $lineNum, $fileName, $trace);
  }

  public function createFrame($message, $lineNum, $fileName, $trace) {
    $frame = array();
    $frame["filename"] = $fileName;
    $frame["message"] = $message;
    $frame["lines"] = file($fileName);
    $frame["line_num"] = $lineNum;
    $frame["request"] = $_REQUEST;
    $time = microtime(TRUE); 
    $frame["time"] = $time - $this->startTime;

    if (isset($trace["local"])) {
      if (is_object($trace["local"])) {
        $frame["local"] = $this->reflect($trace["local"]);
      } else {
        $frame["local"] = $trace["local"];
      }
    } else {
      $frame["local"] = array();
    }

    if (is_array($frame["local"])) {
      unset($frame["local"]["this"]);
    }

    if (isset($trace["class"]) && $trace["class"] !== "Breakpoint") {
      if (is_object($trace["object"])) {
        $frame["instance"] = $this->reflect($trace["object"]);
      } else {
        $frame["instance"] = get_object_vars($trace["object"]);
      }
      $frame["class_name"] = $trace["class"];
      unset($frame["instance"]["bettererrors"]);
    } else {
      $frame["instance"] = array();
      $frame["class_name"] = $fileName;
    }

    if (isset($trace["function"])) {
      $frame["method_name"] = $trace["function"];
    } else {
      $frame["method_name"] = "";
    }

    $this->frames[] = $this->getFormattedLines($frame);
  }

  public function reflect($obj) {
    $reflected = array();
    $reflect = new ReflectionObject($obj);
    $props = $reflect->getProperties();

    foreach($props as $prop) {
      $prop->setAccessible(TRUE);
      $reflected[$prop->getName()] = $prop->getValue($obj);
    }
    return $reflected;
  }

  public function getFormattedLines($frame) {
    $linesBack = 10;
    $lines = $frame["lines"];
    $lineNum = $frame["line_num"];
    $startNum = max(0, $lineNum - $linesBack) + 1;
    $endNum = min(count($lines), $lineNum + $linesBack);
    $highlightLine = $lineNum;

    $output = "<pre data-line='".$highlightLine."'";
    $output .= " data-line-offset='".$startNum."'>";
    $output .= "<code class='language-php'>";

    for ($i = $startNum; $i < $endNum; $i++) {
      $line = $lines[$i];
      $className = "";
      if ($i + 1 === $lineNum) {
        $className = "highlight";
        $matches = array();
        preg_match("/Inspect\((.*)\)/", $line, $matches);

        if (isset($matches[1])) {
          $frame["inspect"] = $matches[1]; 
        } else {
          $frame["inspect"] = "Inspect";
        }
      }
      $output .= $line;
    }

    $frame["formatted_lines"] = $output."</code></pre>";

    return $frame;
  }

  public function render() {
    if (count($this->frames) > 0) {
      ob_end_clean();
      global $GlobalDebuggerFrames, $GlobalDebuggerExceptions, $GlobalDebuggerPrismCSS, $GlobalDebuggerPrismJS;
      $GlobalDebuggerFrames = $this->frames;
      $GlobalDebuggerExceptions = $this->exceptions;
      $GlobalDebuggerPrismJS = file_get_contents(dirname(__file__)."/prism.js");
      $GlobalDebuggerPrismCSS = file_get_contents(dirname(__file__)."/prism.css");
      $GlobalDebuggerCSS = file_get_contents(dirname(__file__)."/breakpoint.css");

      include("breakpoint_template.php");
      exit;
    }
  }
}

Breakpoint::Breakpoint();
