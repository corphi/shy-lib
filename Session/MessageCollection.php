<?php

namespace Shy\Session;



/**
 * A collection of messages and their corresponding CSS class (defaults to “warning”).
 */
class MessageCollection
{
	const TYPE = 0;
	const TEXT = 1;

	/**
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Appends a new message.
	 * @param string $text
	 * @param string $type
	 * @return MessageCollection
	 */
	public function add($text, $type = 'warning')
	{
		$this->messages[] = array(self::TYPE => $type, self::TEXT => (string) $text);
		return $this;
	}

	/**
	 * Whether the collection actually contains messages.
	 * @return boolean
	 */
	public function has_any()
	{
		return (bool) $this->messages;
	}

	/**
	 * Renders the messages as a series of paragraphs.
	 */
	public function render()
	{
		foreach ($this->messages as $message) {
			echo '<p class="' . $message[self::TYPE] . '">' . $message[self::TEXT] . '</p>';
		}
		$this->messages = array();
	}

	public function __sleep()
	{
		return array('messages');
	}
}
