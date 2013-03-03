<?php

namespace Shy\Forms;

use \Shy\DateRange;



/**
 * Enforce a date, optionally limited to an allowed \Shy\DateRange.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class DateConstraint implements Constraint
{
	/**
	 * Only allow past dates.
	 * @var integer
	 */
	const PAST_ONLY = -1;
	/**
	 * Only allow future dates.
	 * @var integer
	 */
	const FUTURE_ONLY = 1;

	/**
	 * The range of allowed dates.
	 * @var DateRange
	 */
	protected $range;

	/**
	 * @param DateRange|integer $range Range of allowed dates. There are class constants.
	 */
	public function __construct($range = null)
	{
		if ($range) {
			switch ($range) {
				case self::PAST_ONLY:
					$this->range = new DateRange(null, new \DateTime());
					break;
				case self::FUTURE_ONLY:
					$this->range = new DateRange(new \DateTime(), null);
					break;
				default:
					if ($range instanceof DateRange) {
						$this->range = $range;
					}
					throw new \Exception(__CLASS__ . '::' . __METHOD__ . '(): $range is neither an allowed constant nor a DateRange.');
			}
		} else {
			$this->range = null;
		}
	}

	public function check_against($value)
	{
		try {
			$dt = new \DateTime($value);
			return $this->range ? $this->range->contains($dt) : true;
		} catch (\Exception $ex) {
			return false;
		}
	}

	public function get_html_attributes()
	{
		return array('type' => 'date');
	}

	public function get_reason()
	{
		return 'That’s not a date.';
	}
}
