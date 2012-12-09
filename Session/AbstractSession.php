<?php

namespace Shy\Session;



/**
 * A little help for your session. Will continue a running session
 * automatically, but doesn’t start them on its own if you don’t want it to.
 *
 * Features:
 *   - Lock the session to the current IP address (AbstractSession::$lock_ip = true)
 *   - Secure your settings by calling AbstractSession::apply_secure_settings().
 *     (http cookies only, and secure iff you use https)
 *
 *
 * Prerequisites:
 *   - PHP 5.3 or later (namespaces support)
 *   - Using another session manager in parallel will probably cause trouble.
 *   - For some functions, you need to include util.inc.
 *
 *
 * Usage:
 * class MySession extends \Shy\Session\AbstractSession
 * {
 *   // Prepare everything inside your constructor
 *   protected function __construct()
 *   {
 *     session_name('my_custom_session');
 *     parent::apply_secure_settings();
 *     parent::$auto_start = false;        // or
 *     parent::$force_start = true;
 *     parent::$lock_ip = true;
 *     parent::__construct();
 *   }
 * }
 *
 * // Query the singleton instance via Late Static Bindings
 * $s = MySession::get_instance();
 *
 * $s->end();     // end current session and delete session cookie
 * $s->purge();   // clear session contents and change session id
 *
 *
 * Utilities:
 *    $s->redirect('/to/where/you/like');
 *    $s->form_errors()  // collection of form errors tied to a control
 *    $s->messages()     // collection of general purpose messages
 */
abstract class AbstractSession
{
	/**
	 * Changes PHP settings to most secure cookie usage; if parameters are
	 * omitted, use automagic path detection and current (exact) host only.
	 * Return whether it succeeded (the session hasn’t started yet).
	 * @param string $domain
	 * @param string $path
	 * @return boolean
	 */
	protected static function apply_secure_settings($domain = '', $path = null)
	{
		if (self::has_started()) {
			return false;
		}

		if ($path === null) {
			$path = substr($_SERVER['PHP_SELF'], 0,
				strrpos($_SERVER['PHP_SELF'], '/') + 1
			);
		}
		ini_set('session.use_only_cookies', 1);
		$lifetime = session_get_cookie_params();
		$lifetime = $lifetime['lifetime'];
		$secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off';
		session_set_cookie_params($lifetime, $path, $domain, $secure, true);
		return true;
	}


	/**
	 * Always start the session. (Default: Only continue it)
	 * @var boolean
	 */
	protected static $force_start = false;

	/**
	 * Automatically start the session if a cookie is found. (Default: Do it)
	 * @var boolean
	 */
	protected static $auto_start = true;


	/**
	 * Make sure that the session is only continued when the IP address didn’t
	 * change. The session will be purged when the IP address changes.
	 * @var boolean
	 */
	protected static $lock_ip = false;

	/**
	 * Whether to include POSTed data during session serialization.
	 * @var boolean
	 */
	protected $capture_post = false;



	/**
	 * Create a new instance.
	 */
	protected function __construct()
	{
		if (session_id()) {
			throw new \Exception('Session has already been started.');
		}

		if (self::$force_start
			|| self::$auto_start && isset($_COOKIE) && isset($_COOKIE[session_name()])
		) {
			$this->start();
		}
	}
	/**
	 * Singleton: Get the instance, create it if necessary.
	 * (Late static bindings!)
	 * @param boolean $force_start
	 * @return AbstractSession
	 */
	public static function get_instance()
	{
		static $instance = null;
		if (!$instance) {
			$instance = get_called_class();
			if ($instance === 'Shy\\Session\\AbstractSession') {
				throw new \RuntimeException('Shy\\Session\\AbstractSession::get_instance() must be called from a subclass first.');
			}
			$instance = new $instance();
		}
		return $instance;
	}

	/**
	 * Whether the session has been started.
	 * @return boolean
	 */
	public static function has_started()
	{
		return (bool) session_id();
	}

