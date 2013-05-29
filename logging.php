<?php
/*

    Written by:     Wouter van Vliet (wouter@escotday.com)
    
    License:     FreeWare
            You can use this class for anything you like, commercial or non commercial. 
            The author, that's me, would very much like to hear what you think of this
            humble class script.
            
    About Bugs:    Well, it's not that much code, so I don't expect any. If you would find one
            I would very much appriciate it if you'd let me know. Also if there's some-
            thing that could be done better, I'd like to discuss it with you ;P
    
    PHP Version:    PHP 4.3 or above, because of the usage of debug_stacktrace(). If you comment
            it out I believe it works in pretty much every PHP version.
            
*/

# Define constants to be used
define("L_STDERR", 1);
define("L_STDOUT", 2);
define("DATESTRING_FULL", "Y-m-d D H:i:s");
# Define FS_ROOTDIR to the rootdir of your project to reduce the length of filepaths
# in stacktraces..
define("FS_ROOTDIR", '');

# Implements a global error reporting structure
class Logging {
    
    var $LogHandle;
    var $LogType;

    var $DisAbled = Array();

    function Logging($Target = L_STDOUT) {
        if ($Target == L_STDOUT) {
            $this->setLogType(L_STDOUT);
        } elseif ($Target == L_STDERR) {
            if ($Handle = @fopen('php://stderr', 'a')) {
                $this->setLogHandle($Handle);
                $this->setLogType(L_STDERR);
            } else {
                $this->setLogType(L_STDOUT);
            };
        } elseif (is_resource($Target)) {
            $this->setLogHandle($Target);
            $this->setLogType(L_STDERR);
        } else {
            if ($Handle = @fopen($Target, 'a')) {
                $this->setLogHandle($Handle);
                $this->setLogType(L_STDERR);
            } else {
                $this->setLogType(L_STDOUT);
            };
        };
    }

    function disableType($Type) {
        $this->DisAbled[$Type] = 1;
    }

    function enableType($Type) {
        unset($this->DisAbled[$Type]);
    }

    function setLogHandle($LH) {
        $this->LogHandle = $LH;
    }

    function setLogType($LT) {
        $OldLogType = $this->LogType;
        $this->LogType = $LT;
        return $OldLogType;
    }

    function Error($String) {
        $this->_doWrite('error', $String);
    }
    
    function Warning($String) {
        $this->_doWrite('warning', $String);
    }

    function Notice($String) {
        $this->_doWrite('notice', $String);
    }

    function Fatal($String) {
        $this->_doWrite('FATAL', $String);
        die;
    }

    function Dumper($VarName) {
        $this->_doWrite('notice', '{VARDUMP}'."\n".var_export($VarName, true));
    }

    function _doWrite($Type, $String) {
        if (isset($this->DisAbled[$Type])) return false;

        $LogString = '['.date(DATESTRING_FULL).'] ['.$Type.'] ' . $String;

        if ($Type != 'notice') {
            $BT = debug_backtrace();
            $Indent = "\t--";
            while($Stack = array_pop($BT)) {
                if (!isset($Stack['line'])) continue;
                if (isset($Stack['function']) && $Stack['function'] == '_dowrite') continue;
                $Stack['file'] = substr($Stack['file'], strlen(FS_ROOTDIR));
                $Appendix = ' '."\n$Indent -> ";
                if (isset($Stack['class'])) $Appendix .= $Stack['class'].$Stack['type'];
                if (isset($Stack['function'])) $Appendix .= $Stack['function'].'()';
                $LogString .= $Appendix.' @ '.$Stack['file'].' line '.$Stack['line'];
                $Indent .= "--";
            };
        };
        
        switch($this->LogType) {
            case L_STDERR:
                if (!$this->LogHandle) return false;
                fwrite($this->LogHandle, $LogString . "\n");
                break;
            case L_STDOUT:
                if (!isset($_SERVER['REMOTE_ADDR'])) {
                    print "$LogString\n";
                } else {
                    print "<PRE>\n<b>$LogString</b>\n</PRE>";
                }
                break;

        };
    }
};
?> 