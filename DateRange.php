<?php

namespace Shy;



/**
 * A class representing an actual interval af two given DateTime objects,
 * not just a duration (like DateInterval). Start and end dates are optional,
 * thus you can merely define a lower and an upper bound.
 * 
 * @author Philipp Cordes
 * @license GNU General Public License, version 3
 */
class DateRange
{
	/**
	 * @var \DateTime
	 */
	protected $begin, $end;

	public function __construct(\DateTime $begin = null, \DateTime $end = null)
	{
		$this->begin = $begin;
		$this->end = $end;
	}

	/**
	 * Create the corresponding DateInterval, or null if it is infinite.
	 * @return \DateInterval|null
	 */
	public function get_interval()
	{
		if ($this->begin && $this->end) {
			return new \DateInterval($this->begin, $this->end);
		}
		return null;
	}

	/**
	 * Whether the DateRange contains the given DateTime.
	 * @param \DateTime $dt
	 * @return boolean
	 */
	public function contains(\DateTime $dt)
	{
		if ($this->begin) {
			if ($this->end) {
				return $this->begin <= $dt && $dt <= $this->end;
			}
			return $this->begin <= $dt;
		} elseif ($this->end) {
			return $dt <= $this->end;
		}
		return true;
	}
}