	/**
	 * Starts the session; will report an error message to self::$messages on failure.
	 * @param boolean $report_failure
	 * @return boolean
	 */
	public function start($report_failure = true)
	{
		if (self::has_started()) {
			throw new \Exception('Session has already been started.');
		}

		// session hasn’t started yet
		if (!session_start() || !session_id()) {
			// session failed to start
			return false;
		}

		// --- Initialize session, extract SHY stuff ---
		if (!$this->register_dispose()) {
			return false;
		}

		if (!isset($_SESSION['SHY']) || !is_array($_SESSION['SHY'])) {
			// New session; nothing to read
			return true;
		}

		$shy = &$_SESSION['SHY'];

		// Check current IP address
		if (isset($shy['IP'])) {
			self::$lock_ip = true;
			if ($shy['IP'] != $_SERVER['REMOTE_ADDR']) {
				// The address changed
				$this->purge();
				$this->messages('Your IP address changed: Your session data has been purged.', 'info');
			}
		}

		// Re-use old POST values
		if (isset($shy['POST'])) {
			$_POST += $shy['POST']; // Current POST values enjoy precedence
		}

		// Restore messages
		if (isset($shy['MESSAGES'])) {
			$this->messages = unserialize($shy['MESSAGES']);
		}

		if (isset($shy['SHY'])) {
			$_SESSION['SHY'] = $shy['SHY'];
		} else {
			unset($_SESSION['SHY']);
		}

		return true;
	}

	/**
	 * Clean up and store the session object’s settings.
	 */
	public function dispose()
	{
		if ($this->has_messages()) {
			$shy['MESSAGES'] = serialize($this->messages);
		}

		if (self::$lock_ip) {
			$shy['IP'] = $_SERVER['REMOTE_ADDR'];
		}

		if ($this->capture_post) {
			$shy['POST'] = &$_POST;
		}

		if (isset($_SESSION['SHY'])) {
			$shy['SHY'] = &$_SESSION['SHY'];
		}

		if (isset($shy) && $shy) {
			$_SESSION['SHY'] = $shy;
		}
	}
	/**
	 * Register dispose() for execution before the script terminates.
	 */
	private function register_dispose()
	{
		static $done = false;
		if ($done) {
			return false;
		}
		register_shutdown_function(array($this, 'dispose'));
		return $done = true;
	}


	/**
	* Redirects to the given page. Will resolve relative urls (as needed for HTTP/1.1).
	* @param string $to
	* @param bool $relative
	*/
	public function redirect($to, $relative = true)
	{
		if ($relative) {
			$to = \Shy\resolve_href(\Shy\current_url(), $to);
		}

		if ($this->has_messages() && !session_id()) {
			$this->start();
		}

		header('Location: ' . $to);
		exit;
	}


	/**
	 * Ends the current session, deletes the existing session cookie. The
	 * session must be started (i.e. picked up) for this to work.
	 * @return bool
	 */
	public function end()
	{
		if (!session_id()) {
			return false;
		}
		$name = session_name();
		$cookie = session_get_cookie_params();
		session_destroy();
		setcookie($name, false, $cookie['lifetime'], $cookie['path'],
			$cookie['domain'], $cookie['secure']
		);
		return true;
	}
	/**
	 * Deletes all session variables, generates an new session id.
	 * @return void
	 */
	public function purge()
	{
		session_regenerate_id(true);
		session_destroy();
	}


	/**
	 * @var MessageCollection
	 */
	private $messages;
	/**
	 * Add a message to the collection or retrieve it.
	 * @param string $message
	 * @param string $class
	 * @return MessageCollection
	 */
	public function messages($message = null, $class = 'warning')
	{
		if (!isset($this->messages)) {
			$this->messages = new MessageCollection();
		}
		if ($message === null) {
			return $this->messages;
		}
		return $this->messages->add($message, $class);
	}
	/**
	 * Whether there are messages pending.
	 * @return boolean
	 */
	protected function has_messages()
	{
		return isset($this->messages) && $this->messages->has_any();
	}
	/**
	 * Render the messages without creating the collection.
	 * @return void
	 */
	public function render_messages()
	{
		if (isset($this->messages)) {
			$this->messages->render();
		}
	}

	/**
	 * @var FormErrorCollection
	 */
	private $form_errors;
	/**
	 * A collection of form errors. Created on demand.
	 * @return FormErrorCollection
	 */
	public function &form_errors()
	{
		if (!isset($this->form_errors)) {
			$this->form_errors = new FormErrorCollection();
		}
		return $this->form_errors;
	}
	/**
	 * Checks for form errors without creating the collection.
	 * @return boolean
	 */
	public function has_form_errors()
	{
		return isset($this->form_errors) && $this->form_errors->has_any();
	}
	/**
	 * Render the messages without creating the collection.
	 * @param string $control
	 * @return mixed
	 */
	public function render_form_error($control)
	{
		if (isset($this->form_errors)) {
			return $this->form_errors->render_for($control);
		}
		return false;
	}
}
