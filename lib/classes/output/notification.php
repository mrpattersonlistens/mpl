<?php
/**
 * Notification class
 */
namespace output;

class notification implements \renderable {
    /**
     * A notification of level 'success'.
     */
    const NOTIFY_SUCCESS = 'success';
    /**
     * A notification of level 'warning'.
     */
    const NOTIFY_WARNING = 'warning';
    /**
     * A notification of level 'info'.
     */
    const NOTIFY_INFO = 'info';
    /**
     * A notification of level 'error'.
     */
    const NOTIFY_ERROR = 'error';
    /**
     * @var string Message payload.
     */
    protected $message = '';
    /**
     * @var string Message type.
     */
    protected $messagetype = self::NOTIFY_INFO;
    /**
     * @var bool $announce Whether this notification should be announced assertively to screen readers.
     */
    protected $announce = true;
    /**
     * @var bool $closebutton Whether this notification should inlcude a button to dismiss itself.
     */
    protected $closebutton = true;
    /**
     * @var array $extraclasses A list of any extra classes that may be required.
     */
    protected $extraclasses = array();
    /**
     * Notification constructor.
     *
     * @param string $message the message to print out
     * @param string $messagetype normally NOTIFY_PROBLEM or NOTIFY_SUCCESS.
     */
    public function __construct($message, $messagetype = null) {
        $this->message = $message;
        if (empty($messagetype)) {
            $messagetype = self::NOTIFY_INFO;
        }
        $this->messagetype = $messagetype;
    }
    /**
     * Set whether this notification should be announced assertively to screen readers.
     *
     * @param bool $announce
     * @return $this
     */
    public function set_announce($announce = false) {
        $this->announce = (bool) $announce;
        return $this;
    }
    /**
     * Set whether this notification should include a button to disiss itself.
     *
     * @param bool $button
     * @return $this
     */
    public function set_show_closebutton($button = true) {
        $this->closebutton = (bool) $button;
        return $this;
    }
    /**
     * Add any extra classes that this notification requires.
     *
     * @param array $classes
     * @return $this
     */
    public function set_extra_classes($classes = array()) {
        $this->extraclasses = $classes;
        return $this;
    }
    /**
     * Get the message for this notification.
     *
     * @return string message
     */
    public function get_message() {
        return $this->message;
    }
    /**
     * Get the message type for this notification.
     *
     * @return string message type
     */
    public function get_message_type() {
        return $this->messagetype;
    }
    
    public function render() {
        global $OUTPUT;
        
        switch ($this->messagetype) {
			case self::NOTIFY_ERROR:
			case self::NOTIFY_WARNING:
				$title = \lingua::express(\lingua::ANGRY).'!';
				$color = "w3-red";
				$icon = $OUTPUT->icon('warning', 'i', array('class' => 'w3-margin-right'));
				break;
			case self::NOTIFY_SUCCESS:
				$title = \lingua::express(\lingua::HAPPY).'!';
				$color = "w3-green";
				$icon = $OUTPUT->icon('check_circle', 'i', array('class' => 'w3-margin-right'));
				break;
			default:
				$title = \lingua::express(\lingua::NEUTRAL).':';
				$color = "w3-blue";
				$icon = $OUTPUT->icon('info', 'i', array('class' => 'w3-margin-right'));
				break;
        }
        return "<div class='notify w3-display-container w3-animate-opacity $color'>
				  <button class='w3-button w3-display-topright' onclick=\"this.parentElement.style.display='none'\">" . $OUTPUT->icon('close') . "</button>
				  <div class='w3-padding'>$icon<span class='notificationgreeting'>$title</span> {$this->message}</div>
			   </div>";
    }
}